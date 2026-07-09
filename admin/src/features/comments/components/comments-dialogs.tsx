import { CommentsDeleteDialog } from './comments-delete-dialog'
import { useComments } from './comments-provider'

export function CommentsDialogs() {
  const { open, setOpen, currentRow, setCurrentRow } = useComments()
  return (
    <>
      {currentRow && (
        <CommentsDeleteDialog
          key={`comment-delete-${currentRow.id}`}
          open={open === 'delete'}
          onOpenChange={() => {
            setOpen('delete')
            setTimeout(() => {
              setCurrentRow(null)
            }, 500)
          }}
          currentRow={currentRow}
        />
      )}
    </>
  )
}
