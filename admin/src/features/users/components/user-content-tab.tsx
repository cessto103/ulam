import { useNavigate } from '@tanstack/react-router'
import { Eye, MessageSquare, ThumbsDown, ThumbsUp, Store as StoreIcon } from 'lucide-react'
import { Badge } from '@/components/ui/badge'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { useUserContentQuery, type UserPost, type UserRecipe, type UserStore } from '../hooks/use-user-content'

const API_ORIGIN = (import.meta.env.VITE_API_URL as string | undefined)?.replace(/\/api\/?$/, '') ?? ''

function img(path: string) {
  return path.startsWith('http') ? path : `${API_ORIGIN}${path}`
}

function PostRow({ post, onClick }: { post: UserPost; onClick: () => void }) {
  return (
    <div
      onClick={onClick}
      className='flex cursor-pointer items-start gap-3 border-b py-3 last:border-0 hover:bg-muted/50'
    >
      {post.images?.[0] && (
        <img src={img(post.images[0])} alt='' className='h-12 w-12 shrink-0 rounded-md border object-cover' />
      )}
      <div className='min-w-0 flex-1'>
        <p className='truncate text-sm'>{post.body}</p>
        <div className='mt-1 flex items-center gap-3 text-xs text-muted-foreground'>
          <Badge variant='outline' className='capitalize'>{post.post_type.replace('_', ' ')}</Badge>
          <span className='flex items-center gap-1'><ThumbsUp size={12} /> {post.puso_count}</span>
          <span className='flex items-center gap-1'><ThumbsDown size={12} /> {post.dislike_count}</span>
          <span className='flex items-center gap-1'><Eye size={12} /> {post.views_count}</span>
          <span className='flex items-center gap-1'><MessageSquare size={12} /> {post.comments_count}</span>
          <span className='ms-auto'>{new Date(post.created_at).toLocaleDateString()}</span>
        </div>
      </div>
    </div>
  )
}

function RecipeRow({ recipe, onClick }: { recipe: UserRecipe; onClick: () => void }) {
  const cover = recipe.image_urls?.[0] ?? recipe.image_url
  return (
    <div
      onClick={onClick}
      className='flex cursor-pointer items-start gap-3 border-b py-3 last:border-0 hover:bg-muted/50'
    >
      {cover && (
        <img src={img(cover)} alt='' className='h-12 w-12 shrink-0 rounded-md border object-cover' />
      )}
      <div className='min-w-0 flex-1'>
        <p className='truncate text-sm font-medium'>{recipe.title}</p>
        <div className='mt-1 flex items-center gap-3 text-xs text-muted-foreground'>
          {!recipe.is_published && <Badge variant='secondary'>Draft</Badge>}
          <span className='flex items-center gap-1'><ThumbsUp size={12} /> {recipe.vote_up_count}</span>
          <span className='flex items-center gap-1'><ThumbsDown size={12} /> {recipe.vote_down_count}</span>
          <span className='flex items-center gap-1'><Eye size={12} /> {recipe.views_count}</span>
          <span className='ms-auto'>{new Date(recipe.created_at).toLocaleDateString()}</span>
        </div>
      </div>
    </div>
  )
}

function StoreRow({ store }: { store: UserStore }) {
  return (
    <div className='flex items-start gap-3 border-b py-3 last:border-0'>
      <div className='flex h-12 w-12 shrink-0 items-center justify-center overflow-hidden rounded-md border bg-muted'>
        {store.photo ? (
          <img src={img(store.photo)} alt='' className='h-full w-full object-cover' />
        ) : (
          <StoreIcon size={16} className='text-muted-foreground' />
        )}
      </div>
      <div className='min-w-0 flex-1'>
        <p className='truncate text-sm font-medium'>{store.name}</p>
        <div className='mt-1 flex items-center gap-3 text-xs text-muted-foreground'>
          {store.market && <span>{store.market.name}</span>}
          <span>{store.items_count} item{store.items_count === 1 ? '' : 's'}</span>
          {store.is_verified && <Badge variant='outline'>Verified</Badge>}
          {!store.is_active && <Badge variant='secondary'>Inactive</Badge>}
        </div>
      </div>
    </div>
  )
}

export function UserContentTab({ userId }: { userId: number }) {
  const { data, isLoading } = useUserContentQuery(userId)
  const navigate = useNavigate()

  if (isLoading || !data) {
    return <p className='text-sm text-muted-foreground'>Loading...</p>
  }

  return (
    <div className='grid gap-4 lg:grid-cols-2'>
      <Card>
        <CardHeader>
          <CardTitle className='text-sm'>Posts ({data.posts.length})</CardTitle>
        </CardHeader>
        <CardContent>
          {data.posts.length === 0 ? (
            <p className='text-sm text-muted-foreground'>No posts yet.</p>
          ) : (
            data.posts.map((post) => (
              <PostRow
                key={post.id}
                post={post}
                onClick={() => navigate({ to: '/posts/$postId', params: { postId: String(post.id) } })}
              />
            ))
          )}
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle className='text-sm'>Recipes ({data.recipes.length})</CardTitle>
        </CardHeader>
        <CardContent>
          {data.recipes.length === 0 ? (
            <p className='text-sm text-muted-foreground'>No recipes yet.</p>
          ) : (
            data.recipes.map((recipe) => (
              <RecipeRow
                key={recipe.id}
                recipe={recipe}
                onClick={() => navigate({ to: '/recipes/$recipeId', params: { recipeId: String(recipe.id) } })}
              />
            ))
          )}
        </CardContent>
      </Card>

      <Card className='lg:col-span-2'>
        <CardHeader>
          <CardTitle className='text-sm'>Stores ({data.stores.length})</CardTitle>
        </CardHeader>
        <CardContent>
          {data.stores.length === 0 ? (
            <p className='text-sm text-muted-foreground'>No stores yet.</p>
          ) : (
            data.stores.map((store) => <StoreRow key={store.id} store={store} />)
          )}
        </CardContent>
      </Card>
    </div>
  )
}
