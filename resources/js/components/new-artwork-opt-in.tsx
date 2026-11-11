import { usePage } from '@inertiajs/react';
import { Mail } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { toast } from 'sonner';
import { Button } from '@/components/ui/button';
import type { User } from '@/types';

type PageProps = {
    auth: {
        user: User | null;
    };
};

const getCsrfToken = (): string => {
    const metaToken = document
        .querySelector('meta[name="csrf-token"]')
        ?.getAttribute('content');

    if (metaToken) return metaToken;

    // Fall back to the encrypted XSRF cookie Laravel sets on every request.
    const cookieMatch = document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]+)/);

    return cookieMatch ? decodeURIComponent(cookieMatch[1]) : '';
};

/**
 * Small consent card asking logged-in users whether they want to receive an
 * email whenever a new artwork is uploaded. Shown only to users who have not
 * made a choice yet (notify_on_new_artwork === null).
 */
export default function NewArtworkOptIn() {
    const { auth } = usePage<PageProps>().props;
    const user = auth?.user ?? null;

    const respondedRef = useRef(false);
    const undecided =
        user?.notify_on_new_artwork === null && !respondedRef.current;
    const [visible, setVisible] = useState(false);
    const [pending, setPending] = useState(false);

    useEffect(() => {
        if (!undecided) {
            setVisible(false);

            return;
        }

        // Give the page a moment to settle before the card slides in.
        const timer = setTimeout(() => setVisible(true), 1200);

        return () => clearTimeout(timer);
    }, [undecided]);

    const respond = async (subscribed: boolean) => {
        if (pending) return;
        setPending(true);

        try {
            const response = await fetch('/notifications/new-artwork', {
                method: 'PUT',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken(),
                },
                body: JSON.stringify({ subscribed }),
            });

            if (response.ok) {
                respondedRef.current = true;
                setVisible(false);
                toast.success(
                    subscribed
                        ? "Great — we'll email you when a new artwork is uploaded."
                        : "No problem — you won't receive new artwork emails.",
                );
            } else if (response.status !== 401) {
                toast.error('Something went wrong. Please try again.');
            }
        } catch {
            toast.error('Something went wrong. Please try again.');
        } finally {
            setPending(false);
        }
    };

    if (!visible) return null;

    return (
        <div
            role="dialog"
            aria-label="New artwork email notifications"
            aria-live="polite"
            className="animate-in fade-in slide-in-from-bottom-4 fixed right-4 bottom-4 z-[70] w-[calc(100vw-2rem)] max-w-sm duration-500"
        >
            <div className="rounded-xl border border-main-line bg-main-surface p-4 shadow-xl">
                <div className="flex items-start gap-3">
                    <span
                        aria-hidden="true"
                        className="mt-0.5 flex size-9 shrink-0 items-center justify-center rounded-full bg-main-chip text-main-blue"
                    >
                        <Mail className="size-4" />
                    </span>
                    <div className="space-y-1">
                        <h2 className="text-sm font-semibold text-main-ink">
                            New artwork alerts
                        </h2>
                        <p className="text-xs leading-relaxed text-main-muted">
                            Want to get an email when a new artwork is uploaded
                            to the gallery?
                        </p>
                    </div>
                </div>
                <div className="mt-4 flex justify-end gap-2">
                    <Button
                        variant="ghost"
                        size="sm"
                        className="text-main-ink hover:bg-main-chip"
                        onClick={() => void respond(false)}
                        disabled={pending}
                    >
                        No thanks
                    </Button>
                    <Button
                        size="sm"
                        className="bg-main-blue text-main-on-blue hover:bg-main-blue/90"
                        onClick={() => void respond(true)}
                        disabled={pending}
                    >
                        Yes, notify me
                    </Button>
                </div>
            </div>
        </div>
    );
}
