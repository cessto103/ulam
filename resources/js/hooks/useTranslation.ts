import { usePage } from '@inertiajs/react';
import { PageProps } from '@/types';

export function useTranslation() {
    const { messages, locale } = usePage<PageProps>().props;

    function t(key: string, replacements?: Record<string, string | number>): string {
        let text = messages[key] ?? key;
        if (replacements) {
            Object.entries(replacements).forEach(([k, v]) => {
                text = text.replace(`:${k}`, String(v));
            });
        }
        return text;
    }

    return { t, locale };
}
