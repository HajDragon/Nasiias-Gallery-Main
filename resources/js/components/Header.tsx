import { Link, usePage } from '@inertiajs/react';

export default function Header() {
    const url = usePage().url;
    const path = url.split('?')[0] ?? '/';
    const isHome = path === '/';
    const isGallery = path.startsWith('/gallery');

    const scrollToContact = (event: React.MouseEvent<HTMLAnchorElement>) => {
        const target = document.getElementById('contact');
        if (!target) return;
        event.preventDefault();
        target.scrollIntoView({ behavior: 'smooth' });
    };

    return (
        <header className="border-b border-[#F0F7FF]/15 bg-[#293241] text-[#F0F7FF]">
            <div className="mx-auto flex max-w-[1440px] flex-col items-center gap-4 px-5 pt-[calc(env(safe-area-inset-top)+1.5rem)] pb-5 sm:flex-row sm:items-center sm:justify-between sm:gap-6 sm:px-8 lg:px-12">
                <Link
                    href="/"
                    className="flex shrink-0 justify-center"
                    aria-label="Nastiia's Gallery home"
                >
                    <img
                        src="/nastias-gallery-logo.png"
                        alt=""
                        width={1067}
                        height={772}
                        className="m-0 h-16 w-auto max-w-[60vw] object-contain object-center invert sm:h-20"
                    />
                </Link>
                <nav
                    className="flex w-full min-w-0 [scrollbar-width:none] items-center justify-between gap-1 overflow-x-auto rounded-full border border-[#F0F7FF]/15 p-1 sm:w-auto sm:justify-start sm:gap-2 [&::-webkit-scrollbar]:hidden"
                    aria-label="Main"
                >
                    <Link
                        href="/"
                        className={`shrink-0 rounded-full px-3 py-2 text-xs font-semibold tracking-[0.14em] whitespace-nowrap uppercase transition-colors sm:px-5 sm:py-2.5 sm:text-sm ${
                            isHome
                                ? 'bg-[#EEF3F8] text-[#293241]'
                                : 'hover:bg-[#EEF3F8]/10'
                        }`}
                        aria-current={isHome ? 'page' : undefined}
                    >
                        Home
                    </Link>
                    <Link
                        href="/gallery"
                        className={`shrink-0 rounded-full px-3 py-2 text-xs font-semibold tracking-[0.14em] whitespace-nowrap uppercase transition-colors sm:px-5 sm:py-2.5 sm:text-sm ${
                            isGallery
                                ? 'bg-[#EEF3F8] text-[#293241]'
                                : 'hover:bg-[#EEF3F8]/10'
                        }`}
                        aria-current={isGallery ? 'page' : undefined}
                    >
                        Gallery
                    </Link>
                    <a
                        href="#contact"
                        onClick={scrollToContact}
                        className="shrink-0 rounded-full px-3 py-2 text-xs font-semibold tracking-[0.14em] whitespace-nowrap uppercase transition-colors hover:bg-[#EEF3F8]/10 sm:px-5 sm:py-2.5 sm:text-sm"
                    >
                        Contact us
                    </a>
                </nav>
            </div>
        </header>
    );
}
