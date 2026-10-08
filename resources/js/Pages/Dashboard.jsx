import AppLayout from '@/Layouts/AppLayout';
import { Head, Link } from '@inertiajs/react';

const STATUS_STYLES = {
    active: { label: 'Activ', dot: 'bg-green-600', text: 'text-green-700', bg: 'bg-green-50' },
    draft: { label: 'Ciornă', dot: 'bg-gray-400', text: 'text-gray-600', bg: 'bg-gray-100' },
    archived: { label: 'Arhivat', dot: 'bg-gray-400', text: 'text-gray-500', bg: 'bg-gray-100' },
};

function NewEventButton() {
    return (
        <Link
            href={route('events.create')}
            className="inline-flex items-center rounded-md border border-transparent bg-gray-800 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white transition hover:bg-gray-700"
        >
            Eveniment nou
        </Link>
    );
}

export default function Dashboard({ events }) {
    const activeCount = events.filter((event) => event.status === 'active').length;

    return (
        <AppLayout title="Evenimente" active="dashboard" actions={<NewEventButton />}>
            <Head title="Evenimente" />

            <div className="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                {events.length === 0 ? (
                    <div className="rounded-lg bg-white px-6 py-14 text-center shadow-sm">
                        <p className="text-sm font-medium text-gray-900">Niciun eveniment încă</p>
                        <p className="mx-auto mt-1 max-w-sm text-sm text-gray-500">
                            Creează primul eveniment ca să începi să trimiți invitații și să colectezi răspunsuri.
                        </p>
                        <div className="mt-5">
                            <NewEventButton />
                        </div>
                    </div>
                ) : (
                    <>
                        <div className="mb-7 grid grid-cols-1 gap-5 sm:grid-cols-3">
                            <div className="rounded-lg bg-white p-5 shadow-sm">
                                <div className="text-xs font-semibold uppercase tracking-wide text-gray-400">Evenimente active</div>
                                <div className="mt-2 text-3xl font-semibold text-gray-900">{activeCount}</div>
                            </div>
                        </div>

                        <div className="overflow-hidden rounded-lg bg-white shadow-sm">
                            <div className="grid grid-cols-[2.2fr_1fr_1fr_1.2fr] gap-3 border-b border-gray-200 px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-400">
                                <span>Eveniment</span>
                                <span>Dată</span>
                                <span>Participanți</span>
                                <span>Stare</span>
                            </div>

                            {events.map((event) => {
                                const style = STATUS_STYLES[event.status] ?? STATUS_STYLES.draft;

                                return (
                                    <div
                                        key={event.id}
                                        className="grid grid-cols-[2.2fr_1fr_1fr_1.2fr] items-center gap-3 border-b border-gray-100 px-5 py-4 text-sm last:border-0 hover:bg-gray-50"
                                    >
                                        <span className="font-medium text-gray-900">{event.name}</span>
                                        <span className="text-gray-700">{event.date}</span>
                                        <span className="text-gray-700">{event.participants ?? '—'}</span>
                                        <span className={`inline-flex w-fit items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-semibold ${style.bg} ${style.text}`}>
                                            <span className={`h-1.5 w-1.5 rounded-full ${style.dot}`} />
                                            {style.label}
                                        </span>
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
