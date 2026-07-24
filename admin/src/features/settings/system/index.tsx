import { useState } from 'react'
import { useMutation } from '@tanstack/react-query'
import { CheckCircle2, PlayCircle, XCircle } from 'lucide-react'
import { toast } from 'sonner'
import apiClient from '@/lib/api-client'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent } from '@/components/ui/card'
import { ConfirmDialog } from '@/components/confirm-dialog'
import { ContentSection } from '../components/content-section'

type ScheduleResult = {
  command: string
  exit_code: number
  output: string
}

const COMMAND_LABELS: Record<string, string> = {
  'ulam:maintenance': 'Maintenance (subscriptions, boosts, OTPs)',
  'billing:process-lifecycle': 'Billing lifecycle',
  'prices:refresh-ai': 'AI market price refresh',
  'prices:refresh-gov': 'DA/DTI reference price refresh',
  'ulam:expire-strikes': 'Expire moderation strikes',
  'ulam:weather-daily': 'Daily weather notification',
  'ulam:daily-reminders': 'Daily spending reminders',
}

function useRunSchedule() {
  return useMutation({
    mutationFn: async () => {
      const { data } = await apiClient.post<{ results: ScheduleResult[] }>(
        '/admin/system/run-schedule'
      )
      return data.results
    },
  })
}

export function SettingsSystem() {
  const [confirmOpen, setConfirmOpen] = useState(false)
  const { mutate: runSchedule, isPending, data: results } = useRunSchedule()

  const handleConfirm = () => {
    runSchedule(undefined, {
      onSuccess: () => {
        setConfirmOpen(false)
        toast.success('Scheduled jobs ran.')
      },
      onError: (error: any) => {
        toast.error(
          error?.response?.data?.message ?? 'Could not run scheduled jobs.'
        )
      },
    })
  }

  return (
    <ContentSection
      title='System'
      desc='Manually run every job the production cron normally drives.'
    >
      <>
      <div className='space-y-4'>
        <Card>
          <CardContent className='flex flex-col gap-3 pt-6'>
            <p className='text-sm text-muted-foreground'>
              Runs all 7 jobs directly, in order: maintenance, billing
              lifecycle, AI price refresh, government price refresh, strike
              expiry, weather notifications, and daily spending reminders.
              AI price refresh still respects its own "AI feature controls"
              switch (Monetization page) and simply skips with a message if
              it's paused. This sends real push notifications (weather,
              reminders) and processes real billing transitions — it's the
              same work the cron does every day, just on demand. Don't run
              it repeatedly in a short window; it's throttled to 3 runs per
              hour.
            </p>
            <div>
              <Button
                onClick={() => setConfirmOpen(true)}
                disabled={isPending}
                className='space-x-1.5'
              >
                <PlayCircle size={18} />
                <span>{isPending ? 'Running…' : 'Run Scheduled Jobs Now'}</span>
              </Button>
            </div>
          </CardContent>
        </Card>

        {results && (
          <div className='space-y-2'>
            {results.map((r) => (
              <Card key={r.command}>
                <CardContent className='flex items-start gap-3 pt-4'>
                  {r.exit_code === 0 ? (
                    <CheckCircle2 size={18} className='mt-0.5 shrink-0 text-green-600' />
                  ) : (
                    <XCircle size={18} className='mt-0.5 shrink-0 text-red-600' />
                  )}
                  <div className='min-w-0 flex-1'>
                    <div className='flex flex-wrap items-center gap-2'>
                      <span className='font-medium'>
                        {COMMAND_LABELS[r.command] ?? r.command}
                      </span>
                      <Badge variant='outline' className='font-mono text-xs'>
                        {r.command}
                      </Badge>
                    </div>
                    {r.output && (
                      <pre className='mt-1 overflow-x-auto rounded bg-muted p-2 text-xs whitespace-pre-wrap'>
                        {r.output}
                      </pre>
                    )}
                  </div>
                </CardContent>
              </Card>
            ))}
          </div>
        )}
      </div>

      <ConfirmDialog
        open={confirmOpen}
        onOpenChange={setConfirmOpen}
        handleConfirm={handleConfirm}
        disabled={isPending}
        isLoading={isPending}
        title='Run all scheduled jobs now?'
        confirmText={isPending ? 'Running…' : 'Run Now'}
        desc='This immediately runs all 7 production cron jobs — real push notifications go out and real billing transitions process. Use this for testing or catching up after a missed cron, not routinely.'
      />
      </>
    </ContentSection>
  )
}
