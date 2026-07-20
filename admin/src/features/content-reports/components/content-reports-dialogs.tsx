import { ContentReportsBanDialog } from './content-reports-ban-dialog'
import { ContentReportsDeleteDialog } from './content-reports-delete-dialog'
import { ContentReportsRestrictDialog } from './content-reports-restrict-dialog'
import { ContentReportsWarnDialog } from './content-reports-warn-dialog'
import { useContentReports } from './content-reports-provider'

export function ContentReportsDialogs() {
  const { open, setOpen, currentRow, setCurrentRow } = useContentReports()
  return (
    <>
      {currentRow && (
        <>
          <ContentReportsWarnDialog
            key={`content-report-warn-${currentRow.id}`}
            open={open === 'warn'}
            onOpenChange={() => {
              setOpen('warn')
              setTimeout(() => {
                setCurrentRow(null)
              }, 500)
            }}
            currentRow={currentRow}
          />

          <ContentReportsRestrictDialog
            key={`content-report-restrict-${currentRow.id}`}
            open={open === 'restrict'}
            onOpenChange={() => {
              setOpen('restrict')
              setTimeout(() => {
                setCurrentRow(null)
              }, 500)
            }}
            currentRow={currentRow}
          />

          <ContentReportsBanDialog
            key={`content-report-ban-${currentRow.id}`}
            open={open === 'ban'}
            onOpenChange={() => {
              setOpen('ban')
              setTimeout(() => {
                setCurrentRow(null)
              }, 500)
            }}
            currentRow={currentRow}
          />

          <ContentReportsDeleteDialog
            key={`content-report-delete-${currentRow.id}`}
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
