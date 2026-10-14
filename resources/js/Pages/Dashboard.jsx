import AppLayout from '@/Layouts/AppLayout';
import { useAppTranslations } from '@/i18n/appTranslations';
import { Head, Link, usePage } from '@inertiajs/react';

function NewEventButton({ label }) {
    return (
        <Link
            href={route('events.create')}
            className="inline-flex items-center rounded-md border border-transparent bg-ink px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white transition hover:bg-ink-soft"
        >
            {label}
        </Link>
    );
}

export default function Dashboard({ events }) {
    const { flash } = usePage().props;
    const { t } = useAppTranslations();
    const statusStyles = {
        active: { label: t.statusEvent.active, dot: 'bg-teal', text: 'text-teal-ink', bg: 'bg-teal-soft' },
        draft: { label: t.statusEvent.draft, dot: 'bg-ink-muted', text: 'text-ink-soft', bg: 'bg-paper-sunken' },
        locked: { label: t.statusEvent.locked, dot: 'bg-amber-600', text: 'text-amber-700', bg: 'bg-amber-50' },
        archived: { label: t.statusEvent.archived, dot: 'bg-ink-muted', text: 'text-ink-muted', bg: 'bg-paper-sunken' },
    };
    const activeCount = events.filter((event) => event.status === 'active').length;

    return (
        <AppLayout title={t.dashboard.title} active="dashboard" actions={<NewEventButton label={t.dashboard.newEvent} />}>
            <Head title={t.dashboard.title} />

            <div className="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                {flash?.status && (
                    <div className="mb-5 rounded-md border border-teal bg-teal-soft px-4 py-3 text-sm text-teal-ink">
                        {flash.status}
                    </div>
                )}

                {events.length === 0 ? (
                    <div className="rounded-lg bg-white px-6 py-14 text-center shadow-sm">
                        <p className="text-sm font-medium text-ink">{t.dashboard.emptyTitle}</p>
                        <p className="mx-auto mt-1 max-w-sm text-sm text-ink-muted">{t.dashboard.emptyBody}</p>
                        <div className="mt-5">
                            <NewEventButton label={t.dashboard.newEvent} />
                        </div>
                    </div>
                ) : (
                    <>
                        <div className="mb-7 grid grid-cols-1 gap-5 sm:grid-cols-3">
                            <div className="rounded-lg bg-white p-5 shadow-sm">
                                <div className="text-xs font-semibold uppercase tracking-wide text-ink-muted">{t.dashboard.activeEvents}</div>
                                <div className="mt-2 text-3xl font-semibold text-ink">{activeCount}</div>
                            </div>
                        </div>

                        <div className="overflow-hidden rounded-lg bg-white shadow-sm">
                            <div className="grid grid-cols-[2.2fr_1fr_1.2fr_auto] gap-3 border-b border-line px-5 py-3 text-xs font-semibold uppercase tracking-wide text-ink-muted">
                                <span>{t.dashboard.colEvent}</span>
                                <span>{t.dashboard.colDate}</span>
                                <span>{t.dashboard.colStatus}</span>
                                <span></span>
                            </div>

                            {events.map((event) => {
                                const style = statusStyles[event.status] ?? statusStyles.draft;

                                return (
                                    <div
                                        key={event.id}
                                        className="grid grid-cols-[2.2fr_1fr_1.2fr_auto] items-center gap-3 border-b border-line px-5 py-4 text-sm last:border-0 hover:bg-paper-raised"
                                    >
                                        <span className="font-medium text-ink">{event.name}</span>
                                        <span className="text-ink-soft">{event.date}</span>
                                        <span className={`inline-flex w-fit items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-semibold ${style.bg} ${style.text}`}>
                                            <span className={`h-1.5 w-1.5 rounded-full ${style.dot}`} />
                                            {style.label}
                                        </span>
                                        <Link href={route('participants.status', event.id)} className="text-xs text-accent underline">
                                            {t.dashboard.participants}
                                        </Link>
                                    </div>
                                );
                            })}
                        </div>
                    </>
                )}
            </div>
        </AppLayout>
    );
}
