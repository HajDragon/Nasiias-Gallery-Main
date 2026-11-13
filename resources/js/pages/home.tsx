'use client';

import { Head, Link } from '@inertiajs/react';
import { useState, useEffect, useRef } from 'react';
import { X, Heart, MessageSquare, Share2 } from 'lucide-react';
import Header from '@/components/Header';
import { ScrollMorphHero } from '@/components/ui/scroll-morph-hero';
import { useTranslation } from '@/lib/i18n';
import { cn } from '@/lib/utils';

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

interface HomeProps {
    artworks: Artwork[];
    categories: Array<{ id: number; name: string; slug: string }>;
}

function FilterBar({
    active,
    onChange,
    categories,
}: {
    active: string;
    onChange: (category: string) => void;
    categories: Array<{ name: string; slug: string }>;
}) {
    const { t } = useTranslation();
    const allLabel = t('common.all');

    return (
        <div className="border-y border-[#293241]/15 py-3">
            <div
                className="-mx-5 flex [scrollbar-width:none] gap-2 overflow-x-auto px-5 sm:mx-0 sm:flex-wrap sm:px-0 [&::-webkit-scrollbar]:hidden"
                role="group"
                aria-label={t('home.filter_aria')}
            >
                {[allLabel, ...categories.map((c) => c.name)].map(
                    (category) => {
                        const isAll = category === allLabel;
                        const isActive =
                            active === category || (active === '' && isAll);

                        return (
                            <button
                                key={category}
                                type="button"
                                onClick={() => onChange(isAll ? '' : category)}
                                className={cn(
                                    'shrink-0 rounded-full border px-4 py-2 text-[10px] font-semibold tracking-[0.15em] uppercase transition-colors sm:text-[11px]',
                                    isActive
                                        ? 'border-[#293241] bg-[#293241] text-[#F0F7FF]'
                                        : 'border-[#293241]/20 text-[#293241] hover:border-[#98C1D9] hover:bg-[#98C1D9]/20',
                                )}
                                aria-pressed={isActive}
                            >
                                {category}
                            </button>
                        );
                    },
                )}
            </div>
        </div>
    );
}

function ArtworkHoverCue() {
    const { t } = useTranslation();

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
                    {t('home.click_me')}
                </span>
            </span>
        </span>
    );
}

function FeedCard({
    artwork,
    index,
    onOpen,
}: {
    artwork: Artwork;
    index: number;
    onOpen: () => void;
}) {
    const { t } = useTranslation();
    const aspectRatios = [
        'aspect-[3/4]',
        'aspect-[4/5]',
        'aspect-[1/1]',
        'aspect-[5/6]',
        'aspect-[4/3]',
    ];
    const aspect = aspectRatios[index % aspectRatios.length];

    return (
        <article className="group break-inside-avoid bg-white hover:z-20">
            <Link
                href={`/gallery/${artwork.id}`}
                onClick={(e) => {
                    e.preventDefault();
                    onOpen();
                }}
                className="group/image relative block w-full cursor-zoom-in overflow-hidden hover:z-10"
                aria-label={t('artwork.open', {
                    title: artwork.title || t('artwork.untitled'),
                })}
            >
                <div className={cn('bg-white', aspect)}>
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
                        {artwork.title || t('artwork.untitled')}
                    </h2>
                    <span
                        className="h-1.5 w-1.5 shrink-0 rounded-full bg-[#98C1D9] transition-transform duration-300 group-hover:scale-150"
                        aria-hidden="true"
                    />
                </div>
                <p className="mt-1.5 text-[9px] font-semibold tracking-[0.18em] text-[#293241]/55 uppercase">
                    {artwork.category?.name || t('artwork.gallery_piece')}
                </p>
            </div>
        </article>
    );
}

