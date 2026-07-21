import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import apiClient from '@/lib/api-client'
import { type User } from '../data/schema'

type UsersSearch = {
  page?: number
  pageSize?: number
  search?: string
  role?: string[]
  plan?: string[]
  banned?: string[]
}

type PaginatedResponse<T> = {
  data: T[]
  current_page: number
  last_page: number
  total: number
}

export function useUserQuery(id: number | null) {
  return useQuery({
    queryKey: ['admin-user', id],
    queryFn: async () => {
      const { data } = await apiClient.get<{ user: User }>(`/admin/users/${id}`)
      return data.user
    },
    enabled: id != null,
  })
}

export function useUsersQuery(search: UsersSearch) {
  return useQuery({
    queryKey: ['admin-users', search],
    queryFn: async () => {
      const { data } = await apiClient.get<PaginatedResponse<User>>('/admin/users', {
        params: {
          page: search.page,
          per_page: search.pageSize,
          search: search.search || undefined,
          role: search.role?.[0],
          plan: search.plan?.[0],
          banned: search.banned && search.banned.length > 0 ? true : undefined,
        },
      })
      return data
    },
    placeholderData: (prev) => prev,
  })
}

type CreateUserInput = {
  name: string
  username: string
  email: string
  password: string
  role: string
  plan: string
}

export function useCreateUser() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (input: CreateUserInput) => apiClient.post('/admin/users', input),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['admin-users'] }),
  })
}

type UpdateUserInput = Partial<CreateUserInput> & { id: number }

export function useUpdateUser() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: ({ id, ...input }: UpdateUserInput) =>
      apiClient.patch(`/admin/users/${id}`, input),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['admin-users'] }),
  })
}

export function useDeleteUser() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (id: number) => apiClient.delete(`/admin/users/${id}`),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['admin-users'] }),
  })
}

export function useBanUser() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: ({ id, ban_reason }: { id: number; ban_reason: string }) =>
      apiClient.post(`/admin/users/${id}/ban`, { ban_reason }),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['admin-users'] }),
  })
}

export function useUnbanUser() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (id: number) => apiClient.post(`/admin/users/${id}/unban`),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['admin-users'] }),
  })
}
