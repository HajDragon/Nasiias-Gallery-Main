import { useTranslation } from '@/lib/i18n';

interface FilterBarProps {
    /** Active category name; empty string means "All". */
    active: string;
    onChange: (category: string) => void;
    /** Category names without the leading "All" entry. */
    categories: string[];
}

export default function FilterBar({
    active,
    onChange,
    categories,
}: FilterBarProps) {
    const { t } = useTranslation();
    const allLabel = t('common.all');

    return (
        <div className="mb-10 border-y border-[#293241]/15 py-3 sm:mb-12">
            <div
                className="-mx-5 flex [scrollbar-width:none] gap-2 overflow-x-auto px-5 sm:mx-0 sm:flex-wrap sm:px-0 [&::-webkit-scrollbar]:hidden"
                role="group"
                aria-label={t('gallery.filter_aria')}
            >
                {[allLabel, ...categories].map((category) => {
                    const isAll = category === allLabel;
                    const isActive = isAll
                        ? active === ''
                        : active === category;

                    return (
                        <button
                            key={category}
                            type="button"
                            onClick={() => onChange(isAll ? '' : category)}
                            className={`shrink-0 rounded-full border px-4 py-2 text-[10px] font-semibold tracking-[0.15em] uppercase transition-colors sm:text-[11px] ${
                                isActive
                                    ? 'border-[#293241] bg-[#293241] text-[#F0F7FF]'
                                    : 'border-[#293241]/20 text-[#293241] hover:border-[#98C1D9] hover:bg-[#98C1D9]/20'
                            }`}
                            aria-pressed={isActive}
                        >
                            {category}
                        </button>
                    );
                })}
            </div>
        </div>
    );
}
