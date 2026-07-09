import {
  Building2,
  ChefHat,
  ClipboardList,
  CreditCard,
  Flag,
  Landmark,
  LayoutDashboard,
  MessageCircle,
  MessagesSquare,
  Palette,
  Receipt,
  Settings,
  Store,
  UserCog,
  Users,
  Wrench,
} from 'lucide-react'
import { Logo } from '@/assets/logo'
import { type SidebarData } from '../types'

export const sidebarData: SidebarData = {
  user: {
    name: 'uLam Admin',
    email: 'admin@ulam.app',
    avatar: `${import.meta.env.BASE_URL}avatars/shadcn.jpg`,
  },
  teams: [
    {
      name: 'uLam Admin',
      logo: Logo,
      plan: 'Household Budgeting',
    },
  ],
  navGroups: [
    {
      title: 'General',
      items: [
        {
          title: 'Dashboard',
          url: '/',
          icon: LayoutDashboard,
        },
        {
          title: 'Payments',
          url: '/payments',
          icon: CreditCard,
        },
      ],
    },
    {
      title: 'Community',
      items: [
        {
          title: 'Posts',
          url: '/posts',
          icon: MessagesSquare,
        },
        {
          title: 'Comments',
          url: '/comments',
          icon: MessageCircle,
        },
      ],
    },
    {
      title: 'Prices & Markets',
      items: [
        {
          title: 'Markets',
          url: '/markets',
          icon: Building2,
        },
        {
          title: 'Stores & Stalls',
          url: '/tindahan',
          icon: Store,
        },
        {
          title: 'Market Prices',
          url: '/market-prices',
          icon: Receipt,
        },
        {
          title: 'Community Price Reports',
          url: '/community-price-reports',
          icon: ClipboardList,
        },
        {
          title: 'Government Price References',
          url: '/government-price-references',
          icon: Landmark,
        },
        {
          title: 'Reported Listings',
          url: '/listing-reports',
          icon: Flag,
        },
      ],
    },
    {
      title: 'Recipes',
      items: [
        {
          title: 'Recipes',
          url: '/recipes',
          icon: ChefHat,
        },
      ],
    },
    {
      title: 'Users',
      items: [
        {
          title: 'Users',
          url: '/users',
          icon: Users,
        },
      ],
    },
    {
      title: 'Other',
      items: [
        {
          title: 'Settings',
          icon: Settings,
          items: [
            {
              title: 'Profile',
              url: '/settings',
              icon: UserCog,
            },
            {
              title: 'Account',
              url: '/settings/account',
              icon: Wrench,
            },
            {
              title: 'Appearance',
              url: '/settings/appearance',
              icon: Palette,
            },
          ],
        },
      ],
    },
  ],
}
