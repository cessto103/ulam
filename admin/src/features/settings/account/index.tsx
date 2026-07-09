import { ContentSection } from '../components/content-section'
import { AccountForm } from './account-form'

export function SettingsAccount() {
  return (
    <ContentSection
      title='Account'
      desc='Change the password you use to sign in to the admin panel and the app.'
    >
      <AccountForm />
    </ContentSection>
  )
}
