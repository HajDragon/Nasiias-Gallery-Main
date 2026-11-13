import { Link } from '@inertiajs/react';
import type { PropsWithChildren } from 'react';
import { useCurrentUrl } from '@/hooks/use-current-url';
import { useTranslation } from '@/lib/i18n';
import { toUrl } from '@/lib/utils';
import { edit as editAppearance } from '@/routes/appearance';
import { edit } from '@/routes/profile';
import { edit as editSecurity } from '@/routes/security';
import type { NavItem } from '@/types';

const sidebarNavItems: NavItem[] = [
    {
        title: 'settings.profile',
        href: edit(),
        icon: null,
    },
    {
        title: 'settings.security',
        href: editSecurity(),
        icon: null,
    },
    {
        title: 'settings.appearance',
        href: editAppearance(),
        icon: null,
    },
];

export default function SettingsLayout({ children }: PropsWithChildren) {
    const { isCurrentOrParentUrl } = useCurrentUrl();
    const { t } = useTranslation();

    return (
        <div className="py-2">
            <header className="mb-8 space-y-2">
                <h1 className="font-display text-[clamp(2.25rem,6vw,3.5rem)] leading-[0.95] tracking-[-0.04em]">
                    {t('settings.heading_prefix')}{' '}
                    <span className="text-[#98C1D9] italic">
                        {t('settings.heading_accent')}
                    </span>
                </h1>
                <p className="max-w-xl text-sm leading-7 text-[#293241]/70 sm:text-base">
                    {t('settings.description')}
                </p>
            </header>

            <div className="flex flex-col gap-6 lg:flex-row lg:gap-12">
                <aside className="lg:w-48 lg:shrink-0">
                    <nav
                        className="flex flex-wrap gap-2 lg:flex-col lg:items-start"
                        aria-label={t('settings.title')}
                    >
                        {sidebarNavItems.map((item, index) => {
                            const active = isCurrentOrParentUrl(item.href);

                            return (
                                <Link
                                    key={`${toUrl(item.href)}-${index}`}
                                    href={item.href}
                                    aria-current={active ? 'page' : undefined}
                                    className={`shrink-0 rounded-full border px-4 py-2 text-[10px] font-semibold tracking-[0.15em] uppercase transition-colors sm:text-[11px] ${
                                        active
                                            ? 'border-[#293241] bg-[#293241] text-[#F0F7FF]'
                                            : 'border-[#293241]/20 text-[#293241] hover:border-[#98C1D9] hover:bg-[#98C1D9]/20'
                                    }`}
                                >
                                    {t(item.title)}
                                </Link>
                            );
                        })}
                    </nav>
                </aside>

                <div className="min-w-0 flex-1">
                    <section className="max-w-2xl rounded-xl border border-[#293241]/15 bg-white p-6 sm:p-8">
                        {children}
                    </section>
                </div>
            </div>
        </div>
    );
}
