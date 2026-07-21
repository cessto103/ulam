import { z } from 'zod'

export const emailTemplateSlugs = [
  'welcome',
  'email_verification_otp',
  'password_reset_otp',
  'secondary_email_otp',
] as const

export type EmailTemplateSlug = (typeof emailTemplateSlugs)[number]

const emailTemplateSchema = z.object({
  id: z.number(),
  slug: z.enum(emailTemplateSlugs),
  subject: z.string(),
  intro_md: z.string(),
  note_md: z.string().nullable(),
  cta_label: z.string().nullable(),
  updated_at: z.string(),
})
export type EmailTemplate = z.infer<typeof emailTemplateSchema>

export const TEMPLATE_META: Record<
  EmailTemplateSlug,
  { label: string; description: string; hasCode: boolean; hasCta: boolean; placeholders: string[] }
> = {
  welcome: {
    label: 'Welcome (onboarding)',
    description: 'Sent right after a new account is created.',
    hasCode: false,
    hasCta: true,
    placeholders: ['{{name}}'],
  },
  email_verification_otp: {
    label: 'Verify Email',
    description: 'Sent with a one-time code to verify a new account’s email.',
    hasCode: true,
    hasCta: false,
    placeholders: ['{{name}}', '{{code}}'],
  },
  password_reset_otp: {
    label: 'Reset Password',
    description: 'Sent with a one-time code when a user requests a password reset.',
    hasCode: true,
    hasCta: false,
    placeholders: ['{{name}}', '{{code}}'],
  },
  secondary_email_otp: {
    label: 'Verify Secondary Email',
    description: 'Sent with a one-time code when a user adds a backup email address.',
    hasCode: true,
    hasCta: false,
    placeholders: ['{{name}}', '{{code}}'],
  },
}
