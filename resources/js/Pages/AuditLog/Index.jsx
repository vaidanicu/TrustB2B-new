import AppLayout from '@/Layouts/AppLayout';
import { Head } from '@inertiajs/react';

function formatTimestamp(iso) {
    return new Intl.DateTimeFormat('ro-RO', {
        day: '2-digit',
        month: 'short',
        hour: '2-digit',
        minute: '2-digit',
    }).format(new Date(iso));
}

export default function Index({ entries }) {
    return (
        <AppLayout title="Jurnal de audit" active="audit-log">
            <Head title="Jurnal de audit" />

            <div className="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                <p className="-mt-2 mb-6 text-sm text-gray-400">
                    Ultimele {entries.length} evenimente vizibile pentru organizația ta.
                </p>

                <div className="overflow-hidden rounded-lg bg-white shadow-sm">
                    <div className="grid grid-cols-[1.1fr_1.2fr_2fr_1.6fr] gap-3 border-b border-gray-200 px-5 py-3 text-xs font-semibold uppercase tracking-wide text-gray-400">
                        <span>Data / Ora</span>
                        <span>Actor</span>
                        <span>Acțiune</span>
                        <span>Obiect</span>
                    </div>

                    {entries.length === 0 && (
                        <p className="px-5 py-6 text-sm text-gray-500">Niciun eveniment înregistrat încă.</p>
                    )}

                    {entries.map((entry) => (
                        <div
                            key={entry.id}
                            className="grid grid-cols-[1.1fr_1.2fr_2fr_1.6fr] items-center gap-3 border-b border-gray-100 px-5 py-3.5 text-sm last:border-0 hover:bg-gray-50"
                        >
                            <span className="font-mono tabular-nums text-gray-500">{formatTimestamp(entry.at)}</span>
                            <span className="text-gray-900">{entry.causer}</span>
                            <span className="text-gray-700">
                                {entry.description}
                                {entry.role && <span className="text-gray-400"> · rol: {entry.role}</span>}
                            </span>
                            <span className="text-gray-500">{entry.subject}</span>
                        </div>
                    ))}
                </div>
            </div>
        </AppLayout>
    );
}
