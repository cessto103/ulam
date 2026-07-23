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
import { Checkbox } from '@/components/ui/checkbox'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Switch } from '@/components/ui/switch'
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs'
import { Textarea } from '@/components/ui/textarea'
import { Trash2, Plus, RotateCcw } from 'lucide-react'
import { ConfigDrawer } from '@/components/config-drawer'
import { Header } from '@/components/layout/header'
import { Main } from '@/components/layout/main'
import { ProfileDropdown } from '@/components/profile-dropdown'
import { Search } from '@/components/search'
import { ThemeSwitch } from '@/components/theme-switch'
import {
  type BoostOption,
  type PremiumFeature,
  type PremiumPricing,
  type SellerPlan,
  usePaymentSettingsQuery,
  usePremiumFeaturesQuery,
  usePremiumPricingQuery,
  useResetPremiumFeatures,
  useSellerPlansQuery,
  useUpdateBoostOption,
  useUpdatePaymentSettings,
  useUpdatePlan,
  useUpdatePlanPrices,
  useUpdatePremiumFeatures,
  useUpdatePremiumPricing,
} from './hooks/use-monetization'

// Mirrors the mobile app's compiled-in default (app/upgrade.tsx) — seeds the
// editor the first time, before the admin has saved anything of their own.
const DEFAULT_PREMIUM_FEATURES: PremiumFeature[] = [
  { emoji: '🍳', title_en: 'AI Meal Planning', title_tl: 'AI Meal Planning', desc_en: 'Get a meal plan every day', desc_tl: 'Humingi ng meal plan araw-araw', free: false },
  { emoji: '📊', title_en: 'Budget Tracking', title_tl: 'Budget Tracking', desc_en: 'Log expenses, track your savings', desc_tl: 'Mag-log ng gastos, tingnan ang savings', free: true },
  { emoji: '📢', title_en: 'Price Reporting', title_tl: 'Price Reporting', desc_en: 'Report and check prices', desc_tl: 'Mag-report at makita ang presyo', free: true },
  { emoji: '👥', title_en: 'Community', title_tl: 'Komunidad', desc_en: 'Posts, likes, and tips from neighbors', desc_tl: 'Mga post, puso, at diskarte ng kapitbahay', free: true },
  { emoji: '🔓', title_en: 'Unlimited AI Plans', title_tl: 'Unlimited AI Plans', desc_en: 'No limits, as many times as you want', desc_tl: 'Walang limitasyon, kahit ilang beses', free: false },
  { emoji: '🤝', title_en: 'Shopping List Sharing', title_tl: 'Shopping List Sharing', desc_en: 'Share your shopping list with family', desc_tl: 'Ibahagi ang shopping list mo sa pamilya', free: false },
  { emoji: '📅', title_en: '7-Day Meal Planning', title_tl: '7-Araw na Meal Planning', desc_en: 'Plan your meals a full week ahead', desc_tl: 'Magplano ng meals para sa isang buong linggo', free: false },
]

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
          Boost {option.target === 'tindahan' ? 'Store' : 'Recipe'}:{' '}
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
          all payment UI in the app instantly: no app update needed.
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

