'use client';

import { Head, Link, router } from '@inertiajs/react';
import { ChevronLeft } from 'lucide-react';
import Header from '@/components/Header';
import FilterBar from '@/components/FilterBar';
import ArtworkHoverCue from '@/components/ArtworkHoverCue';
import { ArtworkModal } from '@/components/ArtworkModal';
import ContactFooter from '@/components/ContactFooter';
import { useArtworkModal } from '@/hooks/use-artwork-modal';
import { useTranslation } from '@/lib/i18n';

interface Artwork {
    id: number;
    title: string | null;
    alt_text: string;
    image_url: string;
    image_srcset?: string | null;
    category: { name: string; slug: string } | null;
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
    const { t } = useTranslation();
    const modal = useArtworkModal();

    // FilterBar works with category names, but the URL/back-end use slugs.
    const activeCategoryName =
        selectedCategory === ''
            ? ''
            : (categories.find((category) => category.slug === selectedCategory)
                  ?.name ?? '');

    const handleFilterChange = (categoryName: string) => {
        const slug = categories.find(
            (category) => category.name === categoryName,
        )?.slug;

        router.get('/gallery', slug ? { category: slug } : {}, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    return (
        <>
            <Head title={t('gallery.title')} />
            <div className="min-h-screen bg-background text-foreground">
                <Header />
                <main id="gallery">
                    <section className="mx-auto max-w-[1440px] px-5 pt-14 pb-10 sm:px-8 sm:pt-20 sm:pb-12 lg:px-12 lg:pt-24">
                        <div className="lg:text-center">
                            <p className="mb-5 flex items-center gap-3 text-[10px] font-semibold tracking-[0.24em] uppercase sm:text-xs lg:justify-center">
                                <span className="h-px w-8 bg-primary-blue" />
                                {t('gallery.eyebrow')}
                            </p>
                            <h1 className="max-w-4xl font-display text-[clamp(3.75rem,10vw,8.75rem)] leading-[0.82] tracking-[-0.055em] lg:mx-auto">
                                {t('gallery.heading_1')}
                                <br />
                                <span className="text-primary-blue italic">
                                    {t('gallery.heading_2')}
                                </span>
                            </h1>
                            <div className="mt-8 flex items-end justify-between border-t border-foreground/20 pt-4 lg:mx-auto lg:mt-12 lg:w-72 lg:flex-col lg:items-center lg:gap-3 lg:text-center">
                                <p className="max-w-48 text-sm leading-6 text-foreground/70 lg:max-w-none">
                                    {t('gallery.quote')}
                                </p>
                            </div>
                        </div>
                    </section>

                    <section
                        className="mx-auto max-w-[1440px] px-5 pb-20 sm:px-8 sm:pb-28 lg:px-12"
                        aria-label={t('gallery.aria_collection')}
                    >
                        <FilterBar
                            active={activeCategoryName}
                            onChange={handleFilterChange}
                            categories={categories.map(
                                (category) => category.name,
                            )}
                        />

                        <div className="columns-2 gap-4 sm:columns-3 sm:gap-5 lg:columns-6 lg:gap-6">
                            {artworks.data.map((artwork, index) => (
                                <article
                                    key={artwork.id}
                                    className="group break-inside-avoid bg-card hover:z-20"
                                >
                                    <Link
                                        href={`/gallery/${artwork.id}`}
                                        onClick={(event) => {
                                            // Keep new-tab/modified clicks native.
                                            if (
                                                event.metaKey ||
                                                event.ctrlKey ||
                                                event.shiftKey
                                            )
                                                return;
                                            event.preventDefault();
                                            void modal.open(artwork.id);
                                        }}
                                        className="group/image relative block w-full cursor-zoom-in overflow-hidden hover:z-10"
                                        aria-label={t('artwork.open', {
                                            title:
                                                artwork.title ||
                                                t('artwork.untitled'),
                                        })}
                                    >
                                        <div
                                            className={`bg-card ${masonryAspects[index % masonryAspects.length]}`}
                                        >
                                            <img
                                                src={artwork.image_url}
                                                srcSet={
                                                    artwork.image_srcset ??
                                                    undefined
                                                }
                                                sizes="(min-width: 1024px) 220px, (min-width: 640px) 30vw, 50vw"
                                                decoding="async"
                                                alt={artwork.alt_text}
                                                className="h-full w-full rounded-lg object-contain transition-transform duration-700 ease-out group-hover/image:scale-[1.3]"
                                                loading="lazy"
                                            />
                                        </div>
                                        <ArtworkHoverCue />
                                    </Link>
                                    <div className="border-t border-border/15 p-4">
                                        <div className="flex items-center justify-between gap-2">
                                            <h2 className="font-display text-base leading-tight tracking-[-0.02em] sm:text-lg">
                                                {artwork.title ||
                                                    t('artwork.untitled')}
                                            </h2>
                                            <span
                                                className="h-1.5 w-1.5 shrink-0 rounded-full bg-primary-blue transition-transform duration-300 group-hover:scale-150"
                                                aria-hidden="true"
                                            />
                                        </div>
                                        <p className="mt-1.5 text-[9px] font-semibold tracking-[0.18em] text-foreground/55 uppercase">
                                            {artwork.category?.name ||
                                                t('artwork.gallery_piece')}
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
                                        className="flex h-10 w-10 items-center justify-center rounded-full border border-border/15 text-foreground transition-colors hover:bg-accent focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-foreground"
                                        aria-label={t('gallery.previous_page')}
                                    >
                                        <ChevronLeft className="h-5 w-5" />
                                    </button>
                                )}
                                <span className="text-sm text-foreground/60">
                                    {t('gallery.page_of', {
                                        current: artworks.current_page,
                                        last: artworks.last_page,
                                    })}
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
                                        className="flex h-10 w-10 items-center justify-center rounded-full border border-border/15 text-foreground transition-colors hover:bg-accent focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-foreground"
                                        aria-label={t('gallery.next_page')}
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
            {modal.artwork && (
                <ArtworkModal artwork={modal.artwork} onClose={modal.close} />
            )}
        </>
    );
}
