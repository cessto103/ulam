import { z } from 'zod'
import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { useMutation } from '@tanstack/react-query'
import { toast } from 'sonner'
import apiClient from '@/lib/api-client'
import { useAuthStore, type AuthUser } from '@/stores/auth-store'
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
import { Input } from '@/components/ui/input'

const profileFormSchema = z.object({
  name: z.string().min(1, 'Name is required.'),
  username: z
    .string()
    .min(1, 'Username is required.')
    .regex(/^[a-zA-Z0-9_]+$/, 'Letters, numbers, and underscores only.'),
  email: z.email('Enter a valid email.'),
})

type ProfileFormValues = z.infer<typeof profileFormSchema>

export function ProfileForm() {
  const { auth } = useAuthStore()

  const form = useForm<ProfileFormValues>({
    resolver: zodResolver(profileFormSchema),
    defaultValues: {
      name: auth.user?.name ?? '',
      username: auth.user?.username ?? '',
      email: auth.user?.email ?? '',
    },
  })

  const { mutate: saveProfile, isPending } = useMutation({
    mutationFn: async (values: ProfileFormValues) => {
      const { data } = await apiClient.patch<{ user: AuthUser }>(
        '/admin/profile',
        values
      )
      return data.user
    },
    onSuccess: (user) => {
      auth.setUser(user)
      toast.success('Profile updated.')
    },
    onError: (error: any) => {
      toast.error(error?.response?.data?.message ?? 'Could not update profile.')
    },
  })

  return (
    <Form {...form}>
      <form
        onSubmit={form.handleSubmit((values) => saveProfile(values))}
        className='space-y-8'
      >
        <FormField
          control={form.control}
          name='name'
          render={({ field }) => (
            <FormItem>
              <FormLabel>Name</FormLabel>
              <FormControl>
                <Input placeholder='Your name' {...field} />
              </FormControl>
              <FormMessage />
            </FormItem>
          )}
        />
        <FormField
          control={form.control}
          name='username'
          render={({ field }) => (
            <FormItem>
              <FormLabel>Username</FormLabel>
              <FormControl>
                <Input placeholder='username' {...field} />
              </FormControl>
              <FormDescription>
                You can sign in with either your email or this username.
              </FormDescription>
              <FormMessage />
            </FormItem>
          )}
        />
        <FormField
          control={form.control}
          name='email'
          render={({ field }) => (
            <FormItem>
              <FormLabel>Email</FormLabel>
              <FormControl>
                <Input placeholder='you@example.com' {...field} />
              </FormControl>
              <FormMessage />
            </FormItem>
          )}
        />
        <Button type='submit' disabled={isPending}>
          Save profile
        </Button>
      </form>
    </Form>
  )
}