function PremiumFeaturesCard() {
  const { data: saved, isLoading } = usePremiumFeaturesQuery()
  const update = useUpdatePremiumFeatures()
  const reset = useResetPremiumFeatures()

  const [features, setFeatures] = useState<PremiumFeature[]>(DEFAULT_PREMIUM_FEATURES)
  const [dirty, setDirty] = useState(false)

  useEffect(() => {
    if (isLoading) return
    setFeatures(saved?.length ? saved : DEFAULT_PREMIUM_FEATURES)
    setDirty(false)
  }, [saved, isLoading])

  const setField = (i: number, patch: Partial<PremiumFeature>) => {
    setFeatures((prev) => prev.map((f, idx) => (idx === i ? { ...f, ...patch } : f)))
    setDirty(true)
  }

  const removeRow = (i: number) => {
    setFeatures((prev) => prev.filter((_, idx) => idx !== i))
    setDirty(true)
  }

  const addRow = () => {
    setFeatures((prev) => [...prev, { emoji: '✨', title_en: '', title_tl: '', desc_en: '', desc_tl: '', free: false }])
    setDirty(true)
  }

  return (
    <Card>
      <CardHeader>
        <CardTitle>uLam Premium: included features</CardTitle>
        <CardDescription>
          The "Included in Premium" list shown on the app's Upgrade screen. Mark a row "Free" if it's
          available to everyone (shown greyed-out, for comparison), leave unchecked for Premium-only features.
        </CardDescription>
      </CardHeader>
      <CardContent className='space-y-3'>
        {features.map((f, i) => (
          <div key={i} className='space-y-2 rounded-md border p-3'>
            <div className='flex items-center gap-2'>
              <Input
                value={f.emoji}
                onChange={(e) => setField(i, { emoji: e.target.value })}
                className='h-8 w-14 text-center'
                maxLength={4}
              />
              <div className='flex flex-1 items-center gap-2'>
                <Checkbox
                  id={`free-${i}`}
                  checked={f.free}
                  onCheckedChange={(checked) => setField(i, { free: checked === true })}
                />
                <Label htmlFor={`free-${i}`} className='text-xs font-normal text-muted-foreground'>
                  Free for everyone
                </Label>
              </div>
              <Button variant='ghost' size='icon' onClick={() => removeRow(i)}>
                <Trash2 className='size-4 text-red-500' />
              </Button>
            </div>
            <div className='grid gap-2 sm:grid-cols-2'>
              <Input placeholder='Title (English)' value={f.title_en} onChange={(e) => setField(i, { title_en: e.target.value })} />
              <Input placeholder='Title (Tagalog)' value={f.title_tl} onChange={(e) => setField(i, { title_tl: e.target.value })} />
              <Input placeholder='Description (English)' value={f.desc_en} onChange={(e) => setField(i, { desc_en: e.target.value })} />
              <Input placeholder='Description (Tagalog)' value={f.desc_tl} onChange={(e) => setField(i, { desc_tl: e.target.value })} />
            </div>
          </div>
        ))}

        <div className='flex flex-wrap items-center justify-between gap-2 pt-1'>
          <Button variant='outline' size='sm' onClick={addRow}>
            <Plus /> Add feature
          </Button>
          <div className='flex gap-2'>
            <Button
              variant='outline'
              size='sm'
              disabled={reset.isPending}
              onClick={() =>
                reset.mutate(undefined, {
                  onSuccess: () => toast.success('Back to the built-in feature list.'),
                  onError: (error: any) => toast.error(error?.response?.data?.message ?? 'Could not reset.'),
                })
              }
            >
              <RotateCcw /> Reset to built-in
            </Button>
            <Button
              size='sm'
              disabled={!dirty || update.isPending}
              onClick={() =>
                update.mutate(features, {
                  onSuccess: () => { toast.success('Premium features saved.'); setDirty(false) },
                  onError: (error: any) => toast.error(error?.response?.data?.message ?? 'Could not save.'),
                })
              }
            >
              Save changes
            </Button>
          </div>
        </div>
      </CardContent>
    </Card>
  )
}

const EMPTY_PRICING: PremiumPricing = {
  premium_price_monthly: '59',
  premium_price_yearly: '499',
  premium_promo_enabled: '0',
  premium_promo_label: '',
  premium_promo_price_monthly: '',
  premium_promo_price_yearly: '',
}

