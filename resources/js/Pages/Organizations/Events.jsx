import AppLayout from '@/Layouts/AppLayout';
import { useAppTranslations } from '@/i18n/appTranslations';
import { Head, Link, router, usePage } from '@inertiajs/react';

function NewEventButton({ organizationId, label }) {
    return (
        <Link
            href={`${route('events.create')}?organization_id=${organizationId}`}
            className="inline-flex items-center rounded-md border border-transparent bg-ink px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white transition hover:bg-ink-soft"
        >
            {label}
        </Link>
    );
}

export default function Events({ organization, events }) {
    const { flash } = usePage().props;
    const { t } = useAppTranslations();
    const oe = t.organizations.events;
    const statusStyles = {
        active: { label: t.statusEvent.active, dot: 'bg-teal', text: 'text-teal-ink', bg: 'bg-teal-soft' },
        draft: { label: t.statusEvent.draft, dot: 'bg-ink-muted', text: 'text-ink-soft', bg: 'bg-paper-sunken' },
        locked: { label: t.statusEvent.locked, dot: 'bg-amber-600', text: 'text-amber-700', bg: 'bg-amber-50' },
        archived: { label: t.statusEvent.archived, dot: 'bg-ink-muted', text: 'text-ink-muted', bg: 'bg-paper-sunken' },
    };

    return (
        <AppLayout
            title={`${oe.title} — ${organization.name}`}
            active="organizations"
            actions={<NewEventButton organizationId={organization.id} label={oe.newEvent} />}
        >
            <Head title={`${oe.title} — ${organization.name}`} />

            <div className="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                <div className="-mt-2 mb-6 flex items-center justify-between">
                    <Link href={route('organizations.index')} className="text-sm text-ink-muted hover:text-ink">
                        {oe.back}
                    </Link>
                </div>

                {flash?.status && (
                    <div className="mb-5 rounded-md border border-teal bg-teal-soft px-4 py-3 text-sm text-teal-ink">
                        {flash.status}
                    </div>
                )}

                {events.length === 0 ? (
                    <div className="rounded-lg bg-white px-6 py-14 text-center shadow-sm">
                        <p className="text-sm font-medium text-ink">{oe.emptyTitle}</p>
                        <p className="mx-auto mt-1 max-w-sm text-sm text-ink-muted">{oe.emptyBody}</p>
                        <div className="mt-5">
                            <NewEventButton organizationId={organization.id} label={oe.newEvent} />
                        </div>
                    </div>
                ) : (
                    <div className="overflow-hidden rounded-lg bg-white shadow-sm">
                        <div className="grid grid-cols-[2.2fr_1fr_1.2fr_auto] gap-3 border-b border-line px-5 py-3 text-xs font-semibold uppercase tracking-wide text-ink-muted">
                            <span>{oe.colEvent}</span>
                            <span>{oe.colDate}</span>
                            <span>{oe.colStatus}</span>
                            <span></span>
                        </div>

                        {events.map((event) => {
                            const style = statusStyles[event.status] ?? statusStyles.draft;

                            function destroy() {
                                if (confirm(oe.confirmDelete)) {
                                    router.delete(route('events.destroy', event.id));
                                }
                            }

                            return (
                                <div
                                    key={event.id}
                                    className="grid grid-cols-[2.2fr_1fr_1.2fr_auto] items-center gap-3 border-b border-line px-5 py-4 text-sm last:border-0 hover:bg-paper-raised"
                                >
                                    <span className="font-medium text-ink">
                                        {event.name}
                                        {event.isRestaurant && (
                                            <span className="ml-2 rounded-full bg-paper-sunken px-2 py-0.5 text-xs font-semibold text-ink-muted">
                                                {oe.restaurantBadge}
                                            </span>
                                        )}
                                    </span>
                                    <span className="text-ink-soft">{event.date}</span>
                                    <span className={`inline-flex w-fit items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-semibold ${style.bg} ${style.text}`}>
                                        <span className={`h-1.5 w-1.5 rounded-full ${style.dot}`} />
                                        {style.label}
                                    </span>
                                    <span className="flex items-center justify-end gap-3">
                                        <button type="button" onClick={destroy} className="text-xs text-ink-muted underline hover:text-danger-ink">
                                            {oe.delete}
                                        </button>
                                    </span>
                                </div>
                            );
                        })}
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
