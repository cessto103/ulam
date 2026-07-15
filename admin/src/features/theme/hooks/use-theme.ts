import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import apiClient from '@/lib/api-client'

export type SectionConfig = {
  image?: string | null
  focal_x?: number
  focal_y?: number
  fit?: 'cover' | 'contain'
  overlay_colors?: string[]
  overlay_opacity?: number
}

export type ThemePreset = {
  id: number
  name: string
  slug: string
  sections: Record<string, SectionConfig>
  is_active: boolean
  updated_at: string
}

const KEY = 'admin-theme-presets'

export function usePresetsQuery() {
  return useQuery({
    queryKey: [KEY],
    queryFn: async () => (await apiClient.get<{ presets: ThemePreset[] }>('/admin/theme/presets')).data.presets,
  })
}

export function useCreatePreset() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: async (body: { name: string; duplicate_from?: number }) =>
      (await apiClient.post<{ preset: ThemePreset }>('/admin/theme/presets', body)).data.preset,
    onSuccess: () => qc.invalidateQueries({ queryKey: [KEY] }),
  })
}

export function useRenamePreset() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: async ({ id, name }: { id: number; name: string }) =>
      (await apiClient.patch<{ preset: ThemePreset }>(`/admin/theme/presets/${id}`, { name })).data.preset,
    onSuccess: () => qc.invalidateQueries({ queryKey: [KEY] }),
  })
}

export function useActivatePreset() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: async (id: number) =>
      (await apiClient.post<{ preset: ThemePreset }>(`/admin/theme/presets/${id}/activate`)).data.preset,
    onSuccess: () => qc.invalidateQueries({ queryKey: [KEY] }),
  })
}

export function useDeletePreset() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: async (id: number) => apiClient.delete(`/admin/theme/presets/${id}`),
    onSuccess: () => qc.invalidateQueries({ queryKey: [KEY] }),
  })
}

export function useUploadSectionImage() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: async ({ presetId, section, file }: { presetId: number; section: string; file: File }) => {
      const form = new FormData()
      form.append('image', file)
      return (await apiClient.post<{ preset: ThemePreset }>(`/admin/theme/presets/${presetId}/${section}/image`, form, {
        headers: { 'Content-Type': 'multipart/form-data' },
      })).data.preset
    },
    onSuccess: () => qc.invalidateQueries({ queryKey: [KEY] }),
  })
}

export function useUpdateSection() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: async ({ presetId, section, patch }: { presetId: number; section: string; patch: Partial<SectionConfig> }) =>
      (await apiClient.patch<{ preset: ThemePreset }>(`/admin/theme/presets/${presetId}/${section}`, patch)).data.preset,
    onSuccess: () => qc.invalidateQueries({ queryKey: [KEY] }),
  })
}

export function useResetSection() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: async ({ presetId, section }: { presetId: number; section: string }) =>
      (await apiClient.delete<{ preset: ThemePreset }>(`/admin/theme/presets/${presetId}/${section}`)).data.preset,
    onSuccess: () => qc.invalidateQueries({ queryKey: [KEY] }),
  })
}
