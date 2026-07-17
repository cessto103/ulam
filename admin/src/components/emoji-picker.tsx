import { Button } from '@/components/ui/button'
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover'

// Icons are stored as emoji strings app-wide (e.g. `achievements.icon`),
// not icon-library names — the mobile app has no matching icon library to
// map a name to, so this stays consistent with that convention.
const EMOJI_OPTIONS = [
  '🎯', '✅', '📅', '🔥', '⭐', '🏆', '🎁', '💰',
  '🛒', '🍚', '🥗', '🐟', '🍗', '🥩', '🥚', '🍞',
  '🧴', '🧂', '📸', '💬', '👍', '📊', '🎉', '🌅',
  '☀️', '🌙', '💸', '🥦', '🍌', '📢', '🔔', '💪',
]

export function EmojiPicker({
  value,
  onChange,
}: {
  value: string
  onChange: (emoji: string) => void
}) {
  return (
    <Popover>
      <PopoverTrigger asChild>
        <Button
          type='button'
          variant='outline'
          className='h-10 w-14 text-lg'
        >
          {value || '🙂'}
        </Button>
      </PopoverTrigger>
      <PopoverContent className='w-64'>
        <div className='grid grid-cols-8 gap-1'>
          {EMOJI_OPTIONS.map((emoji) => (
            <button
              key={emoji}
              type='button'
              onClick={() => onChange(emoji)}
              className={`flex size-7 items-center justify-center rounded-md text-lg hover:bg-accent ${
                value === emoji ? 'bg-accent ring-1 ring-ring' : ''
              }`}
            >
              {emoji}
            </button>
          ))}
        </div>
      </PopoverContent>
    </Popover>
  )
}
