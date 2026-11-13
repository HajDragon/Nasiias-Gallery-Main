import { createInertiaApp } from '@inertiajs/react';
import NewArtworkOptIn from '@/components/new-artwork-opt-in';
import { Toaster } from '@/components/ui/sonner';
import { TooltipProvider } from '@/components/ui/tooltip';
import { initializeTheme } from '@/hooks/use-appearance';
import AppLayout from '@/layouts/app-layout';
import AuthLayout from '@/layouts/auth-layout';
import SettingsLayout from '@/layouts/settings/layout';
import SiteLayout from '@/layouts/site-layout';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

/**
 * Outermost layout for every page. It renders inside Inertia's page-context
 * provider, which lets NewArtworkOptIn read shared auth props with usePage —
 * including on pages that previously had no layout at all (welcome, gallery).
 */
const PromptLayout = ({ children }: { children: React.ReactNode }) => (
    <>
        <NewArtworkOptIn />
        {children}
    </>
);

void createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    layout: (name) => {
        switch (true) {
            case name === 'welcome':
            case name === 'design-test':
            case name === 'gallery/index':
            case name === 'gallery/show':
                return [PromptLayout];
            case name.startsWith('auth/'):
                return [PromptLayout, AuthLayout];
            case name.startsWith('settings/'):
                return [PromptLayout, SiteLayout, SettingsLayout];
            default:
                return [PromptLayout, AppLayout];
        }
    },
    strictMode: true,
    withApp(app) {
        return (
            <TooltipProvider delayDuration={0}>
                {app}
                <Toaster />
            </TooltipProvider>
        );
    },
    progress: {
        color: '#4B5563',
    },
});

// This will set light / dark mode on load...
initializeTheme();
