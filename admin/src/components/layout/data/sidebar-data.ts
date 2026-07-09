import {
  Bell,
  Building2,
  ChefHat,
  ClipboardList,
  Construction,
  Bug,
  FileX,
  Flag,
  HelpCircle,
  Landmark,
  LayoutDashboard,
  Lock,
  MessageCircle,
  MessagesSquare,
  Monitor,
  Palette,
  Receipt,
  ServerOff,
  Settings,
  ShieldCheck,
  Store,
  UserCog,
  UserX,
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
      title: 'Pages',
      items: [
        {
          title: 'Auth',
          icon: ShieldCheck,
          items: [
            {
              title: 'Sign In',
              url: '/sign-in',
            },
            {
              title: 'Sign In (2 Col)',
              url: '/sign-in-2',
            },
            {
              title: 'Sign Up',
              url: '/sign-up',
            },
            {
              title: 'Forgot Password',
              url: '/forgot-password',
            },
            {
              title: 'OTP',
              url: '/otp',
            },
          ],
        },
        {
          title: 'Errors',
          icon: Bug,
          items: [
            {
              title: 'Unauthorized',
              url: '/errors/unauthorized',
              icon: Lock,
            },
            {
              title: 'Forbidden',
              url: '/errors/forbidden',
              icon: UserX,
            },
            {
              title: 'Not Found',
              url: '/errors/not-found',
              icon: FileX,
            },
            {
              title: 'Internal Server Error',
              url: '/errors/internal-server-error',
              icon: ServerOff,
            },
            {
              title: 'Maintenance Error',
              url: '/errors/maintenance-error',
              icon: Construction,
            },
          ],
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
            {
              title: 'Notifications',
              url: '/settings/notifications',
              icon: Bell,
            },
            {
              title: 'Display',
              url: '/settings/display',
              icon: Monitor,
            },
          ],
        },
        {
          title: 'Help Center',
          url: '/help-center',
          icon: HelpCircle,
        },
      ],
    },
  ],
}
