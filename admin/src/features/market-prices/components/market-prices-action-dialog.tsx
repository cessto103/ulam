'use client'

import { z } from 'zod'
import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { toast } from 'sonner'
import { useMarketOptionsQuery } from '@/features/markets/hooks/use-markets'
import { useTindahanOptionsQuery } from '@/features/tindahan/hooks/use-tindahan'
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
import { SelectDropdown } from '@/components/select-dropdown'
import { type MarketPrice } from '../data/schema'
import {
  useCreateMarketPrice,
  useUpdateMarketPrice,
} from '../hooks/use-market-prices'

const NONE = 'none'

const formSchema = z.object({
  market_id: z.string().optional(),
  tindahan_id: z.string().optional(),
  item_name: z.string().min(1, 'Item name is required.'),
  category: z.string().optional(),
  price_per_unit: z
    .string()
    .min(1, 'Price is required.')
    .refine((v) => !Number.isNaN(Number(v)) && Number(v) >= 0, {
      message: 'Enter a valid non-negative price.',
    }),
  unit: z.string().min(1, 'Unit is required.'),
  is_available: z.boolean(),
})
type MarketPriceForm = z.infer<typeof formSchema>

type MarketPriceActionDialogProps = {
  currentRow?: MarketPrice
  open: boolean
  onOpenChange: (open: boolean) => void
}

export function MarketPricesActionDialog({
  currentRow,
  open,
  onOpenChange,
}: MarketPriceActionDialogProps) {
  const isEdit = !!currentRow
  const { data: marketOptions, isPending: loadingMarkets } =
    useMarketOptionsQuery()
  const { data: tindahanOptions, isPending: loadingTindahan } =
    useTindahanOptionsQuery()
  const { mutate: createMarketPrice, isPending: creating } =
    useCreateMarketPrice()
  const { mutate: updateMarketPrice, isPending: updating } =
    useUpdateMarketPrice()
  const isPending = creating || updating

  const form = useForm<MarketPriceForm>({
    resolver: zodResolver(formSchema),
    defaultValues: isEdit
      ? {
          market_id: currentRow.market_id
            ? String(currentRow.market_id)
            : NONE,
          tindahan_id: currentRow.tindahan_id
            ? String(currentRow.tindahan_id)
            : NONE,
          item_name: currentRow.item_name,
          category: currentRow.category ?? '',
          price_per_unit: currentRow.price_per_unit,
          unit: currentRow.unit,
          is_available: currentRow.is_available,
        }
      : {
          market_id: NONE,
          tindahan_id: NONE,
          item_name: '',
          category: '',
          price_per_unit: '',
          unit: '',
          is_available: true,
        },
  })

  const onSubmit = (values: MarketPriceForm) => {
    const onSuccess = () => {
      form.reset()
      onOpenChange(false)
      toast.success(isEdit ? 'Price updated.' : 'Price created.')
    }
    const onError = (error: any) => {
      toast.error(error?.response?.data?.message ?? 'Could not save price.')
    }

    const payload = {
      market_id:
        !values.market_id || values.market_id === NONE
          ? null
          : Number(values.market_id),
      tindahan_id:
        !values.tindahan_id || values.tindahan_id === NONE
          ? null
          : Number(values.tindahan_id),
      item_name: values.item_name,
      category: values.category || null,
      price_per_unit: Number(values.price_per_unit),
      unit: values.unit,
      is_available: values.is_available,
    }

    if (isEdit) {
      updateMarketPrice(
        { id: currentRow.id, ...payload },
        { onSuccess, onError }
      )
    } else {
      createMarketPrice(payload, { onSuccess, onError })
    }
  }

  const marketItems = [
    { label: 'No market', value: NONE },
    ...(marketOptions ?? []).map((m) => ({ label: m.name, value: String(m.id) })),
  ]

  const tindahanItems = [
    { label: 'No tindahan', value: NONE },
    ...(tindahanOptions ?? []).map((t) => ({
      label: t.name,
      value: String(t.id),
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
            {isEdit ? 'Edit Market Price' : 'Add New Market Price'}
          </DialogTitle>
          <DialogDescription>
            {isEdit ? 'Update the price here. ' : 'Create a new price entry here. '}
            Click save when you&apos;re done.
          </DialogDescription>
        </DialogHeader>
        <div className='w-[calc(100%+0.75rem)] overflow-y-auto py-1 pe-3'>
          <Form {...form}>
            <form
              id='market-price-form'
              onSubmit={form.handleSubmit(onSubmit)}
              className='space-y-4 px-0.5'
            >
              <FormField
                control={form.control}
                name='item_name'
                render={({ field }) => (
                  <FormItem className='grid grid-cols-6 items-center space-y-0 gap-x-4 gap-y-1'>
                    <FormLabel className='col-span-2 text-end'>Item</FormLabel>
                    <FormControl>
                      <Input
                        placeholder='Bangus'
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
                name='category'
                render={({ field }) => (
                  <FormItem className='grid grid-cols-6 items-center space-y-0 gap-x-4 gap-y-1'>
                    <FormLabel className='col-span-2 text-end'>
                      Category
                    </FormLabel>
                    <FormControl>
                      <Input
                        placeholder='isda, karne, gulay, ...'
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
                name='price_per_unit'
                render={({ field }) => (
                  <FormItem className='grid grid-cols-6 items-center space-y-0 gap-x-4 gap-y-1'>
                    <FormLabel className='col-span-2 text-end'>
                      Price
                    </FormLabel>
                    <FormControl>
                      <Input
                        inputMode='decimal'
                        placeholder='180.00'
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
                name='unit'
                render={({ field }) => (
                  <FormItem className='grid grid-cols-6 items-center space-y-0 gap-x-4 gap-y-1'>
                    <FormLabel className='col-span-2 text-end'>Unit</FormLabel>
                    <FormControl>
                      <Input
                        placeholder='kg, piece, bundle, ...'
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
                name='tindahan_id'
                render={({ field }) => (
                  <FormItem className='grid grid-cols-6 items-center space-y-0 gap-x-4 gap-y-1'>
                    <FormLabel className='col-span-2 text-end'>
                      Tindahan
                    </FormLabel>
                    <SelectDropdown
                      defaultValue={field.value}
                      onValueChange={field.onChange}
                      placeholder='Select a tindahan'
                      className='col-span-4'
                      isPending={loadingTindahan}
                      items={tindahanItems}
                    />
                    <FormMessage className='col-span-4 col-start-3' />
                  </FormItem>
                )}
              />
              <FormField
                control={form.control}
                name='is_available'
                render={({ field }) => (
                  <FormItem className='grid grid-cols-6 items-center space-y-0 gap-x-4 gap-y-1'>
                    <FormLabel className='col-span-2 text-end'>
                      Available
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
          <Button type='submit' form='market-price-form' disabled={isPending}>
            Save changes
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  )
}
