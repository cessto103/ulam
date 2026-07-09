import { MarketPricesActionDialog } from './market-prices-action-dialog'
import { MarketPricesDeleteDialog } from './market-prices-delete-dialog'
import { useMarketPrices } from './market-prices-provider'

export function MarketPricesDialogs() {
  const { open, setOpen, currentRow, setCurrentRow } = useMarketPrices()
  return (
    <>
      <MarketPricesActionDialog
        key='market-price-add'
        open={open === 'add'}
        onOpenChange={() => setOpen('add')}
      />

      {currentRow && (
        <>
          <MarketPricesActionDialog
            key={`market-price-edit-${currentRow.id}`}
            open={open === 'edit'}
            onOpenChange={() => {
              setOpen('edit')
              setTimeout(() => {
                setCurrentRow(null)
              }, 500)
            }}
            currentRow={currentRow}
          />

          <MarketPricesDeleteDialog
            key={`market-price-delete-${currentRow.id}`}
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
