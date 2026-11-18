import { useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';

export default function ContactFooter() {
    const form = useForm({ name: '', email: '', message: '', consent: false });

    function submit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        form.post('/contact', {
            preserveScroll: true,
            onSuccess: () => form.reset(),
        });
    }

    return (
        <footer id="contact" className="bg-[#293241] text-[#F0F7FF]">
            <div className="mx-auto grid max-w-[1440px] gap-10 px-5 py-12 sm:px-8 sm:py-16 lg:grid-cols-[0.8fr_1.2fr] lg:gap-20 lg:px-12 lg:py-20">
                <div className="flex flex-col justify-between gap-10">
                    <div>
                        <p className="mb-4 text-[10px] font-semibold tracking-[0.22em] text-[#98C1D9] uppercase">
                            Contact us
                        </p>
                        <h2 className="max-w-md font-display text-4xl leading-[0.95] tracking-[-0.04em] sm:text-5xl lg:text-6xl">
                            Let&apos;s talk about art.
                        </h2>
                        <p className="mt-5 max-w-sm text-sm leading-6 text-[#F0F7FF]/65">
                            Questions about a work, an artist, or a future
                            visit? Send us a note and we&apos;ll be in touch.
                        </p>
                    </div>
                </div>

                <form
                    className="grid gap-6"
                    aria-label="Contact Stillhouse Gallery"
                    onSubmit={submit}
                >
                    <div className="grid gap-6 sm:grid-cols-2">
                        <label className="grid gap-2 text-[10px] font-semibold tracking-[0.18em] uppercase">
                            Name
                            <input
                                type="text"
                                name="name"
                                value={form.data.name}
                                onChange={(event) =>
                                    form.setData('name', event.target.value)
                                }
                                maxLength={255}
                                autoComplete="name"
                                required
                                placeholder="Your name"
                                className="rounded-lg border border-[#F0F7FF]/20 bg-white px-4 py-3 text-base tracking-normal text-[#293241] normal-case transition-colors outline-none placeholder:text-[#293241]/40 focus:border-[#98C1D9] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#98C1D9]"
                            />
                        </label>
                        <label className="grid gap-2 text-[10px] font-semibold tracking-[0.18em] uppercase">
                            Email
                            <input
                                type="email"
                                name="email"
                                value={form.data.email}
                                onChange={(event) =>
                                    form.setData('email', event.target.value)
                                }
                                maxLength={255}
                                autoComplete="email"
                                required
                                placeholder="you@example.com"
                                className="rounded-lg border border-[#F0F7FF]/20 bg-white px-4 py-3 text-base tracking-normal text-[#293241] normal-case transition-colors outline-none placeholder:text-[#293241]/40 focus:border-[#98C1D9] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#98C1D9]"
                            />
                        </label>
                    </div>
                    <label className="grid gap-2 text-[10px] font-semibold tracking-[0.18em] uppercase">
                        Message
                        <textarea
                            name="message"
                            value={form.data.message}
                            onChange={(event) =>
                                form.setData('message', event.target.value)
                            }
                            maxLength={10000}
                            required
                            rows={4}
                            placeholder="Tell us how we can help"
                            className="resize-none rounded-lg border border-[#F0F7FF]/20 bg-white px-4 py-3 text-base leading-6 tracking-normal text-[#293241] normal-case transition-colors outline-none placeholder:text-[#293241]/40 focus:border-[#98C1D9] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#98C1D9]"
                        />
                    </label>
                    <label className="flex items-start gap-3 text-xs leading-5 text-[#F0F7FF]/75">
                        <input
                            type="checkbox"
                            name="consent"
                            checked={form.data.consent}
                            onChange={(event) =>
                                form.setData('consent', event.target.checked)
                            }
                            required
                            className="mt-0.5 h-4 w-4 shrink-0 accent-[#98C1D9]"
                        />
                        <span>
                            I agree that my details and message will be used to
                            respond to my enquiry.
                        </span>
                    </label>
                    {Object.entries(form.errors).map(([field, error]) => (
                        <p
                            key={field}
                            role="alert"
                            className="text-sm text-red-200"
                        >
                            {error}
                        </p>
                    ))}
                    {form.recentlySuccessful && (
                        <p role="status" className="text-sm text-[#98C1D9]">
                            Thank you. Your message has been submitted.
                            We&apos;ll be in touch.
                        </p>
                    )}
                    <button
                        type="submit"
                        disabled={form.processing}
                        className="mt-2 flex w-full items-center justify-between rounded-full bg-[#98C1D9] px-6 py-4 text-xs font-semibold tracking-[0.18em] text-[#293241] uppercase transition-colors hover:bg-[#EEF3F8] focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-[#98C1D9] sm:w-auto sm:min-w-52"
                    >
                        {form.processing ? 'Sending...' : 'Send message'}
                        <span aria-hidden="true">→</span>
                    </button>
                </form>
            </div>
        </footer>
    );
}
