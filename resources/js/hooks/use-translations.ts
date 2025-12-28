import { usePage } from '@inertiajs/react';
import { SharedData } from '@/types';

export function useTranslations() {
    const { translations } = usePage<SharedData>().props;

    const t = (key: string, replacements: Record<string, string> = {}) => {
        let translation = translations[key] || key;

        Object.keys(replacements).forEach((replacementKey) => {
            translation = translation.replace(`:${replacementKey}`, replacements[replacementKey]);
        });

        return translation;
    };

    return { t };
}
