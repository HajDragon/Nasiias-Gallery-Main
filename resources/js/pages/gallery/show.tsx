'use client';

import { Head } from '@inertiajs/react';
import { useState, useEffect, useCallback, useRef } from 'react';
import { X, Heart, ChevronDown, LoaderCircle } from 'lucide-react';
import { router } from '@inertiajs/react';
import Header from '@/components/Header';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { useInitials } from '@/hooks/use-initials';
import { useTranslation } from '@/lib/i18n';

interface GalleryShowProps {
    artwork: {
        id: number;
        title: string | null;
        description: string | null;
        alt_text: string;
        image_url: string;
        image_srcset?: string | null;
        category: { name: string; slug: string } | null;
        view_count: number;
        like_count: number;
        created_at: string | null;
    };
}

interface Comment {
    id: number;
    body: string;
    user: { display_name: string; avatar: string | null };
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
    const [loadingOlderComments, setLoadingOlderComments] = useState(false);
    const [nextCommentsUrl, setNextCommentsUrl] = useState<string | null>(null);
    const [submittingComment, setSubmittingComment] = useState(false);
    const [togglingLike, setTogglingLike] = useState(false);
    const [interactionError, setInteractionError] = useState('');
    const [commentError, setCommentError] = useState('');
    const commentTextareaRef = useRef<HTMLTextAreaElement>(null);
    const { t } = useTranslation();
    const getInitials = useInitials();

    const imageUrl = artwork.image_url;

    // Fetch comments on mount
    const fetchComments = useCallback(async () => {
        try {
            const response = await fetch(`/gallery/${artwork.id}/comments`, {
                headers: { Accept: 'application/json' },
            });
            if (response.ok) {
                const data = await response.json();
                setComments(data.data);
                setNextCommentsUrl(data.next_page_url);
            }
        } catch (error) {
            console.error('Failed to fetch comments:', error);
        } finally {
            setLoadingComments(false);
        }
    }, [artwork.id]);

