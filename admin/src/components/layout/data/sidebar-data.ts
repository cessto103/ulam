import {
  BadgeCheck,
  Building2,
  ChefHat,
  CircleHelp,
  ClipboardList,
  CreditCard,
  Crown,
  Flag,
  Gift,
  Image as ImageIcon,
  Info,
  Landmark,
  LayoutDashboard,
  LifeBuoy,
  ListChecks,
  MessageCircle,
  MessagesSquare,
  Palette,
  Receipt,
  Rocket,
  ScrollText,
  Settings,
  ShieldAlert,
  Store,
  Tags,
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
      title: 'Monetization',
      items: [
        {
          title: 'Premium Subscriptions',
          url: '/premium-subscribers',
          icon: Crown,
        },
        {
          title: 'Seller Subscriptions',
          url: '/seller-subscriptions',
          icon: BadgeCheck,
        },
        {
          title: 'Plans & Pricing',
          url: '/monetization',
          icon: Tags,
        },
        {
          title: 'Boost Review',
          url: '/boosts',
          icon: Rocket,
        },
      ],
    },
    {
      title: 'Content',
      items: [
        {
          title: 'Legal Documents',
          url: '/legal',
          icon: ScrollText,
        },
        {
          title: 'Branding',
          url: '/branding',
          icon: Palette,
        },
        {
          title: 'Theme',
          url: '/theme',
          icon: ImageIcon,
        },
        {
          title: 'About the App',
          url: '/about',
          icon: Info,
        },
        {
          title: 'Technical Guide',
          url: '/technical',
          icon: Wrench,
        },
      ],
    },
    {
      title: 'Gamification',
      items: [
        {
          title: 'Tasks',
          url: '/tasks',
          icon: ListChecks,
        },
        {
          title: 'Reward Tiers',
          url: '/reward-tiers',
          icon: Gift,
        },
      ],
    },
    {
      title: 'Support',
      items: [
        {
          title: 'Support Tickets',
          url: '/support-tickets',
          icon: LifeBuoy,
        },
        {
          title: 'FAQs',
          url: '/faqs',
          icon: CircleHelp,
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
          title: 'Store Comments & Ratings',
          url: '/tindahan-comments',
          icon: MessageCircle,
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
          title: 'Tingi Staple Prices',
          url: '/staple-prices',
          icon: Receipt,
        },
      ],
    },
    {
      title: 'Moderation',
      items: [
        {
          title: 'Reported Listings',
          url: '/listing-reports',
          icon: Flag,
        },
        {
          title: 'Content Reports',
          url: '/content-reports',
          icon: ShieldAlert,
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
        {
          title: 'Recipe Comments',
          url: '/recipe-comments',
          icon: MessageCircle,
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
        {
          title: 'Connection Labels',
          url: '/connection-labels',
          icon: Tags,
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
