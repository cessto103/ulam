import { createFileRoute } from '@tanstack/react-router'
import { PostDetailPage } from '@/features/posts/post-detail'

export const Route = createFileRoute('/_authenticated/posts/$postId')({
  component: PostDetailPage,
})
