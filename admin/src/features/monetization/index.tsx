import { useEffect, useState } from 'react'
import { toast } from 'sonner'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Switch } from '@/components/ui/switch'
import { Textarea } from '@/components/ui/textarea'
import { ConfigDrawer } from '@/components/config-drawer'
import { Header } from '@/components/layout/header'
import { Main } from '@/components/layout/main'
import { ProfileDropdown } from '@/components/profile-dropdown'
import { Search } from '@/components/search'
import { ThemeSwitch } from '@/components/theme-switch'
import {
  type BoostOption,
  type SellerPlan,
  usePaymentSettingsQuery,
  useSellerPlansQuery,
  useUpdateBoostOption,
  useUpdatePaymentSettings,
  useUpdatePlan,
  useUpdatePlanPrices,
} from './hooks/use-monetization'

const DURATIONS = ['7d', '15d', '1m', '1y'] as const
const DURATION_LABEL: Record<string, string> = {
  '7d': '7 days',
  '15d': '15 days',
  '1m': 'Monthly',
  '1y': 'Yearly',
}

function PlanCard({ plan }: { plan: SellerPlan }) {
  const updatePlan = useUpdatePlan()
  const updatePrices = useUpdatePlanPrices()

  const [maxStores, setMaxStores] = useState(String(plan.max_stores))
  const [maxItems, setMaxItems] = useState(String(plan.max_items_per_store))
  const [prices, setPrices] = useState<Record<string, string>>({})

  useEffect(() => {
    setMaxStores(String(plan.max_stores))
    setMaxItems(String(plan.max_items_per_store))
    setPrices(
      Object.fromEntries(
        plan.prices.map((p) => [p.duration, String(Number(p.price))])
      )
    )
  }, [plan])

  const isFree = plan.slug === 'free'

  const limitsDirty =
    maxStores !== String(plan.max_stores) ||
    maxItems !== String(plan.max_items_per_store)

  const pricesDirty = plan.prices.some(
    (p) => prices[p.duration] !== undefined && prices[p.duration] !== String(Number(p.price))
  )

  const save = () => {
    if (limitsDirty) {
      updatePlan.mutate(
        {
          id: plan.id,
          max_stores: Number(maxStores),
          max_items_per_store: Number(maxItems),
        },
        {
          onSuccess: () => toast.success(`${plan.name} limits saved.`),
          onError: (error: any) =>
            toast.error(error?.response?.data?.message ?? 'Could not save.'),
        }
      )
    }
    if (!isFree && pricesDirty) {
      updatePrices.mutate(
        {
          id: plan.id,
          prices: DURATIONS.filter((d) => prices[d] !== undefined).map((d) => ({
            duration: d,
            price: Number(prices[d]),
          })),
        },
        {
          onSuccess: () => toast.success(`${plan.name} prices saved.`),
          onError: (error: any) =>
            toast.error(error?.response?.data?.message ?? 'Could not save.'),
        }
      )
    }
  }

  return (
    <Card>
      <CardHeader>
        <div className='flex items-center justify-between'>
          <CardTitle className='flex items-center gap-2'>
            {plan.name}
            <Badge variant='outline' className='capitalize'>
              {plan.slug}
            </Badge>
          </CardTitle>
          {(limitsDirty || pricesDirty) && (
            <Button
              size='sm'
              onClick={save}
              disabled={updatePlan.isPending || updatePrices.isPending}
            >
              Save
            </Button>
          )}
        </div>
        {plan.tagline && <CardDescription>{plan.tagline}</CardDescription>}
      </CardHeader>
      <CardContent className='space-y-4'>
        <div className='grid grid-cols-2 gap-3'>
          <div className='space-y-1.5'>
            <Label>Max stores</Label>
            <Input
              type='number'
              min={1}
              value={maxStores}
              onChange={(e) => setMaxStores(e.target.value)}
            />
          </div>
          <div className='space-y-1.5'>
            <Label>Items per store</Label>
            <Input
              type='number'
              min={1}
              value={maxItems}
              onChange={(e) => setMaxItems(e.target.value)}
            />
          </div>
        </div>

        {!isFree && (
          <div className='grid grid-cols-2 gap-3 sm:grid-cols-4'>
            {DURATIONS.map((d) => (
              <div key={d} className='space-y-1.5'>
                <Label>{DURATION_LABEL[d]}</Label>
                <div className='relative'>
                  <span className='absolute start-2.5 top-1/2 -translate-y-1/2 text-sm text-muted-foreground'>
                    ₱
                  </span>
                  <Input
                    type='number'
                    min={1}
                    className='ps-6'
                    value={prices[d] ?? ''}
                    onChange={(e) =>
                      setPrices((prev) => ({ ...prev, [d]: e.target.value }))
                    }
                  />
                </div>
              </div>
            ))}
          </div>
        )}
      </CardContent>
    </Card>
  )
}

