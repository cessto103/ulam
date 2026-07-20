'use client'

import { useRef } from 'react'
import { type UseFormReturn, useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { addDays, format } from 'date-fns'
import { ImageUp, Loader2 } from 'lucide-react'
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
import { Switch } from '@/components/ui/switch'
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs'
import { Textarea } from '@/components/ui/textarea'
import { type SponsoredAd } from '../data/schema'
import {
  useCreateSponsoredAd,
  useUpdateSponsoredAd,
  useUploadSponsoredAdImage,
} from '../hooks/use-sponsored-ads'

const API_ORIGIN = (import.meta.env.VITE_API_URL as string | undefined)?.replace(/\/api\/?$/, '') ?? ''

const QUICK_PICK_DAYS = [3, 7, 14, 30]

/** Parses a plain YYYY-MM-DD string as a local-time date, avoiding the
 * UTC-midnight parsing that `new Date('YYYY-MM-DD')` does (which can land on
 * the wrong calendar day once reformatted, depending on the browser's
 * timezone relative to UTC). */
function parseDateOnly(value: string): Date {
  const [y, m, d] = value.split('-').map(Number)
  return new Date(y, (m || 1) - 1, d || 1)
}

const formSchema = z.object({
  product_name: z.string().min(1, 'Product name is required.'),
  company_name: z.string().min(1, 'Company name is required.'),
  tagline: z.string().optional(),
  description: z.string().optional(),
  image_url: z.string().optional(),
  link_url: z.string().optional(),
  cta_label: z.string().optional(),
  start_date: z.string().min(1, 'Start date is required.'),
  end_date: z.string().min(1, 'End date is required.'),
  is_enabled: z.boolean(),
  show_to_free: z.boolean(),
  show_to_premium: z.boolean(),
  show_in_recipe_feed: z.boolean(),
  show_in_community_feed: z.boolean(),
  amount_paid: z
    .string()
    .min(1, 'Amount paid is required.')
    .refine((v) => !Number.isNaN(Number(v)), 'Must be a number.'),
  payment_received_at: z.string().optional(),
  contact_name: z.string().optional(),
  contact_email: z.string().optional(),
  notes: z.string().optional(),
})
type SponsoredAdForm = z.infer<typeof formSchema>

function ImageUploadField({ form }: { form: UseFormReturn<SponsoredAdForm> }) {
  const fileRef = useRef<HTMLInputElement>(null)
  const { mutate: uploadImage, isPending: uploading } = useUploadSponsoredAdImage()
  const imageUrl = form.watch('image_url')

  return (
    <FormItem className='grid grid-cols-6 items-center space-y-0 gap-x-4 gap-y-1'>
      <FormLabel className='col-span-2 text-end'>Product Photo</FormLabel>
      <div className='col-span-4 flex items-center gap-3'>
        {imageUrl ? (
          <img
            src={`${API_ORIGIN}${imageUrl}`}
            alt=''
            className='h-16 w-16 rounded-md border object-cover'
          />
        ) : null}
        <input
          ref={fileRef}
          type='file'
          accept='image/png,image/jpeg,image/webp'
          className='hidden'
          onChange={(e) => {
            const f = e.target.files?.[0]
            if (f) {
              uploadImage(f, {
                onSuccess: (url) => form.setValue('image_url', url, { shouldDirty: true }),
                onError: (e: any) => toast.error(e?.response?.data?.message ?? 'Upload failed.'),
              })
            }
            if (fileRef.current) fileRef.current.value = ''
          }}
        />
        <Button type='button' variant='outline' onClick={() => fileRef.current?.click()} disabled={uploading}>
          {uploading ? <Loader2 className='animate-spin' /> : <ImageUp />}
          {imageUrl ? 'Replace' : 'Upload'}
        </Button>
      </div>
    </FormItem>
  )
}

type SponsoredAdsActionDialogProps = {
  currentRow?: SponsoredAd
  open: boolean
  onOpenChange: (open: boolean) => void
}

export function SponsoredAdsActionDialog({
  currentRow,
  open,
  onOpenChange,
}: SponsoredAdsActionDialogProps) {
  const isEdit = !!currentRow
  const { mutate: createAd, isPending: creating } = useCreateSponsoredAd()
  const { mutate: updateAd, isPending: updating } = useUpdateSponsoredAd()
  const isPending = creating || updating

  const form = useForm<SponsoredAdForm>({
    resolver: zodResolver(formSchema),
    defaultValues: isEdit
      ? {
          product_name: currentRow.product_name,
          company_name: currentRow.company_name,
          tagline: currentRow.tagline ?? '',
          description: currentRow.description ?? '',
          image_url: currentRow.image_url ?? '',
          link_url: currentRow.link_url ?? '',
          cta_label: currentRow.cta_label ?? '',
          start_date: currentRow.start_date.slice(0, 10),
          end_date: currentRow.end_date.slice(0, 10),
          is_enabled: currentRow.is_enabled,
          show_to_free: currentRow.show_to_free,
          show_to_premium: currentRow.show_to_premium,
          show_in_recipe_feed: currentRow.show_in_recipe_feed,
          show_in_community_feed: currentRow.show_in_community_feed,
          amount_paid: currentRow.amount_paid,
          payment_received_at: currentRow.payment_received_at?.slice(0, 10) ?? '',
          contact_name: currentRow.contact_name ?? '',
          contact_email: currentRow.contact_email ?? '',
          notes: currentRow.notes ?? '',
        }
      : {
          product_name: '',
          company_name: '',
          tagline: '',
          description: '',
          image_url: '',
          link_url: '',
          cta_label: '',
          start_date: '',
          end_date: '',
          is_enabled: false,
          show_to_free: true,
          show_to_premium: true,
          show_in_recipe_feed: true,
          show_in_community_feed: true,
          amount_paid: '',
          payment_received_at: '',
          contact_name: '',
          contact_email: '',
          notes: '',
        },
  })

  const applyQuickPick = (days: number) => {
    const start = form.getValues('start_date')
    if (!start) {
      form.setError('start_date', { message: 'Pick a start date first.' })
      return
    }
    form.setValue('end_date', format(addDays(parseDateOnly(start), days), 'yyyy-MM-dd'), {
      shouldValidate: true,
    })
  }

  const onSubmit = (values: SponsoredAdForm) => {
    const onSuccess = () => {
      form.reset()
      onOpenChange(false)
      toast.success(isEdit ? 'Sponsored ad updated.' : 'Sponsored ad created.')
    }
    const onError = (error: any) => {
      toast.error(error?.response?.data?.message ?? 'Could not save sponsored ad.')
    }

    const payload = {
      product_name: values.product_name,
      company_name: values.company_name,
      tagline: values.tagline?.trim() || null,
      description: values.description?.trim() || null,
      image_url: values.image_url?.trim() || null,
      link_url: values.link_url?.trim() || null,
      cta_label: values.cta_label?.trim() || null,
      start_date: values.start_date,
      end_date: values.end_date,
      is_enabled: values.is_enabled,
      show_to_free: values.show_to_free,
      show_to_premium: values.show_to_premium,
      show_in_recipe_feed: values.show_in_recipe_feed,
      show_in_community_feed: values.show_in_community_feed,
      amount_paid: Number(values.amount_paid),
      payment_received_at: values.payment_received_at || null,
      contact_name: values.contact_name?.trim() || null,
      contact_email: values.contact_email?.trim() || null,
      notes: values.notes?.trim() || null,
    }

    if (isEdit) {
      updateAd({ id: currentRow.id, ...payload }, { onSuccess, onError })
    } else {
      createAd(payload, { onSuccess, onError })
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
      <DialogContent className='sm:max-w-3xl'>
        <DialogHeader className='text-start'>
          <DialogTitle>{isEdit ? 'Edit Sponsored Ad' : 'Add Sponsored Ad'}</DialogTitle>
          <DialogDescription>
            {isEdit ? 'Update the sponsored ad here. ' : 'Set up a new paid placement here. '}
            Click save when you&apos;re done.
          </DialogDescription>
        </DialogHeader>
        <Tabs defaultValue='details' className='w-full'>
          <TabsList>
            <TabsTrigger value='details'>Ad Details</TabsTrigger>
            <TabsTrigger value='bookkeeping'>Bookkeeping (Admin Only)</TabsTrigger>
          </TabsList>
          <Form {...form}>
            <form id='sponsored-ad-form' onSubmit={form.handleSubmit(onSubmit)}>
              <TabsContent value='details'>
                <div className='w-[calc(100%+0.75rem)] max-h-[60vh] overflow-y-auto py-1 pe-3'>
                  <div className='space-y-4 px-0.5'>
                    <FormField
                      control={form.control}
                      name='product_name'
                      render={({ field }) => (
                        <FormItem className='grid grid-cols-6 items-center space-y-0 gap-x-4 gap-y-1'>
                          <FormLabel className='col-span-2 text-end'>Product</FormLabel>
                          <FormControl>
                            <Input placeholder='Pancit Canton' className='col-span-4' autoComplete='off' {...field} />
                          </FormControl>
                          <FormMessage className='col-span-4 col-start-3' />
                        </FormItem>
                      )}
                    />
                    <FormField
                      control={form.control}
                      name='company_name'
                      render={({ field }) => (
                        <FormItem className='grid grid-cols-6 items-center space-y-0 gap-x-4 gap-y-1'>
                          <FormLabel className='col-span-2 text-end'>Company</FormLabel>
                          <FormControl>
                            <Input placeholder='Lucky Me' className='col-span-4' autoComplete='off' {...field} />
                          </FormControl>
                          <FormMessage className='col-span-4 col-start-3' />
                        </FormItem>
                      )}
                    />
                    <FormField
                      control={form.control}
                      name='tagline'
                      render={({ field }) => (
                        <FormItem className='grid grid-cols-6 items-center space-y-0 gap-x-4 gap-y-1'>
                          <FormLabel className='col-span-2 text-end'>Tagline</FormLabel>
                          <FormControl>
                            <Input placeholder='New Spicy Beef flavor!' className='col-span-4' {...field} />
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
                            <Textarea placeholder='Longer supporting copy, shown under the tagline.' className='col-span-4' {...field} />
                          </FormControl>
                          <FormMessage className='col-span-4 col-start-3' />
                        </FormItem>
                      )}
                    />
                    <ImageUploadField form={form} />
                    <FormField
                      control={form.control}
                      name='link_url'
                      render={({ field }) => (
                        <FormItem className='grid grid-cols-6 items-center space-y-0 gap-x-4 gap-y-1'>
                          <FormLabel className='col-span-2 text-end'>Link URL</FormLabel>
                          <FormControl>
                            <Input placeholder='https://...' className='col-span-4' {...field} />
                          </FormControl>
                          <FormMessage className='col-span-4 col-start-3' />
                        </FormItem>
                      )}
                    />
                    <FormField
                      control={form.control}
                      name='cta_label'
                      render={({ field }) => (
                        <FormItem className='grid grid-cols-6 items-center space-y-0 gap-x-4 gap-y-1'>
                          <FormLabel className='col-span-2 text-end'>Button Text</FormLabel>
                          <FormControl>
                            <Input placeholder='Learn More' className='col-span-4' {...field} />
                          </FormControl>
                          <FormMessage className='col-span-4 col-start-3' />
                        </FormItem>
                      )}
                    />
                    <FormField
                      control={form.control}
                      name='start_date'
                      render={({ field }) => (
                        <FormItem className='grid grid-cols-6 items-center space-y-0 gap-x-4 gap-y-1'>
                          <FormLabel className='col-span-2 text-end'>Start date</FormLabel>
                          <FormControl>
                            <Input type='date' className='col-span-4' {...field} />
                          </FormControl>
                          <FormMessage className='col-span-4 col-start-3' />
                        </FormItem>
                      )}
                    />
                    <FormField
                      control={form.control}
                      name='end_date'
                      render={({ field }) => (
                        <FormItem className='grid grid-cols-6 items-center space-y-0 gap-x-4 gap-y-1'>
                          <FormLabel className='col-span-2 text-end'>End date</FormLabel>
                          <div className='col-span-4 space-y-1.5'>
                            <FormControl>
                              <Input type='date' {...field} />
                            </FormControl>
                            <div className='flex gap-1.5'>
                              {QUICK_PICK_DAYS.map((days) => (
                                <Button
                                  key={days}
                                  type='button'
                                  variant='outline'
                                  size='sm'
                                  className='h-6 px-2 text-xs'
                                  onClick={() => applyQuickPick(days)}
                                >
                                  +{days}d
                                </Button>
                              ))}
                            </div>
                          </div>
                          <FormMessage className='col-span-4 col-start-3' />
                        </FormItem>
                      )}
                    />
                    <FormField
                      control={form.control}
                      name='is_enabled'
                      render={({ field }) => (
                        <FormItem className='grid grid-cols-6 items-center space-y-0 gap-x-4 gap-y-1'>
                          <FormLabel className='col-span-2 text-end'>Enabled</FormLabel>
                          <FormControl>
                            <Switch checked={field.value} onCheckedChange={field.onChange} className='col-span-4 justify-self-start' />
                          </FormControl>
                          <FormMessage className='col-span-4 col-start-3' />
                        </FormItem>
                      )}
                    />
                    <FormField
                      control={form.control}
                      name='show_to_free'
                      render={({ field }) => (
                        <FormItem className='grid grid-cols-6 items-center space-y-0 gap-x-4 gap-y-1'>
                          <FormLabel className='col-span-2 text-end'>Show to Free users</FormLabel>
                          <FormControl>
                            <Switch checked={field.value} onCheckedChange={field.onChange} className='col-span-4 justify-self-start' />
                          </FormControl>
                          <FormMessage className='col-span-4 col-start-3' />
                        </FormItem>
                      )}
                    />
                    <FormField
                      control={form.control}
                      name='show_to_premium'
                      render={({ field }) => (
                        <FormItem className='grid grid-cols-6 items-center space-y-0 gap-x-4 gap-y-1'>
                          <FormLabel className='col-span-2 text-end'>Show to Premium users</FormLabel>
                          <FormControl>
                            <Switch checked={field.value} onCheckedChange={field.onChange} className='col-span-4 justify-self-start' />
                          </FormControl>
                          <FormMessage className='col-span-4 col-start-3' />
                        </FormItem>
                      )}
                    />
                    <FormField
                      control={form.control}
                      name='show_in_recipe_feed'
                      render={({ field }) => (
                        <FormItem className='grid grid-cols-6 items-center space-y-0 gap-x-4 gap-y-1'>
                          <FormLabel className='col-span-2 text-end'>Show in Recipe feed</FormLabel>
                          <FormControl>
                            <Switch checked={field.value} onCheckedChange={field.onChange} className='col-span-4 justify-self-start' />
                          </FormControl>
                          <FormMessage className='col-span-4 col-start-3' />
                        </FormItem>
                      )}
                    />
                    <FormField
                      control={form.control}
                      name='show_in_community_feed'
                      render={({ field }) => (
                        <FormItem className='grid grid-cols-6 items-center space-y-0 gap-x-4 gap-y-1'>
                          <FormLabel className='col-span-2 text-end'>Show in Community feed</FormLabel>
                          <FormControl>
                            <Switch checked={field.value} onCheckedChange={field.onChange} className='col-span-4 justify-self-start' />
                          </FormControl>
                          <FormMessage className='col-span-4 col-start-3' />
                        </FormItem>
                      )}
                    />
                  </div>
                </div>
              </TabsContent>
              <TabsContent value='bookkeeping'>
                <div className='w-[calc(100%+0.75rem)] max-h-[60vh] overflow-y-auto py-1 pe-3'>
                  <div className='space-y-4 px-0.5'>
                    <FormField
                      control={form.control}
                      name='amount_paid'
                      render={({ field }) => (
                        <FormItem className='grid grid-cols-6 items-center space-y-0 gap-x-4 gap-y-1'>
                          <FormLabel className='col-span-2 text-end'>Amount Paid (₱)</FormLabel>
                          <FormControl>
                            <Input placeholder='5000.00' inputMode='decimal' className='col-span-4' {...field} />
                          </FormControl>
                          <FormMessage className='col-span-4 col-start-3' />
                        </FormItem>
                      )}
                    />
                    <FormField
                      control={form.control}
                      name='payment_received_at'
                      render={({ field }) => (
                        <FormItem className='grid grid-cols-6 items-center space-y-0 gap-x-4 gap-y-1'>
                          <FormLabel className='col-span-2 text-end'>Payment received</FormLabel>
                          <FormControl>
                            <Input type='date' className='col-span-4' {...field} />
                          </FormControl>
                          <FormMessage className='col-span-4 col-start-3' />
                        </FormItem>
                      )}
                    />
                    <FormField
                      control={form.control}
                      name='contact_name'
                      render={({ field }) => (
                        <FormItem className='grid grid-cols-6 items-center space-y-0 gap-x-4 gap-y-1'>
                          <FormLabel className='col-span-2 text-end'>Contact name</FormLabel>
                          <FormControl>
                            <Input placeholder='Optional' className='col-span-4' {...field} />
                          </FormControl>
                          <FormMessage className='col-span-4 col-start-3' />
                        </FormItem>
                      )}
                    />
                    <FormField
                      control={form.control}
                      name='contact_email'
                      render={({ field }) => (
                        <FormItem className='grid grid-cols-6 items-center space-y-0 gap-x-4 gap-y-1'>
                          <FormLabel className='col-span-2 text-end'>Contact email</FormLabel>
                          <FormControl>
                            <Input placeholder='Optional' className='col-span-4' {...field} />
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
                          <FormLabel className='col-span-2 text-end'>Notes</FormLabel>
                          <FormControl>
                            <Textarea placeholder='Deal terms, renewal reminders, ...' className='col-span-4' rows={4} {...field} />
                          </FormControl>
                          <FormMessage className='col-span-4 col-start-3' />
                        </FormItem>
                      )}
                    />
                  </div>
                </div>
              </TabsContent>
            </form>
          </Form>
        </Tabs>
        <DialogFooter>
          <Button type='submit' form='sponsored-ad-form' disabled={isPending}>
            Save changes
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  )
}
