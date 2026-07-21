import { useEffect, useState } from 'react'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { toast } from 'sonner'
import { Loader2 } from 'lucide-react'
import apiClient from '@/lib/api-client'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import { ConfigDrawer } from '@/components/config-drawer'
import { Header } from '@/components/layout/header'
import { Main } from '@/components/layout/main'
import { ProfileDropdown } from '@/components/profile-dropdown'
import { Search } from '@/components/search'
import { ThemeSwitch } from '@/components/theme-switch'

type BusinessSettings = {
  biz_registered_name: string | null
  biz_trade_name: string | null
  biz_address: string | null
  biz_tin: string | null
  biz_vat_status: 'vat_registered' | 'non_vat' | null
  biz_atp_number: string | null
  biz_atp_date: string | null
  biz_atp_valid_until: string | null
  biz_contact_email: string | null
  biz_contact_phone: string | null
  biz_notes: string | null
  invoice_number_prefix: string | null
  invoice_number_padding: string | null
}

const QUERY_KEY = 'admin-business-settings'

export function BusinessSettingsPage() {
  const qc = useQueryClient()
  const [form, setForm] = useState<BusinessSettings | null>(null)

  const { data, isLoading } = useQuery({
    queryKey: [QUERY_KEY],
    queryFn: async () => (await apiClient.get<BusinessSettings>('/admin/business-settings')).data,
  })

  useEffect(() => {
    if (data && !form) setForm(data)
  }, [data, form])

  const save = useMutation({
    mutationFn: async () => apiClient.put<BusinessSettings>('/admin/business-settings', form),
    onSuccess: (res) => {
      qc.setQueryData([QUERY_KEY], res.data)
      toast.success('Business & tax settings saved.')
    },
    onError: (error: any) =>
      toast.error(error?.response?.data?.message ?? 'Could not save.'),
  })

  const field = (key: keyof BusinessSettings) => ({
    value: form?.[key] ?? '',
    onChange: (e: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement>) =>
      setForm((f) => f && { ...f, [key]: e.target.value }),
  })

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
          <h2 className='text-2xl font-bold tracking-tight'>Business & Tax Settings</h2>
          <p className='text-muted-foreground'>
            Registered business identity and BIR receipting details. Everything here is optional
            and admin-only — fill it in whenever your registration paperwork is ready. Nothing
            here is shown to app users; the business fields are stamped onto an invoice only once
            it's marked as paid, frozen at that moment even if you edit these later.
          </p>
        </div>

        {isLoading || !form ? (
          <p className='text-muted-foreground'>Loading...</p>
        ) : (
          <div className='flex flex-col gap-4 max-w-2xl'>
            <Card>
              <CardHeader>
                <CardTitle className='text-base'>Registered business identity</CardTitle>
                <CardDescription>As it appears on your BIR Certificate of Registration.</CardDescription>
              </CardHeader>
              <CardContent className='space-y-4'>
                <div className='grid grid-cols-2 gap-3'>
                  <div className='space-y-1.5'>
                    <Label>Registered business name</Label>
                    <Input placeholder='Not set' {...field('biz_registered_name')} />
                  </div>
                  <div className='space-y-1.5'>
                    <Label>Trade name (if different)</Label>
                    <Input placeholder='Not set' {...field('biz_trade_name')} />
                  </div>
                </div>
                <div className='space-y-1.5'>
                  <Label>Registered business address</Label>
                  <Input placeholder='Not set' {...field('biz_address')} />
                </div>
                <div className='grid grid-cols-2 gap-3'>
                  <div className='space-y-1.5'>
                    <Label>TIN</Label>
                    <Input placeholder='000-000-000-0000' {...field('biz_tin')} />
                  </div>
                  <div className='space-y-1.5'>
                    <Label>VAT status</Label>
                    <Select
                      value={form.biz_vat_status ?? undefined}
                      onValueChange={(v) =>
                        setForm((f) => f && { ...f, biz_vat_status: v as BusinessSettings['biz_vat_status'] })
                      }
                    >
                      <SelectTrigger>
                        <SelectValue placeholder='Not set' />
                      </SelectTrigger>
                      <SelectContent>
                        <SelectItem value='vat_registered'>VAT-registered</SelectItem>
                        <SelectItem value='non_vat'>Non-VAT</SelectItem>
                      </SelectContent>
                    </Select>
                  </div>
                </div>
              </CardContent>
            </Card>

            <Card>
              <CardHeader>
                <CardTitle className='text-base'>Authority to Print / receipting</CardTitle>
                <CardDescription>
                  From your printed booklet's Authority to Print, or your accredited e-invoicing
                  system's permit number if you go that route instead.
                </CardDescription>
              </CardHeader>
              <CardContent className='space-y-4'>
                <div className='space-y-1.5'>
                  <Label>ATP / permit number</Label>
                  <Input placeholder='Not set' {...field('biz_atp_number')} />
                </div>
                <div className='grid grid-cols-2 gap-3'>
                  <div className='space-y-1.5'>
                    <Label>Date issued</Label>
                    <Input type='date' {...field('biz_atp_date')} />
                  </div>
                  <div className='space-y-1.5'>
                    <Label>Valid until</Label>
                    <Input type='date' {...field('biz_atp_valid_until')} />
                  </div>
                </div>
              </CardContent>
            </Card>

            <Card>
              <CardHeader>
                <CardTitle className='text-base'>Invoice numbering</CardTitle>
                <CardDescription>
                  How official invoice numbers are formatted once an invoice is marked as paid.
                </CardDescription>
              </CardHeader>
              <CardContent className='space-y-4'>
                <div className='grid grid-cols-2 gap-3'>
                  <div className='space-y-1.5'>
                    <Label>Prefix</Label>
                    <Input placeholder='INV-2026-' {...field('invoice_number_prefix')} />
                  </div>
                  <div className='space-y-1.5'>
                    <Label>Digits (padding)</Label>
                    <Input type='number' min={1} max={10} placeholder='6' {...field('invoice_number_padding')} />
                  </div>
                </div>
                <p className='text-xs text-muted-foreground'>
                  The counter itself starts at 1 automatically and only advances when an invoice is
                  marked as paid — it isn't editable here.
                </p>
              </CardContent>
            </Card>

            <Card>
              <CardHeader>
                <CardTitle className='text-base'>Contact & notes</CardTitle>
                <CardDescription>Stamped onto generated invoices alongside your business identity above.</CardDescription>
              </CardHeader>
              <CardContent className='space-y-4'>
                <div className='grid grid-cols-2 gap-3'>
                  <div className='space-y-1.5'>
                    <Label>Business contact email</Label>
                    <Input placeholder='Not set' {...field('biz_contact_email')} />
                  </div>
                  <div className='space-y-1.5'>
                    <Label>Business contact phone</Label>
                    <Input placeholder='Not set' {...field('biz_contact_phone')} />
                  </div>
                </div>
                <div className='space-y-1.5'>
                  <Label>Notes</Label>
                  <Textarea
                    rows={4}
                    placeholder='Anything else your paperwork needs that does not fit a box above (RDO code, branch code, special ATP conditions, etc.)'
                    {...field('biz_notes')}
                  />
                </div>
              </CardContent>
            </Card>

            <Button disabled={save.isPending} onClick={() => save.mutate()} className='self-start'>
              {save.isPending ? <Loader2 className='animate-spin' /> : null} Save
            </Button>
          </div>
        )}
      </Main>
    </>
  )
}
