import { MarketsActionDialog } from './markets-action-dialog'
import { MarketsDeleteDialog } from './markets-delete-dialog'
import { useMarkets } from './markets-provider'
import { MarketsRefreshAiDialog } from './markets-refresh-ai-dialog'

export function MarketsDialogs() {
  const { open, setOpen, currentRow, setCurrentRow } = useMarkets()
  return (
    <>
      <MarketsActionDialog
        key='market-add'
        open={open === 'add'}
        onOpenChange={() => setOpen('add')}
      />

      {currentRow && (
        <>
          <MarketsActionDialog
            key={`market-edit-${currentRow.id}`}
            open={open === 'edit'}
            onOpenChange={() => {
              setOpen('edit')
              setTimeout(() => {
                setCurrentRow(null)
              }, 500)
            }}
            currentRow={currentRow}
          />

          <MarketsDeleteDialog
            key={`market-delete-${currentRow.id}`}
            open={open === 'delete'}
            onOpenChange={() => {
              setOpen('delete')
              setTimeout(() => {
                setCurrentRow(null)
              }, 500)
            }}
            currentRow={currentRow}
          />

          <MarketsRefreshAiDialog
            key={`market-refresh-ai-${currentRow.id}`}
            open={open === 'refresh-ai'}
            onOpenChange={() => {
              setOpen('refresh-ai')
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
