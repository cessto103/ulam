import { SponsoredAdsActionDialog } from './sponsored-ads-action-dialog'
import { SponsoredAdsDeleteDialog } from './sponsored-ads-delete-dialog'
import { useSponsoredAds } from './sponsored-ads-provider'

export function SponsoredAdsDialogs() {
  const { open, setOpen, currentRow, setCurrentRow } = useSponsoredAds()
  return (
    <>
      <SponsoredAdsActionDialog
        key='sponsored-ad-add'
        open={open === 'add'}
        onOpenChange={() => setOpen('add')}
      />

      {currentRow && (
        <>
          <SponsoredAdsActionDialog
            key={`sponsored-ad-edit-${currentRow.id}`}
            open={open === 'edit'}
            onOpenChange={() => {
              setOpen('edit')
              setTimeout(() => {
                setCurrentRow(null)
              }, 500)
            }}
            currentRow={currentRow}
          />

          <SponsoredAdsDeleteDialog
            key={`sponsored-ad-delete-${currentRow.id}`}
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
