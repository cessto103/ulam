import { Avatar, AvatarFallback } from '@/components/ui/avatar'
import { Badge } from '@/components/ui/badge'
import { useXpLeaderboard } from '../hooks/use-dashboard'

function initials(name: string) {
  return name
    .split(' ')
    .slice(0, 2)
    .map((w) => w[0] ?? '')
    .join('')
    .toUpperCase()
}

export function XpLeaderboard() {
  const { data: leaderboard = [], isLoading } = useXpLeaderboard()

  if (isLoading) {
    return <p className='text-sm text-muted-foreground'>Loading...</p>
  }

  if (leaderboard.length === 0) {
    return <p className='text-sm text-muted-foreground'>No users yet.</p>
  }

  return (
    <div className='space-y-6'>
      {leaderboard.map((user, i) => (
        <div key={user.id} className='flex items-center gap-4'>
          <span className='w-4 text-sm font-medium text-muted-foreground'>
            {i + 1}
          </span>
          <Avatar className='h-9 w-9'>
            <AvatarFallback>{initials(user.name)}</AvatarFallback>
          </Avatar>
          <div className='flex flex-1 flex-wrap items-center justify-between gap-2'>
            <div className='space-y-1'>
              <p className='text-sm leading-none font-medium'>{user.name}</p>
              <p className='text-sm text-muted-foreground'>
                {user.municipality ?? '—'} · {user.streak_days}d streak
              </p>
            </div>
            <div className='flex items-center gap-2'>
              <Badge variant='outline'>Lvl {user.level}</Badge>
              <span className='font-medium'>{user.xp} XP</span>
            </div>
          </div>
        </div>
      ))}
    </div>
  )
}
