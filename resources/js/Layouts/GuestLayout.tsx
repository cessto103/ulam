import LanguageSwitcher from '@/Components/LanguageSwitcher';
import { useTranslation } from '@/hooks/useTranslation';
import { Link } from '@inertiajs/react';
import { PropsWithChildren } from 'react';

export default function GuestLayout({ children }: PropsWithChildren) {
    const { t } = useTranslation();

    return (
        <div className="flex min-h-screen bg-gray-50">
            {/* Left panel — branding, hidden on mobile */}
            <div className="hidden lg:flex lg:w-5/12 flex-col justify-center items-center bg-gradient-to-br from-green-600 to-green-800 px-12 text-white">
                <div className="max-w-xs text-center">
                    <div className="text-5xl font-black tracking-tight mb-2">uLam</div>
                    <div className="text-green-200 text-sm font-medium mb-8">{t('tagline')}</div>
                    <div className="space-y-4 text-left">
                        <Feature icon="🤖" title={t('ai_meal_planning')} desc={t('ai_meal_planning_desc')} />
                        <Feature icon="💰" title={t('budget_tracker')} desc={t('budget_tracker_desc')} />
                        <Feature icon="🏪" title={t('market_prices')} desc={t('market_prices_desc')} />
                        <Feature icon="👥" title={t('community')} desc={t('community_desc')} />
                    </div>
                </div>
            </div>

            {/* Right panel — form */}
            <div className="flex flex-1 flex-col justify-center items-center px-6 py-12">
                {/* Logo (mobile only) */}
                <Link href="/" className="lg:hidden mb-8 flex flex-col items-center">
                    <span className="text-3xl font-black text-green-700 tracking-tight">uLam</span>
                    <span className="text-xs text-gray-400 mt-0.5">{t('tagline')}</span>
                </Link>

                <div className="w-full max-w-sm">
                    <div className="mb-4 flex justify-end">
                        <LanguageSwitcher />
                    </div>
                    {children}
                </div>
            </div>
        </div>
    );
}

function Feature({ icon, title, desc }: { icon: string; title: string; desc: string }) {
    return (
        <div className="flex items-start gap-3">
            <span className="text-xl flex-shrink-0">{icon}</span>
            <div>
                <div className="text-sm font-semibold">{title}</div>
                <div className="text-xs text-green-200">{desc}</div>
            </div>
        </div>
    );
}
