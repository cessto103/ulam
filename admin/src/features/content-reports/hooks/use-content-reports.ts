import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import apiClient from '@/lib/api-client'
import { type ContentReport } from '../data/schema'

type ContentReportsSearch = {
  page?: number
  pageSize?: number
  status?: string[]
  content_type?: string[]
}

type PaginatedResponse<T> = {
  data: T[]
  current_page: number
  last_page: number
  total: number
}

const QUERY_KEY = 'admin-content-reports'

export function useContentReportsQuery(search: ContentReportsSearch) {
  return useQuery({
    queryKey: [QUERY_KEY, search],
    queryFn: async () => {
      const { data } = await apiClient.get<PaginatedResponse<ContentReport>>(
        '/admin/content-reports',
        {
          params: {
            page: search.page,
            per_page: search.pageSize,
            status: search.status?.[0],
            content_type: search.content_type?.[0],
          },
        }
      )
      return data
    },
    placeholderData: (prev) => prev,
  })
}

/** Full detail incl. strike history -- fetched on-demand when a Warn/Restrict/Ban dialog opens. */
export function useContentReport(id: number | null) {
  return useQuery({
    queryKey: [QUERY_KEY, 'show', id],
    queryFn: async () => {
      const { data } = await apiClient.get<{ report: ContentReport }>(`/admin/content-reports/${id}`)
      return data.report
    },
    enabled: id !== null,
  })
}

export function useDeleteContentReport() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (id: number) => apiClient.delete(`/admin/content-reports/${id}`),
    onSuccess: () => qc.invalidateQueries({ queryKey: [QUERY_KEY] }),
  })
}

function useModerationAction(action: 'warn' | 'restrict' | 'ban') {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: ({ id, reason }: { id: number; reason?: string }) =>
      apiClient.post<{ report: ContentReport; message?: string }>(
        `/admin/content-reports/${id}/${action}`,
        { reason }
      ),
    onSuccess: () => qc.invalidateQueries({ queryKey: [QUERY_KEY] }),
  })
}

export const useWarnReportedUser = () => useModerationAction('warn')
export const useRestrictReportedUser = () => useModerationAction('restrict')
export const useBanReportedUser = () => useModerationAction('ban')

export function useDismissContentReport() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: (id: number) =>
      apiClient.post<{ report: ContentReport }>(`/admin/content-reports/${id}/dismiss`),
    onSuccess: () => qc.invalidateQueries({ queryKey: [QUERY_KEY] }),
  })
}