function BoostRow({ option }: { option: BoostOption }) {
  const update = useUpdateBoostOption()
  const [price, setPrice] = useState(String(Number(option.price)))

  useEffect(() => setPrice(String(Number(option.price))), [option])

  const dirty = price !== String(Number(option.price))

  return (
    <div className='flex items-center justify-between gap-3 rounded-md border p-3'>
      <div>
        <p className='text-sm font-medium'>
          Boost {option.target === 'tindahan' ? 'Store' : 'Recipe'} —{' '}
          {option.duration_days} days
        </p>
        <p className='text-xs text-muted-foreground'>
          Sold in the app once boosting launches (Phase 3). Price is editable
          now.
        </p>
      </div>
      <div className='flex items-center gap-2'>
        <div className='relative w-28'>
          <span className='absolute start-2.5 top-1/2 -translate-y-1/2 text-sm text-muted-foreground'>
            ₱
          </span>
          <Input
            type='number'
            min={1}
            className='ps-6'
            value={price}
            onChange={(e) => setPrice(e.target.value)}
          />
        </div>
        {dirty && (
          <Button
            size='sm'
            disabled={update.isPending}
            onClick={() =>
              update.mutate(
                { id: option.id, price: Number(price) },
                {
                  onSuccess: () => toast.success('Boost price saved.'),
                  onError: (error: any) =>
                    toast.error(
                      error?.response?.data?.message ?? 'Could not save.'
                    ),
                }
              )
            }
          >
            Save
          </Button>
        )}
      </div>
    </div>
  )
}

function PaymentSettingsCard() {
  const { data: settings } = usePaymentSettingsQuery()
  const update = useUpdatePaymentSettings()

  const [enabled, setEnabled] = useState(true)
  const [gcashNumber, setGcashNumber] = useState('')
  const [gcashName, setGcashName] = useState('')
  const [instructions, setInstructions] = useState('')
  const [supportNote, setSupportNote] = useState('')

  useEffect(() => {
    if (!settings) return
    setEnabled(settings.payments_enabled !== '0')
    setGcashNumber(settings.gcash_number ?? '')
    setGcashName(settings.gcash_account_name ?? '')
    setInstructions(settings.payment_instructions ?? '')
    setSupportNote(settings.payment_support_note ?? '')
  }, [settings])

  return (
    <Card>
      <CardHeader>
        <CardTitle>Checkout availability</CardTitle>
        <CardDescription>
          Shown to sellers on the subscription screen. The kill switch hides
          all payment UI in the app instantly — no app update needed.
        </CardDescription>
      </CardHeader>
      <CardContent className='space-y-4'>
        <div className='flex items-center justify-between rounded-md border p-3'>
          <div>
            <p className='text-sm font-medium'>Payments enabled</p>
            <p className='text-xs text-muted-foreground'>
              Turn off to hide the subscription checkout in the app.
            </p>
          </div>
          <Switch checked={enabled} onCheckedChange={setEnabled} />
        </div>
        <div className='grid gap-3 sm:grid-cols-2'>
          <div className='space-y-1.5'>
            <Label>Legacy manual GCash number (migration only)</Label>
            <Input
              placeholder='09XX XXX XXXX'
              value={gcashNumber}
              onChange={(e) => setGcashNumber(e.target.value)}
            />
          </div>
          <div className='space-y-1.5'>
            <Label>Legacy account name (migration only)</Label>
            <Input
              placeholder='J*** D.'
              value={gcashName}
              onChange={(e) => setGcashName(e.target.value)}
            />
          </div>
        </div>
        <div className='space-y-1.5'>
          <Label>Payment instructions</Label>
          <Textarea
            rows={4}
            value={instructions}
            onChange={(e) => setInstructions(e.target.value)}
          />
        </div>
        <div className='space-y-1.5'>
          <Label>Support note</Label>
          <Input
            value={supportNote}
            onChange={(e) => setSupportNote(e.target.value)}
          />
        </div>
        <div className='flex justify-end'>
          <Button
            disabled={update.isPending}
            onClick={() =>
              update.mutate(
                {
                  payments_enabled: enabled,
                  gcash_number: gcashNumber || null,
                  gcash_account_name: gcashName || null,
                  payment_instructions: instructions || null,
                  payment_support_note: supportNote || null,
                },
                {
                  onSuccess: () => toast.success('Payment settings saved.'),
                  onError: (error: any) =>
                    toast.error(
                      error?.response?.data?.message ?? 'Could not save.'
                    ),
                }
              )
            }
          >
            Save payment settings
          </Button>
        </div>
      </CardContent>
    </Card>
  )
}

export function Monetization() {
  const { data, isLoading } = useSellerPlansQuery()

  return (
    <>
      <Header fixed>
        <Search className='me-auto' />
        <ThemeSwitch />
        <ConfigDrawer />
        <ProfileDropdown />
      </Header>

      <Main className='flex flex-1 flex-col gap-4 sm:gap-6'>
        <div>
          <h2 className='text-2xl font-bold tracking-tight'>
            Plans & Pricing
          </h2>
          <p className='text-muted-foreground'>
            Seller tiers, boost prices, and the GCash details sellers pay to.
            Changes apply to the app immediately.
          </p>
        </div>

        <PaymentSettingsCard />

        {isLoading ? (
          <p className='text-muted-foreground'>Loading plans...</p>
        ) : (
          <>
            <div className='grid gap-4 lg:grid-cols-2'>
              {(data?.plans ?? []).map((plan) => (
                <PlanCard key={plan.id} plan={plan} />
              ))}
            </div>

            <div>
              <h3 className='mb-2 text-lg font-semibold'>Boost prices</h3>
              <div className='grid gap-2 lg:grid-cols-2'>
                {(data?.boost_options ?? []).map((option) => (
                  <BoostRow key={option.id} option={option} />
                ))}
              </div>
            </div>
          </>
        )}
      </Main>
    </>
  )
}
