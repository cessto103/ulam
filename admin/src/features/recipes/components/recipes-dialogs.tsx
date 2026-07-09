import { RecipesActionDialog } from './recipes-action-dialog'
import { RecipesDeleteDialog } from './recipes-delete-dialog'
import { useRecipes } from './recipes-provider'

export function RecipesDialogs() {
  const { open, setOpen, currentRow, setCurrentRow } = useRecipes()
  return (
    <>
      <RecipesActionDialog
        key='recipe-add'
        open={open === 'add'}
        onOpenChange={() => setOpen('add')}
      />

      {currentRow && (
        <>
          <RecipesActionDialog
            key={`recipe-edit-${currentRow.id}`}
            open={open === 'edit'}
            onOpenChange={() => {
              setOpen('edit')
              setTimeout(() => {
                setCurrentRow(null)
              }, 500)
            }}
            currentRow={currentRow}
          />

          <RecipesDeleteDialog
            key={`recipe-delete-${currentRow.id}`}
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
