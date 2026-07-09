'use client'

import { z } from 'zod'
import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { toast } from 'sonner'
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
import { marketTypeOptions } from '../data/data'
import { type Market } from '../data/schema'
import { useCreateMarket, useUpdateMarket } from '../hooks/use-markets'

const formSchema = z.object({
  name: z.string().min(1, 'Name is required.'),
  type: z.string().min(1, 'Type is required.'),
  barangay: z.string().optional(),
  municipality: z.string().optional(),
  province: z.string().optional(),
  region: z.string().optional(),
  latitude: z.string().optional(),
  longitude: z.string().optional(),
  is_active: z.boolean(),
})
type MarketForm = z.infer<typeof formSchema>

type MarketActionDialogProps = {
  currentRow?: Market
  open: boolean
  onOpenChange: (open: boolean) => void
}

export function MarketsActionDialog({
  currentRow,
  open,
  onOpenChange,
}: MarketActionDialogProps) {
  const isEdit = !!currentRow
  const { mutate: createMarket, isPending: creating } = useCreateMarket()
  const { mutate: updateMarket, isPending: updating } = useUpdateMarket()
  const isPending = creating || updating

  const form = useForm<MarketForm>({
    resolver: zodResolver(formSchema),
    defaultValues: isEdit
      ? {
          name: currentRow.name,
          type: currentRow.type,
          barangay: currentRow.barangay ?? '',
          municipality: currentRow.municipality ?? '',
          province: currentRow.province ?? '',
          region: currentRow.region ?? '',
          latitude: currentRow.latitude?.toString() ?? '',
          longitude: currentRow.longitude?.toString() ?? '',
          is_active: currentRow.is_active,
        }
      : {
          name: '',
          type: 'wet_market',
          barangay: '',
          municipality: '',
          province: '',
          region: '',
          latitude: '',
          longitude: '',
          is_active: true,
        },
  })

  const onSubmit = (values: MarketForm) => {
    const onSuccess = () => {
      form.reset()
      onOpenChange(false)
      toast.success(isEdit ? 'Market updated.' : 'Market created.')
    }
    const onError = (error: any) => {
      toast.error(error?.response?.data?.message ?? 'Could not save market.')
    }

    const payload = {
      name: values.name,
      type: values.type,
      barangay: values.barangay || null,
      municipality: values.municipality || null,
      province: values.province || null,
      region: values.region || null,
      latitude: values.latitude ? Number(values.latitude) : null,
      longitude: values.longitude ? Number(values.longitude) : null,
      is_active: values.is_active,
    }

    if (isEdit) {
      updateMarket({ id: currentRow.id, ...payload }, { onSuccess, onError })
    } else {
      createMarket(payload, { onSuccess, onError })
    }
  }

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
          <DialogTitle>{isEdit ? 'Edit Market' : 'Add New Market'}</DialogTitle>
          <DialogDescription>
            {isEdit ? 'Update the market here. ' : 'Create new market here. '}
            Click save when you&apos;re done.
          </DialogDescription>
        </DialogHeader>
        <div className='w-[calc(100%+0.75rem)] overflow-y-auto py-1 pe-3'>
          <Form {...form}>
            <form
              id='market-form'
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
                        placeholder='Divisoria Public Market'
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
                name='type'
                render={({ field }) => (
                  <FormItem className='grid grid-cols-6 items-center space-y-0 gap-x-4 gap-y-1'>
                    <FormLabel className='col-span-2 text-end'>Type</FormLabel>
                    <SelectDropdown
                      defaultValue={field.value}
                      onValueChange={field.onChange}
                      placeholder='Select a type'
                      className='col-span-4'
                      items={marketTypeOptions.map(({ label, value }) => ({
                        label,
                        value,
                      }))}
                    />
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
                name='latitude'
                render={({ field }) => (
                  <FormItem className='grid grid-cols-6 items-center space-y-0 gap-x-4 gap-y-1'>
                    <FormLabel className='col-span-2 text-end'>
                      Latitude
                    </FormLabel>
                    <FormControl>
                      <Input
                        className='col-span-4'
                        inputMode='decimal'
                        placeholder='14.5995'
                        {...field}
                      />
                    </FormControl>
                    <FormMessage className='col-span-4 col-start-3' />
                  </FormItem>
                )}
              />
              <FormField
                control={form.control}
                name='longitude'
                render={({ field }) => (
                  <FormItem className='grid grid-cols-6 items-center space-y-0 gap-x-4 gap-y-1'>
                    <FormLabel className='col-span-2 text-end'>
                      Longitude
                    </FormLabel>
                    <FormControl>
                      <Input
                        className='col-span-4'
                        inputMode='decimal'
                        placeholder='120.9842'
                        {...field}
                      />
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
            </form>
          </Form>
        </div>
        <DialogFooter>
          <Button type='submit' form='market-form' disabled={isPending}>
            Save changes
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  )
}
