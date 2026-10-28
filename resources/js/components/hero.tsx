import { useState } from 'react';

export default function Hero() {
    const [{ x, y }, setPos] = useState({ x: 0, y: 0 });

    return (
        <section
            className="relative flex h-screen items-center justify-center overflow-hidden bg-[#0a0a14]"
            onMouseMove={(event) =>
                setPos({ x: event.clientX, y: event.clientY })
            }
        >
            {/* Blue cursor glow — z-0, BEHIND the card, so the opaque red box
          blocks its center and light only spills out past the edges */}
            <div
                aria-hidden="true"
                className="pointer-events-none absolute inset-0 z-0"
                style={{
                    background: `radial-gradient(600px circle at ${x}px ${y}px, rgba(59, 130, 246, 0.6), transparent 65%)`,
                }}
            />

            {/* Fully opaque card — z-10 above the glow */}
            <div className="relative z-10 w-full max-w-xl bg-red-600 px-10 py-12">
                {/* PASTE YOUR MAIN HEADING HERE */}
                <h1 className="mb-5 text-4xl font-bold text-white sm:text-5xl">
                    Main Heading
                </h1>

                {/* PASTE YOUR PARAGRAPH TEXT HERE — 4 lines, one span per line */}
                <div className="text-base leading-7 text-white/90 sm:text-lg">
                    {/* line 1 */}
                    <span className="block">Paragraph line one goes here.</span>
                    {/* line 2 */}
                    <span className="block">Paragraph line two goes here.</span>
                    {/* line 3 */}
                    <span className="block">
                        Paragraph line three goes here.
                    </span>
                    {/* line 4 */}
                    <span className="block">
                        Paragraph line four goes here.
                    </span>
                </div>
            </div>
        </section>
    );
}
