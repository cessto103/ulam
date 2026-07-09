import { TindahanActionDialog } from './tindahan-action-dialog'
import { TindahanDeleteDialog } from './tindahan-delete-dialog'
import { useTindahan } from './tindahan-provider'

export function TindahanDialogs() {
  const { open, setOpen, currentRow, setCurrentRow } = useTindahan()
  return (
    <>
      <TindahanActionDialog
        key='tindahan-add'
        open={open === 'add'}
        onOpenChange={() => setOpen('add')}
      />

      {currentRow && (
        <>
          <TindahanActionDialog
            key={`tindahan-edit-${currentRow.id}`}
            open={open === 'edit'}
            onOpenChange={() => {
              setOpen('edit')
              setTimeout(() => {
                setCurrentRow(null)
              }, 500)
            }}
            currentRow={currentRow}
          />

          <TindahanDeleteDialog
            key={`tindahan-delete-${currentRow.id}`}
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
