import AppLayout from '@/Layouts/AppLayout';
import InputError from '@/Components/InputError';
import { useAppTranslations } from '@/i18n/appTranslations';
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { useState } from 'react';

// Grupele exacte cerute de A-05: eingeladen, zugestellt, geöffnet,
// teilnehmend, nicht teilnehmend, keine Antwort, unzustellbar, zurückgezogen.
// delivered/undeliverable/no_response/withdrawn nu sunt încă scrise de
// backend (AP10-13 nu are trimitere reală de email sau măturare automată),
// dar grupele rămân aici ca să nu dispară din tablou când vor exista.
const STATUS_KEYS = ['invited', 'delivered', 'opened', 'attending', 'declined', 'no_response', 'undeliverable', 'withdrawn'];

const ROW_TONES = {
    attending: { dot: 'bg-teal', text: 'text-teal-ink', bg: 'bg-teal-soft' },
    declined: { dot: 'bg-ink-muted', text: 'text-ink-soft', bg: 'bg-paper-sunken' },
    invited: { dot: 'bg-amber-600', text: 'text-amber-700', bg: 'bg-amber-50' },
    delivered: { dot: 'bg-amber-600', text: 'text-amber-700', bg: 'bg-amber-50' },
    opened: { dot: 'bg-amber-600', text: 'text-amber-700', bg: 'bg-amber-50' },
    no_response: { dot: 'bg-amber-600', text: 'text-amber-700', bg: 'bg-amber-50' },
    undeliverable: { dot: 'bg-red-600', text: 'text-red-700', bg: 'bg-red-50' },
    withdrawn: { dot: 'bg-ink-muted', text: 'text-ink-muted', bg: 'bg-paper-sunken' },
};

function InviteForm({ eventId, t }) {
    const { data, setData, post, processing, errors, reset } = useForm({ name: '', email: '', address: '', company_name: '' });

    function submit(e) {
        e.preventDefault();
        post(route('participants.store', eventId), { onSuccess: () => reset() });
    }

    return (
        <form onSubmit={submit} className="mb-6 flex flex-wrap items-end gap-3 rounded-lg bg-white p-4 shadow-sm">
            <div className="min-w-[200px] flex-1">
                <label className="block text-xs font-medium text-ink-soft">{t.statusBoard.inviteNameLabel}</label>
                <input
                    type="text"
                    value={data.name}
                    onChange={(e) => setData('name', e.target.value)}
                    placeholder={t.statusBoard.inviteNamePlaceholder}
                    className="mt-1 block w-full rounded-md border-line text-sm shadow-sm focus:border-accent focus:ring-accent"
                />
                <InputError message={errors.name} className="mt-1" />
            </div>
            <div className="min-w-[220px] flex-1">
                <label className="block text-xs font-medium text-ink-soft">{t.statusBoard.inviteEmailLabel}</label>
                <input
                    type="email"
                    value={data.email}
                    onChange={(e) => setData('email', e.target.value)}
                    placeholder={t.statusBoard.inviteEmailPlaceholder}
                    className="mt-1 block w-full rounded-md border-line text-sm shadow-sm focus:border-accent focus:ring-accent"
                />
                <InputError message={errors.email} className="mt-1" />
            </div>
            <div className="min-w-[200px] flex-1">
                <label className="block text-xs font-medium text-ink-soft">{t.statusBoard.inviteAddressLabel}</label>
                <input
                    type="text"
                    value={data.address}
                    onChange={(e) => setData('address', e.target.value)}
                    placeholder={t.statusBoard.optional}
                    className="mt-1 block w-full rounded-md border-line text-sm shadow-sm focus:border-accent focus:ring-accent"
                />
                <InputError message={errors.address} className="mt-1" />
            </div>
            <div className="min-w-[180px] flex-1">
                <label className="block text-xs font-medium text-ink-soft">{t.statusBoard.inviteCompanyLabel}</label>
                <input
                    type="text"
                    value={data.company_name}
                    onChange={(e) => setData('company_name', e.target.value)}
                    placeholder={t.statusBoard.optional}
                    className="mt-1 block w-full rounded-md border-line text-sm shadow-sm focus:border-accent focus:ring-accent"
                />
                <InputError message={errors.company_name} className="mt-1" />
            </div>
            <button
                type="submit"
                disabled={processing}
                className="inline-flex h-[38px] items-center rounded-md border border-transparent bg-ink px-4 text-xs font-semibold uppercase tracking-widest text-white transition hover:bg-ink-soft disabled:opacity-50"
            >
                {t.statusBoard.inviteButton}
            </button>
        </form>
    );
}

function InvitationLinkBanner({ url, emailSent, t, common }) {
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
        <div className={'mb-5 rounded-md border px-4 py-3 text-sm ' + (emailSent ? 'border-teal bg-teal-soft text-teal-ink' : 'border-amber-600 bg-amber-50 text-amber-700')}>
            <p className="mb-1.5 font-medium">{emailSent ? t.statusBoard.emailSent : t.statusBoard.emailFailed}</p>
            <div className="flex flex-wrap items-center justify-between gap-3">
                <span className="break-all">{t.statusBoard.linkLabel} {url}</span>
                <button type="button" onClick={copy} className="shrink-0 rounded-md border border-current/30 px-3 py-1 text-xs font-semibold uppercase tracking-wide">
                    {copied ? common.copied : common.copy}
                </button>
            </div>
        </div>
    );
}

