import { Head, router } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';
import Header from '@/components/Header';
import FilterBar from '@/components/FilterBar';
import ArtworkHoverCue from '@/components/ArtworkHoverCue';
import ContactFooter from '@/components/ContactFooter';
import IntroAnimation from '@/components/ui/intro-animation';
import { useTranslation } from '@/lib/i18n';

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

interface WelcomeProps {
    artworks: Artwork[];
    categories: Array<{ id: number; name: string; slug: string }>;
}

function FeedCard({
    artwork,
    liked,
    onSelect,
    onLike,
}: {
    artwork: Artwork;
    liked: boolean;
    onSelect: (artwork: Artwork) => void;
    onLike: (artwork: Artwork) => void;
}) {
    const { t } = useTranslation();
    const lastTapRef = useRef(0);
    const openTimerRef = useRef<ReturnType<typeof setTimeout> | null>(null);
    const burstTimerRef = useRef<ReturnType<typeof setTimeout> | null>(null);
    const [burstKey, setBurstKey] = useState(0);

    useEffect(() => {
        return () => {
            if (openTimerRef.current) clearTimeout(openTimerRef.current);
            if (burstTimerRef.current) clearTimeout(burstTimerRef.current);
        };
    }, []);

    const handleTap = () => {
        const now = Date.now();

        // second tap within 300ms = like, cancel the pending open
        if (now - lastTapRef.current < 300) {
            lastTapRef.current = 0;

            if (openTimerRef.current) clearTimeout(openTimerRef.current);

            onLike(artwork);
            setBurstKey((key) => key + 1);

            // hard guarantee: heart element is removed exactly 1s after the double-tap
            if (burstTimerRef.current) clearTimeout(burstTimerRef.current);
            burstTimerRef.current = setTimeout(() => setBurstKey(0), 1000);
            return;
        }

        lastTapRef.current = now;
        openTimerRef.current = setTimeout(() => onSelect(artwork), 300);
    };

    return (
        <article className="relative h-[100dvh] touch-manipulation snap-start snap-always overflow-hidden bg-white">
            <button
                type="button"
                onClick={handleTap}
                className="group/image absolute inset-0 block w-full text-left"
                aria-label={t('welcome.double_tap_to_like', {
                    title: artwork.title || t('artwork.untitled'),
                })}
            >
                <div className="h-full w-full bg-white">
                    <img
                        src={artwork.image_url}
                        alt={artwork.alt_text}
                        className="h-full w-full rounded-lg object-contain"
                        loading="lazy"
                    />
                </div>

                <div className="pointer-events-none absolute inset-x-0 bottom-0 h-40 bg-gradient-to-t from-[#293241]/85 via-[#293241]/30 to-transparent" />

                <div className="pointer-events-none absolute inset-x-0 bottom-0 flex items-end justify-between gap-4 px-5 pb-8">
                    <div className="min-w-0">
                        <h2 className="font-display text-3xl text-[#F0F7FF] drop-shadow-sm">
                            {artwork.title || t('artwork.untitled')}
                        </h2>
                        <p className="mt-1.5 text-[10px] font-semibold tracking-[0.18em] text-[#F0F7FF]/75 uppercase">
                            {artwork.category?.name ||
                                t('artwork.gallery_piece')}
                        </p>
                    </div>
                    <svg
                        viewBox="0 0 24 24"
                        className={`h-7 w-7 shrink-0 drop-shadow-md ${
                            liked ? 'text-[#E63946]' : 'text-[#F0F7FF]/40'
                        }`}
                        aria-hidden="true"
                    >
                        <path
                            d="M20.8 4.9a5.5 5.5 0 0 0-7.8 0L12 5.9l-1-1a5.5 5.5 0 0 0-7.8 7.8l1 1L12 21.5l7.8-7.8 1-1a5.5 5.5 0 0 0 0-7.8Z"
                            fill={liked ? 'currentColor' : 'none'}
                            stroke="currentColor"
                            strokeWidth="1.6"
                            strokeLinejoin="round"
                        />
                    </svg>
                </div>

                <ArtworkHoverCue />

                {burstKey > 0 && (
                    <span
                        key={burstKey}
                        className="heart-burst pointer-events-none absolute inset-0 z-20 grid place-items-center"
                        aria-hidden="true"
                    >
                        <svg
                            viewBox="0 0 24 24"
                            className="h-40 w-40 drop-shadow-[0_8px_24px_rgba(41,50,65,0.45)]"
                        >
                            <path
                                d="M20.8 4.9a5.5 5.5 0 0 0-7.8 0L12 5.9l-1-1a5.5 5.5 0 0 0-7.8 7.8l1 1L12 21.5l7.8-7.8 1-1a5.5 5.5 0 0 0 0-7.8Z"
                                fill="#F0F7FF"
                                stroke="#293241"
                                strokeWidth="1.2"
                                strokeLinejoin="round"
                            />
                        </svg>
                    </span>
                )}
            </button>
        </article>
    );
}

