import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { useTranslation } from '@/hooks/useTranslation';
import { PageProps } from '@/types';
import { Head, usePage } from '@inertiajs/react';

export default function Dashboard() {
    const { auth } = usePage<PageProps>().props;
    const { t } = useTranslation();

    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center justify-between">
                    <div>
                        <h2 className="text-base font-bold text-gray-900">
                            {t('dashboard_greeting', { name: auth.user.name })} 👋
                        </h2>
                        <p className="text-xs text-gray-500 mt-0.5">{t('dashboard_subtitle')}</p>
                    </div>
                    {auth.user.plan !== 'premium' && (
                        <span className="rounded-full bg-green-50 px-2.5 py-1 text-xs font-medium text-green-700">
                            {t('free_plan')}
                        </span>
                    )}
                </div>
            }
        >
            <Head title="Home — uLam" />

            <div className="py-6">
                <div className="mx-auto max-w-2xl px-4 sm:px-6 space-y-4">
                    <div className="grid grid-cols-2 gap-3">
                        <ActionCard
                            icon="🤖"
                            title={t('generate_meal_plan')}
                            desc={t('generate_meal_plan_desc')}
                            color="bg-green-50 border-green-100"
                        />
                        <ActionCard
                            icon="💰"
                            title={t('log_expenses')}
                            desc={t('log_expenses_desc')}
                            color="bg-blue-50 border-blue-100"
                        />
                        <ActionCard
                            icon="🏪"
                            title={t('market_prices_action')}
                            desc={t('market_prices_action_desc')}
                            color="bg-orange-50 border-orange-100"
                        />
                        <ActionCard
                            icon="👥"
                            title={t('community_action')}
                            desc={t('community_action_desc')}
                            color="bg-purple-50 border-purple-100"
                        />
                    </div>

                    <div className="rounded-xl border border-green-100 bg-green-50 p-4">
                        <p className="text-xs font-semibold text-green-800">💡 {t('ulam_tip_label')}</p>
                        <p className="mt-1 text-xs text-green-700">{t('ulam_tip_text')}</p>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}

function ActionCard({ icon, title, desc, color }: { icon: string; title: string; desc: string; color: string }) {
    return (
        <button className={`flex flex-col gap-1.5 rounded-xl border p-4 text-left transition-opacity hover:opacity-80 ${color}`}>
            <span className="text-xl">{icon}</span>
            <span className="text-xs font-semibold text-gray-900">{title}</span>
            <span className="text-xs text-gray-500">{desc}</span>
        </button>
    );
}
