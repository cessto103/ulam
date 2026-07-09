import {
  CartesianGrid,
  Legend,
  Line,
  LineChart,
  ResponsiveContainer,
  Tooltip,
  XAxis,
  YAxis,
} from 'recharts'
import { useGrowthQuery } from '../hooks/use-dashboard'

function shortDate(iso: string) {
  const d = new Date(iso)
  return `${d.getMonth() + 1}/${d.getDate()}`
}

export function GrowthChart({ days = 30 }: { days?: number }) {
  const { data: series = [], isLoading } = useGrowthQuery(days)

  if (isLoading) {
    return (
      <p className='flex h-[300px] items-center justify-center text-sm text-muted-foreground'>
        Loading...
      </p>
    )
  }

  return (
    <ResponsiveContainer width='100%' height={300}>
      <LineChart data={series} margin={{ top: 4, right: 8, bottom: 0, left: -20 }}>
        <CartesianGrid
          vertical={false}
          stroke='var(--border)'
          strokeDasharray='0'
        />
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
        <Legend
          iconType='plainline'
          wrapperStyle={{ fontSize: 12 }}
          formatter={(value) => (value === 'signups' ? 'New users' : 'Posts')}
        />
        <Line
          type='monotone'
          dataKey='signups'
          stroke='var(--chart-1)'
          strokeWidth={2}
          dot={false}
          activeDot={{ r: 4 }}
        />
        <Line
          type='monotone'
          dataKey='posts'
          stroke='var(--chart-2)'
          strokeWidth={2}
          dot={false}
          activeDot={{ r: 4 }}
        />
      </LineChart>
    </ResponsiveContainer>
  )
}
