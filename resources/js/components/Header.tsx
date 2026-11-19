import { Link, usePage, router } from '@inertiajs/react';
import { LogOut, Settings, Menu, X } from 'lucide-react';
import LanguageSwitcher from '@/components/language-switcher';
import Switch from '@/components/ui/sky-toggle';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    Sheet,
    SheetContent,
    SheetTrigger,
} from '@/components/ui/sheet';
import { useAppearance } from '@/hooks/use-appearance';
import { useInitials } from '@/hooks/use-initials';
import { useTranslation } from '@/lib/i18n';
import { useState } from 'react';
import { edit } from '@/routes/profile';
import { logout } from '@/routes';

export default function Header() {
    const page = usePage();
    const { t } = useTranslation();
    const { resolvedAppearance, updateAppearance } = useAppearance();
    const url = page.url;
    const path = url.split('?')[0] ?? '/';
    const isHome = path === '/';
    const isGallery = path.startsWith('/gallery');
    const user = page.props.auth?.user ?? null;
    const getInitials = useInitials();
    const [mobileMenuOpen, setMobileMenuOpen] = useState(false);

    const scrollToContact = (event: React.MouseEvent<Element>) => {
        const target = document.getElementById('contact');
        if (!target) return;
        event.preventDefault();
        target.scrollIntoView({ behavior: 'smooth' });
        setMobileMenuOpen(false);
    };

    const handleLogout = () => {
        router.flushAll();
        setMobileMenuOpen(false);
    };

    const navigationLinks = [
        { href: '/', label: t('nav.home'), active: isHome },
        { href: '/gallery', label: t('nav.gallery'), active: isGallery },
        { href: '#contact', label: t('nav.contact'), onClick: scrollToContact },
    ];

    return (
        <>
            {/* Mobile Header with Sidebar */}
            <header className="border-b border-border/15 bg-header text-header-foreground lg:hidden">
                <Sheet open={mobileMenuOpen} onOpenChange={setMobileMenuOpen}>
                    <div className="grid h-16 w-full grid-cols-[1fr_auto_1fr] items-center sm:h-20 md:h-24">
                        {/* Left column: hamburger button */}
                        <SheetTrigger asChild>
                            <Button
                                variant="ghost"
                                size="icon"
                                className="h-10 w-10 justify-self-start"
                                aria-label={t('nav.menu')}
                                aria-expanded={mobileMenuOpen}
                            >
                                {mobileMenuOpen ? (
                                    <X className="h-5 w-5" aria-hidden="true" />
                                ) : (
                                    <Menu
                                        className="h-5 w-5"
                                        aria-hidden="true"
                                    />
                                )}
                            </Button>
                        </SheetTrigger>

                        {/* Center column: logo - links to home */}
                        <Link
                            href="/"
                            className="flex min-w-0 items-center justify-self-center"
                            aria-label={t('nav.home')}
                        >
                            <img
                                src="/nastias-gallery-logo.png"
                                alt=""
                                width={1067}
                                height={772}
                                className="h-12 w-auto max-w-[55vw] object-contain object-center invert sm:h-16 md:h-20"
                            />
                        </Link>

                        {/* Right column: empty spacer (for symmetry) */}
                        <div className="w-10" aria-hidden="true" />
                    </div>

                    <SheetContent side="left" className="w-72 p-0 sm:w-80">
                        <nav
                            className="flex flex-1 flex-col overflow-y-auto px-4 py-6"
                            aria-label={t('nav.main')}
                        >
                            <div className="h-4" aria-hidden="true" />
                            {user ? (
                                <DropdownMenu>
                                    <DropdownMenuTrigger asChild>
                                        <Button
                                            variant="ghost"
                                            className="w-full justify-start gap-3 rounded-lg px-4 py-3 text-sm font-medium hover:bg-accent"
                                        >
                                            <Avatar className="size-8 overflow-hidden rounded-full">
                                                <AvatarImage
                                                    src={
                                                        user.avatar ?? undefined
                                                    }
                                                    alt={user.display_name}
                                                />
                                                <AvatarFallback className="rounded-lg bg-neutral-200 text-black">
                                                    {getInitials(
                                                        user.display_name ?? '',
                                                    )}
                                                </AvatarFallback>
                                            </Avatar>
                                            <span className="flex-1 truncate">
                                                {user.display_name}
                                            </span>
                                        </Button>
                                    </DropdownMenuTrigger>
                                    <DropdownMenuContent
                                        className="w-56"
                                        align="end"
                                    >
                                        <DropdownMenuLabel className="p-0 font-normal">
                                            <div className="flex items-center gap-2 px-1 py-1.5 text-left text-sm">
                                                <span className="font-medium">
                                                    {user.display_name}
                                                </span>
                                                <span className="text-xs text-muted-foreground">
                                                    {user.email}
                                                </span>
                                            </div>
                                        </DropdownMenuLabel>
                                        <DropdownMenuSeparator />
                                        <DropdownMenuItem asChild>
                                            <Link
                                                className="block flex w-full cursor-pointer items-center gap-2"
                                                href={edit()}
                                                prefetch
                                            >
                                                <Settings className="mr-2 size-4" />
                                                {t('nav.settings')}
                                            </Link>
                                        </DropdownMenuItem>
                                        <DropdownMenuSeparator />
                                        <DropdownMenuItem asChild>
                                            <Link
                                                className="block flex w-full cursor-pointer items-center gap-2 text-destructive focus:text-destructive-foreground"
                                                href={logout()}
                                                as="button"
                                                onClick={handleLogout}
                                            >
                                                <LogOut className="mr-2 size-4" />
                                                {t('nav.log_out')}
                                            </Link>
                                        </DropdownMenuItem>
                                    </DropdownMenuContent>
                                </DropdownMenu>
                            ) : (
                                <Link
                                    href="/login"
                                    className="flex items-center gap-3 rounded-lg px-4 py-3 text-sm font-medium text-foreground/70 transition-colors hover:bg-accent hover:text-foreground"
                                >
                                    {t('nav.account')}
                                </Link>
                            )}
                            <div className="flex items-center justify-between gap-4 border-t border-border/15 pt-4">
                                <LanguageSwitcher />
                                <Switch
                                    checked={resolvedAppearance === 'dark'}
                                    onChange={() =>
                                        updateAppearance(
                                            resolvedAppearance === 'dark'
                                                ? 'light'
                                                : 'dark',
                                        )
                                    }
                                    aria-label="Toggle theme"
                                    className="[--sky-toggle-size:11px]"
                                />
                            </div>
                            <div className="mt-4 flex flex-col space-y-2">
                                {navigationLinks.map((link) => (
                                    <Link
                                        key={link.href}
                                        href={link.href}
                                        onClick={link.onClick}
                                        className={`flex items-center gap-3 rounded-lg px-4 py-3 text-sm font-medium transition-colors ${
                                            link.active
                                                ? 'bg-primary text-primary-foreground'
                                                : 'text-foreground/70 hover:bg-accent hover:text-foreground'
                                        }`}
                                        aria-current={
                                            link.active ? 'page' : undefined
                                        }
                                    >
                                        {link.label}
                                    </Link>
                                ))}
                            </div>
                        </nav>
                    </SheetContent>
                </Sheet>
            </header>

            {/* Desktop Header */}
            <header className="hidden border-b border-border/15 bg-header text-header-foreground lg:flex">
                <div className="mx-auto flex max-w-[1440px] flex-col items-center gap-4 px-5 pt-[calc(env(safe-area-inset-top)+1.5rem)] pb-5 sm:flex-row sm:items-center sm:justify-between sm:gap-6 sm:px-8 lg:px-12">
                    <Link
                        href="/"
                        className="flex shrink-0"
                        aria-label={t('nav.home')}
                    >
                        <img
                            src="/nastias-gallery-logo.png"
                            alt=""
                            width={1067}
                            height={772}
                            className="m-0 h-[5.6rem] w-auto max-w-[60vw] object-contain object-center invert sm:h-28"
                        />
                    </Link>
                    <nav
                        className="flex w-full min-w-0 [scrollbar-width:none] items-center justify-end gap-1 overflow-x-auto rounded-full border border-border/15 p-1 sm:w-auto sm:justify-end sm:gap-2 [&::-webkit-scrollbar]:hidden"
                        aria-label={t('nav.main')}
                    >
                        <Link
                            href="/"
                            className={`shrink-0 rounded-full px-3 py-2 text-xs font-semibold tracking-[0.14em] whitespace-nowrap uppercase transition-colors sm:px-5 sm:py-2.5 sm:text-sm ${
                                isHome
                                    ? 'bg-header-foreground text-header'
                                    : 'hover:bg-header-foreground/10'
                            }`}
                            aria-current={isHome ? 'page' : undefined}
                        >
                            {t('nav.home')}
                        </Link>
                        <Link
                            href="/gallery"
                            className={`shrink-0 rounded-full px-3 py-2 text-xs font-semibold tracking-[0.14em] whitespace-nowrap uppercase transition-colors sm:px-5 sm:py-2.5 sm:text-sm ${
                                isGallery
                                    ? 'bg-header-foreground text-header'
                                    : 'hover:bg-header-foreground/10'
                            }`}
                            aria-current={isGallery ? 'page' : undefined}
                        >
                            {t('nav.gallery')}
                        </Link>
                        <a
                            href="#contact"
                            onClick={scrollToContact}
                            className="shrink-0 rounded-full px-3 py-2 text-xs font-semibold tracking-[0.14em] whitespace-nowrap uppercase transition-colors hover:bg-header-foreground/10 sm:px-5 sm:py-2.5 sm:text-sm"
                        >
                            {t('nav.contact')}
                        </a>
                        {user ? (
                            <DropdownMenu>
                                <DropdownMenuTrigger asChild>
                                    <Button
                                        variant="ghost"
                                        className="size-10 rounded-full p-1 hover:bg-header-foreground/10!"
                                    >
                                        <Avatar className="size-8 overflow-hidden rounded-full">
                                            <AvatarImage
                                                src={user.avatar ?? undefined}
                                                alt={user.display_name}
                                            />
                                            <AvatarFallback className="rounded-lg bg-neutral-200 text-black">
                                                {getInitials(
                                                    user.display_name ?? '',
                                                )}
                                            </AvatarFallback>
                                        </Avatar>
                                    </Button>
                                </DropdownMenuTrigger>
                                <DropdownMenuContent
                                    className="w-56"
                                    align="end"
                                >
                                    <DropdownMenuLabel className="p-0 font-normal">
                                        <div className="flex items-center gap-2 px-1 py-1.5 text-left text-sm">
                                            <span className="font-medium">
                                                {user.display_name}
                                            </span>
                                            <span className="text-xs text-muted-foreground">
                                                {user.email}
                                            </span>
                                        </div>
                                    </DropdownMenuLabel>
                                    <DropdownMenuSeparator />
                                    <DropdownMenuItem asChild>
                                        <Link
                                            className="block flex w-full cursor-pointer items-center gap-2"
                                            href={edit()}
                                            prefetch
                                        >
                                            <Settings className="mr-2 size-4" />
                                            {t('nav.settings')}
                                        </Link>
                                    </DropdownMenuItem>
                                    <DropdownMenuSeparator />
                                    <DropdownMenuItem asChild>
                                        <Link
                                            className="block flex w-full cursor-pointer items-center gap-2 text-destructive focus:text-destructive-foreground"
                                            href={logout()}
                                            as="button"
                                            onClick={handleLogout}
                                        >
                                            <LogOut className="mr-2 size-4" />
                                            {t('nav.log_out')}
                                        </Link>
                                    </DropdownMenuItem>
                                </DropdownMenuContent>
                            </DropdownMenu>
                        ) : (
                            <Link
                                href="/login"
                                className={`shrink-0 rounded-full px-3 py-2 text-xs font-semibold tracking-[0.14em] whitespace-nowrap uppercase transition-colors hover:bg-header-foreground/10 sm:px-5 sm:py-2.5 sm:text-sm`}
                            >
                                {t('nav.account')}
                            </Link>
                        )}

                        <LanguageSwitcher className="sm:ml-1" />
                        <Switch
                            checked={resolvedAppearance === 'dark'}
                            onChange={() =>
                                updateAppearance(
                                    resolvedAppearance === 'dark'
                                        ? 'light'
                                        : 'dark',
                                )
                            }
                            aria-label="Toggle theme"
                            className="flex shrink-0 items-center [--sky-toggle-size:10px] sm:ml-1 sm:[--sky-toggle-size:11px]"
                        />
                    </nav>
                </div>
            </header>
        </>
    );
}
