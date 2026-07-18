import { Link } from '@tanstack/react-router'
import { ArrowLeft, CheckCircle2, MapPin, Store, Tag, XCircle } from 'lucide-react'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs'
import { ConfigDrawer } from '@/components/config-drawer'
import { Header } from '@/components/layout/header'
import { Main } from '@/components/layout/main'
import { ProfileDropdown } from '@/components/profile-dropdown'
import { Search } from '@/components/search'
import { ThemeSwitch } from '@/components/theme-switch'
import { marketTypeOptions } from './data/data'
import { type MarketDetail, type MarketPriceRow, useMarketDetailQuery } from './hooks/use-markets'

function typeLabel(type: string) {
  return marketTypeOptions.find((t) => t.value === type)?.label ?? type
}

export function MarketDetail({ marketId }: { marketId: number }) {
  const { data: market, isLoading } = useMarketDetailQuery(marketId)

  return (
    <>
      <Header fixed><Search className='me-auto' /><ThemeSwitch /><ConfigDrawer /><ProfileDropdown /></Header>
      <Main className='flex flex-1 flex-col gap-4 sm:gap-6'>
        <div>
          <Link to='/markets'>
            <Button variant='ghost' size='sm' className='mb-2 -ms-2'>
              <ArrowLeft className='me-1 size-4' /> Back to Markets
            </Button>
          </Link>
        </div>

        {isLoading || !market ? (
          <div className='text-muted-foreground'>Loading market…</div>
        ) : (
          <>
            <div className='flex flex-wrap items-start justify-between gap-2'>
              <div>
                <div className='flex items-center gap-2'>
                  <h2 className='text-2xl font-bold tracking-tight'>{market.name}</h2>
                  <Badge variant='outline'>{typeLabel(market.type)}</Badge>
                  {market.is_active ? (
                    <Badge className='bg-emerald-500/15 text-emerald-600'>Active</Badge>
                  ) : (
                    <Badge className='bg-muted text-muted-foreground'>Inactive</Badge>
                  )}
                </div>
                <p className='text-muted-foreground'>
                  {[market.barangay, market.municipality, market.province].filter(Boolean).join(', ') || 'No location on file'}
                </p>
              </div>
            </div>

            <div className='grid gap-3 sm:grid-cols-2 xl:grid-cols-4'>
              <Metric title='Stalls' value={market.tindahan_count ?? 0} icon={Store} />
              <Metric title='Prices listed' value={market.prices_count ?? 0} icon={Tag} />
              <Metric title='Region' value={market.region ?? '-'} icon={MapPin} />
              <Metric
                title='Source'
                value={market.source ?? 'manual'}
                icon={market.is_active ? CheckCircle2 : XCircle}
              />
            </div>

            <Card>
              <CardHeader><CardTitle className='text-base'>Market details</CardTitle></CardHeader>
              <CardContent className='grid gap-x-6 gap-y-2 text-sm sm:grid-cols-2 lg:grid-cols-3'>
                <Detail label='Coordinates' value={market.latitude && market.longitude ? `${market.latitude}, ${market.longitude}` : '-'} />
                <Detail label='OSM ID' value={market.osm_id ? String(market.osm_id) : '-'} />
                <Detail label='Listed by' value={market.user ? `${market.user.name} (${market.user.email})` : 'System / unattributed'} />
                <Detail label='Created' value={new Date(market.created_at).toLocaleString()} />
                <Detail label='Last updated' value={new Date(market.updated_at).toLocaleString()} />
              </CardContent>
            </Card>

            <div>
              <h3 className='mb-2 text-lg font-semibold'>Stalls in this market</h3>
              <div className='overflow-hidden rounded-md border'>
                <Table>
                  <TableHeader>
                    <TableRow>
                      <TableHead>Name</TableHead>
                      <TableHead>Type</TableHead>
                      <TableHead>Status</TableHead>
                      <TableHead>Rating</TableHead>
                      <TableHead>Prices</TableHead>
                    </TableRow>
                  </TableHeader>
                  <TableBody>
                    {market.tindahan.length === 0 ? (
                      <TableRow><TableCell colSpan={5} className='h-20 text-center text-muted-foreground'>No stalls listed under this market.</TableCell></TableRow>
                    ) : (
                      market.tindahan.map((stall) => (
                        <TableRow key={stall.id}>
                          <TableCell className='font-medium'>{stall.name}</TableCell>
                          <TableCell className='capitalize'>{stall.type}</TableCell>
                          <TableCell>
                            <div className='flex gap-1'>
                              <Badge className={`text-xs ${stall.is_active ? 'bg-emerald-500/15 text-emerald-600' : 'bg-muted text-muted-foreground'}`}>
                                {stall.is_active ? 'Active' : 'Inactive'}
                              </Badge>
                              {stall.is_verified && <Badge variant='outline' className='text-xs'>Verified</Badge>}
                            </div>
                          </TableCell>
                          <TableCell>{stall.ratings_count > 0 ? `${stall.average_rating.toFixed(1)} (${stall.ratings_count})` : '-'}</TableCell>
                          <TableCell>{stall.prices_count}</TableCell>
                        </TableRow>
                      ))
                    )}
                  </TableBody>
                </Table>
              </div>
            </div>

            <div>
              <h3 className='mb-2 text-lg font-semibold'>Prices at this market</h3>
              <PricesByStall market={market} />
            </div>
          </>
        )}
      </Main>
    </>
  )
}

