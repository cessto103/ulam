'use client'

import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { toast } from 'sonner'
import { z } from 'zod'
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
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import { Textarea } from '@/components/ui/textarea'
import { type Invoice } from '../data/schema'
import {
  useCreateInvoice,
  useSponsoredAdOptions,
  useUpdateInvoice,
  type SponsoredAdOption,
} from '../hooks/use-invoices'

const formSchema = z.object({
  sponsored_ad_id: z.string().optional(),
  buyer_name: z.string().min(1, 'Buyer name is required.'),
  buyer_contact_name: z.string().optional(),
  buyer_email: z.string().optional(),
  buyer_address: z.string().optional(),
  description: z.string().min(1, 'Description is required.'),
  amount: z
    .string()
    .min(1, 'Amount is required.')
    .refine((v) => !Number.isNaN(Number(v)) && Number(v) >= 0, 'Must be a number.'),
  notes: z.string().optional(),
})
type InvoiceForm = z.infer<typeof formSchema>

type InvoiceActionDialogProps = {
  currentRow?: Invoice
  open: boolean
  onOpenChange: (open: boolean) => void
}

export function InvoiceActionDialog({ currentRow, open, onOpenChange }: InvoiceActionDialogProps) {
  const isEdit = !!currentRow
  const { mutate: createInvoice, isPending: creating } = useCreateInvoice()
  const { mutate: updateInvoice, isPending: updating } = useUpdateInvoice()
  const { data: sponsoredAds = [] } = useSponsoredAdOptions()
  const isPending = creating || updating

  const form = useForm<InvoiceForm>({
    resolver: zodResolver(formSchema),
    defaultValues: isEdit
      ? {
          sponsored_ad_id: currentRow.sponsored_ad_id ? String(currentRow.sponsored_ad_id) : undefined,
          buyer_name: currentRow.buyer_name,
          buyer_contact_name: currentRow.buyer_contact_name ?? '',
          buyer_email: currentRow.buyer_email ?? '',
          buyer_address: currentRow.buyer_address ?? '',
          description: currentRow.description,
          amount: currentRow.amount,
          notes: currentRow.notes ?? '',
        }
      : {
          sponsored_ad_id: undefined,
          buyer_name: '',
          buyer_contact_name: '',
          buyer_email: '',
          buyer_address: '',
          description: '',
          amount: '',
          notes: '',
        },
  })

  // One-way convenience only -- picking a Sponsored Ad prefills the fields
  // below from it, but there's no live sync afterward in either direction.
  const applySponsoredAd = (ad: SponsoredAdOption) => {
    form.setValue('buyer_name', ad.company_name, { shouldValidate: true })
    if (ad.contact_name) form.setValue('buyer_contact_name', ad.contact_name)
    if (ad.contact_email) form.setValue('buyer_email', ad.contact_email)
    form.setValue('description', `Sponsored placement: ${ad.product_name}`, { shouldValidate: true })
    form.setValue('amount', ad.amount_paid, { shouldValidate: true })
  }

  const onSubmit = (values: InvoiceForm) => {
    const onSuccess = () => {
      form.reset()
      onOpenChange(false)
      toast.success(isEdit ? 'Invoice updated.' : 'Draft invoice created.')
    }
    const onError = (error: any) => {
      toast.error(error?.response?.data?.message ?? 'Could not save invoice.')
    }

    const payload = {
      sponsored_ad_id: values.sponsored_ad_id ? Number(values.sponsored_ad_id) : null,
      buyer_name: values.buyer_name,
      buyer_contact_name: values.buyer_contact_name?.trim() || null,
      buyer_email: values.buyer_email?.trim() || null,
      buyer_address: values.buyer_address?.trim() || null,
      description: values.description,
      amount: Number(values.amount),
      notes: values.notes?.trim() || null,
    }

    if (isEdit) {
      updateInvoice({ id: currentRow.id, ...payload }, { onSuccess, onError })
    } else {
      createInvoice(payload, { onSuccess, onError })
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
      <DialogContent className='sm:max-w-2xl'>
        <DialogHeader className='text-start'>
          <DialogTitle>{isEdit ? 'Edit Draft Invoice' : 'New Invoice'}</DialogTitle>
          <DialogDescription>
            {isEdit
              ? 'Only drafts can be edited — this stays fully editable until marked as paid.'
              : 'Starts as an editable draft with no official number yet.'}
          </DialogDescription>
        </DialogHeader>
        <Form {...form}>
          <form id='invoice-form' onSubmit={form.handleSubmit(onSubmit)} className='space-y-4'>
            {!isEdit && sponsoredAds.length > 0 && (
              <FormItem className='grid grid-cols-6 items-center space-y-0 gap-x-4 gap-y-1'>
                <FormLabel className='col-span-2 text-end'>Link to Sponsored Ad</FormLabel>
                <div className='col-span-4'>
                  <Select
                    onValueChange={(v) => {
                      form.setValue('sponsored_ad_id', v)
                      const ad = sponsoredAds.find((a) => String(a.id) === v)
                      if (ad) applySponsoredAd(ad)
                    }}
                  >
                    <SelectTrigger className='w-full'>
                      <SelectValue placeholder='Optional — prefills the fields below' />
                    </SelectTrigger>
                    <SelectContent>
                      {sponsoredAds.map((ad) => (
                        <SelectItem key={ad.id} value={String(ad.id)}>
                          {ad.product_name} — {ad.company_name}
                        </SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                </div>
              </FormItem>
            )}
            <FormField
              control={form.control}
              name='buyer_name'
              render={({ field }) => (
                <FormItem className='grid grid-cols-6 items-center space-y-0 gap-x-4 gap-y-1'>
                  <FormLabel className='col-span-2 text-end'>Buyer / company</FormLabel>
                  <FormControl>
                    <Input placeholder='Lucky Me' className='col-span-4' autoComplete='off' {...field} />
                  </FormControl>
                  <FormMessage className='col-span-4 col-start-3' />
                </FormItem>
              )}
            />
            <FormField
              control={form.control}
              name='buyer_contact_name'
              render={({ field }) => (
                <FormItem className='grid grid-cols-6 items-center space-y-0 gap-x-4 gap-y-1'>
                  <FormLabel className='col-span-2 text-end'>Contact person</FormLabel>
                  <FormControl>
                    <Input placeholder='Optional' className='col-span-4' {...field} />
                  </FormControl>
                  <FormMessage className='col-span-4 col-start-3' />
                </FormItem>
              )}
            />
            <FormField
              control={form.control}
              name='buyer_email'
              render={({ field }) => (
                <FormItem className='grid grid-cols-6 items-center space-y-0 gap-x-4 gap-y-1'>
                  <FormLabel className='col-span-2 text-end'>Buyer email</FormLabel>
                  <FormControl>
                    <Input type='email' placeholder='Where to send it' className='col-span-4' {...field} />
                  </FormControl>
                  <FormMessage className='col-span-4 col-start-3' />
                </FormItem>
              )}
            />
            <FormField
              control={form.control}
              name='buyer_address'
              render={({ field }) => (
                <FormItem className='grid grid-cols-6 items-center space-y-0 gap-x-4 gap-y-1'>
                  <FormLabel className='col-span-2 text-end'>Buyer address</FormLabel>
                  <FormControl>
                    <Input placeholder='Optional' className='col-span-4' {...field} />
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
                  <FormLabel className='col-span-2 text-end'>Description</FormLabel>
                  <FormControl>
                    <Textarea placeholder='What this invoice is for' className='col-span-4' {...field} />
                  </FormControl>
                  <FormMessage className='col-span-4 col-start-3' />
                </FormItem>
              )}
            />
            <FormField
              control={form.control}
              name='amount'
              render={({ field }) => (
                <FormItem className='grid grid-cols-6 items-center space-y-0 gap-x-4 gap-y-1'>
                  <FormLabel className='col-span-2 text-end'>Amount (₱)</FormLabel>
                  <FormControl>
                    <Input placeholder='5000.00' inputMode='decimal' className='col-span-4' {...field} />
                  </FormControl>
                  <FormMessage className='col-span-4 col-start-3' />
                </FormItem>
              )}
            />
            <FormField
              control={form.control}
              name='notes'
              render={({ field }) => (
                <FormItem className='grid grid-cols-6 items-center space-y-0 gap-x-4 gap-y-1'>
                  <FormLabel className='col-span-2 text-end'>Internal notes</FormLabel>
                  <FormControl>
                    <Textarea placeholder='Never shown on the invoice itself' className='col-span-4' rows={3} {...field} />
                  </FormControl>
                  <FormMessage className='col-span-4 col-start-3' />
                </FormItem>
              )}
            />
          </form>
        </Form>
        <DialogFooter>
          <Button type='submit' form='invoice-form' disabled={isPending}>
            Save draft
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  )
}
