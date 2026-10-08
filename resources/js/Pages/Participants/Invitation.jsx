import BrandMark from '@/Components/BrandMark';
import { Head, Link } from '@inertiajs/react';
import { useState } from 'react';

function RsvpOption({ selected, onClick, label, hint }) {
    return (
        <button
            type="button"
            onClick={onClick}
            className={
                'flex min-h-[64px] flex-1 flex-col items-center justify-center gap-0.5 rounded-lg border px-4 py-3 text-center transition ' +
                (selected ? 'border-ink bg-ink text-white' : 'border-line bg-white text-ink-soft')
            }
        >
            <span className="text-sm font-semibold">{label}</span>
            {hint && <span className={'text-xs ' + (selected ? 'text-white/60' : 'text-ink-muted')}>{hint}</span>}
        </button>
    );
}

export default function Invitation({ event }) {
    const [attending, setAttending] = useState(true);
    const [name, setName] = useState('');
    const [email, setEmail] = useState('');
    const [photoConsent, setPhotoConsent] = useState(true);

    return (
        <div className="min-h-screen bg-paper pb-10">
            <Head title={event.name} />

            <div className="flex items-center justify-center gap-2 pt-7 pb-5">
                <BrandMark size={24} className="text-ink-muted" />
                <span className="text-[15px] font-semibold text-ink-soft">TrustBridge</span>
            </div>

            <div className="mx-auto w-full max-w-md px-4">
                <div className="rounded-lg bg-white p-5 shadow-sm">
                    <p className="text-xs font-medium uppercase tracking-wide text-ink-muted">{event.organizer} te invită</p>
                    <h1 className="mt-1 text-lg font-semibold leading-snug text-ink">{event.name}</h1>
                    <p className="mt-2 text-sm text-ink-soft">
                        {event.date} · {event.location}
                    </p>

                    <a href="#" className="mt-3 inline-block text-xs text-accent underline">
                        Informații privind protecția datelor (versiunea {event.documentVersion})
                    </a>
                </div>

                <div className="mt-4 rounded-lg bg-white p-5 shadow-sm">
                    <p className="mb-3 text-sm font-medium text-ink-soft">Participi?</p>
                    <div className="flex gap-3">
                        <RsvpOption selected={attending} onClick={() => setAttending(true)} label="Particip" />
                        <RsvpOption selected={!attending} onClick={() => setAttending(false)} label="Nu particip" />
                    </div>

                    {attending && (
                        <div className="mt-5 flex flex-col gap-4 border-t border-line pt-5">
                            <div>
                                <label className="block text-sm font-medium text-ink-soft">Nume complet</label>
                                <input
                                    type="text"
                                    value={name}
                                    onChange={(e) => setName(e.target.value)}
                                    placeholder="ex. Andrei Popescu"
                                    className="mt-1 block min-h-[44px] w-full rounded-md border-line text-base shadow-sm focus:border-accent focus:ring-accent"
                                />
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-ink-soft">Email</label>
                                <input
                                    type="email"
                                    value={email}
                                    onChange={(e) => setEmail(e.target.value)}
                                    placeholder="nume@exemplu.ro"
                                    className="mt-1 block min-h-[44px] w-full rounded-md border-line text-base shadow-sm focus:border-accent focus:ring-accent"
                                />
                            </div>
                        </div>
                    )}
                </div>

                <div className="mt-4 rounded-lg bg-white p-5 shadow-sm">
                    <label className="flex min-h-[44px] items-start gap-3">
                        <input
                            type="checkbox"
                            checked={photoConsent}
                            onChange={(e) => setPhotoConsent(e.target.checked)}
                            className="mt-0.5 h-5 w-5 rounded border-line text-accent focus:ring-accent"
                        />
                        <span className="text-sm text-ink-soft">
                            <span className="block font-medium text-ink">Preferințe foto (opțional)</span>
                            Sunt de acord să apar în fotografiile oficiale ale evenimentului.
                        </span>
                    </label>
                    <p className="mt-2 text-xs text-ink-muted">
                        Răspunsul tău la eveniment nu depinde de această alegere — o poți schimba oricând.
                    </p>
                </div>

                <Link
                    href={route('participants.confirmation', {
                        nume: name || 'Andrei',
                        particip: attending ? 1 : 0,
                        poze: photoConsent ? 1 : 0,
                    })}
                    className="mt-5 flex min-h-[48px] w-full items-center justify-center rounded-md bg-ink px-4 text-sm font-semibold uppercase tracking-widest text-white"
                >
                    Trimite răspunsul
                </Link>

                <p className="mt-4 text-center text-xs text-ink-muted">
                    Acest link e personal și expiră pe {event.linkExpires}.
                </p>
            </div>
        </div>
    );
}
