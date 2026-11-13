import type { PropsWithChildren } from 'react';
import Header from '@/components/Header';

/**
 * Public site shell — the navy pill-nav header on the light canvas, matching
 * the welcome and gallery pages, with the standard max-width content area.
 */
export default function SiteLayout({ children }: PropsWithChildren) {
    return (
        <div className="gallery-surface min-h-screen bg-[#EEF3F8] text-[#293241]">
            <Header />
            <main
                id="top"
                className="mx-auto max-w-[1440px] px-5 py-8 sm:px-8 sm:py-12 lg:px-12"
            >
                {children}
            </main>
        </div>
    );
}
