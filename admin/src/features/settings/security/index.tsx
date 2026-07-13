import { ContentSection } from '../components/content-section'
import { TwoFactorForm } from './two-factor-form'

export function SettingsSecurity() {
  return (
    <ContentSection
      title='Security'
      desc='Two-factor authentication for your admin sign-in.'
    >
      <TwoFactorForm />
    </ContentSection>
  )
}
