import { router } from '@inertiajs/react';
import Header from '@/components/Header';
import { ArtworkModal } from '@/components/ArtworkModal';
import type { ArtworkDetail } from '@/components/ArtworkModal';

// Standalone page for direct links/SEO; in-app clicks use the overlay instead.
export default function GalleryShow({ artwork }: { artwork: ArtworkDetail }) {
    return (
        <div className="min-h-screen bg-background text-foreground">
            <Header />
            <ArtworkModal
                artwork={artwork}
                onClose={() => router.visit('/', { method: 'get' })}
            />
        </div>
    );
}