export default function StatusBoard({ event, participants, checkinUrl }) {
    const { flash } = usePage().props;
    const { t: all } = useAppTranslations();
    const t = all.participants;

    const counts = Object.fromEntries(STATUS_KEYS.map((key) => [key, 0]));
    participants.forEach((participant) => {
        counts[participant.status] = (counts[participant.status] ?? 0) + 1;
    });

    const pendingDeletions = participants.filter((participant) => participant.deletionRequested);

    return (
        <AppLayout title={event.name} active="dashboard">
            <Head title={`${t.statusBoard.colName} — ${event.name}`} />

            <div className="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                <div className="-mt-2 mb-6 flex items-center justify-between">
                    <Link href={route('dashboard')} className="text-sm text-ink-muted hover:text-ink">
                        {t.statusBoard.back}
                    </Link>
                    <div className="flex items-center gap-4">
                        <p className="text-sm text-ink-muted">
                            {event.date} · {event.location}
                        </p>
                        <Link href={checkinUrl} className="text-sm text-accent underline">
                            {t.statusBoard.checkinLink}
                        </Link>
                    </div>
                </div>

                {flash?.status && (
                    <div className="mb-5 rounded-md border border-teal bg-teal-soft px-4 py-3 text-sm text-teal-ink">
                        {flash.status}
                    </div>
                )}
                {flash?.invitationUrl && <InvitationLinkBanner url={flash.invitationUrl} emailSent={flash.invitationEmailSent} t={t} common={all.common} />}

                {pendingDeletions.length > 0 && (
                    <div className="mb-5 rounded-md border border-danger bg-danger-soft px-4 py-3 text-sm text-danger-ink">
                        {t.statusBoard.pendingDeletions(pendingDeletions.length)}
                    </div>
                )}

                <InviteForm eventId={event.id} t={t} />

                <div className="mb-7 grid grid-cols-2 gap-3 sm:grid-cols-4 lg:grid-cols-8">
                    {STATUS_KEYS.map((key) => (
                        <div key={key} className="rounded-lg bg-white p-4 shadow-sm">
                            <div className="text-[11px] font-semibold uppercase leading-tight tracking-wide text-ink-muted">
                                {t.statusGroups[key]}
                            </div>
                            <div className="mt-1.5 text-2xl font-semibold text-ink">{counts[key]}</div>
                        </div>
                    ))}
                </div>

                <div className="overflow-hidden rounded-lg bg-white shadow-sm">
                    <div className="grid grid-cols-[1.4fr_1.6fr_1fr_1fr_auto] gap-3 border-b border-line px-5 py-3 text-xs font-semibold uppercase tracking-wide text-ink-muted">
                        <span>{t.statusBoard.colName}</span>
                        <span>{t.statusBoard.colEmail}</span>
                        <span>{t.statusBoard.colStatus}</span>
                        <span>{t.statusBoard.colRespondedAt}</span>
                        <span></span>
                    </div>

                    {participants.length === 0 ? (
                        <p className="px-5 py-6 text-sm text-ink-muted">{t.statusBoard.empty}</p>
                    ) : (
                        participants.map((participant) => {
                            const tone = ROW_TONES[participant.status] ?? ROW_TONES.invited;

                            function destroy() {
                                if (confirm(t.statusBoard.confirmDelete)) {
                                    router.delete(route('participants.destroy', [event.id, participant.id]), { preserveScroll: true });
                                }
                            }

                            function approveDeletion() {
                                if (confirm(t.statusBoard.confirmApproveDeletion)) {
                                    router.delete(route('participants.destroy', [event.id, participant.id]), { preserveScroll: true });
                                }
                            }

                            function denyDeletion() {
                                router.patch(route('participants.deny-deletion', [event.id, participant.id]), {}, { preserveScroll: true });
                            }

                            return (
                                <div
                                    key={participant.id}
                                    className={
                                        'grid grid-cols-[1.4fr_1.6fr_1fr_1fr_auto] items-center gap-3 border-b border-line px-5 py-3.5 text-sm last:border-0 hover:bg-paper-raised ' +
                                        (participant.deletionRequested ? 'bg-danger-soft/40' : '')
                                    }
                                >
                                    <span className="font-medium text-ink">
                                        {participant.name}
                                        {participant.checkedIn && (
                                            <span className="ml-2 inline-flex items-center rounded-full bg-teal-soft px-2 py-0.5 text-[10px] font-semibold uppercase text-teal-ink">
                                                {t.statusBoard.checkedInBadge}
                                            </span>
                                        )}
                                        {participant.deletionRequested && (
                                            <span className="ml-2 inline-flex items-center rounded-full bg-danger-soft px-2 py-0.5 text-[10px] font-semibold uppercase text-danger-ink">
                                                {t.statusBoard.deletionRequestedBadge}
                                            </span>
                                        )}
                                    </span>
                                    <span className="text-ink-muted">{participant.email}</span>
                                    <span className={`inline-flex w-fit items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-semibold ${tone.bg} ${tone.text}`}>
                                        <span className={`h-1.5 w-1.5 rounded-full ${tone.dot}`} />
                                        {t.statusRow[participant.status] ?? t.statusRow.invited}
                                    </span>
                                    <span className="text-ink-muted">{participant.respondedAt ?? '—'}</span>
                                    {participant.deletionRequested ? (
                                        <span className="flex items-center gap-3">
                                            <button type="button" onClick={approveDeletion} className="text-xs font-semibold text-danger-ink underline">
                                                {t.statusBoard.approveDeletion}
                                            </button>
                                            <button type="button" onClick={denyDeletion} className="text-xs text-ink-muted underline hover:text-ink-soft">
                                                {t.statusBoard.denyDeletion}
                                            </button>
                                        </span>
                                    ) : (
                                        <button type="button" onClick={destroy} className="text-xs text-ink-muted underline hover:text-danger-ink">
                                            {t.statusBoard.delete}
                                        </button>
                                    )}
                                </div>
                            );
                        })
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
