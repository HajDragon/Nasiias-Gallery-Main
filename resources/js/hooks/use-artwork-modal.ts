import { router } from '@inertiajs/react';
import { useCallback, useRef, useState } from 'react';
import type { ArtworkDetail } from '@/components/ArtworkModal';

/**
 * Opens an artwork as an overlay on the current page. The page never
 * navigates, so scroll position and loaded artworks survive closing.
 * If the detail fetch fails we fall back to the standalone /gallery/{id} page.
 */
export function useArtworkModal() {
    const [artwork, setArtwork] = useState<ArtworkDetail | null>(null);
    const latestId = useRef<number | null>(null);

    const open = useCallback(async (id: number) => {
        latestId.current = id;
        try {
            const response = await fetch(`/gallery/${id}`, {
                headers: { Accept: 'application/json' },
            });
            if (!response.ok) throw new Error(String(response.status));
            const detail = (await response.json()) as ArtworkDetail;
            if (latestId.current === id) setArtwork(detail);
        } catch {
            router.visit(`/gallery/${id}`);
        }
    }, []);

    const close = useCallback(() => {
        latestId.current = null;
        setArtwork(null);
    }, []);

    return { artwork, open, close };
}