function PremiumPricingCard() {
  const { data: saved, isLoading } = usePremiumPricingQuery()
  const update = useUpdatePremiumPricing()

  const [form, setForm] = useState<PremiumPricing>(EMPTY_PRICING)
  const [dirty, setDirty] = useState(false)

  useEffect(() => {
    if (isLoading || !saved) return
    setForm(saved)
    setDirty(false)
  }, [saved, isLoading])

  const setField = (patch: Partial<PremiumPricing>) => {
    setForm((prev) => ({ ...prev, ...patch }))
    setDirty(true)
  }

  const promoOn = form.premium_promo_enabled === '1'

  return (
    <Card>
      <CardHeader>
        <CardTitle>Pricing</CardTitle>
        <CardDescription>
          Monthly and yearly prices for uLam Premium, and an optional promo discount for either plan.
        </CardDescription>
      </CardHeader>
      <CardContent className='space-y-5'>
        <div className='grid gap-3 sm:grid-cols-2'>
          <div className='space-y-1.5'>
            <Label>Monthly price</Label>
            <div className='relative'>
              <span className='absolute start-2.5 top-1/2 -translate-y-1/2 text-sm text-muted-foreground'>₱</span>
              <Input
                type='number'
                min={0}
                className='ps-6'
                value={form.premium_price_monthly}
                onChange={(e) => setField({ premium_price_monthly: e.target.value })}
              />
            </div>
          </div>
          <div className='space-y-1.5'>
            <Label>Yearly price</Label>
            <div className='relative'>
              <span className='absolute start-2.5 top-1/2 -translate-y-1/2 text-sm text-muted-foreground'>₱</span>
              <Input
                type='number'
                min={0}
                className='ps-6'
                value={form.premium_price_yearly}
                onChange={(e) => setField({ premium_price_yearly: e.target.value })}
              />
            </div>
          </div>
        </div>

        <div className='space-y-4 rounded-md border p-3'>
          <div className='flex items-center justify-between'>
            <div>
              <p className='text-sm font-medium'>Promo discount</p>
              <p className='text-xs text-muted-foreground'>
                Shows a strikethrough price and a savings badge on the Upgrade screen. Leave a plan's
                discounted price blank to keep that plan at its regular price.
              </p>
            </div>
            <Switch
              checked={promoOn}
              onCheckedChange={(checked) => setField({ premium_promo_enabled: checked ? '1' : '0' })}
            />
          </div>

          {promoOn && (
            <>
              <div className='space-y-1.5'>
                <Label>Promo name</Label>
                <Input
                  placeholder="e.g. Mother's Day special discount!"
                  value={form.premium_promo_label}
                  onChange={(e) => setField({ premium_promo_label: e.target.value })}
                />
              </div>
              <div className='grid gap-3 sm:grid-cols-2'>
                <div className='space-y-1.5'>
                  <Label>Discounted monthly price</Label>
                  <div className='relative'>
                    <span className='absolute start-2.5 top-1/2 -translate-y-1/2 text-sm text-muted-foreground'>₱</span>
                    <Input
                      type='number'
                      min={0}
                      className='ps-6'
                      placeholder='No discount'
                      value={form.premium_promo_price_monthly}
                      onChange={(e) => setField({ premium_promo_price_monthly: e.target.value })}
                    />
                  </div>
                </div>
                <div className='space-y-1.5'>
                  <Label>Discounted yearly price</Label>
                  <div className='relative'>
                    <span className='absolute start-2.5 top-1/2 -translate-y-1/2 text-sm text-muted-foreground'>₱</span>
                    <Input
                      type='number'
                      min={0}
                      className='ps-6'
                      placeholder='No discount'
                      value={form.premium_promo_price_yearly}
                      onChange={(e) => setField({ premium_promo_price_yearly: e.target.value })}
                    />
                  </div>
                </div>
              </div>
            </>
          )}
        </div>

        <div className='flex justify-end'>
          <Button
            disabled={!dirty || update.isPending}
            onClick={() =>
              update.mutate(form, {
                onSuccess: () => { toast.success('Pricing saved.'); setDirty(false) },
                onError: (error: any) => toast.error(error?.response?.data?.message ?? 'Could not save.'),
              })
            }
          >
            Save pricing
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

        <Tabs defaultValue='premium'>
          <TabsList>
            <TabsTrigger value='premium'>uLam Premium: included features</TabsTrigger>
            <TabsTrigger value='seller-plans'>Seller plans</TabsTrigger>
            <TabsTrigger value='boost-prices'>Boost prices</TabsTrigger>
          </TabsList>

          <TabsContent value='premium' className='flex flex-col gap-4 sm:gap-6'>
            <PremiumFeaturesCard />
            <PremiumPricingCard />
          </TabsContent>

          <TabsContent value='seller-plans'>
            {isLoading ? (
              <p className='text-muted-foreground'>Loading plans...</p>
            ) : (
              <div className='grid gap-4 lg:grid-cols-2'>
                {(data?.plans ?? []).map((plan) => (
                  <PlanCard key={plan.id} plan={plan} />
                ))}
              </div>
            )}
          </TabsContent>

          <TabsContent value='boost-prices'>
            {isLoading ? (
              <p className='text-muted-foreground'>Loading boost prices...</p>
            ) : (
              <div className='grid gap-2 lg:grid-cols-2'>
                {(data?.boost_options ?? []).map((option) => (
                  <BoostRow key={option.id} option={option} />
                ))}
              </div>
            )}
          </TabsContent>
        </Tabs>
      </Main>
    </>
  )
}
