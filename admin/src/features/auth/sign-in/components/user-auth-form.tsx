import { useState } from 'react'
import { z } from 'zod'
import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { useMutation } from '@tanstack/react-query'
import { useNavigate } from '@tanstack/react-router'
import { Loader2, LogIn } from 'lucide-react'
import { toast } from 'sonner'
import apiClient from '@/lib/api-client'
import { useAuthStore, type AuthUser } from '@/stores/auth-store'
import { cn } from '@/lib/utils'
import { Button } from '@/components/ui/button'
import {
  Form,
  FormControl,
  FormField,
  FormItem,
  FormLabel,
  FormMessage,
} from '@/components/ui/form'
import { Input } from '@/components/ui/input'
import { PasswordInput } from '@/components/password-input'

const formSchema = z.object({
  login: z.string().min(1, 'Please enter your email or username.'),
  password: z.string().min(1, 'Please enter your password.'),
  otp: z.string().optional(),
})

interface UserAuthFormProps extends React.HTMLAttributes<HTMLFormElement> {
  redirectTo?: string
}

export function UserAuthForm({
  className,
  redirectTo,
  ...props
}: UserAuthFormProps) {
  const navigate = useNavigate()
  const { auth } = useAuthStore()
  const [needsOtp, setNeedsOtp] = useState(false)

  const form = useForm<z.infer<typeof formSchema>>({
    resolver: zodResolver(formSchema),
    defaultValues: {
      login: '',
      password: '',
      otp: '',
    },
  })

  const { mutate: login, isPending } = useMutation({
    mutationFn: async (data: z.infer<typeof formSchema>) => {
      const { data: body } = await apiClient.post<{ user: AuthUser; token: string }>(
        '/admin/login',
        { ...data, otp: data.otp || undefined }
      )
      return body
    },
    onSuccess: (body) => {
      auth.setUser(body.user)
      auth.setAccessToken(body.token)
      navigate({ to: redirectTo || '/', replace: true })
      toast.success(`Welcome back, ${body.user.name}!`)
    },
    onError: (error: any) => {
      if (error?.response?.data?.requires_2fa) {
        setNeedsOtp(true)
        toast.info(error.response.data.message ?? 'Enter your authenticator code.')
        return
      }
      const message =
        error?.response?.data?.message ?? 'Could not sign in. Please try again.'
      toast.error(message)
    },
  })

  function onSubmit(data: z.infer<typeof formSchema>) {
    login(data)
  }

  return (
    <Form {...form}>
      <form
        onSubmit={form.handleSubmit(onSubmit)}
        className={cn('grid gap-3', className)}
        {...props}
      >
        <FormField
          control={form.control}
          name='login'
          render={({ field }) => (
            <FormItem>
              <FormLabel>Email or username</FormLabel>
              <FormControl>
                <Input placeholder='name@example.com' {...field} />
              </FormControl>
              <FormMessage />
            </FormItem>
          )}
        />
        <FormField
          control={form.control}
          name='password'
          render={({ field }) => (
            <FormItem className='relative'>
              <FormLabel>Password</FormLabel>
              <FormControl>
                <PasswordInput placeholder='********' {...field} />
              </FormControl>
              <FormMessage />
            </FormItem>
          )}
        />
        {needsOtp && (
          <FormField
            control={form.control}
            name='otp'
            render={({ field }) => (
              <FormItem>
                <FormLabel>Authenticator code</FormLabel>
                <FormControl>
                  <Input
                    placeholder='123456'
                    inputMode='numeric'
                    maxLength={6}
                    autoFocus
                    {...field}
                  />
                </FormControl>
                <FormMessage />
              </FormItem>
            )}
          />
        )}
        <Button className='mt-2' disabled={isPending}>
          {isPending ? <Loader2 className='animate-spin' /> : <LogIn />}
          Sign in
        </Button>
      </form>
    </Form>
  )
}
