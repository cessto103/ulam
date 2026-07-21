import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import apiClient from '@/lib/api-client'
import { type EmailTemplate, type EmailTemplateSlug } from '../data/schema'

const KEY = 'admin-email-templates'

export function useEmailTemplatesQuery() {
  return useQuery({
    queryKey: [KEY],
    queryFn: async () => (await apiClient.get<{ templates: EmailTemplate[] }>('/admin/email-templates')).data.templates,
  })
}

export function useUpdateEmailTemplate() {
  const qc = useQueryClient()
  return useMutation({
    mutationFn: async ({
      slug,
      ...body
    }: {
      slug: EmailTemplateSlug
      subject: string
      intro_md: string
      note_md: string | null
      cta_label: string | null
    }) => (await apiClient.put<{ template: EmailTemplate }>(`/admin/email-templates/${slug}`, body)).data.template,
    onSuccess: () => qc.invalidateQueries({ queryKey: [KEY] }),
  })
}

export function useUploadEmailImage() {
  return useMutation({
    mutationFn: async (file: File) => {
      const form = new FormData()
      form.append('image', file)
      const { data } = await apiClient.post<{ url: string }>('/admin/email-templates/upload-image', form, {
        headers: { 'Content-Type': 'multipart/form-data' },
      })
      return data.url
    },
  })
}

export function useSendTestEmail() {
  return useMutation({
    mutationFn: async (slug: EmailTemplateSlug) =>
      (await apiClient.post<{ message: string }>(`/admin/email-templates/${slug}/test`)).data.message,
  })
}