export default function Welcome({ artworks, categories }: WelcomeProps) {
    const { t } = useTranslation();
    const [activeCategory, setActiveCategory] = useState('');
    const [likedIds, setLikedIds] = useState<Set<number>>(new Set());

    const visibleArtworks =
        activeCategory === ''
            ? artworks
            : artworks.filter(
                  (artwork) => artwork.category?.name === activeCategory,
              );

    const openArtwork = (artwork: Artwork) => {
        router.visit(`/gallery/${artwork.id}`);
    };

    const likeArtwork = (artwork: Artwork) => {
        setLikedIds((current) => {
            if (current.has(artwork.id)) return current;

            const next = new Set(current);
            next.add(artwork.id);
            return next;
        });
    };

    return (
        <>
            <Head title="nastiia.gallery" />
            <div className="gallery-surface min-h-screen bg-[#EEF3F8] text-[#293241]">
                <Header />
                <main id="top">
                    <section
                        className="h-[100dvh] overflow-hidden"
                        aria-label={t('welcome.aria_intro')}
                    >
                        <IntroAnimation
                            items={artworks.map((artwork) => ({
                                image: artwork.image_url,
                                alt: artwork.alt_text,
                            }))}
                            onSelect={(index) => {
                                const artwork = artworks[index];
                                if (artwork) openArtwork(artwork);
                            }}
                        />
                    </section>

                    <section
                        id="art-library"
                        className="mx-auto max-w-[1440px] px-5 pb-20 sm:px-8 sm:pb-28 lg:px-12"
                        aria-label={t('welcome.aria_collection')}
                    >
                        <h2 className="mb-6 pt-8 font-display text-[clamp(2.5rem,7vw,4.5rem)] leading-[0.9] tracking-[-0.045em] sm:mb-8">
                            {t('welcome.my')}{' '}
                            <span className="text-[#98C1D9] italic">
                                {t('welcome.art_library')}
                            </span>
                        </h2>
                        <p className="mb-8 max-w-2xl text-sm leading-7 text-[#293241]/70 sm:text-base sm:leading-8">
                            {t('welcome.intro')}
                        </p>

                        <div className="sticky top-0 z-30 -mx-5 bg-[#EEF3F8] px-5 sm:static sm:z-auto sm:mx-0 sm:px-0">
                            <FilterBar
                                active={activeCategory}
                                onChange={setActiveCategory}
                                categories={categories.map(
                                    (category) => category.name,
                                )}
                            />
                        </div>

                        <p className="mt-2 mb-2 text-center text-[11px] font-semibold tracking-[0.3em] text-[#293241]/55 uppercase sm:hidden">
                            {t('welcome.scroll')}
                        </p>

                        <div className="h-[100dvh] snap-y snap-mandatory [scrollbar-width:none] overflow-y-scroll sm:hidden [&::-webkit-scrollbar]:hidden">
                            {visibleArtworks.map((artwork) => (
                                <FeedCard
                                    key={artwork.id}
                                    artwork={artwork}
                                    liked={likedIds.has(artwork.id)}
                                    onSelect={openArtwork}
                                    onLike={likeArtwork}
                                />
                            ))}
                        </div>

                        <div className="hidden grid-cols-1 gap-x-6 gap-y-12 sm:grid lg:grid-cols-3 lg:gap-x-8 lg:gap-y-16">
                            {visibleArtworks.map((artwork, index) => (
                                <article
                                    key={artwork.id}
                                    className="group bg-white"
                                >
                                    <button
                                        type="button"
                                        onClick={() => openArtwork(artwork)}
                                        className="group/image relative block w-full cursor-zoom-in overflow-hidden text-left hover:z-10"
                                        aria-label={t('artwork.open', {
                                            title:
                                                artwork.title ||
                                                t('artwork.untitled'),
                                        })}
                                    >
                                        <div className="aspect-[4/5] w-full bg-white">
                                            <img
                                                src={artwork.image_url}
                                                alt={artwork.alt_text}
                                                className="h-full w-full rounded-lg object-contain transition-transform duration-700 ease-out group-hover/image:scale-[1.035]"
                                                loading={
                                                    index < 3 ? 'eager' : 'lazy'
                                                }
                                                fetchPriority={
                                                    index === 0
                                                        ? 'high'
                                                        : undefined
                                                }
                                            />
                                        </div>
                                        <span className="absolute top-4 right-4 grid h-8 w-8 place-items-center rounded-full bg-[#EEF3F8]/90 text-[10px] font-semibold backdrop-blur-sm">
                                            {String(index + 1).padStart(2, '0')}
                                        </span>
                                        <ArtworkHoverCue />
                                    </button>
                                    <div className="border-t border-[#293241]/15 p-4">
                                        <div className="flex items-baseline justify-between gap-4">
                                            <h2 className="font-display text-xl leading-tight tracking-[-0.02em] sm:text-[1.5rem]">
                                                {artwork.title ||
                                                    t('artwork.untitled')}
                                            </h2>
                                            <span
                                                className="h-2 w-2 shrink-0 rounded-full bg-[#98C1D9] transition-transform duration-300 group-hover:scale-150"
                                                aria-hidden="true"
                                            />
                                        </div>
                                        <p className="mt-1.5 text-[10px] font-semibold tracking-[0.18em] text-[#293241]/55 uppercase">
                                            {artwork.category?.name ||
                                                t('artwork.gallery_piece')}
                                        </p>
                                    </div>
                                </article>
                            ))}
                        </div>
                    </section>
                </main>
                <ContactFooter />
            </div>
        </>
    );
}
