import { router, usePage } from '@inertiajs/react';
import { Languages } from 'lucide-react';
import { useTranslation } from '@/lib/i18n';
import { cn } from '@/lib/utils';

const shortLabels: Record<string, string> = {
    en: 'EN',
    uk: 'UA',
};

export default function LanguageSwitcher({
    className,
}: {
    className?: string;
}) {
    const { locale, availableLocales } = usePage().props;
    const { t } = useTranslation();

    const changeLocale = (code: string) => {
        if (code === locale) {
            return;
        }

        router.post(`/locale/${code}`, {}, { preserveScroll: true });
    };

    return (
        <div
            className={cn(
                'flex shrink-0 items-center gap-0.5 rounded-full border border-[#F0F7FF]/15 p-1',
                className,
            )}
            role="group"
            aria-label={t('common.language')}
        >
            <Languages
                className="ml-1.5 h-3.5 w-3.5 opacity-60"
                aria-hidden="true"
            />
            {Object.keys(availableLocales).map((code) => (
                <button
                    key={code}
                    type="button"
                    onClick={() => changeLocale(code)}
                    aria-pressed={locale === code}
                    aria-label={availableLocales[code]}
                    title={availableLocales[code]}
                    className={cn(
                        'rounded-full px-2 py-1 text-[10px] font-semibold tracking-[0.14em] uppercase transition-colors',
                        locale === code
                            ? 'bg-[#EEF3F8] text-[#293241]'
                            : 'text-[#F0F7FF] hover:bg-[#EEF3F8]/10',
                    )}
                >
                    {shortLabels[code] ?? code.toUpperCase()}
                </button>
            ))}
        </div>
    );
}
