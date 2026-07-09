import { router } from '@inertiajs/react';
import { useTranslation } from '@/hooks/useTranslation';

export default function LanguageSwitcher({ className = '' }: { className?: string }) {
    const { locale } = useTranslation();

    const toggle = () => {
        const next = locale === 'en' ? 'tl' : 'en';
        router.post(route('language.switch'), { locale: next }, {
            preserveScroll: true,
            preserveState: false,
        });
    };

    return (
        <button
            onClick={toggle}
            title={locale === 'en' ? 'Switch to Filipino' : 'Switch to English'}
            className={`inline-flex items-center gap-1 rounded-full border border-gray-200 bg-white px-2.5 py-1 text-xs font-medium text-gray-600 transition-colors hover:border-green-400 hover:text-green-700 ${className}`}
        >
            {locale === 'en' ? (
                <><span>🇵🇭</span><span>Filipino</span></>
            ) : (
                <><span>🇺🇸</span><span>English</span></>
            )}
        </button>
    );
}
