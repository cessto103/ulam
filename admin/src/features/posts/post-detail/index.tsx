import { useState } from 'react'
import { getRouteApi } from '@tanstack/react-router'
import { ArrowLeft, BadgeCheck, Eye, MapPin, MessageSquare, ThumbsDown, ThumbsUp, Trash2 } from 'lucide-react'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent } from '@/components/ui/card'
import { ConfigDrawer } from '@/components/config-drawer'
import { Header } from '@/components/layout/header'
import { Main } from '@/components/layout/main'
import { ProfileDropdown } from '@/components/profile-dropdown'
import { Search } from '@/components/search'
import { ThemeSwitch } from '@/components/theme-switch'
import { postTypes } from '../data/data'
import { type PostCommentDetail } from '../data/schema'
import { PostsDeleteDialog } from '../components/posts-delete-dialog'
import { usePostQuery } from '../hooks/use-posts'

const API_ORIGIN = (import.meta.env.VITE_API_URL as string | undefined)?.replace(/\/api\/?$/, '') ?? ''

const route = getRouteApi('/_authenticated/posts/$postId')

function CommentRow({ comment, indent = false }: { comment: PostCommentDetail; indent?: boolean }) {
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

export function PostDetailPage() {
  const { postId } = route.useParams()
  const navigate = route.useNavigate()
  const { data: post, isLoading } = usePostQuery(Number(postId))
  const [deleteOpen, setDeleteOpen] = useState(false)

  const typeInfo = postTypes.find((t) => t.value === post?.post_type)

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
          <Button variant='ghost' size='sm' onClick={() => navigate({ to: '/posts' })}>
            <ArrowLeft /> Back to Posts
          </Button>
          {post && (
            <Button variant='destructive' size='sm' onClick={() => setDeleteOpen(true)}>
              <Trash2 /> Delete
            </Button>
          )}
        </div>

        {isLoading || !post ? (
          <p className='text-muted-foreground'>Loading...</p>
        ) : (
          <div className='grid gap-4 lg:grid-cols-[1fr_320px]'>
            <Card>
              <CardContent className='space-y-4 pt-6'>
                <div className='flex items-center gap-2'>
                  {typeInfo?.icon && <typeInfo.icon size={14} />}
                  <Badge variant='outline' className='capitalize'>{typeInfo?.label ?? post.post_type}</Badge>
                  {post.is_sponsored && (
                    <Badge className='gap-1'><BadgeCheck size={12} /> Sponsored</Badge>
                  )}
                  <span className='ms-auto text-xs text-muted-foreground'>
                    {new Date(post.created_at).toLocaleString()}
                  </span>
                </div>

                <p className='whitespace-pre-wrap text-sm'>{post.body}</p>

                {post.images && post.images.length > 0 && (
                  <div className='grid grid-cols-2 gap-2 sm:grid-cols-3'>
                    {post.images.map((img, i) => (
                      <img
                        key={i}
                        src={img.startsWith('http') ? img : `${API_ORIGIN}${img}`}
                        alt=''
                        className='aspect-square w-full rounded-md border object-cover'
                      />
                    ))}
                  </div>
                )}

                <div className='flex items-center gap-5 border-t pt-4 text-sm text-muted-foreground'>
                  <span className='flex items-center gap-1.5'><ThumbsUp size={14} /> {post.puso_count}</span>
                  <span className='flex items-center gap-1.5'><ThumbsDown size={14} /> {post.dislike_count}</span>
                  <span className='flex items-center gap-1.5'><Eye size={14} /> {post.views_count ?? 0}</span>
                  <span className='flex items-center gap-1.5'><MessageSquare size={14} /> {post.comments_count}</span>
                </div>
              </CardContent>
            </Card>

            <div className='space-y-4'>
              <Card>
                <CardContent className='pt-6'>
                  <div className='mb-2 text-xs font-medium uppercase text-muted-foreground'>Author</div>
                  <div className='flex items-center gap-3'>
                    <div className='flex h-10 w-10 items-center justify-center overflow-hidden rounded-full bg-muted text-sm font-semibold'>
                      {post.user.avatar ? (
                        <img src={`${API_ORIGIN}${post.user.avatar}`} alt='' className='h-full w-full object-cover' />
                      ) : (
                        post.user.name.slice(0, 2).toUpperCase()
                      )}
                    </div>
                    <div>
                      <div className='text-sm font-semibold'>{post.user.name}</div>
                      <div className='text-xs text-muted-foreground'>@{post.user.username}</div>
                    </div>
                  </div>
                  {(post.user.barangay || post.user.municipality) && (
                    <div className='mt-3 flex items-center gap-1.5 text-xs text-muted-foreground'>
                      <MapPin size={12} />
                      {[post.user.barangay, post.user.municipality].filter(Boolean).join(', ')}
                    </div>
                  )}
                </CardContent>
              </Card>
            </div>

            <Card className='lg:col-span-2'>
              <CardContent className='pt-6'>
                <div className='mb-2 text-xs font-medium uppercase text-muted-foreground'>
                  Comments ({post.comments_count})
                </div>
                {post.comments.length === 0 ? (
                  <p className='text-sm text-muted-foreground'>No comments yet.</p>
                ) : (
                  <div className='divide-y'>
                    {post.comments.map((comment) => (
                      <CommentRow key={comment.id} comment={comment} />
                    ))}
                  </div>
                )}
              </CardContent>
            </Card>
          </div>
        )}
      </Main>

      {post && (
        <PostsDeleteDialog
          open={deleteOpen}
          onOpenChange={setDeleteOpen}
          onDeleted={() => navigate({ to: '/posts' })}
          currentRow={post}
        />
      )}
    </>
  )
}
