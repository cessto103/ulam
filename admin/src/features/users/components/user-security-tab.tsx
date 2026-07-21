import { useState } from 'react'
import { LogOut, Monitor, Smartphone } from 'lucide-react'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent } from '@/components/ui/card'
import { ConfirmDialog } from '@/components/confirm-dialog'
import {
  type UserSession,
  useRevokeUserSession,
  useUserSessionsQuery,
} from '../hooks/use-user-sessions'

function relativeTime(iso: string | null): string {
  if (!iso) return 'Never used'
  const diffMs = Date.now() - new Date(iso).getTime()
  const hours = Math.floor(diffMs / 3_600_000)
  if (hours < 1) return 'Active now'
  if (hours < 24) return `${hours}h ago`
  const days = Math.floor(hours / 24)
  return `${days}d ago`
}

export function UserSecurityTab({ userId }: { userId: number }) {
  const { data: sessions, isLoading } = useUserSessionsQuery(userId)
  const { mutate: revoke, isPending } = useRevokeUserSession(userId)
  const [target, setTarget] = useState<UserSession | null>(null)

  return (
    <>
      <Card>
        <CardContent className='pt-6'>
          <div className='mb-3 text-xs font-medium uppercase text-muted-foreground'>
            Devices & Login Sessions
          </div>
          {isLoading ? (
            <p className='text-sm text-muted-foreground'>Loading...</p>
          ) : !sessions?.length ? (
            <p className='text-sm text-muted-foreground'>No recorded sessions yet.</p>
          ) : (
            <div className='divide-y'>
              {sessions.map((session) => (
                <div key={session.id} className='flex items-center gap-3 py-3'>
                  <div className='flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-muted'>
                    {session.platform === 'ios' || session.platform === 'android' ? (
                      <Smartphone size={16} className='text-muted-foreground' />
                    ) : (
                      <Monitor size={16} className='text-muted-foreground' />
                    )}
                  </div>
                  <div className='flex-1'>
                    <div className='flex items-center gap-2'>
                      <span className='text-sm font-semibold'>
                        {session.device_name ?? 'Unknown device'}
                      </span>
                      {session.app_version && (
                        <Badge variant='outline' className='text-xs'>
                          v{session.app_version}
                        </Badge>
                      )}
                    </div>
                    <div className='text-xs text-muted-foreground'>
                      {relativeTime(session.last_used_at)}
                      {session.ip_address && ` · ${session.ip_address}`}
                    </div>
                  </div>
                  <Button
                    variant='ghost'
                    size='sm'
                    disabled={isPending}
                    onClick={() => setTarget(session)}
                  >
                    <LogOut /> Sign out
                  </Button>
                </div>
              ))}
            </div>
          )}
        </CardContent>
      </Card>

      <ConfirmDialog
        open={!!target}
        onOpenChange={(open) => !open && setTarget(null)}
        title='Sign out this device?'
        desc={target?.device_name ?? 'Unknown device'}
        destructive
        confirmText='Sign out'
        isLoading={isPending}
        handleConfirm={() => {
          if (!target) return
          revoke(target.id, { onSuccess: () => setTarget(null) })
        }}
      />
    </>
  )
}
