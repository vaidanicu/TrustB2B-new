import Logo from '@/Components/Logo';
import InputError from '@/Components/InputError';
import { Head, Link, router, useForm } from '@inertiajs/react';
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

/**
 * Serves two cases from one component: the static marketing demo (no
 * signedUrl — ParticipantController@invitation, one sample event, submits
 * by navigating to a query-string confirmation, same as always) and the
 * real per-recipient flow (signedUrl present — ParticipantController@show,
 * submits by POSTing back to that exact signed address).
 */
export default function Invitation({ event, participantName, signedUrl }) {
    const isReal = Boolean(signedUrl);

    const [demoAttending, setDemoAttending] = useState(true);
    const [demoName, setDemoName] = useState('');
    const [demoEmail, setDemoEmail] = useState('');
    const [demoPhotoConsent, setDemoPhotoConsent] = useState(true);

    const { data, setData, post, processing, errors } = useForm({
        name: participantName ?? '',
        attending: true,
        photo_consent: true,
    });

    function submitReal(e) {
        e.preventDefault();
        post(signedUrl);
    }

    function requestDeletion() {
        if (confirm('Sigur vrei să ceri ștergerea datelor tale pentru acest eveniment? Organizatorul va procesa cererea.')) {
            router.patch(signedUrl);
        }
    }

    const attending = isReal ? data.attending : demoAttending;
    const name = isReal ? data.name : demoName;
    const photoConsent = isReal ? data.photo_consent : demoPhotoConsent;
    const setAttending = isReal ? (value) => setData('attending', value) : setDemoAttending;
    const setName = isReal ? (value) => setData('name', value) : setDemoName;
    const setPhotoConsent = isReal ? (value) => setData('photo_consent', value) : setDemoPhotoConsent;

    return (
        <div className="min-h-screen bg-paper pb-10">
            <Head title={event.name} />

            <div className="flex items-center justify-center pt-7 pb-5">
                <Logo height={20} />
            </div>

            <form onSubmit={isReal ? submitReal : (e) => e.preventDefault()} className="mx-auto w-full max-w-md px-4">
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
                                {isReal && <InputError message={errors.name} className="mt-1" />}
                            </div>
                            {!isReal && (
                                <div>
                                    <label className="block text-sm font-medium text-ink-soft">Email</label>
                                    <input
                                        type="email"
                                        value={demoEmail}
                                        onChange={(e) => setDemoEmail(e.target.value)}
                                        placeholder="nume@exemplu.ro"
                                        className="mt-1 block min-h-[44px] w-full rounded-md border-line text-base shadow-sm focus:border-accent focus:ring-accent"
                                    />
                                </div>
                            )}
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

                {isReal ? (
                    <button
                        type="submit"
                        disabled={processing}
                        className="mt-5 flex min-h-[48px] w-full items-center justify-center rounded-md bg-ink px-4 text-sm font-semibold uppercase tracking-widest text-white disabled:opacity-50"
                    >
                        {processing ? 'Se trimite…' : 'Trimite răspunsul'}
                    </button>
                ) : (
                    <Link
                        href={route('participants.confirmation', {
                            nume: demoName || 'Andrei',
                            particip: demoAttending ? 1 : 0,
                            poze: demoPhotoConsent ? 1 : 0,
                        })}
                        className="mt-5 flex min-h-[48px] w-full items-center justify-center rounded-md bg-ink px-4 text-sm font-semibold uppercase tracking-widest text-white"
                    >
                        Trimite răspunsul
                    </Link>
                )}

                <p className="mt-4 text-center text-xs text-ink-muted">
                    Acest link e personal și expiră pe {event.linkExpires}.
                </p>

                {isReal && (
                    <button
                        type="button"
                        onClick={requestDeletion}
                        className="mt-3 block w-full text-center text-xs text-ink-muted underline hover:text-danger-ink"
                    >
                        Vreau să fiu șters din sistem
                    </button>
                )}
            </form>
        </div>
    );
}