    const loadOlderComments = async () => {
        if (!nextCommentsUrl || loadingOlderComments) return;
        setLoadingOlderComments(true);
        setCommentError('');
        try {
            const response = await fetch(nextCommentsUrl, {
                headers: { Accept: 'application/json' },
            });
            if (!response.ok) {
                setCommentError(
                    t(
                        response.status === 429
                            ? 'gallery.rate_limited'
                            : 'gallery.request_failed',
                    ),
                );
                return;
            }
            const data = await response.json();
            setComments((current) =>
                Array.from(
                    new Map(
                        [...current, ...(data.data as Comment[])].map(
                            (comment) => [comment.id, comment],
                        ),
                    ).values(),
                ),
            );
            setNextCommentsUrl(data.next_page_url);
        } catch (error) {
            console.error('Failed to load older comments:', error);
            setCommentError(t('gallery.request_failed'));
        } finally {
            setLoadingOlderComments(false);
        }
    };

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
        setInteractionError('');
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
            } else {
                setInteractionError(
                    t(
                        response.status === 429
                            ? 'gallery.rate_limited'
                            : 'gallery.request_failed',
                    ),
                );
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
        setCommentError('');
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
            } else {
                const data = await response.json().catch(() => null);
                setCommentError(
                    data?.errors?.body?.[0] ??
                        t(
                            response.status === 429
                                ? 'gallery.rate_limited'
                                : 'gallery.request_failed',
                        ),
                );
            }
        } catch (error) {
            console.error('Failed to submit comment:', error);
            setCommentError(t('gallery.request_failed'));
        } finally {
            setSubmittingComment(false);
        }
    };

    return (
        <>
            <Head title={artwork.title || t('gallery.show_title')} />
            <div className="min-h-screen bg-background text-foreground">
                <Header />
                <div
                    className="fixed inset-0 z-50 flex items-center justify-center bg-background/90 backdrop-blur-sm sm:p-6"
                    role="presentation"
                    onMouseDown={handleBackdropClick}
                >
                    <div
                        className="grid h-full w-full min-w-0 grid-cols-1 grid-rows-[auto_minmax(0,1fr)] overflow-hidden bg-background shadow-2xl sm:h-[min(82vh,760px)] sm:max-w-5xl sm:grid-cols-[minmax(0,1.2fr)_minmax(0,0.8fr)] sm:grid-rows-[minmax(0,1fr)]"
                        role="dialog"
                        aria-modal="true"
                        aria-labelledby="artwork-dialog-title"
                    >
                        <div className="min-h-0 min-w-0 bg-foreground sm:h-full">
                            <img
                                src={imageUrl}
                                srcSet={artwork.image_srcset ?? undefined}
                                sizes="(min-width: 640px) 700px, 100vw"
                                decoding="async"
                                alt={artwork.alt_text}
                                className="h-full max-h-[46vh] w-full object-contain sm:max-h-none"
                            />
                        </div>

                        <div className="flex min-h-0 min-w-0 flex-col sm:h-full">
                            <div className="flex min-w-0 shrink-0 items-center gap-3 border-b border-border/15 px-4 py-3 sm:px-5">
                                <span className="grid h-9 w-9 shrink-0 place-items-center overflow-hidden rounded-full bg-foreground">
                                    <img
                                        src="/nastias-gallery-logo.png"
                                        alt=""
                                        className="h-5 w-auto object-contain invert dark:invert-0"
                                    />
                                </span>
                                <p className="min-w-0 flex-1 truncate text-sm font-semibold">
                                    nastiia.gallery
                                </p>
                                <button
                                    type="button"
                                    onClick={onClose}
                                    className="grid h-10 w-10 shrink-0 place-items-center rounded-full border border-border/15 transition-colors hover:bg-background focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-foreground"
                                    aria-label={t('artwork.close')}
                                >
                                    <X className="h-4 w-4" aria-hidden="true" />
                                </button>
                            </div>

                            <div className="flex min-w-0 shrink-0 items-center gap-4 border-b border-border/15 px-4 py-3 sm:px-5">
                                <button
                                    type="button"
                                    onClick={toggleLike}
                                    aria-pressed={liked}
                                    disabled={togglingLike}
                                    className={`flex items-center gap-2 text-xs font-semibold tracking-[0.15em] uppercase transition-colors ${
                                        liked
                                            ? 'text-destructive'
                                            : 'text-foreground/65 hover:text-foreground'
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
                                    {liked
                                        ? t('artwork.liked')
                                        : t('artwork.like')}
                                    <span className="text-sm">{likeCount}</span>
                                </button>
                            </div>

                            <div className="min-h-0 min-w-0 flex-1 overflow-y-auto px-4 py-4 sm:px-5">
                                <div className="flex min-w-0 gap-3">
                                    <span className="grid h-8 w-8 shrink-0 place-items-center overflow-hidden rounded-full bg-foreground">
                                        <img
                                            src="/nastias-gallery-logo.png"
                                            alt=""
                                            className="h-4 w-auto object-contain invert dark:invert-0"
                                        />
                                    </span>
                                    <div className="min-w-0 flex-1">
                                        <h2
                                            id="artwork-dialog-title"
                                            className="font-display text-xl leading-tight tracking-[-0.02em] sm:text-2xl"
                                        >
                                            {artwork.title ||
                                                t('artwork.untitled')}
                                        </h2>
                                        <p className="mt-1 text-[9px] font-semibold tracking-[0.18em] text-foreground/55 uppercase">
                                            {artwork.category?.name ||
                                                t('artwork.gallery_piece')}
                                        </p>
                                        <p className="mt-3 text-sm leading-6 [overflow-wrap:anywhere] text-foreground/85">
                                            <span className="mr-1.5 font-semibold text-foreground">
                                                nastiia.gallery
                                            </span>
                                            {artwork.description ||
                                                t('artwork.no_description')}
                                        </p>
                                    </div>
                                </div>

                                <div className="mt-5 border-t border-border/15 pt-5">
                                    {interactionError && (
                                        <p
                                            role="alert"
                                            className="mb-4 text-sm text-destructive"
                                        >
                                            {interactionError}
                                        </p>
                                    )}
                                    {loadingComments ? (
                                        <div className="py-3 text-center text-sm text-foreground/55">
                                            {t('gallery.loading_comments')}
                                        </div>
                                    ) : comments.length > 0 ? (
                                        <div className="space-y-5">
                                            {comments.map((comment) => (
                                                <div
                                                    key={comment.id}
                                                    className="flex min-w-0 gap-3"
                                                >
                                                    <Avatar className="size-8 shrink-0">
                                                        <AvatarImage
                                                            src={
                                                                comment.user
                                                                    .avatar ??
                                                                undefined
                                                            }
                                                            alt={
                                                                comment.user
                                                                    .display_name
                                                            }
                                                        />
                                                        <AvatarFallback className="bg-primary-blue/35 text-[10px] font-semibold">
                                                            {getInitials(
                                                                comment.user
                                                                    .display_name,
                                                            )}
                                                        </AvatarFallback>
                                                    </Avatar>
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
                                                {t(
                                                    'gallery.start_conversation',
                                                )}
                                            </p>
                                            <p className="mt-1 text-xs text-foreground/55">
                                                {t('gallery.share_feeling')}
                                            </p>
                                        </div>
                                    )}
                                    {!loadingComments && nextCommentsUrl && (
                                        <div className="mt-6 flex justify-center border-t border-border/15 pt-4">
                                            <button
                                                type="button"
                                                disabled={loadingOlderComments}
                                                aria-busy={loadingOlderComments}
                                                onClick={() =>
                                                    void loadOlderComments()
                                                }
                                                className="inline-flex min-h-11 max-w-full items-center justify-center gap-2 rounded-full border border-foreground/15 bg-foreground/3 px-5 py-2.5 text-[10px] font-semibold tracking-[0.14em] text-foreground/70 uppercase transition-colors hover:border-foreground/30 hover:bg-foreground/7 hover:text-foreground focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-foreground disabled:cursor-wait disabled:opacity-50"
                                            >
                                                {loadingOlderComments ? (
                                                    <LoaderCircle
                                                        className="h-3.5 w-3.5 shrink-0 motion-safe:animate-spin"
                                                        aria-hidden="true"
                                                    />
                                                ) : (
                                                    <ChevronDown
                                                        className="h-3.5 w-3.5 shrink-0"
                                                        aria-hidden="true"
                                                    />
                                                )}
                                                {loadingOlderComments
                                                    ? t(
                                                          'gallery.loading_comments',
                                                      )
                                                    : t(
                                                          'gallery.older_comments',
                                                      )}
                                            </button>
                                        </div>
                                    )}
                                </div>
                            </div>

                            {commentError && (
                                <p
                                    role="alert"
                                    className="shrink-0 px-4 pt-2 text-sm text-destructive sm:px-5"
                                >
                                    {commentError}
                                </p>
                            )}
                            <form
                                className="flex min-w-0 shrink-0 flex-col gap-2 border-t border-border/15 px-4 py-3 sm:flex-row sm:items-center sm:px-5"
                                onSubmit={submitComment}
                            >
                                <label
                                    htmlFor="artwork-comment"
                                    className="sr-only"
                                >
                                    {t('artwork.add_comment')}
                                </label>
                                <div className="min-w-0 flex-1">
                                    <textarea
                                        ref={commentTextareaRef}
                                        id="artwork-comment"
                                        value={newComment}
                                        onChange={handleCommentChange}
                                        onKeyDown={handleCommentKeyDown}
                                        placeholder={t(
                                            'artwork.comment_placeholder',
                                        )}
                                        maxLength={280}
                                        rows={1}
                                        className="w-full min-w-0 resize-none overflow-y-auto border-0 bg-transparent py-2 text-sm [overflow-wrap:anywhere] outline-none placeholder:text-foreground/40"
                                        style={{
                                            height: 'auto',
                                            maxHeight: '128px',
                                        }}
                                    />
                                </div>
                                <div className="flex shrink-0 items-center gap-3">
                                    <span
                                        className={`text-[10px] font-semibold tracking-[0.16em] uppercase ${newComment.length > 250 ? 'text-destructive' : 'text-foreground/40'}`}
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
                                            ? t('artwork.posting')
                                            : t('artwork.post')}
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