function MobileFeedCard({
    artwork,
    onOpen,
    onLike,
}: {
    artwork: Artwork;
    onOpen: () => void;
    onLike: () => void;
}) {
    const { t } = useTranslation();
    const lastTapRef = useRef(0);
    const tapTimeoutRef = useRef<ReturnType<typeof setTimeout> | null>(null);

    const handleTap = () => {
        const now = Date.now();
        if (now - lastTapRef.current < 300) {
            if (tapTimeoutRef.current) clearTimeout(tapTimeoutRef.current);
            onLike();
            lastTapRef.current = 0;
        } else {
            tapTimeoutRef.current = setTimeout(() => {
                onOpen();
            }, 300);
        }
        lastTapRef.current = now;
    };

    return (
        <article className="group relative h-[100dvh] snap-center bg-white hover:z-20">
            <div
                onClick={handleTap}
                onDoubleClick={(e) => {
                    e.preventDefault();
                    if (tapTimeoutRef.current)
                        clearTimeout(tapTimeoutRef.current);
                }}
                className="group/image relative h-full w-full cursor-zoom-in overflow-hidden hover:z-10"
                aria-label={t('artwork.open', {
                    title: artwork.title || t('artwork.untitled'),
                })}
            >
                <div className="aspect-[9/16] h-full bg-white">
                    <img
                        src={artwork.image_url}
                        alt={artwork.alt_text}
                        className="h-full w-full object-contain transition-transform duration-700 ease-out group-hover/image:scale-[1.05]"
                        loading="lazy"
                    />
                </div>
            </div>
            <div className="absolute right-0 bottom-0 left-0 border-t border-[#293241]/15 bg-gradient-to-t from-white/95 to-transparent p-4">
                <div className="flex items-start justify-between gap-2">
                    <div className="min-w-0 flex-1">
                        <h2 className="truncate font-display text-lg leading-tight tracking-[-0.02em]">
                            {artwork.title || t('artwork.untitled')}
                        </h2>
                        <p className="mt-1 text-[9px] font-semibold tracking-[0.18em] text-[#293241]/55 uppercase">
                            {artwork.category?.name ||
                                t('artwork.gallery_piece')}
                        </p>
                    </div>
                    <span
                        className="h-2 w-2 shrink-0 rounded-full bg-[#98C1D9] transition-transform duration-300 group-hover:scale-150"
                        aria-hidden="true"
                    />
                </div>
                <p className="mt-3 line-clamp-3 text-sm leading-6 text-[#293241]/75">
                    {artwork.description || t('artwork.no_description')}
                </p>
                <div className="mt-4 flex items-center gap-4">
                    <button
                        type="button"
                        onClick={(e) => {
                            e.stopPropagation();
                            onLike();
                        }}
                        className="flex items-center gap-1.5 text-[10px] font-semibold tracking-[0.14em] text-[#E63946] uppercase transition-opacity hover:opacity-80"
                        aria-label={t('artwork.like')}
                        aria-pressed={false}
                    >
                        <Heart
                            className="h-4 w-4 fill-current"
                            aria-hidden="true"
                        />
                        <span>{artwork.like_count}</span>
                    </button>
                    <button
                        type="button"
                        onClick={(e) => e.stopPropagation()}
                        className="flex items-center gap-1.5 text-[10px] font-semibold tracking-[0.14em] text-[#293241]/55 uppercase transition-colors hover:text-[#293241]"
                        aria-label={t('artwork.comments')}
                    >
                        <MessageSquare className="h-4 w-4" aria-hidden="true" />
                        <span>{artwork.view_count}</span>
                    </button>
                    <button
                        type="button"
                        onClick={(e) => e.stopPropagation()}
                        className="flex items-center gap-1.5 text-[10px] font-semibold tracking-[0.14em] text-[#293241]/55 uppercase transition-colors hover:text-[#293241]"
                        aria-label={t('artwork.share')}
                    >
                        <Share2 className="h-4 w-4" aria-hidden="true" />
                    </button>
                </div>
            </div>
        </article>
    );
}

