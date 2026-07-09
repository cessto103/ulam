import { ListingReportsBanOwnerDialog } from './listing-reports-ban-owner-dialog'
import { ListingReportsDeactivateDialog } from './listing-reports-deactivate-dialog'
import { ListingReportsDeleteDialog } from './listing-reports-delete-dialog'
import { useListingReports } from './listing-reports-provider'

export function ListingReportsDialogs() {
  const { open, setOpen, currentRow, setCurrentRow } = useListingReports()
  return (
    <>
      {currentRow && (
        <>
          <ListingReportsBanOwnerDialog
            key={`listing-report-ban-owner-${currentRow.id}`}
            open={open === 'ban-owner'}
            onOpenChange={() => {
              setOpen('ban-owner')
              setTimeout(() => {
                setCurrentRow(null)
              }, 500)
            }}
            currentRow={currentRow}
          />

          <ListingReportsDeactivateDialog
            key={`listing-report-deactivate-${currentRow.id}`}
            open={open === 'deactivate'}
            onOpenChange={() => {
              setOpen('deactivate')
              setTimeout(() => {
                setCurrentRow(null)
              }, 500)
            }}
            currentRow={currentRow}
          />

          <ListingReportsDeleteDialog
            key={`listing-report-delete-${currentRow.id}`}
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