/** Groups this market's prices into one tab per stall, plus a "General"
 * tab for prices attached to the market itself (no specific tindahan_id). */
function PricesByStall({ market }: { market: MarketDetail }) {
  const generalPrices = market.prices.filter((p) => !p.tindahan)
  const stallTabs = market.tindahan.map((stall) => ({
    key: String(stall.id),
    label: stall.name,
    prices: market.prices.filter((p) => p.tindahan?.id === stall.id),
  }))
  const tabs = [
    ...(generalPrices.length > 0 ? [{ key: 'general', label: 'General', prices: generalPrices }] : []),
    ...stallTabs,
  ]

  if (tabs.length === 0) {
    return (
      <div className='overflow-hidden rounded-md border'>
        <div className='flex h-20 items-center justify-center text-muted-foreground'>
          No prices listed for this market yet.
        </div>
      </div>
    )
  }

  return (
    <Tabs defaultValue={tabs[0].key}>
      <TabsList className='h-auto flex-wrap justify-start p-1'>
        {tabs.map((t) => (
          <TabsTrigger key={t.key} value={t.key}>
            {t.label} <span className='ms-1 text-xs text-muted-foreground'>({t.prices.length})</span>
          </TabsTrigger>
        ))}
      </TabsList>
      {tabs.map((t) => (
        <TabsContent key={t.key} value={t.key}>
          <PriceTable prices={t.prices} />
        </TabsContent>
      ))}
    </Tabs>
  )
}

function PriceTable({ prices }: { prices: MarketPriceRow[] }) {
  return (
    <div className='overflow-hidden rounded-md border'>
      <Table>
        <TableHeader>
          <TableRow>
            <TableHead>Item</TableHead>
            <TableHead>Category</TableHead>
            <TableHead>Price</TableHead>
            <TableHead>Updated</TableHead>
          </TableRow>
        </TableHeader>
        <TableBody>
          {prices.length === 0 ? (
            <TableRow><TableCell colSpan={4} className='h-20 text-center text-muted-foreground'>No prices for this stall yet.</TableCell></TableRow>
          ) : (
            prices.map((price) => (
              <TableRow key={price.id}>
                <TableCell className='font-medium'>{price.item_name}</TableCell>
                <TableCell className='capitalize'>{price.category}</TableCell>
                <TableCell>₱{Number(price.price_per_unit).toFixed(2)} / {price.unit}</TableCell>
                <TableCell>{new Date(price.updated_at).toLocaleDateString()}</TableCell>
              </TableRow>
            ))
          )}
        </TableBody>
      </Table>
    </div>
  )
}

function Metric({ title, value, icon: Icon }: { title: string; value: string | number; icon: typeof Store }) {
  return (
    <Card>
      <CardHeader className='flex flex-row items-center justify-between pb-2'>
        <CardTitle className='text-sm font-medium'>{title}</CardTitle>
        <Icon className='size-4 text-muted-foreground' />
      </CardHeader>
      <CardContent><div className='text-2xl font-bold capitalize'>{value}</div></CardContent>
    </Card>
  )
}

function Detail({ label, value }: { label: string; value: string }) {
  return (
    <div>
      <div className='text-xs uppercase tracking-wide text-muted-foreground'>{label}</div>
      <div>{value}</div>
    </div>
  )
}