export default function Home({ artworks, categories }: HomeProps) {
    const { t } = useTranslation();
    const [selectedCategory, setSelectedCategory] = useState('');
    const [showModal, setShowModal] = useState(false);
    const [selectedArtwork, setSelectedArtwork] = useState<Artwork | null>(
        null,
    );
    const [filteredArtworks, setFilteredArtworks] = useState(artworks);
    const [likedArtworks, setLikedArtworks] = useState<Set<number>>(new Set());

    useEffect(() => {
        if (selectedCategory) {
            setFilteredArtworks(
                artworks.filter((a) => a.category?.name === selectedCategory),
            );
        } else {
            setFilteredArtworks(artworks);
        }
    }, [selectedCategory, artworks]);

    const handleFilterChange = (category: string) => {
        setSelectedCategory(category);
        const url = new URL(window.location.href);
        if (category) {
            url.searchParams.set('category', category);
        } else {
            url.searchParams.delete('category');
        }
        url.searchParams.set('page', '1');
        window.history.pushState({}, '', url);
    };

    const handleOpenArtwork = (artwork: Artwork) => {
        setSelectedArtwork(artwork);
        setShowModal(true);
        document.body.style.overflow = 'hidden';
    };

    const handleLikeArtwork = (artwork: Artwork) => {
        setLikedArtworks((prev) => {
            const next = new Set(prev);
            if (next.has(artwork.id)) {
                next.delete(artwork.id);
            } else {
                next.add(artwork.id);
            }
            return next;
        });
    };

    const handleCloseModal = () => {
        setShowModal(false);
        setSelectedArtwork(null);
        document.body.style.overflow = '';
    };

    const handleEscape = (e: KeyboardEvent) => {
        if (e.key === 'Escape' && showModal) {
            handleCloseModal();
        }
    };

    useEffect(() => {
        window.addEventListener('keydown', handleEscape);
        return () => window.removeEventListener('keydown', handleEscape);
    }, [showModal]);

    return (
        <>
            <Head title={t('home.title')} />
            <div className="min-h-screen bg-[#EEF3F8] text-[#293241]">
                <Header />

                {/* Intro Animation Section */}
                <ScrollMorphHero />

                {/* Art Library Section */}
                <section
                    id="art-library"
                    className="relative z-10 bg-[#EEF3F8]"
                >
                    <div className="mx-auto max-w-[1440px] px-5 pt-10 pb-20 sm:px-8 sm:pt-16 lg:px-12">
                        <div className="mb-10 lg:mb-16 lg:text-center">
                            <p className="mb-5 flex items-center gap-3 text-[10px] font-semibold tracking-[0.24em] text-[#293241]/60 uppercase sm:text-xs lg:justify-center">
                                <span className="h-px w-8 bg-[#98C1D9]" />
                                {t('home.eyebrow')}
                            </p>
                            <h2 className="max-w-3xl font-display text-[clamp(2.5rem,8vw,5rem)] leading-[0.88] tracking-[-0.04em] lg:mx-auto">
                                {t('home.heading_1')}
                                <br />
                                <span className="text-[#98C1D9] italic">
                                    {t('home.heading_2')}
                                </span>
                            </h2>
                        </div>

                        <FilterBar
                            active={selectedCategory}
                            onChange={handleFilterChange}
                            categories={categories}
                        />

                        {/* Desktop Masonry Grid - matching design repo breakpoints */}
                        <div className="hidden columns-2 gap-4 [column-fill:auto] sm:columns-3 sm:gap-5 lg:block lg:columns-6 lg:gap-6">
                            {filteredArtworks.map((artwork, index) => (
                                <FeedCard
                                    key={artwork.id}
                                    artwork={artwork}
                                    index={index}
                                    onOpen={() => handleOpenArtwork(artwork)}
                                />
                            ))}
                        </div>

                        {/* Mobile Feed Cards - full screen snap feed with double-tap to like */}
                        <div className="h-[100dvh] snap-y snap-mandatory overflow-y-scroll pb-20 lg:hidden">
                            {filteredArtworks.map((artwork) => (
                                <MobileFeedCard
                                    key={artwork.id}
                                    artwork={artwork}
                                    onOpen={() => handleOpenArtwork(artwork)}
                                    onLike={() => handleLikeArtwork(artwork)}
                                />
                            ))}
                        </div>

                        {filteredArtworks.length === 0 && (
                            <div className="py-20 text-center">
                                <p className="font-display text-xl text-[#293241]/55">
                                    {t('home.empty')}
                                </p>
                                <p className="mt-2 text-sm text-[#293241]/45">
                                    {t('home.empty_hint')}
                                </p>
                            </div>
                        )}
                    </div>
                </section>

                {/* Footer / Contact Section */}
                <footer
                    id="contact"
                    className="relative z-10 bg-[#293241] text-[#F0F7FF]"
                >
                    <div className="mx-auto max-w-[1440px] px-5 py-16 sm:px-8 sm:py-24 lg:px-12">
                        <div className="grid gap-12 lg:grid-cols-2 lg:gap-16">
                            <div className="max-w-md">
                                <p className="mb-5 flex items-center gap-3 text-[10px] font-semibold tracking-[0.24em] text-[#98C1D9] uppercase">
                                    <span className="h-px w-8 bg-[#98C1D9]" />
                                    {t('home.contact_eyebrow')}
                                </p>
                                <h3 className="font-display text-[clamp(2rem,6vw,3.5rem)] leading-[0.9] tracking-[-0.03em]">
                                    {t('home.contact_heading_1')}
                                    <br />
                                    <span className="text-[#98C1D9] italic">
                                        {t('home.contact_heading_2')}
                                    </span>
                                </h3>
                                <p className="mt-6 text-base leading-7 text-[#F0F7FF]/70">
                                    {t('home.contact_body')}
                                </p>
                                <div className="mt-10 flex flex-col gap-4">
                                    <a
                                        href="mailto:hello@nastiia.gallery"
                                        className="flex items-center gap-3 rounded-full border border-[#F0F7FF]/20 px-5 py-4 text-sm font-medium transition-colors hover:bg-[#F0F7FF]/10"
                                    >
                                        <svg
                                            className="h-5 w-5 text-[#98C1D9]"
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            strokeWidth="1.5"
                                            aria-hidden="true"
                                        >
                                            <rect
                                                x="2"
                                                y="4"
                                                width="20"
                                                height="16"
                                                rx="2"
                                            />
                                            <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7" />
                                        </svg>
                                        hello@nastiia.gallery
                                    </a>
                                    <a
                                        href="https://instagram.com/nastiia.gallery"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        className="flex items-center gap-3 rounded-full border border-[#F0F7FF]/20 px-5 py-4 text-sm font-medium transition-colors hover:bg-[#F0F7FF]/10"
                                    >
                                        <svg
                                            className="h-5 w-5 text-[#98C1D9]"
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            strokeWidth="1.5"
                                            aria-hidden="true"
                                        >
                                            <rect
                                                x="2"
                                                y="2"
                                                width="20"
                                                height="20"
                                                rx="5"
                                            />
                                            <path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z" />
                                            <line
                                                x1="17.5"
                                                x2="17.51"
                                                y1="6.5"
                                                y2="6.51"
                                            />
                                        </svg>
                                        @nastiia.gallery
                                    </a>
                                </div>
                            </div>

                            <form
                                className="space-y-5"
                                action="#"
                                method="POST"
                            >
                                <div className="grid gap-5 sm:grid-cols-2">
                                    <div>
                                        <label
                                            htmlFor="name"
                                            className="sr-only"
                                        >
                                            {t('contact.name')}
                                        </label>
                                        <input
                                            type="text"
                                            id="name"
                                            name="name"
                                            placeholder={t('home.form_name')}
                                            className="w-full rounded-lg border border-[#F0F7FF]/20 bg-transparent px-4 py-3.5 text-sm text-[#F0F7FF] transition-colors outline-none placeholder:text-[#F0F7FF]/40 focus:border-[#98C1D9]"
                                        />
                                    </div>
                                    <div>
                                        <label
                                            htmlFor="email"
                                            className="sr-only"
                                        >
                                            {t('contact.email')}
                                        </label>
                                        <input
                                            type="email"
                                            id="email"
                                            name="email"
                                            placeholder={t('home.form_email')}
                                            className="w-full rounded-lg border border-[#F0F7FF]/20 bg-transparent px-4 py-3.5 text-sm text-[#F0F7FF] transition-colors outline-none placeholder:text-[#F0F7FF]/40 focus:border-[#98C1D9]"
                                        />
                                    </div>
                                </div>
                                <div>
                                    <label
                                        htmlFor="message"
                                        className="sr-only"
                                    >
                                        {t('contact.message')}
                                    </label>
                                    <textarea
                                        id="message"
                                        name="message"
                                        rows={5}
                                        placeholder={t('home.form_message')}
                                        className="w-full resize-none rounded-lg border border-[#F0F7FF]/20 bg-transparent px-4 py-3.5 text-sm text-[#F0F7FF] transition-colors outline-none placeholder:text-[#F0F7FF]/40 focus:border-[#98C1D9]"
                                    />
                                </div>
                                <button
                                    type="submit"
                                    className="w-full rounded-full border border-[#98C1D9] bg-[#98C1D9] px-6 py-3.5 text-sm font-semibold tracking-[0.14em] text-[#293241] uppercase transition-colors hover:border-[#7DD3C7] hover:bg-[#7DD3C7] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#98C1D9]"
                                >
                                    {t('home.form_submit')}
                                </button>
                            </form>
                        </div>

                        <div className="mt-16 flex flex-col items-center gap-4 border-t border-[#F0F7FF]/15 pt-8 text-center lg:flex-row lg:justify-between">
                            <p className="text-[11px] text-[#F0F7FF]/45">
                                {t('home.copyright', {
                                    year: new Date().getFullYear(),
                                })}
                            </p>
                            <nav
                                className="flex items-center gap-6"
                                aria-label={t('common.footer')}
                            >
                                <a
                                    href="#"
                                    className="text-[11px] text-[#F0F7FF]/45 transition-colors hover:text-[#F0F7FF]"
                                >
                                    {t('home.privacy')}
                                </a>
                                <a
                                    href="#"
                                    className="text-[11px] text-[#F0F7FF]/45 transition-colors hover:text-[#F0F7FF]"
                                >
                                    {t('home.terms')}
                                </a>
                                <a
                                    href="#"
                                    className="text-[11px] text-[#F0F7FF]/45 transition-colors hover:text-[#F0F7FF]"
                                >
                                    {t('home.cookies')}
                                </a>
                            </nav>
                        </div>
                    </div>
                </footer>
            </div>

            {/* Artwork Modal */}
            {showModal && selectedArtwork && (
                <div
                    className="fixed inset-0 z-50 flex items-center justify-center p-0 backdrop-blur-sm sm:p-6"
                    role="dialog"
                    aria-modal="true"
                    aria-labelledby="artwork-modal-title"
                    onClick={handleCloseModal}
                >
                    <div
                        className="grid h-full w-full min-w-0 grid-cols-1 grid-rows-[auto_minmax(0,1fr)] overflow-hidden bg-[#EEF3F8] shadow-2xl sm:h-[min(82vh,760px)] sm:max-w-5xl sm:grid-cols-[minmax(0,1.2fr)_minmax(0,0.8fr)] sm:grid-rows-[minmax(0,1fr)]"
                        onClick={(e) => e.stopPropagation()}
                    >
                        <div className="min-h-0 min-w-0 bg-[#293241] sm:h-full">
                            <img
                                src={selectedArtwork.image_url}
                                alt={selectedArtwork.alt_text}
                                className="h-full max-h-[46vh] w-full object-contain sm:max-h-none"
                            />
                        </div>

                        <div className="flex min-h-0 min-w-0 flex-col sm:h-full">
                            <div className="flex min-w-0 shrink-0 items-center gap-3 border-b border-[#293241]/15 px-4 py-3 sm:px-5">
                                <span className="grid h-9 w-9 shrink-0 place-items-center overflow-hidden rounded-full bg-[#293241]">
                                    <svg
                                        viewBox="0 0 1067 772"
                                        className="h-5 w-auto object-contain invert"
                                        aria-hidden="true"
                                    >
                                        <path
                                            d="M533.5 386c-81.3 0-147.3-66-147.3-147.3S452.2 91.4 533.5 91.4s147.3 66 147.3 147.3-66 147.3-147.3 147.3zm0-247.3c55.1 0 99.9 44.8 99.9 99.9S588.6 291 533.5 291 433.6 246.2 433.6 191.1s44.8-99.9 99.9-99.9z"
                                            fill="currentColor"
                                        />
                                    </svg>
                                </span>
                                <p className="min-w-0 flex-1 truncate text-sm font-semibold">
                                    nastiia.gallery
                                </p>
                                <button
                                    type="button"
                                    onClick={handleCloseModal}
                                    className="grid h-10 w-10 shrink-0 place-items-center rounded-full border border-[#293241]/15 transition-colors hover:bg-white focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#293241]"
                                    aria-label={t('artwork.close')}
                                >
                                    <X className="h-4 w-4" aria-hidden="true" />
                                </button>
                            </div>

                            <div className="flex min-w-0 shrink-0 items-center gap-4 border-b border-[#293241]/15 px-4 py-3 sm:px-5">
                                <button
                                    type="button"
                                    onClick={() =>
                                        handleLikeArtwork(selectedArtwork)
                                    }
                                    className="flex items-center gap-2 text-xs font-semibold tracking-[0.15em] text-[#E63946] uppercase transition-colors hover:opacity-80"
                                    aria-pressed={likedArtworks.has(
                                        selectedArtwork.id,
                                    )}
                                >
                                    <Heart
                                        className="h-6 w-6 fill-current"
                                        aria-hidden="true"
                                    />
                                    <span>
                                        {selectedArtwork.like_count +
                                            (likedArtworks.has(
                                                selectedArtwork.id,
                                            )
                                                ? 1
                                                : 0)}
                                    </span>
                                </button>
                                <button
                                    type="button"
                                    className="flex items-center gap-2 text-xs font-semibold tracking-[0.15em] text-[#293241]/65 uppercase transition-colors hover:text-[#293241]"
                                >
                                    <MessageSquare
                                        className="h-6 w-6"
                                        aria-hidden="true"
                                    />
                                    <span>{selectedArtwork.view_count}</span>
                                </button>
                                <button
                                    type="button"
                                    className="ml-auto flex items-center gap-2 text-xs font-semibold tracking-[0.15em] text-[#293241]/65 uppercase transition-colors hover:text-[#293241]"
                                >
                                    <Share2
                                        className="h-6 w-6"
                                        aria-hidden="true"
                                    />
                                </button>
                            </div>

                            <div className="min-h-0 min-w-0 flex-1 overflow-y-auto px-4 py-4 sm:px-5">
                                <div className="flex min-w-0 gap-3">
                                    <span className="grid h-8 w-8 shrink-0 place-items-center overflow-hidden rounded-full bg-[#293241]">
                                        <svg
                                            viewBox="0 0 1067 772"
                                            className="h-4 w-auto object-contain invert"
                                            aria-hidden="true"
                                        >
                                            <path
                                                d="M533.5 386c-81.3 0-147.3-66-147.3-147.3S452.2 91.4 533.5 91.4s147.3 66 147.3 147.3-66 147.3-147.3 147.3zm0-247.3c55.1 0 99.9 44.8 99.9 99.9S588.6 291 533.5 291 433.6 246.2 433.6 191.1s44.8-99.9 99.9-99.9z"
                                                fill="currentColor"
                                            />
                                        </svg>
                                    </span>
                                    <div className="min-w-0 flex-1">
                                        <h2
                                            id="artwork-modal-title"
                                            className="font-display text-xl leading-tight tracking-[-0.02em] sm:text-2xl"
                                        >
                                            {selectedArtwork.title ||
                                                t('artwork.untitled')}
                                        </h2>
                                        <p className="mt-1 text-[9px] font-semibold tracking-[0.18em] text-[#293241]/55 uppercase">
                                            {selectedArtwork.category?.name ||
                                                t('artwork.gallery_piece')}
                                        </p>
                                        <p className="mt-3 text-sm leading-6 [overflow-wrap:anywhere] text-[#293241]/85">
                                            <span className="mr-1.5 font-semibold text-[#293241]">
                                                nastiia.gallery
                                            </span>
                                            {selectedArtwork.description ||
                                                t('artwork.no_description')}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div className="shrink-0 border-t border-[#293241]/15 px-4 py-3 sm:px-5">
                                <form
                                    className="flex items-center gap-3"
                                    onSubmit={(e) => e.preventDefault()}
                                >
                                    <label
                                        htmlFor="artwork-comment"
                                        className="sr-only"
                                    >
                                        {t('artwork.add_comment')}
                                    </label>
                                    <input
                                        id="artwork-comment"
                                        placeholder={t(
                                            'artwork.comment_placeholder',
                                        )}
                                        className="min-w-0 flex-1 border-0 bg-transparent py-2 text-sm outline-none placeholder:text-[#293241]/40"
                                    />
                                    <button
                                        type="submit"
                                        className="text-[10px] font-semibold tracking-[0.16em] text-[#98C1D9] uppercase transition-opacity hover:opacity-80"
                                    >
                                        {t('artwork.post')}
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            )}
        </>
    );
}
