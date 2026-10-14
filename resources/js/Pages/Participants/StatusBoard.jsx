import AppLayout from '@/Layouts/AppLayout';
import InputError from '@/Components/InputError';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { useState } from 'react';

// Grupele exacte cerute de A-05: eingeladen, zugestellt, geöffnet,
// teilnehmend, nicht teilnehmend, keine Antwort, unzustellbar, zurückgezogen.
// delivered/undeliverable/no_response/withdrawn nu sunt încă scrise de
// backend (AP10-13 nu are trimitere reală de email sau măturare automată),
// dar grupele rămân aici ca să nu dispară din tablou când vor exista.
const STATUS_GROUPS = [
    { key: 'invited', label: 'Invitați' },
    { key: 'delivered', label: 'Livrate' },
    { key: 'opened', label: 'Deschise' },
    { key: 'attending', label: 'Participă' },
    { key: 'declined', label: 'Nu participă' },
    { key: 'no_response', label: 'Fără răspuns' },
    { key: 'undeliverable', label: 'Nelivrabile' },
    { key: 'withdrawn', label: 'Retrase' },
];

const ROW_STYLES = {
    attending: { label: 'Participă', dot: 'bg-teal', text: 'text-teal-ink', bg: 'bg-teal-soft' },
    declined: { label: 'Nu participă', dot: 'bg-ink-muted', text: 'text-ink-soft', bg: 'bg-paper-sunken' },
    invited: { label: 'Invitat', dot: 'bg-amber-600', text: 'text-amber-700', bg: 'bg-amber-50' },
    delivered: { label: 'Livrată', dot: 'bg-amber-600', text: 'text-amber-700', bg: 'bg-amber-50' },
    opened: { label: 'Deschisă', dot: 'bg-amber-600', text: 'text-amber-700', bg: 'bg-amber-50' },
    no_response: { label: 'Fără răspuns', dot: 'bg-amber-600', text: 'text-amber-700', bg: 'bg-amber-50' },
    undeliverable: { label: 'Nelivrabilă', dot: 'bg-red-600', text: 'text-red-700', bg: 'bg-red-50' },
    withdrawn: { label: 'Retrasă', dot: 'bg-ink-muted', text: 'text-ink-muted', bg: 'bg-paper-sunken' },
};

function InviteForm({ eventId }) {
    const { data, setData, post, processing, errors, reset } = useForm({ name: '', email: '' });

    function submit(e) {
        e.preventDefault();
        post(route('participants.store', eventId), { onSuccess: () => reset() });
    }

    return (
        <form onSubmit={submit} className="mb-6 flex flex-wrap items-end gap-3 rounded-lg bg-white p-4 shadow-sm">
            <div className="min-w-[200px] flex-1">
                <label className="block text-xs font-medium text-ink-soft">Nume</label>
                <input
                    type="text"
                    value={data.name}
                    onChange={(e) => setData('name', e.target.value)}
                    placeholder="ex. Andrei Popescu"
                    className="mt-1 block w-full rounded-md border-line text-sm shadow-sm focus:border-accent focus:ring-accent"
                />
                <InputError message={errors.name} className="mt-1" />
            </div>
            <div className="min-w-[220px] flex-1">
                <label className="block text-xs font-medium text-ink-soft">Email</label>
                <input
                    type="email"
                    value={data.email}
                    onChange={(e) => setData('email', e.target.value)}
                    placeholder="nume@exemplu.ro"
                    className="mt-1 block w-full rounded-md border-line text-sm shadow-sm focus:border-accent focus:ring-accent"
                />
                <InputError message={errors.email} className="mt-1" />
            </div>
            <button
                type="submit"
                disabled={processing}
                className="inline-flex h-[38px] items-center rounded-md border border-transparent bg-ink px-4 text-xs font-semibold uppercase tracking-widest text-white transition hover:bg-ink-soft disabled:opacity-50"
            >
                Invită
            </button>
        </form>
    );
}

