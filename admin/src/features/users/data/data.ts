import { Shield, User as UserIcon } from 'lucide-react'

export const roles = [
  { label: 'Admin', value: 'admin', icon: Shield },
  { label: 'User', value: 'user', icon: UserIcon },
] as const

export const plans = [
  { label: 'Premium', value: 'premium' },
  { label: 'Libre', value: 'libre' },
] as const
