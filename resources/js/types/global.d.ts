import type { Auth } from '@/types/auth';
import type { TranslationTree } from '@/lib/i18n';

declare module 'react' {
    interface InputHTMLAttributes<T> {
        passwordrules?: string;
    }
}

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: {
            name: string;
            auth: Auth;
            sidebarOpen: boolean;
            locale: string;
            availableLocales: Record<string, string>;
            translations: TranslationTree;
            fallbackTranslations: TranslationTree;
            [key: string]: unknown;
        };
    }
}
