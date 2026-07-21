import {
  CartesianGrid,
  Line,
  LineChart,
  ResponsiveContainer,
  Tooltip,
  XAxis,
  YAxis,
} from 'recharts'
import {
  BadgeCheck,
  ChefHat,
  Flame,
  MonitorSmartphone,
  Star,
  Store,
  TrendingUp,
  UserPlus,
  Users,
} from 'lucide-react'
import { Badge } from '@/components/ui/badge'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { useUserOverviewQuery } from '../hooks/use-user-overview'

function shortDate(iso: string) {
  const d = new Date(iso)
  return `${d.getMonth() + 1}/${d.getDate()}`
}

function StatCard({
  icon: Icon,
  label,
  value,
}: {
  icon: typeof Star
  label: string
  value: string | number
}) {
  return (
    <Card>
      <CardContent className='flex items-center gap-3 pt-6'>
        <div className='flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-muted'>
          <Icon size={16} className='text-muted-foreground' />
        </div>
        <div>
          <div className='text-lg font-bold leading-none'>{value}</div>
          <div className='text-xs text-muted-foreground'>{label}</div>
        </div>
      </CardContent>
    </Card>
  )
}

export function UserOverviewTab({ userId }: { userId: number }) {
  const { data, isLoading } = useUserOverviewQuery(userId)

  if (isLoading || !data) {
    return <p className='text-sm text-muted-foreground'>Loading...</p>
  }

  const { stats, counts, last_session } = data

  return (
    <div className='space-y-4'>
      <div className='grid grid-cols-2 gap-3 sm:grid-cols-4 lg:grid-cols-7'>
        <StatCard icon={Star} label='XP' value={stats.xp} />
        <StatCard icon={TrendingUp} label='Level' value={stats.level} />
        <StatCard icon={Flame} label='Streak' value={`${stats.streak_days}d`} />
        <StatCard icon={ChefHat} label='Posts' value={counts.posts} />
        <StatCard icon={ChefHat} label='Recipes' value={counts.recipes} />
        <StatCard icon={Store} label='Stores' value={counts.stores} />
        <StatCard icon={Users} label='Followers' value={counts.followers} />
      </div>

      <div className='grid gap-4 lg:grid-cols-[1fr_320px]'>
        <Card>
          <CardHeader>
            <CardTitle className='text-sm'>XP earned (last 30 days)</CardTitle>
          </CardHeader>
          <CardContent>
            {data.xp_history.length === 0 ? (
              <p className='flex h-[220px] items-center justify-center text-sm text-muted-foreground'>
                No XP activity in this window.
              </p>
            ) : (
              <ResponsiveContainer width='100%' height={220}>
                <LineChart data={data.xp_history} margin={{ top: 4, right: 8, bottom: 0, left: -20 }}>
                  <CartesianGrid vertical={false} stroke='var(--border)' strokeDasharray='0' />
                  <XAxis
                    dataKey='date'
                    tickFormatter={shortDate}
                    stroke='var(--muted-foreground)'
                    fontSize={11}
                    tickLine={false}
                    axisLine={false}
                    minTickGap={24}
                  />
                  <YAxis
                    allowDecimals={false}
                    stroke='var(--muted-foreground)'
                    fontSize={11}
                    tickLine={false}
                    axisLine={false}
                  />
                  <Tooltip
                    labelFormatter={(label) => new Date(label as string).toLocaleDateString()}
                    contentStyle={{
                      backgroundColor: 'var(--popover)',
                      border: '1px solid var(--border)',
                      borderRadius: 8,
                      color: 'var(--popover-foreground)',
                      fontSize: 12,
                    }}
                  />
                  <Line
                    type='monotone'
                    dataKey='xp'
                    stroke='var(--chart-1)'
                    strokeWidth={2}
                    dot={false}
                    activeDot={{ r: 4 }}
                  />
                </LineChart>
              </ResponsiveContainer>
            )}
          </CardContent>
        </Card>

        <div className='space-y-4'>
          <Card>
            <CardHeader>
              <CardTitle className='text-sm'>Profile</CardTitle>
            </CardHeader>
            <CardContent className='space-y-2 text-sm'>
              <div className='flex justify-between gap-4'>
                <span className='text-muted-foreground'>Location</span>
                <span className='text-right'>{stats.location || '-'}</span>
              </div>
              <div className='flex justify-between gap-4'>
                <span className='text-muted-foreground'>Household size</span>
                <span>{stats.household_size ?? '-'}</span>
              </div>
              <div className='flex justify-between gap-4'>
                <span className='text-muted-foreground'>Gender</span>
                <span className='capitalize'>{stats.gender ?? '-'}</span>
              </div>
              <div className='flex justify-between gap-4'>
                <span className='text-muted-foreground'>Joined</span>
                <span>{new Date(stats.joined_at).toLocaleDateString()}</span>
              </div>
              <div className='flex items-center justify-between gap-4'>
                <span className='text-muted-foreground'>Email</span>
                {stats.email_verified_at ? (
                  <Badge variant='outline' className='gap-1'>
                    <BadgeCheck size={12} /> Verified
                  </Badge>
                ) : (
                  <Badge variant='secondary'>Unverified</Badge>
                )}
              </div>
              {stats.bio && (
                <div className='pt-2'>
                  <div className='text-muted-foreground'>Bio</div>
                  <p className='mt-1'>{stats.bio}</p>
                </div>
              )}
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle className='text-sm'>Last login</CardTitle>
            </CardHeader>
            <CardContent className='space-y-2 text-sm'>
              {!last_session ? (
                <p className='text-muted-foreground'>No recorded sessions yet.</p>
              ) : (
                <div className='flex items-start gap-2'>
                  <MonitorSmartphone size={16} className='mt-0.5 text-muted-foreground' />
                  <div>
                    <div className='font-medium'>{last_session.device_name ?? 'Unknown device'}</div>
                    <div className='text-xs text-muted-foreground'>
                      {last_session.last_used_at
                        ? new Date(last_session.last_used_at).toLocaleString()
                        : 'Never used'}
                      {last_session.ip_address && ` · ${last_session.ip_address}`}
                    </div>
                  </div>
                </div>
              )}
              <div className='flex items-center gap-1.5 pt-1 text-xs text-muted-foreground'>
                <UserPlus size={12} /> Full device list on the Security tab.
              </div>
            </CardContent>
          </Card>
        </div>
      </div>
    </div>
  )
}
