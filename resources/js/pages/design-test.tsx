    import { Head } from '@inertiajs/react';
import LiveOrb from '@/components/ui/live-orb';
import { useAppearance } from '@/hooks/use-appearance';

// main-* tokens from resources/css/app.css (handoff palette: light / dark)
const tokens: [name: string, light: string, dark: string, role: string][] = [
    ['main-canvas', '#eef3fb', '#141b2b', 'page background'],
    ['main-surface', '#ffffff', '#1e2533', 'cards / panels'],
    ['main-ink', '#1e2533', '#e9eefb', 'primary text'],
    ['main-muted', '#4c5a70', '#aab8d2', 'secondary text'],
    ['main-blue', '#2563eb', '#a8c7e0', 'action / focus'],
    ['main-on-blue', '#ffffff', '#101828', 'text on blue'],
    ['main-line', '#ccd8ec', '#35415c', 'borders / dividers'],
    ['main-chip', '#dce8fb', '#273149', 'chips / badges'],
    ['main-red', '#c0392b', '#c0392b', 'error text'],
];

export default function DesignTest() {
    const { appearance, resolvedAppearance, updateAppearance } =
        useAppearance();

    return (
        <>
            <Head title="Design test" />
            <div className="min-h-screen bg-main-canvas text-main-ink">
                <div className="mx-auto max-w-5xl space-y-10 p-8">
                    <header className="flex flex-wrap items-center justify-between gap-4">
                        <div>
                            <h1 className="font-display text-4xl">
                                Magilio display — design test
                            </h1>
                            <p className="font-sans text-main-muted">
                                Libron body text. Palette flips with the
                                appearance toggle (top right).
                            </p>
                        </div>
                        <button
                            type="button"
                            onClick={() =>
                                updateAppearance(
                                    resolvedAppearance === 'dark'
                                        ? 'light'
                                        : 'dark',
                                )
                            }
                            className="rounded-full border border-main-line bg-main-surface px-4 py-2 text-sm font-bold"
                        >
                            appearance: {appearance}
                        </button>
                    </header>

                    <section className="space-y-3">
                        <h2 className="font-display text-2xl">Color tokens</h2>
                        <div className="grid grid-cols-2 gap-3 sm:grid-cols-3">
                            {tokens.map(([name, light, dark, role]) => (
                                <div
                                    key={name}
                                    className="overflow-hidden rounded-xl border border-main-line bg-main-surface"
                                >
                                    <div
                                        className="h-16"
                                        style={{
                                            backgroundColor: `var(--${name})`,
                                        }}
                                    />
                                    <div className="space-y-0.5 p-3 text-sm">
                                        <p className="font-bold">{name}</p>
                                        <p className="text-main-muted">
                                            {role}
                                        </p>
                                        <p className="font-mono text-xs">
                                            {light} / {dark}
                                        </p>
                                    </div>
                                </div>
                            ))}
                        </div>
                    </section>

                    <section className="space-y-3">
                        <h2 className="font-display text-2xl">
                            Utility samples
                        </h2>
                        <div className="flex flex-wrap items-center gap-4">
                            <button
                                type="button"
                                className="rounded-lg bg-main-blue px-5 py-2.5 font-bold text-main-on-blue"
                            >
                                primary action
                            </button>
                            <span className="rounded-full bg-main-chip px-3 py-1 text-sm font-bold text-main-blue">
                                chip
                            </span>
                            <span className="font-semibold text-main-red">
                                error message
                            </span>
                            <span className="text-main-muted">muted text</span>
                            <span className="border-b-2 border-main-line">
                                underline border
                            </span>
                        </div>
                        <div className="rounded-xl border border-main-line bg-main-surface p-4">
                            <p className="mb-1 font-semibold">surface card</p>
                            <p className="text-main-muted">
                                Nested on the canvas background — border uses
                                main-line.
                            </p>
                        </div>
                    </section>

                    <section className="space-y-3">
                        <h2 className="font-display text-2xl">LiveOrb</h2>
                        <p className="text-sm text-main-muted">
                            Move your pointer to steer the gaze; each orb
                            blinks. A hidden orb (opacity 0, below) should not
                            keep drawing — check Activity Monitor / Task Manager
                            if curious.
                        </p>
                        <div className="flex flex-wrap items-center gap-8 rounded-xl border border-main-line bg-main-surface p-6">
                            <div className="text-center">
                                <LiveOrb size={140} />
                                <p className="mt-2 text-sm text-main-muted">
                                    default (white)
                                </p>
                            </div>
                            <div className="text-center">
                                <LiveOrb
                                    size={140}
                                    variant="custom"
                                    color="#98C1D9"
                                    eyeColor="#293241"
                                />
                                <p className="mt-2 text-sm text-main-muted">
                                    gallery custom
                                </p>
                            </div>
                            <div className="text-center">
                                <LiveOrb size={140} variant="webgl" />
                                <p className="mt-2 text-sm text-main-muted">
                                    webgl wash
                                </p>
                            </div>
                            <div className="text-center opacity-0" aria-hidden>
                                <LiveOrb
                                    size={140}
                                    variant="custom"
                                    color="#E63946"
                                    eyeColor="#293241"
                                />
                                <p className="mt-2 text-sm">hidden (paused)</p>
                            </div>
                        </div>
                    </section>
                </div>
            </div>
        </>
    );
}
