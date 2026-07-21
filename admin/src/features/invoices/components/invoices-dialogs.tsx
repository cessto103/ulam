import { InvoiceActionDialog } from './invoice-action-dialog'
import { InvoiceDeleteDialog } from './invoice-delete-dialog'
import { InvoiceEmailDialog } from './invoice-email-dialog'
import { InvoiceMarkPaidDialog } from './invoice-mark-paid-dialog'
import { InvoiceVoidDialog } from './invoice-void-dialog'
import { InvoiceViewDialog } from './invoice-view-dialog'
import { useInvoices } from './invoices-provider'

export function InvoicesDialogs() {
  const { open, setOpen, currentRow, setCurrentRow } = useInvoices()

  return (
    <>
      <InvoiceActionDialog key='invoice-add' open={open === 'add'} onOpenChange={() => setOpen('add')} />

      <InvoiceViewDialog
        invoiceId={open === 'view' ? (currentRow?.id ?? null) : null}
        onClose={() => setOpen(null)}
        onEmail={(invoice) => {
          setCurrentRow(invoice)
          setOpen('email')
        }}
      />

      {currentRow && (
        <>
          <InvoiceActionDialog
            key={`invoice-edit-${currentRow.id}`}
            open={open === 'edit'}
            onOpenChange={() => {
              setOpen('edit')
              setTimeout(() => setCurrentRow(null), 500)
            }}
            currentRow={currentRow}
          />

          <InvoiceDeleteDialog
            key={`invoice-delete-${currentRow.id}`}
            open={open === 'delete'}
            onOpenChange={() => {
              setOpen('delete')
              setTimeout(() => setCurrentRow(null), 500)
            }}
            currentRow={currentRow}
          />

          <InvoiceMarkPaidDialog
            key={`invoice-mark-paid-${currentRow.id}`}
            open={open === 'mark-paid'}
            onOpenChange={() => {
              setOpen('mark-paid')
              setTimeout(() => setCurrentRow(null), 500)
            }}
            currentRow={currentRow}
          />

          <InvoiceVoidDialog
            key={`invoice-void-${currentRow.id}`}
            open={open === 'void'}
            onOpenChange={() => {
              setOpen('void')
              setTimeout(() => setCurrentRow(null), 500)
            }}
            currentRow={currentRow}
          />

          <InvoiceEmailDialog
            key={`invoice-email-${currentRow.id}`}
            open={open === 'email'}
            onOpenChange={() => {
              setOpen('email')
              setTimeout(() => setCurrentRow(null), 500)
            }}
            currentRow={currentRow}
          />
        </>
      )}
    </>
  )
}
