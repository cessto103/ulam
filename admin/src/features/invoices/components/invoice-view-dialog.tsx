import { Download, Loader2, Mail } from 'lucide-react'
import { toast } from 'sonner'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog'
import { type Invoice } from '../data/schema'
import { useDownloadInvoicePdf, useInvoiceQuery } from '../hooks/use-invoices'

const STATUS_VARIANT: Record<string, 'default' | 'secondary' | 'destructive' | 'outline'> = {
  draft: 'secondary',
  issued: 'default',
  void: 'destructive',
}

type Props = {
  invoiceId: number | null
  onClose: () => void
  onEmail: (invoice: Invoice) => void
}

/** Fetches fresh, matching the `legal` feature's VersionViewDialog +
 * useLegalVersion(id) pattern -- the table row's own data (from the list
 * query) doesn't eager-load voided_by_user, only GET /admin/invoices/{id}
 * does, so this can't just reuse whatever row was clicked. */
export function InvoiceViewDialog({ invoiceId, onClose, onEmail }: Props) {
  const { data: invoice } = useInvoiceQuery(invoiceId)
  const { mutate: download, isPending: downloading } = useDownloadInvoicePdf()
  const issuer = (invoice?.issuer_snapshot ?? {}) as Record<string, string | null>
  const isVatRegistered = invoice?.vat_status === 'vat_registered'

  return (
    <Dialog open={invoiceId !== null} onOpenChange={(open) => !open && onClose()}>
      <DialogContent className='max-w-2xl'>
        <DialogHeader>
          <DialogTitle className='flex items-center gap-2'>
            {invoice?.invoice_number ?? 'Draft invoice'}
            {invoice && (
              <Badge variant={STATUS_VARIANT[invoice.status] ?? 'outline'} className='capitalize'>
                {invoice.status}
              </Badge>
            )}
          </DialogTitle>
        </DialogHeader>

        {invoice && (
          <div className='space-y-4 text-sm'>
            <div className='grid grid-cols-2 gap-4'>
              <div>
                <div className='text-xs font-medium text-muted-foreground uppercase'>From</div>
                <div className='font-semibold'>{issuer.trade_name || issuer.registered_name || 'uLam'}</div>
                {issuer.address && <div className='text-muted-foreground'>{issuer.address}</div>}
                {issuer.tin && <div className='text-muted-foreground'>TIN: {issuer.tin}</div>}
              </div>
              <div>
                <div className='text-xs font-medium text-muted-foreground uppercase'>Billed to</div>
                <div className='font-semibold'>{invoice.buyer_name}</div>
                {invoice.buyer_contact_name && <div className='text-muted-foreground'>Attn: {invoice.buyer_contact_name}</div>}
                {invoice.buyer_email && <div className='text-muted-foreground'>{invoice.buyer_email}</div>}
              </div>
            </div>

            {invoice.sponsored_ad && (
              <div className='rounded-md border p-3'>
                <div className='text-xs font-medium text-muted-foreground uppercase'>Linked Sponsored Ad</div>
                <div>
                  {invoice.sponsored_ad.product_name} — {invoice.sponsored_ad.company_name}
                </div>
              </div>
            )}

            <div className='rounded-md border p-3'>
              <div className='mb-1 text-xs font-medium text-muted-foreground uppercase'>Description</div>
              <p>{invoice.description}</p>
              <div className='mt-3 space-y-1 border-t pt-3'>
                {isVatRegistered && (
                  <>
                    <div className='flex justify-between text-muted-foreground'>
                      <span>VATable sales</span>
                      <span>₱{Number(invoice.net_amount ?? 0).toFixed(2)}</span>
                    </div>
                    <div className='flex justify-between text-muted-foreground'>
                      <span>VAT (12%)</span>
                      <span>₱{Number(invoice.vat_amount ?? 0).toFixed(2)}</span>
                    </div>
                  </>
                )}
                <div className='flex justify-between text-base font-bold'>
                  <span>Total</span>
                  <span>₱{Number(invoice.amount).toFixed(2)}</span>
                </div>
              </div>
            </div>

            {invoice.status === 'void' && (
              <div className='rounded-md border border-destructive/50 bg-destructive/5 p-3'>
                <div className='text-xs font-medium text-destructive uppercase'>Voided</div>
                <p>{invoice.void_reason}</p>
                <p className='text-xs text-muted-foreground'>
                  {invoice.voided_at ? new Date(invoice.voided_at).toLocaleString() : ''}
                  {invoice.voided_by_user ? ` by ${invoice.voided_by_user.name}` : ''}
                </p>
              </div>
            )}

            <div className='flex justify-end gap-2 border-t pt-3'>
              <Button
                variant='outline'
                disabled={downloading}
                onClick={() =>
                  download(
                    { id: invoice.id, filename: `${invoice.invoice_number ?? 'draft-invoice-' + invoice.id}.pdf` },
                    { onError: () => toast.error('Could not download the PDF.') }
                  )
                }
              >
                {downloading ? <Loader2 className='animate-spin' /> : <Download />}
                Download PDF
              </Button>
              <Button onClick={() => onEmail(invoice)}>
                <Mail />
                Email
              </Button>
            </div>
          </div>
        )}
      </DialogContent>
    </Dialog>
  )
}
