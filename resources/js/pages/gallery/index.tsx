'use client';

import { Head, Link, router } from '@inertiajs/react';
import { ChevronLeft } from 'lucide-react';
import Header from '@/components/Header';
import FilterBar from '@/components/FilterBar';
import ArtworkHoverCue from '@/components/ArtworkHoverCue';
import ContactFooter from '@/components/ContactFooter';

interface Artwork {
    id: number;
    title: string | null;
    description: string | null;
    alt_text: string;
    image_url: string;
    category: { name: string; slug: string } | null;
    view_count: number;
    like_count: number;
    created_at: string | null;
}

interface GalleryIndexProps {
    artworks: {
        data: Artwork[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
    categories: Array<{ id: number; name: string; slug: string }>;
    selectedCategory: string;
}

const masonryAspects = [
    'aspect-[3/4]',
    'aspect-[1/1]',
    'aspect-[4/5]',
    'aspect-[5/6]',
    'aspect-[3/4]',
    'aspect-[4/3]',
] as const;

export default function GalleryIndex({
    artworks,
    categories,
    selectedCategory,
}: GalleryIndexProps) {
    const handleFilterChange = (category: string) => {
        const url = new URL(window.location.href);
        if (category) {
            url.searchParams.set('category', category);
        } else {
            url.searchParams.delete('category');
        }
        url.searchParams.set('page', '1');
        window.history.pushState({}, '', url);

        router.visit(url.pathname + url.search, { preserveState: true });
    };

    return (
        <>
            <Head title="Gallery" />
            <div className="min-h-screen bg-[#EEF3F8] text-[#293241]">
                <Header />
                <main id="gallery">
                    <section className="mx-auto max-w-[1440px] px-5 pt-14 pb-10 sm:px-8 sm:pt-20 sm:pb-12 lg:px-12 lg:pt-24">
                        <div className="lg:text-center">
                            <p className="mb-5 flex items-center gap-3 text-[10px] font-semibold tracking-[0.24em] uppercase sm:text-xs lg:justify-center">
                                <span className="h-px w-8 bg-[#98C1D9]" />
                                The dense hang
                            </p>
                            <h1 className="max-w-4xl font-display text-[clamp(3.75rem,10vw,8.75rem)] leading-[0.82] tracking-[-0.055em] lg:mx-auto">
                                Wall to
                                <br />
                                <span className="text-[#98C1D9] italic">
                                    wall.
                                </span>
                            </h1>
                            <div className="mt-8 flex items-end justify-between border-t border-[#293241]/20 pt-4 lg:mx-auto lg:mt-12 lg:w-72 lg:flex-col lg:items-center lg:gap-3 lg:text-center">
                                <p className="max-w-48 text-sm leading-6 text-[#293241]/70 lg:max-w-none">
                                    Art washes away from the soul the dust of
                                    everyday life
                                </p>
                            </div>
                        </div>
                    </section>

                    <section
                        className="mx-auto max-w-[1440px] px-5 pb-20 sm:px-8 sm:pb-28 lg:px-12"
                        aria-label="Painting masonry collection"
                    >
                        <FilterBar
                            active={selectedCategory}
                            onChange={handleFilterChange}
                            categories={categories.map(
                                (category) => category.name,
                            )}
                        />

                        <div className="columns-2 gap-4 sm:columns-3 sm:gap-5 lg:columns-6 lg:gap-6">
                            {artworks.data.map((artwork, index) => (
                                <article
                                    key={artwork.id}
                                    className="group break-inside-avoid bg-white hover:z-20"
                                >
                                    <Link
                                        href={`/gallery/${artwork.id}`}
                                        className="group/image relative block w-full cursor-zoom-in overflow-hidden hover:z-10"
                                        aria-label={`Open ${artwork.title || 'Untitled'}`}
                                    >
                                        <div
                                            className={`bg-white ${masonryAspects[index % masonryAspects.length]}`}
                                        >
                                            <img
                                                src={artwork.image_url}
                                                alt={artwork.alt_text}
                                                className="h-full w-full rounded-lg object-contain transition-transform duration-700 ease-out group-hover/image:scale-[1.3]"
                                                loading="lazy"
                                            />
                                        </div>
                                        <ArtworkHoverCue />
                                    </Link>
                                    <div className="border-t border-[#293241]/15 p-4">
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
                                            {artwork.category?.name ||
                                                'Gallery Piece'}
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
                                            const url = new URL(
                                                window.location.href,
                                            );
                                            url.searchParams.set(
                                                'page',
                                                String(
                                                    artworks.current_page - 1,
                                                ),
                                            );
                                            router.visit(
                                                url.pathname + url.search,
                                                { preserveState: true },
                                            );
                                        }}
                                        className="flex h-10 w-10 items-center justify-center rounded-full border border-[#293241]/15 text-[#293241] transition-colors hover:bg-[#EEF3F8] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#293241]"
                                        aria-label="Previous page"
                                    >
                                        <ChevronLeft className="h-5 w-5" />
                                    </button>
                                )}
                                <span className="text-sm text-[#293241]/60">
                                    Page {artworks.current_page} of{' '}
                                    {artworks.last_page}
                                </span>
                                {artworks.current_page < artworks.last_page && (
                                    <button
                                        onClick={() => {
                                            const url = new URL(
                                                window.location.href,
                                            );
                                            url.searchParams.set(
                                                'page',
                                                String(
                                                    artworks.current_page + 1,
                                                ),
                                            );
                                            router.visit(
                                                url.pathname + url.search,
                                                { preserveState: true },
                                            );
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
                <ContactFooter />
            </div>
        </>
    );
}
