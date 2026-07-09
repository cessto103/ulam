import { GovernmentPriceReferencesActionDialog } from './government-price-references-action-dialog'
import { GovernmentPriceReferencesDeleteDialog } from './government-price-references-delete-dialog'
import { useGovernmentPriceReferences } from './government-price-references-provider'

export function GovernmentPriceReferencesDialogs() {
  const { open, setOpen, currentRow, setCurrentRow } =
    useGovernmentPriceReferences()
  return (
    <>
      <GovernmentPriceReferencesActionDialog
        key='government-price-reference-add'
        open={open === 'add'}
        onOpenChange={() => setOpen('add')}
      />

      {currentRow && (
        <>
          <GovernmentPriceReferencesActionDialog
            key={`government-price-reference-edit-${currentRow.id}`}
            open={open === 'edit'}
            onOpenChange={() => {
              setOpen('edit')
              setTimeout(() => {
                setCurrentRow(null)
              }, 500)
            }}
            currentRow={currentRow}
          />

          <GovernmentPriceReferencesDeleteDialog
            key={`government-price-reference-delete-${currentRow.id}`}
            open={open === 'delete'}
            onOpenChange={() => {
              setOpen('delete')
              setTimeout(() => {
                setCurrentRow(null)
              }, 500)
            }}
            currentRow={currentRow}
          />
        </>
      )}
    </>
  )
}
