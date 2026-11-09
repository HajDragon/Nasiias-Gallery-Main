import { Head, Link, usePage } from '@inertiajs/react';
import { ChevronLeft } from 'lucide-react';

interface GalleryIndexProps {
    artworks: {
        data: Array<{
            id: number;
            title: string | null;
            description: string | null;
            alt_text: string;
            image_url: string;
            category: { name: string; slug: string } | null;
            view_count: number;
            like_count: number;
            created_at: string | null;
        }>;
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
    categories: Array<{ id: number; name: string; slug: string }>;
    selectedCategory: string;
}

const categories = ['All', 'Abstract', 'Geometric', 'Color Field', 'Expressionism', 'Minimalism'] as const;

type Category = typeof categories[number];

function FilterBar({
    active,
    onChange,
    categories: allCategories,
}: {
    active: string;
    onChange: (category: string) => void;
    categories: Array<{ name: string; slug: string }>;
}) {
    return (
        <div className="mb-10 border-y border-[#293241]/15 py-3 sm:mb-12">
            <div
                className="-mx-5 flex gap-2 overflow-x-auto px-5 [scrollbar-width:none] sm:mx-0 sm:flex-wrap sm:px-0 [&::-webkit-scrollbar]:hidden"
                role="group"
                aria-label="Filter paintings by category"
            >
                {['All', ...allCategories.map((c) => c.name)].map((category) => {
                    const isActive = active === category || (active === '' && category === 'All');

                    return (
                        <button
                            key={category}
                            type="button"
                            onClick={() => onChange(category === 'All' ? '' : category)}
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

function ArtworkHoverCue() {
    return (
        <span
            className="artwork-hover-cue pointer-events-none absolute inset-0 z-10 grid place-items-center bg-[#293241]/10 opacity-0 group-hover/image:animate-[fadeCue_1.5s_ease-in-out_forwards]"
            aria-hidden="true"
        >
            <span className="relative flex h-24 w-24 flex-col items-center justify-center gap-1 rounded-full bg-[#EEF3F8]/95 text-[#293241] shadow-xl backdrop-blur-sm">
                <span className="artwork-click-ring absolute inset-0 rounded-full border border-[#F0F7FF]" />
                <svg viewBox="0 0 24 24" className="h-5 w-5">
                    <path
                        d="m8.5 3.5 8.8 8.8-4 .9 2.3 4-2.2 1.3-2.3-4-2.9 3.1.3-14.1Z"
                        fill="none"
                        stroke="currentColor"
                        strokeWidth="1.5"
                        strokeLinejoin="round"
                    />
                    <path
                        d="M14.5 3v-2M19 5l1.5-1.5M20.5 9h2"
                        fill="none"
                        stroke="currentColor"
                        strokeWidth="1.5"
                        strokeLinecap="round"
                    />
                </svg>
                <span className="text-[9px] font-semibold tracking-[0.14em] uppercase">
                    Click me!
                </span>
            </span>
        </span>
    );
}

export default function GalleryIndex({ artworks, categories, selectedCategory }: GalleryIndexProps) {
    const handleFilterChange = (category: string) => {
        const url = new URL(window.location.href);
        if (category) {
            url.searchParams.set('category', category);
        } else {
            url.searchParams.delete('category');
        }
        url.searchParams.set('page', '1');
        window.history.pushState({}, '', url);

        // Trigger Inertia visit
        const { router } = usePage().props as any;
        // Use the usePage hook properly
    };

    return (
        <>
            <Head title="Gallery" />
            <div className="min-h-screen bg-[#EEF3F8] text-[#293241]">
                <main id="gallery">
                    <section className="mx-auto max-w-[1440px] px-5 pt-14 pb-10 sm:px-8 sm:pt-20 sm:pb-12 lg:px-12 lg:pt-24">
                        <div className="lg:text-center">
                            <p className="mb-5 flex items-center gap-3 text-[10px] font-semibold tracking-[0.24em] uppercase sm:text-xs lg:justify-center">
                                <span className="h-px w-8 bg-[#98C1D9]" />
                                The dense hang
                            </p>
                            <h1 className="font-display max-w-4xl text-[clamp(3.75rem,10vw,8.75rem)] leading-[0.82] tracking-[-0.055em] lg:mx-auto">
                                Wall to
                                <br />
                                <span className="italic text-[#98C1D9]">wall.</span>
                            </h1>
                            <div className="mt-8 flex items-end justify-between border-t border-[#293241]/20 pt-4 lg:mx-auto lg:mt-12 lg:w-72 lg:flex-col lg:items-center lg:gap-3 lg:text-center">
                                <p className="max-w-48 text-sm leading-6 text-[#293241]/70 lg:max-w-none">
                                    Two columns on mobile, six on desktop — every work in one
                                    frame.
                                </p>
                            </div>
                        </div>
                    </section>

                    <section
                        className="mx-auto max-w-[1440px] px-5 pb-20 sm:px-8 sm:pb-28 lg:px-12"
                        aria-label="Painting masonry collection"
                    >
                        <FilterBar active={selectedCategory} onChange={handleFilterChange} categories={categories} />

                        <div className="columns-2 gap-4 sm:columns-3 sm:gap-5 lg:columns-6 lg:gap-6">
                            {artworks.data.map((artwork, index) => (
                                <article key={artwork.id} className="group bg-white break-inside-avoid hover:z-20">
                                    <Link
                                        href={`/gallery/${artwork.id}`}
                                        className="group/image relative block w-full cursor-zoom-in overflow-hidden hover:z-10"
                                        aria-label={`Open ${artwork.title || 'Untitled'}`}
                                    >
                                        <div className={`bg-white aspect-[3/4] ${index % 6 === 0 ? 'aspect-[4/5]' : index % 6 === 1 ? 'aspect-[1/1]' : index % 6 === 2 ? 'aspect-[4/5]' : index % 6 === 3 ? 'aspect-[5/6]' : index % 6 === 4 ? 'aspect-[4/3]' : ''}`}>
                                            <img
                                                src={artwork.image_url}
                                                alt={artwork.alt_text}
                                                className="h-full w-full object-contain rounded-lg transition-transform duration-700 ease-out group-hover/image:scale-[1.3]"
                                                loading="lazy"
                                            />
                                        </div>
                                        <ArtworkHoverCue />
                                    </Link>
                                    <div className="p-4 border-t border-[#293241]/15">
                                        <div className="flex items-center justify-between gap-2">
                                            <h2 className="font-display text-base leading-tight tracking-[-0.02em] sm:text-lg">
                                                {artwork.title || 'Untitled'}
                                            </h2>
                                            <span
                                                className="h-1.5 w-1.5 shrink-0 rounded-full bg-[#98C1D9] transition-transform duration-300 group-hover:scale-150"
                                                aria-hidden="true"
                                            />
                                        </div>
                                        <p className="mt-1.5 text-[9px] font-semibold tracking-[0.18em] text-[#293241]/55 uppercase">
                                            {artwork.category?.name || 'Gallery Piece'}
                                        </p>
                                    </div>
                                </article>
                            ))}
                        </div>

                        {artworks.last_page > 1 && (
                            <div className="mt-12 flex items-center justify-center gap-2">
                                {artworks.current_page > 1 && (
                                    <button
                                        onClick={() => {
                                            const url = new URL(window.location.href);
                                            url.searchParams.set('page', String(artworks.current_page - 1));
                                            window.history.pushState({}, '', url);
                                        }}
                                        className="flex h-10 w-10 items-center justify-center rounded-full border border-[#293241]/15 text-[#293241] transition-colors hover:bg-[#EEF3F8] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#293241]"
                                        aria-label="Previous page"
                                    >
                                        <ChevronLeft className="h-5 w-5" />
                                    </button>
                                )}
                                <span className="text-sm text-[#293241]/60">
                                    Page {artworks.current_page} of {artworks.last_page}
                                </span>
                                {artworks.current_page < artworks.last_page && (
                                    <button
                                        onClick={() => {
                                            const url = new URL(window.location.href);
                                            url.searchParams.set('page', String(artworks.current_page + 1));
                                            window.history.pushState({}, '', url);
                                        }}
                                        className="flex h-10 w-10 items-center justify-center rounded-full border border-[#293241]/15 text-[#293241] transition-colors hover:bg-[#EEF3F8] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#293241]"
                                        aria-label="Next page"
                                    >
                                        <ChevronLeft className="h-5 w-5 rotate-180" />
                                    </button>
                                )}
                            </div>
                        )}
                    </section>
                </main>
            </div>
        </>
    );
}
