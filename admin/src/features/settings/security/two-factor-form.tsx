import { useState } from 'react'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { Loader2, ShieldCheck, ShieldOff } from 'lucide-react'
import { toast } from 'sonner'
import apiClient from '@/lib/api-client'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { PasswordInput } from '@/components/password-input'

type SetupPayload = { secret: string; otpauth_uri: string; qr_svg: string }

export function TwoFactorForm() {
  const qc = useQueryClient()
  const [setupData, setSetupData] = useState<SetupPayload | null>(null)
  const [confirmCode, setConfirmCode] = useState('')
  const [disableCode, setDisableCode] = useState('')
  const [disablePassword, setDisablePassword] = useState('')

  const { data: status, isLoading } = useQuery({
    queryKey: ['admin-2fa-status'],
    queryFn: async () => (await apiClient.get<{ enabled: boolean; enabled_at: string | null }>('/admin/2fa/status')).data,
  })

  const setup = useMutation({
    mutationFn: async () => (await apiClient.post<SetupPayload>('/admin/2fa/setup')).data,
    onSuccess: (data) => setSetupData(data),
    onError: (e: any) => toast.error(e?.response?.data?.message ?? 'Setup failed.'),
  })

  const confirm = useMutation({
    mutationFn: async () => apiClient.post('/admin/2fa/confirm', { code: confirmCode }),
    onSuccess: () => {
      toast.success('Two-factor authentication is now ON.')
      setSetupData(null)
      setConfirmCode('')
      qc.invalidateQueries({ queryKey: ['admin-2fa-status'] })
    },
    onError: (e: any) =>
      toast.error(e?.response?.data?.errors?.code?.[0] ?? e?.response?.data?.message ?? 'Could not confirm.'),
  })

  const disable = useMutation({
    mutationFn: async () => apiClient.post('/admin/2fa/disable', { password: disablePassword, code: disableCode }),
    onSuccess: () => {
      toast.success('Two-factor authentication is OFF.')
      setDisableCode('')
      setDisablePassword('')
      qc.invalidateQueries({ queryKey: ['admin-2fa-status'] })
    },
    onError: (e: any) => {
      const errs = e?.response?.data?.errors
      toast.error(errs?.code?.[0] ?? errs?.password?.[0] ?? e?.response?.data?.message ?? 'Could not disable.')
    },
  })

  if (isLoading) return <Loader2 className='animate-spin text-muted-foreground' />

  if (status?.enabled) {
    return (
      <div className='space-y-6'>
        <div className='flex items-center gap-2'>
          <ShieldCheck className='size-5 text-emerald-600' />
          <Badge className='bg-emerald-500/15 text-emerald-600'>Enabled</Badge>
          {status.enabled_at && (
            <span className='text-xs text-muted-foreground'>
              since {new Date(status.enabled_at).toLocaleString()}
            </span>
          )}
        </div>
        <p className='text-sm text-muted-foreground'>
          Signing in requires a code from your authenticator app. To turn it off, confirm your password and a current code.
        </p>
        <div className='grid gap-3 sm:max-w-sm'>
          <div className='space-y-1.5'>
            <Label>Password</Label>
            <PasswordInput value={disablePassword} onChange={(e) => setDisablePassword(e.target.value)} placeholder='********' />
          </div>
          <div className='space-y-1.5'>
            <Label>Authenticator code</Label>
            <Input value={disableCode} onChange={(e) => setDisableCode(e.target.value.replace(/\D/g, '').slice(0, 6))} placeholder='123456' inputMode='numeric' />
          </div>
          <Button
            variant='destructive'
            disabled={disable.isPending || !disablePassword || disableCode.length !== 6}
            onClick={() => disable.mutate()}
          >
            {disable.isPending ? <Loader2 className='animate-spin' /> : <ShieldOff />}
            Turn off two-factor
          </Button>
        </div>
      </div>
    )
  }

  return (
    <div className='space-y-6'>
      <div className='flex items-center gap-2'>
        <ShieldOff className='size-5 text-muted-foreground' />
        <Badge variant='outline'>Disabled</Badge>
      </div>

      {!setupData ? (
        <>
          <p className='text-sm text-muted-foreground'>
            Add a second lock to your dashboard: after your password, sign-in will also require a 6-digit code
            from Google Authenticator (or any TOTP app) on your phone. Even a stolen password won't get in.
          </p>
          <Button onClick={() => setup.mutate()} disabled={setup.isPending}>
            {setup.isPending ? <Loader2 className='animate-spin' /> : <ShieldCheck />}
            Set up two-factor
          </Button>
        </>
      ) : (
        <div className='space-y-4'>
          <p className='text-sm'>
            <strong>1.</strong> Open Google Authenticator on your phone → tap <strong>+</strong> → <strong>Scan a QR code</strong>:
          </p>
          <div
            className='w-fit rounded-lg border bg-white p-3'
            // Server-generated SVG QR of the otpauth:// URI
            dangerouslySetInnerHTML={{ __html: setupData.qr_svg }}
          />
          <p className='text-xs text-muted-foreground'>
            Can't scan? Enter this key manually: <code className='font-mono select-all'>{setupData.secret}</code>
          </p>
          <p className='text-sm'>
            <strong>2.</strong> Enter the 6-digit code the app shows to finish:
          </p>
          <div className='flex items-center gap-2 sm:max-w-sm'>
            <Input
              value={confirmCode}
              onChange={(e) => setConfirmCode(e.target.value.replace(/\D/g, '').slice(0, 6))}
              placeholder='123456'
              inputMode='numeric'
              className='w-32 text-center tracking-widest'
            />
            <Button disabled={confirm.isPending || confirmCode.length !== 6} onClick={() => confirm.mutate()}>
              {confirm.isPending ? <Loader2 className='animate-spin' /> : null}
              Verify & enable
            </Button>
          </div>
        </div>
      )}
    </div>
  )
}
