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
import { categories } from '../data/data'
import { type CommunityPriceReport } from '../data/schema'
import {
  useCreateCommunityPriceReport,
  useUpdateCommunityPriceReport,
} from '../hooks/use-community-price-reports'

const formSchema = z.object({
  item_name: z.string().min(1, 'Item name is required.'),
  category: z.string().optional(),
  reported_price: z
    .string()
    .min(1, 'Reported price is required.')
    .refine((v) => !Number.isNaN(Number(v)), 'Must be a number.'),
  unit: z.string().min(1, 'Unit is required.'),
  tindahan_id: z.string().optional(),
  market_id: z.string().optional(),
  barangay: z.string().optional(),
  municipality: z.string().optional(),
  province: z.string().optional(),
  is_verified: z.boolean(),
})
type CommunityPriceReportForm = z.infer<typeof formSchema>

type CommunityPriceReportActionDialogProps = {
  currentRow?: CommunityPriceReport
  open: boolean
  onOpenChange: (open: boolean) => void
}

export function CommunityPriceReportsActionDialog({
  currentRow,
  open,
  onOpenChange,
}: CommunityPriceReportActionDialogProps) {
  const isEdit = !!currentRow
  const { mutate: createReport, isPending: creating } =
    useCreateCommunityPriceReport()
  const { mutate: updateReport, isPending: updating } =
    useUpdateCommunityPriceReport()
  const isPending = creating || updating

  const form = useForm<CommunityPriceReportForm>({
    resolver: zodResolver(formSchema),
    defaultValues: isEdit
      ? {
          item_name: currentRow.item_name,
          category: currentRow.category ?? undefined,
          reported_price: currentRow.reported_price,
          unit: currentRow.unit,
          tindahan_id: currentRow.tindahan_id?.toString() ?? '',
          market_id: currentRow.market_id?.toString() ?? '',
          barangay: currentRow.barangay ?? '',
          municipality: currentRow.municipality ?? '',
          province: currentRow.province ?? '',
          is_verified: currentRow.is_verified,
        }
      : {
          item_name: '',
          category: undefined,
          reported_price: '',
          unit: '',
          tindahan_id: '',
          market_id: '',
          barangay: '',
          municipality: '',
          province: '',
          is_verified: false,
        },
  })

  const onSubmit = (values: CommunityPriceReportForm) => {
    const onSuccess = () => {
      form.reset()
      onOpenChange(false)
      toast.success(isEdit ? 'Report updated.' : 'Report created.')
    }
    const onError = (error: any) => {
      toast.error(error?.response?.data?.message ?? 'Could not save report.')
    }

    const payload = {
      item_name: values.item_name,
      category: values.category || null,
      reported_price: Number(values.reported_price),
      unit: values.unit,
      tindahan_id: values.tindahan_id ? Number(values.tindahan_id) : null,
      market_id: values.market_id ? Number(values.market_id) : null,
      barangay: values.barangay || null,
      municipality: values.municipality || null,
      province: values.province || null,
      is_verified: values.is_verified,
    }

    if (isEdit) {
      updateReport({ id: currentRow.id, ...payload }, { onSuccess, onError })
    } else {
      createReport(payload, { onSuccess, onError })
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
          <DialogTitle>
            {isEdit ? 'Edit Price Report' : 'Add Price Report'}
          </DialogTitle>
          <DialogDescription>
            {isEdit ? 'Update the price report here. ' : 'Create a new price report here. '}
            Click save when you&apos;re done.
          </DialogDescription>
        </DialogHeader>
        <div className='w-[calc(100%+0.75rem)] overflow-y-auto py-1 pe-3'>
          <Form {...form}>
            <form
              id='community-price-report-form'
              onSubmit={form.handleSubmit(onSubmit)}
              className='space-y-4 px-0.5'
            >
              <FormField
                control={form.control}
                name='item_name'
                render={({ field }) => (
                  <FormItem className='grid grid-cols-6 items-center space-y-0 gap-x-4 gap-y-1'>
                    <FormLabel className='col-span-2 text-end'>
                      Item name
                    </FormLabel>
                    <FormControl>
                      <Input
                        placeholder='Bigas'
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
                    <SelectDropdown
                      defaultValue={field.value}
                      onValueChange={field.onChange}
                      placeholder='Select a category'
                      className='col-span-4'
                      items={categories.map(({ label, value }) => ({
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
                name='reported_price'
                render={({ field }) => (
                  <FormItem className='grid grid-cols-6 items-center space-y-0 gap-x-4 gap-y-1'>
                    <FormLabel className='col-span-2 text-end'>
                      Reported price
                    </FormLabel>
                    <FormControl>
                      <Input
                        placeholder='55.00'
                        inputMode='decimal'
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
                        placeholder='kg'
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
                name='tindahan_id'
                render={({ field }) => (
                  <FormItem className='grid grid-cols-6 items-center space-y-0 gap-x-4 gap-y-1'>
                    <FormLabel className='col-span-2 text-end'>
                      Tindahan ID
                    </FormLabel>
                    <FormControl>
                      <Input
                        placeholder='Optional'
                        inputMode='numeric'
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
                      Market ID
                    </FormLabel>
                    <FormControl>
                      <Input
                        placeholder='Optional'
                        inputMode='numeric'
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
          <Button
            type='submit'
            form='community-price-report-form'
            disabled={isPending}
          >
            Save changes
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  )
}
