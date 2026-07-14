import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import apiClient from '@/lib/api-client'

export type LegalDoc = {
  id: number
  slug: string
  title: string
  published_version: string | null
  published_at: string | null
  published_by: string | null
  acceptance_count: number
  versions_count: number
  suggested_next_version: string
}

export type LegalVersion = {
  id: number
  document_slug: string | null
  version: string
  changelog: string
  status: 'draft' | 'published' | 'archived'
  author: string | null
  published_by: string | null
  published_at: string | null
  created_at: string
  updated_at: string
  acceptance_count: number | null
  content_md?: string
}

export function useLegalDocuments() {
  return useQuery({
    queryKey: ['admin-legal-docs'],
    queryFn: async () => (await apiClient.get<{ documents: LegalDoc[] }>('/admin/legal-documents')).data.documents,
  })
}

export function useLegalVersions(slug: string, filters: { status?: string; search?: string }) {
  return useQuery({
    queryKey: ['admin-legal-versions', slug, filters],
    queryFn: async () =>
      (await apiClient.get<{ versions: LegalVersion[] }>(`/admin/legal-documents/${slug}/versions`, { params: filters })).data.versions,
    placeholderData: (prev) => prev,
  })
}

export function useLegalVersion(id: number | null) {
  return useQuery({
    queryKey: ['admin-legal-version', id],
    queryFn: async () => (await apiClient.get<{ version: LegalVersion }>(`/admin/legal-versions/${id}`)).data.version,
    enabled: id !== null,
  })
}

function invalidateAll(qc: ReturnType<typeof useQueryClient>) {
  qc.invalidateQueries({ queryKey: ['admin-legal-docs'] })
  qc.invalidateQueries({ queryKey: ['admin-legal-versions'] })
  qc.invalidateQueries({ queryKey: ['admin-legal-version'] })
}

export function useCreateDraft(slug: string) {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: async (input: { duplicate_from?: number; version?: string }) =>
      (await apiClient.post<{ version: LegalVersion }>(`/admin/legal-documents/${slug}/versions`, input)).data.version,
    onSuccess: () => invalidateAll(qc),
  })
}

export function useUpdateDraft() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: async ({ id, ...input }: { id: number; version?: string; changelog?: string; content_md?: string }) =>
      (await apiClient.patch<{ version: LegalVersion }>(`/admin/legal-versions/${id}`, input)).data.version,
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['admin-legal-versions'] })
    },
  })
}

export function usePublishVersion() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: async (id: number) => (await apiClient.post(`/admin/legal-versions/${id}/publish`)).data,
    onSuccess: () => invalidateAll(qc),
  })
}

export function useArchiveVersion() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: async (id: number) => (await apiClient.post(`/admin/legal-versions/${id}/archive`)).data,
    onSuccess: () => invalidateAll(qc),
  })
}

export function useDeleteDraft() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: async (id: number) => (await apiClient.delete(`/admin/legal-versions/${id}`)).data,
    onSuccess: () => invalidateAll(qc),
  })
}
