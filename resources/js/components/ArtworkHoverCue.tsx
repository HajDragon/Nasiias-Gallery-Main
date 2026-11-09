export default function ArtworkHoverCue() {
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
