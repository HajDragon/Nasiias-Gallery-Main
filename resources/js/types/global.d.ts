/// <reference types="vite/client" />

import '@inertiajs/react';
import type { User } from './auth';
import type { TranslationTree } from '../lib/i18n';

declare module '@inertiajs/core' {
    interface InertiaConfig {
        sharedPageProps: {
            auth: {
                user: User | null;
            };
            flash: {
                message?: string;
                error?: string;
            };
            translations: TranslationTree;
            fallbackTranslations: TranslationTree;
            name: string;
            sidebarOpen: boolean;
            locale: string;
            availableLocales: Record<string, string>;
        };
        layoutProps: {
            title?: string;
            description?: string;
        };
    }
}

declare module '@inertiajs/react' {
    interface PageProps {
        auth: {
            user: User | null;
        };
        flash: {
            message?: string;
            error?: string;
        };
        translations: TranslationTree;
        fallbackTranslations: TranslationTree;
        name: string;
        sidebarOpen: boolean;
        locale: string;
        availableLocales: Record<string, string>;
    }
}

declare module 'react' {
    interface InputHTMLAttributes<T> extends HTMLAttributes<T> {
        passwordrules?: string;
    }
}

declare global {
    interface Window {
        __INERTIA_APP_ROOT__: string;
    }
}
