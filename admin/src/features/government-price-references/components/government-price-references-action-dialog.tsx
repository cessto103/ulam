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
import { Textarea } from '@/components/ui/textarea'
import { SelectDropdown } from '@/components/select-dropdown'
import { sources } from '../data/data'
import { type GovernmentPriceReference } from '../data/schema'
import {
  useCreateGovernmentPriceReference,
  useUpdateGovernmentPriceReference,
} from '../hooks/use-government-price-references'

const formSchema = z.object({
  source: z.string().min(1, 'Source is required.'),
  item_name: z.string().min(1, 'Item name is required.'),
  category: z.string().optional(),
  price_min: z
    .string()
    .min(1, 'Minimum price is required.')
    .refine((v) => !Number.isNaN(Number(v)), 'Must be a number.'),
  price_max: z
    .string()
    .min(1, 'Maximum price is required.')
    .refine((v) => !Number.isNaN(Number(v)), 'Must be a number.'),
  unit: z.string().min(1, 'Unit is required.'),
  region: z.string().optional(),
  bulletin_date: z.string().optional(),
  source_note: z.string().optional(),
})
type GovernmentPriceReferenceForm = z.infer<typeof formSchema>

type GovernmentPriceReferenceActionDialogProps = {
  currentRow?: GovernmentPriceReference
  open: boolean
  onOpenChange: (open: boolean) => void
}

export function GovernmentPriceReferencesActionDialog({
  currentRow,
  open,
  onOpenChange,
}: GovernmentPriceReferenceActionDialogProps) {
  const isEdit = !!currentRow
  const { mutate: createReference, isPending: creating } =
    useCreateGovernmentPriceReference()
  const { mutate: updateReference, isPending: updating } =
    useUpdateGovernmentPriceReference()
  const isPending = creating || updating

  const form = useForm<GovernmentPriceReferenceForm>({
    resolver: zodResolver(formSchema),
    defaultValues: isEdit
      ? {
          source: currentRow.source,
          item_name: currentRow.item_name,
          category: currentRow.category ?? '',
          price_min: currentRow.price_min,
          price_max: currentRow.price_max,
          unit: currentRow.unit,
          region: currentRow.region ?? '',
          bulletin_date: currentRow.bulletin_date ?? '',
          source_note: currentRow.source_note ?? '',
        }
      : {
          source: '',
          item_name: '',
          category: '',
          price_min: '',
          price_max: '',
          unit: '',
          region: '',
          bulletin_date: '',
          source_note: '',
        },
  })

  const onSubmit = (values: GovernmentPriceReferenceForm) => {
    const onSuccess = () => {
      form.reset()
      onOpenChange(false)
      toast.success(isEdit ? 'Reference updated.' : 'Reference created.')
    }
    const onError = (error: any) => {
      toast.error(
        error?.response?.data?.message ?? 'Could not save reference.'
      )
    }

    const payload = {
      source: values.source,
      item_name: values.item_name,
      category: values.category || null,
      price_min: Number(values.price_min),
      price_max: Number(values.price_max),
      unit: values.unit,
      region: values.region || null,
      bulletin_date: values.bulletin_date || null,
      source_note: values.source_note || null,
    }

    if (isEdit) {
      updateReference(
        { id: currentRow.id, ...payload },
        { onSuccess, onError }
      )
    } else {
      createReference(payload, { onSuccess, onError })
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
            {isEdit ? 'Edit Price Reference' : 'Add Price Reference'}
          </DialogTitle>
          <DialogDescription>
            {isEdit
              ? 'Update the government price reference here. '
              : 'Create a new government price reference here. '}
            Click save when you&apos;re done.
          </DialogDescription>
        </DialogHeader>
        <div className='w-[calc(100%+0.75rem)] overflow-y-auto py-1 pe-3'>
          <Form {...form}>
            <form
              id='government-price-reference-form'
              onSubmit={form.handleSubmit(onSubmit)}
              className='space-y-4 px-0.5'
            >
              <FormField
                control={form.control}
                name='source'
                render={({ field }) => (
                  <FormItem className='grid grid-cols-6 items-center space-y-0 gap-x-4 gap-y-1'>
                    <FormLabel className='col-span-2 text-end'>
                      Source
                    </FormLabel>
                    <SelectDropdown
                      defaultValue={field.value}
                      onValueChange={field.onChange}
                      placeholder='Select a source'
                      className='col-span-4'
                      items={sources.map(({ label, value }) => ({
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
                    <FormControl>
                      <Input
                        placeholder='Optional'
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
                name='price_min'
                render={({ field }) => (
                  <FormItem className='grid grid-cols-6 items-center space-y-0 gap-x-4 gap-y-1'>
                    <FormLabel className='col-span-2 text-end'>
                      Min price
                    </FormLabel>
                    <FormControl>
                      <Input
                        placeholder='40.00'
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
                name='price_max'
                render={({ field }) => (
                  <FormItem className='grid grid-cols-6 items-center space-y-0 gap-x-4 gap-y-1'>
                    <FormLabel className='col-span-2 text-end'>
                      Max price
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
                name='region'
                render={({ field }) => (
                  <FormItem className='grid grid-cols-6 items-center space-y-0 gap-x-4 gap-y-1'>
                    <FormLabel className='col-span-2 text-end'>
                      Region
                    </FormLabel>
                    <FormControl>
                      <Input
                        placeholder='Optional'
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
                name='bulletin_date'
                render={({ field }) => (
                  <FormItem className='grid grid-cols-6 items-center space-y-0 gap-x-4 gap-y-1'>
                    <FormLabel className='col-span-2 text-end'>
                      Bulletin date
                    </FormLabel>
                    <FormControl>
                      <Input type='date' className='col-span-4' {...field} />
                    </FormControl>
                    <FormMessage className='col-span-4 col-start-3' />
                  </FormItem>
                )}
              />
              <FormField
                control={form.control}
                name='source_note'
                render={({ field }) => (
                  <FormItem className='grid grid-cols-6 items-center space-y-0 gap-x-4 gap-y-1'>
                    <FormLabel className='col-span-2 text-end'>
                      Source note
                    </FormLabel>
                    <FormControl>
                      <Textarea
                        placeholder='Optional'
                        className='col-span-4'
                        {...field}
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
            form='government-price-reference-form'
            disabled={isPending}
          >
            Save changes
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  )
}
