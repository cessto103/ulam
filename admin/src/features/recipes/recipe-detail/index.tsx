import { useState } from 'react'
import { getRouteApi } from '@tanstack/react-router'
import { ArrowLeft, Crown, Eye, MapPin, MessageSquare, ThumbsDown, ThumbsUp, Trash2 } from 'lucide-react'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent } from '@/components/ui/card'
import { ConfigDrawer } from '@/components/config-drawer'
import { Header } from '@/components/layout/header'
import { Main } from '@/components/layout/main'
import { ProfileDropdown } from '@/components/profile-dropdown'
import { Search } from '@/components/search'
import { ThemeSwitch } from '@/components/theme-switch'
import { budgetTags, sources } from '../data/data'
import { type RecipeCommentDetail } from '../data/schema'
import { RecipesDeleteDialog } from '../components/recipes-delete-dialog'
import { useRecipeQuery } from '../hooks/use-recipes'

const API_ORIGIN = (import.meta.env.VITE_API_URL as string | undefined)?.replace(/\/api\/?$/, '') ?? ''

const route = getRouteApi('/_authenticated/recipes/$recipeId')

function CommentRow({ comment, indent = false }: { comment: RecipeCommentDetail; indent?: boolean }) {
  return (
    <div className={indent ? 'ms-8 mt-3' : 'mt-3'}>
      <div className='flex items-start gap-2'>
        <div className='mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center overflow-hidden rounded-full bg-muted text-xs font-semibold'>
          {comment.user?.avatar ? (
            <img src={`${API_ORIGIN}${comment.user.avatar}`} alt='' className='h-full w-full object-cover' />
          ) : (
            (comment.user?.name ?? '??').slice(0, 2).toUpperCase()
          )}
        </div>
        <div className='flex-1'>
          <div className='flex items-baseline gap-2'>
            <span className='text-sm font-semibold'>{comment.user?.name ?? 'Deleted user'}</span>
            <span className='text-xs text-muted-foreground'>{new Date(comment.created_at).toLocaleString()}</span>
          </div>
          <p className='text-sm'>{comment.body}</p>
        </div>
      </div>
      {comment.replies?.map((reply) => (
        <CommentRow key={reply.id} comment={reply} indent />
      ))}
    </div>
  )
}

