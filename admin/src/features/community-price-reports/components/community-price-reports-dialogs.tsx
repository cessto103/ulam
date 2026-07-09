import { CommunityPriceReportsActionDialog } from './community-price-reports-action-dialog'
import { CommunityPriceReportsDeleteDialog } from './community-price-reports-delete-dialog'
import { useCommunityPriceReports } from './community-price-reports-provider'

export function CommunityPriceReportsDialogs() {
  const { open, setOpen, currentRow, setCurrentRow } =
    useCommunityPriceReports()
  return (
    <>
      <CommunityPriceReportsActionDialog
        key='community-price-report-add'
        open={open === 'add'}
        onOpenChange={() => setOpen('add')}
      />

      {currentRow && (
        <>
          <CommunityPriceReportsActionDialog
            key={`community-price-report-edit-${currentRow.id}`}
            open={open === 'edit'}
            onOpenChange={() => {
              setOpen('edit')
              setTimeout(() => {
                setCurrentRow(null)
              }, 500)
            }}
            currentRow={currentRow}
          />

          <CommunityPriceReportsDeleteDialog
            key={`community-price-report-delete-${currentRow.id}`}
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
