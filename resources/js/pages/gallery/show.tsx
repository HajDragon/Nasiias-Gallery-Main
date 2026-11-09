'use client';

import { Head } from '@inertiajs/react';
import { useState, useEffect, useCallback, useRef } from 'react';
import { X, Heart } from 'lucide-react';
import { router } from '@inertiajs/react';
import Header from '@/components/Header';

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

interface Comment {
    id: number;
    body: string;
    user: { display_name: string };
    created_at: string | null;
}

function ArtworkModal({
    artwork,
    onClose,
}: {
    artwork: GalleryShowProps['artwork'];
    onClose: () => void;
}) {
    const [liked, setLiked] = useState(false);
    const [likeCount, setLikeCount] = useState(artwork.like_count);
    const [comments, setComments] = useState<Comment[]>([]);
    const [newComment, setNewComment] = useState('');
    const [loadingComments, setLoadingComments] = useState(true);
    const [submittingComment, setSubmittingComment] = useState(false);
    const [togglingLike, setTogglingLike] = useState(false);
    const commentTextareaRef = useRef<HTMLTextAreaElement>(null);

    const imageUrl = artwork.image_url;

    // Fetch comments on mount
    const fetchComments = useCallback(async () => {
        try {
            const response = await fetch(`/gallery/${artwork.id}/comments`, {
                headers: { Accept: 'application/json' },
            });
            if (response.ok) {
                const data = await response.json();
                setComments(data);
            }
        } catch (error) {
            console.error('Failed to fetch comments:', error);
        } finally {
            setLoadingComments(false);
        }
    }, [artwork.id]);

    useEffect(() => {
        void fetchComments();
    }, [fetchComments]);

    useEffect(() => {
        const handleEscape = (event: KeyboardEvent) => {
            if (event.key === 'Escape') {
                onClose();
            }
        };

        window.addEventListener('keydown', handleEscape);
        document.body.style.overflow = 'hidden';

        return () => {
            window.removeEventListener('keydown', handleEscape);
            document.body.style.overflow = '';
        };
    }, [onClose]);

    const handleBackdropClick = (event: React.MouseEvent<HTMLDivElement>) => {
        if (event.target === event.currentTarget) {
            onClose();
        }
    };

    const toggleLike = async () => {
        if (togglingLike) return;
        setTogglingLike(true);
        try {
            const response = await fetch(`/gallery/${artwork.id}/like`, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN':
                        document
                            .querySelector('meta[name="csrf-token"]')
                            ?.getAttribute('content') || '',
                },
            });
            if (response.ok) {
                const data = await response.json();
                setLiked(data.liked);
                setLikeCount(data.like_count);
            } else if (response.status === 401) {
                // Redirect to login if not authenticated
                window.location.href = '/login';
            }
        } catch (error) {
            console.error('Failed to toggle like:', error);
        } finally {
            setTogglingLike(false);
        }
    };

    // Auto-resize textarea
    const adjustTextareaHeight = () => {
        const textarea = commentTextareaRef.current;
        if (textarea) {
            textarea.style.height = 'auto';
            textarea.style.height = `${Math.min(textarea.scrollHeight, 128)}px`; // max-h-32 = 128px
        }
    };

    const handleCommentChange = (
        event: React.ChangeEvent<HTMLTextAreaElement>,
    ) => {
        setNewComment(event.target.value);
        // Defer to next tick so DOM updates first
        requestAnimationFrame(adjustTextareaHeight);
    };

    const handleCommentKeyDown = (
        event: React.KeyboardEvent<HTMLTextAreaElement>,
    ) => {
        if (event.key === 'Enter' && !event.shiftKey) {
            event.preventDefault();
            // Submit on Enter (without Shift)
            const form = event.currentTarget.closest('form');
            if (form) {
                form.requestSubmit();
            }
        }
    };

    const submitComment = async (event: React.FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        const trimmed = newComment.trim();
        if (!trimmed || submittingComment) return;

        setSubmittingComment(true);
        try {
            const response = await fetch(`/gallery/${artwork.id}/comments`, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN':
                        document
                            .querySelector('meta[name="csrf-token"]')
                            ?.getAttribute('content') || '',
                },
                body: JSON.stringify({ body: trimmed }),
            });
            if (response.ok) {
                const data = await response.json();
                setComments((prev) => [data, ...prev]);
                setNewComment('');
                // Reset textarea height after submit
                requestAnimationFrame(adjustTextareaHeight);
            } else if (response.status === 401) {
                window.location.href = '/login';
            }
        } catch (error) {
            console.error('Failed to submit comment:', error);
        } finally {
            setSubmittingComment(false);
        }
    };

    return (
        <>
            <Head title={artwork.title || 'Artwork'} />
            <div className="min-h-screen bg-[#EEF3F8] text-[#293241]">
                <Header />
                <div
                    className="fixed inset-0 z-50 flex items-center justify-center bg-[#293241]/85 p-0 backdrop-blur-sm sm:p-6"
                    role="presentation"
                    onMouseDown={handleBackdropClick}
                >
                    <div
                        className="grid h-full w-full min-w-0 grid-cols-1 grid-rows-[auto_minmax(0,1fr)] overflow-hidden bg-[#EEF3F8] shadow-2xl sm:h-[min(82vh,760px)] sm:max-w-5xl sm:grid-cols-[minmax(0,1.2fr)_minmax(0,0.8fr)] sm:grid-rows-[minmax(0,1fr)]"
                        role="dialog"
                        aria-modal="true"
                        aria-labelledby="artwork-dialog-title"
                    >
                        <div className="min-h-0 min-w-0 bg-[#293241] sm:h-full">
                            <img
                                src={imageUrl}
                                alt={artwork.alt_text}
                                className="h-full max-h-[46vh] w-full object-contain sm:max-h-none"
                            />
                        </div>

                        <div className="flex min-h-0 min-w-0 flex-col sm:h-full">
                            <div className="flex min-w-0 shrink-0 items-center gap-3 border-b border-[#293241]/15 px-4 py-3 sm:px-5">
                                <span className="grid h-9 w-9 shrink-0 place-items-center overflow-hidden rounded-full bg-[#293241]">
                                    <img
                                        src="/nastias-gallery-logo.png"
                                        alt=""
                                        className="h-5 w-auto object-contain invert"
                                    />
                                </span>
                                <p className="min-w-0 flex-1 truncate text-sm font-semibold">
                                    nastiia.gallery
                                </p>
                                <button
                                    type="button"
                                    onClick={onClose}
                                    className="grid h-10 w-10 shrink-0 place-items-center rounded-full border border-[#293241]/15 transition-colors hover:bg-white focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#293241]"
                                    aria-label="Close artwork"
                                >
                                    <X className="h-4 w-4" aria-hidden="true" />
                                </button>
                            </div>

                            <div className="flex min-w-0 shrink-0 items-center gap-4 border-b border-[#293241]/15 px-4 py-3 sm:px-5">
                                <button
                                    type="button"
                                    onClick={toggleLike}
                                    aria-pressed={liked}
                                    disabled={togglingLike}
                                    className={`flex items-center gap-2 text-xs font-semibold tracking-[0.15em] uppercase transition-colors ${
                                        liked
                                            ? 'text-[#E63946]'
                                            : 'text-[#293241]/65 hover:text-[#293241]'
                                    } disabled:cursor-not-allowed disabled:opacity-50`}
                                >
                                    <Heart
                                        className="h-6 w-6"
                                        aria-hidden="true"
                                        fill={liked ? 'currentColor' : 'none'}
                                        stroke="currentColor"
                                        strokeWidth={1.6}
                                        strokeLinejoin="round"
                                    />
                                    {liked ? 'Liked' : 'Like'}
                                    <span className="text-sm">{likeCount}</span>
                                </button>
                            </div>

                            <div className="min-h-0 min-w-0 flex-1 overflow-y-auto px-4 py-4 sm:px-5">
                                <div className="flex min-w-0 gap-3">
                                    <span className="grid h-8 w-8 shrink-0 place-items-center overflow-hidden rounded-full bg-[#293241]">
                                        <img
                                            src="/nastias-gallery-logo.png"
                                            alt=""
                                            className="h-4 w-auto object-contain invert"
                                        />
                                    </span>
                                    <div className="min-w-0 flex-1">
                                        <h2
                                            id="artwork-dialog-title"
                                            className="font-display text-xl leading-tight tracking-[-0.02em] sm:text-2xl"
                                        >
                                            {artwork.title || 'Untitled'}
                                        </h2>
                                        <p className="mt-1 text-[9px] font-semibold tracking-[0.18em] text-[#293241]/55 uppercase">
                                            {artwork.category?.name ||
                                                'Gallery Piece'}
                                        </p>
                                        <p className="mt-3 text-sm leading-6 [overflow-wrap:anywhere] text-[#293241]/85">
                                            <span className="mr-1.5 font-semibold text-[#293241]">
                                                nastiia.gallery
                                            </span>
                                            {artwork.description ||
                                                'No description available.'}
                                        </p>
                                    </div>
                                </div>

                                <div className="mt-5 border-t border-[#293241]/15 pt-5">
                                    {loadingComments ? (
                                        <div className="py-3 text-center text-sm text-[#293241]/55">
                                            Loading comments...
                                        </div>
                                    ) : comments.length > 0 ? (
                                        <div className="space-y-5">
                                            {comments.map((comment) => (
                                                <div
                                                    key={comment.id}
                                                    className="flex min-w-0 gap-3"
                                                >
                                                    <span
                                                        className="grid h-8 w-8 shrink-0 place-items-center rounded-full bg-[#98C1D9]/35 text-[10px] font-semibold"
                                                        aria-hidden="true"
                                                    >
                                                        {comment.user.display_name
                                                            .charAt(0)
                                                            .toUpperCase()}
                                                    </span>
                                                    <div className="min-w-0 flex-1">
                                                        <p className="pt-1 text-sm leading-6 [overflow-wrap:anywhere] whitespace-pre-wrap">
                                                            <span className="mr-2 shrink-0 font-semibold">
                                                                {
                                                                    comment.user
                                                                        .display_name
                                                                }
                                                            </span>
                                                            {comment.body}
                                                        </p>
                                                    </div>
                                                </div>
                                            ))}
                                        </div>
                                    ) : (
                                        <div className="py-3 text-center">
                                            <p className="font-display text-lg">
                                                Start the conversation.
                                            </p>
                                            <p className="mt-1 text-xs text-[#293241]/55">
                                                Share what this piece makes you
                                                feel.
                                            </p>
                                        </div>
                                    )}
                                </div>
                            </div>

                            <form
                                className="flex min-w-0 shrink-0 flex-col gap-2 border-t border-[#293241]/15 px-4 py-3 sm:flex-row sm:items-center sm:px-5"
                                onSubmit={submitComment}
                            >
                                <label
                                    htmlFor="artwork-comment"
                                    className="sr-only"
                                >
                                    Add a comment
                                </label>
                                <div className="min-w-0 flex-1">
                                    <textarea
                                        ref={commentTextareaRef}
                                        id="artwork-comment"
                                        value={newComment}
                                        onChange={handleCommentChange}
                                        onKeyDown={handleCommentKeyDown}
                                        placeholder="Add a comment..."
                                        maxLength={280}
                                        rows={1}
                                        className="w-full min-w-0 resize-none overflow-y-auto border-0 bg-transparent py-2 text-sm [overflow-wrap:anywhere] outline-none placeholder:text-[#293241]/40"
                                        style={{
                                            height: 'auto',
                                            maxHeight: '128px',
                                        }}
                                    />
                                </div>
                                <div className="flex shrink-0 items-center gap-3">
                                    <span
                                        className={`text-[10px] font-semibold tracking-[0.16em] uppercase ${newComment.length > 250 ? 'text-[#E63946]' : 'text-[#293241]/40'}`}
                                    >
                                        {newComment.length}/280
                                    </span>
                                    <button
                                        type="submit"
                                        disabled={
                                            !newComment.trim() ||
                                            submittingComment
                                        }
                                        className="text-[10px] font-semibold tracking-[0.16em] uppercase transition-opacity disabled:cursor-not-allowed disabled:opacity-30"
                                    >
                                        {submittingComment
                                            ? 'Posting...'
                                            : 'Post'}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </>
    );
}

export default function GalleryShow({ artwork }: GalleryShowProps) {
    return (
        <ArtworkModal
            artwork={artwork}
            onClose={() => router.visit('/', { method: 'get' })}
        />
    );
}
