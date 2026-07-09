'use client'

import { z } from 'zod'
import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { toast } from 'sonner'
import { useMarketOptionsQuery } from '@/features/markets/hooks/use-markets'
import { Button } from '@/components/ui/button'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'
import {
  Form,
  FormControl,
  FormField,
  FormItem,
  FormLabel,
  FormMessage,
} from '@/components/ui/form'
import { Input } from '@/components/ui/input'
import { Switch } from '@/components/ui/switch'
import { Textarea } from '@/components/ui/textarea'
import { SelectDropdown } from '@/components/select-dropdown'
import { type Tindahan } from '../data/schema'
import { useCreateTindahan, useUpdateTindahan } from '../hooks/use-tindahan'

const NO_MARKET = 'none'

const formSchema = z.object({
  name: z.string().min(1, 'Name is required.'),
  market_id: z.string().optional(),
  type: z.string().optional(),
  description: z.string().optional(),
  barangay: z.string().optional(),
  municipality: z.string().optional(),
  province: z.string().optional(),
  region: z.string().optional(),
  contact_number: z.string().optional(),
  gcash_number: z.string().optional(),
  is_active: z.boolean(),
  is_verified: z.boolean(),
})
type TindahanForm = z.infer<typeof formSchema>

type TindahanActionDialogProps = {
  currentRow?: Tindahan
  open: boolean
  onOpenChange: (open: boolean) => void
}