export function RecipeDetailPage() {
  const { recipeId } = route.useParams()
  const navigate = route.useNavigate()
  const { data: recipe, isLoading } = useRecipeQuery(Number(recipeId))
  const [deleteOpen, setDeleteOpen] = useState(false)

  const sourceLabel = sources.find((s) => s.value === recipe?.source)?.label ?? recipe?.source
  const budgetLabel = budgetTags.find((b) => b.value === recipe?.budget_tag)?.label ?? recipe?.budget_tag

  return (
    <>
      <Header fixed>
        <Search className='me-auto' />
        <ThemeSwitch />
        <ConfigDrawer />
        <ProfileDropdown />
      </Header>

      <Main className='flex flex-1 flex-col gap-4 sm:gap-6'>
        <div className='flex items-center justify-between gap-2'>
          <Button variant='ghost' size='sm' onClick={() => navigate({ to: '/recipes' })}>
            <ArrowLeft /> Back to Recipes
          </Button>
          {recipe && (
            <Button variant='destructive' size='sm' onClick={() => setDeleteOpen(true)}>
              <Trash2 /> Delete
            </Button>
          )}
        </div>

        {isLoading || !recipe ? (
          <p className='text-muted-foreground'>Loading...</p>
        ) : (
          <div className='grid gap-4 lg:grid-cols-[1fr_320px]'>
            <Card>
              <CardContent className='space-y-4 pt-6'>
                <div className='flex flex-wrap items-center gap-2'>
                  <Badge variant='outline' className='capitalize'>{sourceLabel}</Badge>
                  <Badge variant='secondary'>{budgetLabel}</Badge>
                  {recipe.is_premium_only && (
                    <Badge className='gap-1'><Crown size={12} /> Premium</Badge>
                  )}
                  {!recipe.is_published && <Badge variant='outline'>Unpublished</Badge>}
                  <span className='ms-auto text-xs text-muted-foreground'>
                    {new Date(recipe.created_at).toLocaleString()}
                  </span>
                </div>

                <h2 className='text-xl font-bold'>{recipe.title}</h2>
                {recipe.description && <p className='text-sm text-muted-foreground'>{recipe.description}</p>}

                {recipe.image_urls && recipe.image_urls.length > 0 ? (
                  <div className='grid grid-cols-2 gap-2 sm:grid-cols-3'>
                    {recipe.image_urls.map((img, i) => (
                      <img
                        key={i}
                        src={img.startsWith('http') ? img : `${API_ORIGIN}${img}`}
                        alt=''
                        className='aspect-square w-full rounded-md border object-cover'
                      />
                    ))}
                  </div>
                ) : recipe.image_url ? (
                  <img
                    src={recipe.image_url.startsWith('http') ? recipe.image_url : `${API_ORIGIN}${recipe.image_url}`}
                    alt=''
                    className='aspect-video w-full rounded-md border object-cover'
                  />
                ) : null}

                {recipe.steps && recipe.steps.length > 0 && (
                  <div>
                    <div className='mb-2 text-xs font-medium uppercase text-muted-foreground'>Steps</div>
                    <ol className='list-inside list-decimal space-y-1 text-sm'>
                      {recipe.steps.map((step, i) => <li key={i}>{step}</li>)}
                    </ol>
                  </div>
                )}

                <div className='flex items-center gap-5 border-t pt-4 text-sm text-muted-foreground'>
                  <span className='flex items-center gap-1.5'><ThumbsUp size={14} /> {recipe.vote_up_count ?? 0}</span>
                  <span className='flex items-center gap-1.5'><ThumbsDown size={14} /> {recipe.vote_down_count ?? 0}</span>
                  <span className='flex items-center gap-1.5'><Eye size={14} /> {recipe.views_count ?? 0}</span>
                  <span className='flex items-center gap-1.5'><MessageSquare size={14} /> {recipe.comments.length}</span>
                </div>
              </CardContent>
            </Card>

            <div className='space-y-4'>
              <Card>
                <CardContent className='pt-6'>
                  <div className='mb-2 text-xs font-medium uppercase text-muted-foreground'>Author</div>
                  {recipe.user ? (
                    <>
                      <div className='flex items-center gap-3'>
                        <div className='flex h-10 w-10 items-center justify-center overflow-hidden rounded-full bg-muted text-sm font-semibold'>
                          {recipe.user.avatar ? (
                            <img src={`${API_ORIGIN}${recipe.user.avatar}`} alt='' className='h-full w-full object-cover' />
                          ) : (
                            recipe.user.name.slice(0, 2).toUpperCase()
                          )}
                        </div>
                        <div>
                          <div className='text-sm font-semibold'>{recipe.user.name}</div>
                          {recipe.user.username && (
                            <div className='text-xs text-muted-foreground'>@{recipe.user.username}</div>
                          )}
                        </div>
                      </div>
                      {(recipe.user.barangay || recipe.user.municipality) && (
                        <div className='mt-3 flex items-center gap-1.5 text-xs text-muted-foreground'>
                          <MapPin size={12} />
                          {[recipe.user.barangay, recipe.user.municipality].filter(Boolean).join(', ')}
                        </div>
                      )}
                    </>
                  ) : (
                    <p className='text-sm text-muted-foreground'>No owner (official/system recipe).</p>
                  )}
                </CardContent>
              </Card>

              {recipe.ingredients && recipe.ingredients.length > 0 && (
                <Card>
                  <CardContent className='pt-6'>
                    <div className='mb-2 text-xs font-medium uppercase text-muted-foreground'>Ingredients</div>
                    <ul className='space-y-1 text-sm'>
                      {recipe.ingredients.map((ing) => (
                        <li key={ing.id} className='flex justify-between gap-2'>
                          <span>{ing.name}</span>
                          <span className='text-muted-foreground'>{[ing.quantity, ing.unit].filter(Boolean).join(' ')}</span>
                        </li>
                      ))}
                    </ul>
                  </CardContent>
                </Card>
              )}
            </div>

            <Card className='lg:col-span-2'>
              <CardContent className='pt-6'>
                <div className='mb-2 text-xs font-medium uppercase text-muted-foreground'>
                  Comments ({recipe.comments.length})
                </div>
                {recipe.comments.length === 0 ? (
                  <p className='text-sm text-muted-foreground'>No comments yet.</p>
                ) : (
                  <div className='divide-y'>
                    {recipe.comments.map((comment) => (
                      <CommentRow key={comment.id} comment={comment} />
                    ))}
                  </div>
                )}
              </CardContent>
            </Card>
          </div>
        )}
      </Main>

      {recipe && (
        <RecipesDeleteDialog
          open={deleteOpen}
          onOpenChange={setDeleteOpen}
          onDeleted={() => navigate({ to: '/recipes' })}
          currentRow={recipe}
        />
      )}
    </>
  )
}
