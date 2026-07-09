import { z } from 'zod'
import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { useMutation } from '@tanstack/react-query'
import { toast } from 'sonner'
import apiClient from '@/lib/api-client'
import { Button } from '@/components/ui/button'
import {
  Form,
  FormControl,
  FormDescription,
  FormField,
  FormItem,
  FormLabel,
  FormMessage,
} from '@/components/ui/form'
import { PasswordInput } from '@/components/password-input'

const accountFormSchema = z
  .object({
    current_password: z.string().min(1, 'Enter your current password.'),
    new_password: z
      .string()
      .min(8, 'New password must be at least 8 characters.'),
    confirm_password: z.string(),
  })
  .refine((data) => data.new_password === data.confirm_password, {
    message: "Passwords don't match.",
    path: ['confirm_password'],
  })

type AccountFormValues = z.infer<typeof accountFormSchema>

export function AccountForm() {
  const form = useForm<AccountFormValues>({
    resolver: zodResolver(accountFormSchema),
    defaultValues: {
      current_password: '',
      new_password: '',
      confirm_password: '',
    },
  })

  const { mutate: changePassword, isPending } = useMutation({
    mutationFn: (values: AccountFormValues) =>
      apiClient.post('/admin/change-password', {
        current_password: values.current_password,
        new_password: values.new_password,
      }),
    onSuccess: () => {
      form.reset()
      toast.success('Password changed. Other devices have been signed out.')
    },
    onError: (error: any) => {
      toast.error(error?.response?.data?.message ?? 'Could not change password.')
    },
  })

  return (
    <Form {...form}>
      <form
        onSubmit={form.handleSubmit((values) => changePassword(values))}
        className='space-y-8'
      >
        <FormField
          control={form.control}
          name='current_password'
          render={({ field }) => (
            <FormItem>
              <FormLabel>Current password</FormLabel>
              <FormControl>
                <PasswordInput placeholder='********' {...field} />
              </FormControl>
              <FormMessage />
            </FormItem>
          )}
        />
        <FormField
          control={form.control}
          name='new_password'
          render={({ field }) => (
            <FormItem>
              <FormLabel>New password</FormLabel>
              <FormControl>
                <PasswordInput placeholder='********' {...field} />
              </FormControl>
              <FormDescription>
                At least 8 characters. Changing it signs out all other devices;
                this one stays signed in.
              </FormDescription>
              <FormMessage />
            </FormItem>
          )}
        />
        <FormField
          control={form.control}
          name='confirm_password'
          render={({ field }) => (
            <FormItem>
              <FormLabel>Confirm new password</FormLabel>
              <FormControl>
                <PasswordInput placeholder='********' {...field} />
              </FormControl>
              <FormMessage />
            </FormItem>
          )}
        />
        <Button type='submit' disabled={isPending}>
          Change password
        </Button>
      </form>
    </Form>
  )
}
