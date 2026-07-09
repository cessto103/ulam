import { PostsDeleteDialog } from './posts-delete-dialog'
import { usePosts } from './posts-provider'
import { PostsToggleSponsoredDialog } from './posts-toggle-sponsored-dialog'

export function PostsDialogs() {
  const { open, setOpen, currentRow, setCurrentRow } = usePosts()
  return (
    <>
      {currentRow && (
        <>
          <PostsToggleSponsoredDialog
            key={`post-toggle-sponsored-${currentRow.id}`}
            open={open === 'toggleSponsored'}
            onOpenChange={() => {
              setOpen('toggleSponsored')
              setTimeout(() => {
                setCurrentRow(null)
              }, 500)
            }}
            currentRow={currentRow}
          />

          <PostsDeleteDialog
            key={`post-delete-${currentRow.id}`}
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
