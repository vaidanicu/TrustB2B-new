import Logo from '@/Components/Logo';
import { Head, router } from '@inertiajs/react';
import { useState } from 'react';

function PasswordBox({ password }) {
    const [copied, setCopied] = useState(false);

    async function copy() {
        try {
            await navigator.clipboard.writeText(password);
            setCopied(true);
        } catch {
            setCopied(false);
        }
    }

    return (
        <div className="mt-4 flex items-center justify-between gap-3 rounded-md border border-line bg-paper-raised px-4 py-3">
            <div>
                <p className="text-xs text-ink-muted">Parola arhivei</p>
                <p className="font-mono text-base font-semibold tracking-wide text-ink">{password}</p>
            </div>
            <button
                type="button"
                onClick={copy}
                className="shrink-0 rounded-md border border-line px-3 py-1.5 text-xs font-semibold uppercase tracking-wide text-ink-soft"
            >
                {copied ? 'Copiat ✓' : 'Copiază'}
            </button>
        </div>
    );
}

export default function PackageDelivery({ eventName, signedUrl, confirmed, password, downloadUrl }) {
    function confirmReceipt() {
        if (confirm('Confirmi că ai descărcat și salvat pachetul? Fotografiile originale vor fi șterse definitiv de pe platformă.')) {
            router.post(signedUrl);
        }
    }

    return (
        <div className="min-h-screen bg-paper pb-10">
            <Head title={eventName} />

            <div className="flex items-center justify-center pt-7 pb-5">
                <Logo height={20} />
            </div>

            <div className="mx-auto w-full max-w-md px-4">
                <div className="rounded-lg bg-white p-6 shadow-sm">
                    <h1 className="font-display text-lg font-semibold text-ink">{eventName}</h1>

                    {confirmed ? (
                        <>
                            <span className="mt-4 flex h-12 w-12 items-center justify-center rounded-full bg-teal-soft">
                                <svg width="24" height="24" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M4 10.5l4 4 8-9" stroke="#2c6a5d" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" />
                                </svg>
                            </span>
                            <p className="mt-3 text-sm text-ink-soft">
                                Primirea pachetului a fost confirmată. Fotografiile și video-urile originale au fost șterse definitiv de pe
                                platformă.
                            </p>
                        </>
                    ) : (
                        <>
                            <p className="mt-2 text-sm text-ink-soft">
                                Fotografiile și video-urile acestui eveniment au ajuns la finalul perioadei de păstrare. Descarcă arhiva criptată
                                mai jos, apoi confirmă primirea — abia după confirmare se șterg fișierele originale de pe platformă.
                            </p>

                            <a
                                href={downloadUrl}
                                className="mt-4 inline-flex items-center justify-center rounded-md bg-ink px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white"
                            >
                                Descarcă pachetul (.zip criptat)
                            </a>

                            <PasswordBox password={password} />

                            <button
                                type="button"
                                onClick={confirmReceipt}
                                className="mt-4 block w-full rounded-md border border-line py-2 text-center text-xs font-semibold uppercase tracking-wide text-ink-soft hover:border-danger hover:text-danger-ink"
                            >
                                Confirm primirea și șterge originalele
                            </button>
                        </>
                    )}
                </div>
            </div>
        </div>
    );
}