export function TindahanActionDialog({
  currentRow,
  open,
  onOpenChange,
}: TindahanActionDialogProps) {
  const isEdit = !!currentRow
  const { data: marketOptions, isPending: loadingMarkets } =
    useMarketOptionsQuery()
  const { mutate: createTindahan, isPending: creating } = useCreateTindahan()
  const { mutate: updateTindahan, isPending: updating } = useUpdateTindahan()
  const isPending = creating || updating

  const form = useForm<TindahanForm>({
    resolver: zodResolver(formSchema),
    defaultValues: isEdit
      ? {
          name: currentRow.name,
          market_id: currentRow.market_id
            ? String(currentRow.market_id)
            : NO_MARKET,
          type: currentRow.type ?? '',
          description: currentRow.description ?? '',
          barangay: currentRow.barangay ?? '',
          municipality: currentRow.municipality ?? '',
          province: currentRow.province ?? '',
          region: currentRow.region ?? '',
          contact_number: currentRow.contact_number ?? '',
          gcash_number: currentRow.gcash_number ?? '',
          is_active: currentRow.is_active,
          is_verified: currentRow.is_verified,
        }
      : {
          name: '',
          market_id: NO_MARKET,
          type: '',
          description: '',
          barangay: '',
          municipality: '',
          province: '',
          region: '',
          contact_number: '',
          gcash_number: '',
          is_active: true,
          is_verified: false,
        },
  })

  const onSubmit = (values: TindahanForm) => {
    const onSuccess = () => {
      form.reset()
      onOpenChange(false)
      toast.success(isEdit ? 'Tindahan updated.' : 'Tindahan created.')
    }
    const onError = (error: any) => {
      toast.error(
        error?.response?.data?.message ?? 'Could not save tindahan.'
      )
    }

    const payload = {
      name: values.name,
      market_id:
        !values.market_id || values.market_id === NO_MARKET
          ? null
          : Number(values.market_id),
      type: values.type || null,
      description: values.description || null,
      barangay: values.barangay || null,
      municipality: values.municipality || null,
      province: values.province || null,
      region: values.region || null,
      contact_number: values.contact_number || null,
      gcash_number: values.gcash_number || null,
      is_active: values.is_active,
      is_verified: values.is_verified,
    }

    if (isEdit) {
      updateTindahan({ id: currentRow.id, ...payload }, { onSuccess, onError })
    } else {
      createTindahan(payload, { onSuccess, onError })
    }
  }

  const marketItems = [
    { label: 'No market (standalone)', value: NO_MARKET },
    ...(marketOptions ?? []).map((m) => ({
      label: m.name,
      value: String(m.id),
    })),
  ]

  return (
    <Dialog
      open={open}
      onOpenChange={(state) => {
        form.reset()
        onOpenChange(state)
      }}
    >
      <DialogContent className='sm:max-w-lg'>
        <DialogHeader className='text-start'>
          <DialogTitle>
            {isEdit ? 'Edit Tindahan' : 'Add New Tindahan'}
          </DialogTitle>
          <DialogDescription>
            {isEdit ? 'Update the store/stall here. ' : 'Create a new store or stall here. '}
            Click save when you&apos;re done.
          </DialogDescription>
        </DialogHeader>
        <div className='w-[calc(100%+0.75rem)] overflow-y-auto py-1 pe-3'>
          <Form {...form}>
            <form
              id='tindahan-form'
              onSubmit={form.handleSubmit(onSubmit)}
              className='space-y-4 px-0.5'
            >
              <FormField
                control={form.control}
                name='name'
                render={({ field }) => (
                  <FormItem className='grid grid-cols-6 items-center space-y-0 gap-x-4 gap-y-1'>
                    <FormLabel className='col-span-2 text-end'>Name</FormLabel>
                    <FormControl>
                      <Input
                        placeholder='Aling Nena Store'
                        className='col-span-4'
                        autoComplete='off'
                        {...field}
                      />
                    </FormControl>
                    <FormMessage className='col-span-4 col-start-3' />
                  </FormItem>
                )}
              />
              <FormField
                control={form.control}
                name='market_id'
                render={({ field }) => (
                  <FormItem className='grid grid-cols-6 items-center space-y-0 gap-x-4 gap-y-1'>
                    <FormLabel className='col-span-2 text-end'>
                      Market
                    </FormLabel>
                    <SelectDropdown
                      defaultValue={field.value}
                      onValueChange={field.onChange}
                      placeholder='Select a market'
                      className='col-span-4'
                      isPending={loadingMarkets}
                      items={marketItems}
                    />
                    <FormMessage className='col-span-4 col-start-3' />
                  </FormItem>
                )}
              />
              <FormField
                control={form.control}
                name='type'
                render={({ field }) => (
                  <FormItem className='grid grid-cols-6 items-center space-y-0 gap-x-4 gap-y-1'>
                    <FormLabel className='col-span-2 text-end'>Type</FormLabel>
                    <FormControl>
                      <Input
                        placeholder='sari-sari, stall, ...'
                        className='col-span-4'
                        {...field}
                      />
                    </FormControl>
                    <FormMessage className='col-span-4 col-start-3' />
                  </FormItem>
                )}
              />
              <FormField
                control={form.control}
                name='description'
                render={({ field }) => (
                  <FormItem className='grid grid-cols-6 items-center space-y-0 gap-x-4 gap-y-1'>
                    <FormLabel className='col-span-2 text-end'>
                      Description
                    </FormLabel>
                    <FormControl>
                      <Textarea className='col-span-4' {...field} />
                    </FormControl>
                    <FormMessage className='col-span-4 col-start-3' />
                  </FormItem>
                )}
              />
              <FormField
                control={form.control}
                name='barangay'
                render={({ field }) => (
                  <FormItem className='grid grid-cols-6 items-center space-y-0 gap-x-4 gap-y-1'>
                    <FormLabel className='col-span-2 text-end'>
                      Barangay
                    </FormLabel>
                    <FormControl>
                      <Input className='col-span-4' {...field} />
                    </FormControl>
                    <FormMessage className='col-span-4 col-start-3' />
                  </FormItem>
                )}
              />
              <FormField
                control={form.control}
                name='municipality'
                render={({ field }) => (
                  <FormItem className='grid grid-cols-6 items-center space-y-0 gap-x-4 gap-y-1'>
                    <FormLabel className='col-span-2 text-end'>
                      Municipality
                    </FormLabel>
                    <FormControl>
                      <Input className='col-span-4' {...field} />
                    </FormControl>
                    <FormMessage className='col-span-4 col-start-3' />
                  </FormItem>
                )}
              />
              <FormField
                control={form.control}
                name='province'
                render={({ field }) => (
                  <FormItem className='grid grid-cols-6 items-center space-y-0 gap-x-4 gap-y-1'>
                    <FormLabel className='col-span-2 text-end'>
                      Province
                    </FormLabel>
                    <FormControl>
                      <Input className='col-span-4' {...field} />
                    </FormControl>
                    <FormMessage className='col-span-4 col-start-3' />
                  </FormItem>
                )}
              />
              <FormField
                control={form.control}
                name='region'
                render={({ field }) => (
                  <FormItem className='grid grid-cols-6 items-center space-y-0 gap-x-4 gap-y-1'>
                    <FormLabel className='col-span-2 text-end'>Region</FormLabel>
                    <FormControl>
                      <Input className='col-span-4' {...field} />
                    </FormControl>
                    <FormMessage className='col-span-4 col-start-3' />
                  </FormItem>
                )}
              />
              <FormField
                control={form.control}
                name='contact_number'
                render={({ field }) => (
                  <FormItem className='grid grid-cols-6 items-center space-y-0 gap-x-4 gap-y-1'>
                    <FormLabel className='col-span-2 text-end'>
                      Contact #
                    </FormLabel>
                    <FormControl>
                      <Input className='col-span-4' {...field} />
                    </FormControl>
                    <FormMessage className='col-span-4 col-start-3' />
                  </FormItem>
                )}
              />
              <FormField
                control={form.control}
                name='gcash_number'
                render={({ field }) => (
                  <FormItem className='grid grid-cols-6 items-center space-y-0 gap-x-4 gap-y-1'>
                    <FormLabel className='col-span-2 text-end'>
                      GCash #
                    </FormLabel>
                    <FormControl>
                      <Input className='col-span-4' {...field} />
                    </FormControl>
                    <FormMessage className='col-span-4 col-start-3' />
                  </FormItem>
                )}
              />
              <FormField
                control={form.control}
                name='is_active'
                render={({ field }) => (
                  <FormItem className='grid grid-cols-6 items-center space-y-0 gap-x-4 gap-y-1'>
                    <FormLabel className='col-span-2 text-end'>
                      Active
                    </FormLabel>
                    <FormControl>
                      <Switch
                        checked={field.value}
                        onCheckedChange={field.onChange}
                        className='col-span-4 justify-self-start'
                      />
                    </FormControl>
                    <FormMessage className='col-span-4 col-start-3' />
                  </FormItem>
                )}
              />
              <FormField
                control={form.control}
                name='is_verified'
                render={({ field }) => (
                  <FormItem className='grid grid-cols-6 items-center space-y-0 gap-x-4 gap-y-1'>
                    <FormLabel className='col-span-2 text-end'>
                      Verified
                    </FormLabel>
                    <FormControl>
                      <Switch
                        checked={field.value}
                        onCheckedChange={field.onChange}
                        className='col-span-4 justify-self-start'
                      />
                    </FormControl>
                    <FormMessage className='col-span-4 col-start-3' />
                  </FormItem>
                )}
              />
            </form>
          </Form>
        </div>
        <DialogFooter>
          <Button type='submit' form='tindahan-form' disabled={isPending}>
            Save changes
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  )
}