function InvitationLinkBanner({ url }) {
    const [copied, setCopied] = useState(false);

    async function copy() {
        try {
            await navigator.clipboard.writeText(url);
            setCopied(true);
        } catch {
            setCopied(false);
        }
    }

    return (
        <div className="mb-5 flex flex-wrap items-center justify-between gap-3 rounded-md border border-teal bg-teal-soft px-4 py-3 text-sm text-teal-ink">
            <span className="break-all">Link de invitație: {url}</span>
            <button type="button" onClick={copy} className="shrink-0 rounded-md border border-teal-ink/30 px-3 py-1 text-xs font-semibold uppercase tracking-wide">
                {copied ? 'Copiat ✓' : 'Copiază'}
            </button>
        </div>
    );
}

export default function StatusBoard({ event, participants }) {
    const { flash } = usePage().props;

    const counts = Object.fromEntries(STATUS_GROUPS.map(({ key }) => [key, 0]));
    participants.forEach((participant) => {
        counts[participant.status] = (counts[participant.status] ?? 0) + 1;
    });

    return (
        <AppLayout title={event.name} active="dashboard">
            <Head title={`Participanți — ${event.name}`} />

            <div className="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                <div className="-mt-2 mb-6 flex items-center justify-between">
                    <Link href={route('dashboard')} className="text-sm text-ink-muted hover:text-ink">
                        ← Evenimente
                    </Link>
                    <p className="text-sm text-ink-muted">
                        {event.date} · {event.location}
                    </p>
                </div>

                {flash?.status && (
                    <div className="mb-5 rounded-md border border-teal bg-teal-soft px-4 py-3 text-sm text-teal-ink">
                        {flash.status}
                    </div>
                )}
                {flash?.invitationUrl && <InvitationLinkBanner url={flash.invitationUrl} />}

                <InviteForm eventId={event.id} />

                <div className="mb-7 grid grid-cols-2 gap-3 sm:grid-cols-4 lg:grid-cols-8">
                    {STATUS_GROUPS.map((group) => (
                        <div key={group.key} className="rounded-lg bg-white p-4 shadow-sm">
                            <div className="text-[11px] font-semibold uppercase leading-tight tracking-wide text-ink-muted">
                                {group.label}
                            </div>
                            <div className="mt-1.5 text-2xl font-semibold text-ink">{counts[group.key]}</div>
                        </div>
                    ))}
                </div>

                <div className="overflow-hidden rounded-lg bg-white shadow-sm">
                    <div className="grid grid-cols-[1.4fr_1.6fr_1fr_1fr] gap-3 border-b border-line px-5 py-3 text-xs font-semibold uppercase tracking-wide text-ink-muted">
                        <span>Nume</span>
                        <span>Email</span>
                        <span>Stare</span>
                        <span>Dată răspuns</span>
                    </div>

                    {participants.length === 0 ? (
                        <p className="px-5 py-6 text-sm text-ink-muted">Niciun participant invitat încă.</p>
                    ) : (
                        participants.map((participant) => {
                            const style = ROW_STYLES[participant.status] ?? ROW_STYLES.invited;

                            return (
                                <div
                                    key={participant.id}
                                    className="grid grid-cols-[1.4fr_1.6fr_1fr_1fr] items-center gap-3 border-b border-line px-5 py-3.5 text-sm last:border-0 hover:bg-paper-raised"
                                >
                                    <span className="font-medium text-ink">{participant.name}</span>
                                    <span className="text-ink-muted">{participant.email}</span>
                                    <span className={`inline-flex w-fit items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-semibold ${style.bg} ${style.text}`}>
                                        <span className={`h-1.5 w-1.5 rounded-full ${style.dot}`} />
                                        {style.label}
                                    </span>
                                    <span className="text-ink-muted">{participant.respondedAt ?? '—'}</span>
                                </div>
                            );
                        })
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
