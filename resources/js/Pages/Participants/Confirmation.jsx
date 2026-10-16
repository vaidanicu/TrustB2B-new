import Logo from '@/Components/Logo';
import { Head, Link } from '@inertiajs/react';

export default function Confirmation({ event, participantName, attending, photoConsent, photos = [] }) {
    return (
        <div className="min-h-screen bg-paper pb-10">
            <Head title="Răspuns înregistrat" />

            <div className="flex items-center justify-center pt-7 pb-5">
                <Logo height={20} />
            </div>

            <div className="mx-auto w-full max-w-md px-4">
                <div className="rounded-lg bg-white p-6 text-center shadow-sm">
                    <span className="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-teal-soft">
                        <svg width="24" height="24" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M4 10.5l4 4 8-9" stroke="#2c6a5d" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" />
                        </svg>
                    </span>

                    <h1 className="mt-4 font-display text-lg font-semibold text-ink">Mulțumim, {participantName}!</h1>

                    <p className="mt-2 text-sm text-ink-soft">
                        Am înregistrat răspunsul tău:{' '}
                        <strong className="text-ink">{attending ? 'participi' : 'nu participi'}</strong> la{' '}
                        <strong className="text-ink">{event.name}</strong>.
                    </p>

                    {attending && (
                        <p className="mt-2 text-sm text-ink-muted">
                            Preferință foto: {photoConsent ? 'ești de acord să apari în fotografiile oficiale.' : 'ai ales să nu apari în fotografiile oficiale.'}
                        </p>
                    )}

                    <p className="mt-4 text-xs text-ink-muted">
                        Poți reveni oricând la acest link ca să-ți schimbi răspunsul, până pe {event.linkExpires}.
                    </p>
                </div>

                {photos.length > 0 && (
                    <div className="mt-4 rounded-lg bg-white p-5 shadow-sm">
                        <p className="mb-3 text-sm font-medium text-ink">Galerie foto</p>
                        <div className="grid grid-cols-3 gap-2">
                            {photos.map((photo) => (
                                <a key={photo.id} href={photo.url} target="_blank" rel="noreferrer">
                                    <img src={photo.url} alt="" className="aspect-square w-full rounded-md object-cover" />
                                </a>
                            ))}
                        </div>
                    </div>
                )}

                <Link href={route('participants.invitation')} className="mt-4 block text-center text-sm text-ink-muted underline">
                    Înapoi la invitație
                </Link>
            </div>
        </div>
    );
}
