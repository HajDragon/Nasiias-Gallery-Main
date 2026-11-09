import { Head, Link, usePage } from '@inertiajs/react';
import { useState, useEffect, useRef } from 'react';
import { ChevronLeft, X } from 'lucide-react';

interface GalleryShowProps {
    artwork: {
        id: number;
        title: string | null;
        description: string | null;
        alt_text: string;
        image_url: string;
        category: { name: string; slug: string } | null;
        view_count: number;
        like_count: number;
        created_at: string | null;
    };
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

export default function GalleryShow({ artwork }: GalleryShowProps) {
    const [liked, setLiked] = useState(false);
    const [comments, setComments] = useState<string[]>([]);
    const [newComment, setNewComment] = useState('');
    const [showBackdrop, setShowBackdrop] = useState(true);
    const closeTimerRef = useRef<ReturnType<typeof setTimeout> | null>(null);

    const imageUrl = artwork.image_url;

    // Animate in the backdrop
    useEffect(() => {
        closeTimerRef.current = setTimeout(() => {
            setShowBackdrop(false);
        }, 100);

        return () => {
            if (closeTimerRef.current) clearTimeout(closeTimerRef.current);
        };
    }, []);

    // Handle escape key
    useEffect(() => {
        const handleEscape = (event: KeyboardEvent) => {
            if (event.key === 'Escape') {
                navigateBack();
            }
        };

        window.addEventListener('keydown', handleEscape);
        document.body.style.overflow = 'hidden';

        return () => {
            window.removeEventListener('keydown', handleEscape);
            document.body.style.overflow = '';
        };
    }, []);

    const navigateBack = () => {
        // Navigate back using Inertia router
        const { router } = usePage().props as any;
        router.visit('/gallery', { preserveState: true, replace: true });
    };

    const handleBackdropClick = (event: React.MouseEvent<HTMLDivElement>) => {
        if (event.target === event.currentTarget) {
            navigateBack();
        }
    };

    const toggleLike = () => {
        setLiked((prev) => !prev);
    };

    const submitComment = (event: React.FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        const trimmed = newComment.trim();
        if (!trimmed) return;

        setComments((prev) => [...prev, trimmed]);
        setNewComment('');
    };

    return (
        <>
            <Head title={artwork.title || 'Artwork'} />
            <div className="min-h-screen bg-[#EEF3F8] text-[#293241]">
                <div
                    className={`fixed inset-0 z-50 flex items-center justify-center p-0 backdrop-blur-sm sm:p-6 transition-opacity duration-100 ${
                        showBackdrop ? 'opacity-100' : 'opacity-0 pointer-events-none'
                    }`}
                    role="presentation"
                    onMouseDown={handleBackdropClick}
                >
                    <div
                        className="grid h-full w-full grid-rows-[auto_minmax(0,1fr)] overflow-hidden bg-[#EEF3F8] shadow-2xl sm:h-auto sm:max-h-[88vh] sm:max-w-5xl sm:grid-cols-[1.2fr_0.8fr] sm:grid-rows-[minmax(0,1fr)]"
                        role="dialog"
                        aria-modal="true"
                        aria-labelledby="artwork-dialog-title"
                    >
                        <div className="min-h-0 bg-[#293241] sm:h-[min(82vh,760px)]">
                            <img
                                src={imageUrl}
                                alt={artwork.alt_text}
                                className="h-full max-h-[46vh] w-full object-contain sm:max-h-none"
                            />
                        </div>

                        <div className="flex min-h-0 flex-col">
                            <div className="flex items-center gap-3 border-b border-[#293241]/15 px-4 py-3 sm:px-5">
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
                                    onClick={navigateBack}
                                    className="grid h-10 w-10 shrink-0 place-items-center rounded-full border border-[#293241]/15 transition-colors hover:bg-white focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#293241]"
                                    aria-label="Close artwork"
                                >
                                    <X className="h-4 w-4" aria-hidden="true" />
                                </button>
                            </div>

                            <div className="flex items-center gap-4 border-b border-[#293241]/15 px-4 py-3 sm:px-5">
                                <button
                                    type="button"
                                    onClick={toggleLike}
                                    aria-pressed={liked}
                                    className={`flex items-center gap-2 text-xs font-semibold tracking-[0.15em] uppercase transition-colors ${
                                        liked
                                            ? 'text-[#E63946]'
                                            : 'text-[#293241]/65 hover:text-[#293241]'
                                    }`}
                                >
                                    <svg viewBox="0 0 24 24" className="h-6 w-6" aria-hidden="true">
                                        <path
                                            d="M20.8 4.9a5.5 5.5 0 0 0-7.8 0L12 5.9l-1-1a5.5 5.5 0 0 0-7.8 7.8l1 1L12 21.5l7.8-7.8 1-1a5.5 5.5 0 0 0 0-7.8Z"
                                            fill={liked ? 'currentColor' : 'none'}
                                            stroke="currentColor"
                                            strokeWidth="1.6"
                                            strokeLinejoin="round"
                                        />
                                    </svg>
                                    {liked ? 'Liked' : 'Like'}
                                </button>
                            </div>

                            <div className="min-h-0 flex-1 overflow-y-auto px-4 py-4 sm:px-5">
                                <div className="flex gap-3">
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
                                    <div className="min-w-0">
                                        <h2
                                            id="artwork-dialog-title"
                                            className="font-display text-xl leading-tight tracking-[-0.02em] sm:text-2xl"
                                        >
                                            {artwork.title || 'Untitled'}
                                        </h2>
                                        <p className="mt-1 text-[9px] font-semibold tracking-[0.18em] text-[#293241]/55 uppercase">
                                            {artwork.category?.name || 'Gallery Piece'}
                                        </p>
                                        <p className="mt-3 text-sm leading-6 text-[#293241]/85">
                                            <span className="mr-1.5 font-semibold text-[#293241]">
                                                nastiia.gallery
                                            </span>
                                            {artwork.description || 'No description available.'}
                                        </p>
                                    </div>
                                </div>

                                <div className="mt-5 border-t border-[#293241]/15 pt-5">
                                    {comments.length > 0 ? (
                                        <div className="space-y-5">
                                            {comments.map((comment, index) => (
                                                <div key={`${artwork.id}-comment-${index}`} className="flex gap-3">
                                                    <span
                                                        className="grid h-8 w-8 shrink-0 place-items-center rounded-full bg-[#98C1D9]/35 text-[10px] font-semibold"
                                                        aria-hidden="true"
                                                    >
                                                        Y
                                                    </span>
                                                    <p className="pt-1 text-sm leading-6">
                                                        <span className="mr-2 font-semibold">You</span>
                                                        {comment}
                                                    </p>
                                                </div>
                                            ))}
                                        </div>
                                    ) : (
                                        <div className="py-3 text-center">
                                            <p className="font-display text-lg">
                                                Start the conversation.
                                            </p>
                                            <p className="mt-1 text-xs text-[#293241]/55">
                                                Share what this piece makes you feel.
                                            </p>
                                        </div>
                                    )}
                                </div>
                            </div>

                            <form
                                className="flex items-center gap-3 border-t border-[#293241]/15 px-4 py-3 sm:px-5"
                                onSubmit={submitComment}
                            >
                                <label htmlFor="artwork-comment" className="sr-only">
                                    Add a comment
                                </label>
                                <input
                                    id="artwork-comment"
                                    value={newComment}
                                    onChange={(event) => setNewComment(event.target.value)}
                                    placeholder="Add a comment..."
                                    className="min-w-0 flex-1 border-0 bg-transparent py-2 text-sm outline-none placeholder:text-[#293241]/40"
                                />
                                <button
                                    type="submit"
                                    disabled={!newComment.trim()}
                                    className="text-[10px] font-semibold tracking-[0.16em] uppercase transition-opacity disabled:cursor-not-allowed disabled:opacity-30"
                                >
                                    Post
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </>
    );
}
