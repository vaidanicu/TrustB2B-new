import AppLayout from '@/Layouts/AppLayout';
import { Head, Link } from '@inertiajs/react';

// Grupele exacte cerute de A-05: eingeladen, zugestellt, geöffnet,
// teilnehmend, nicht teilnehmend, keine Antwort, unzustellbar, zurückgezogen.
const STATUS_GROUPS = [
    { key: 'invited', label: 'Invitați', count: 186 },
    { key: 'delivered', label: 'Livrate', count: 182 },
    { key: 'opened', label: 'Deschise', count: 160 },
    { key: 'attending', label: 'Participă', count: 134, tone: 'good' },
    { key: 'declined', label: 'Nu participă', count: 18, tone: 'muted' },
    { key: 'no-response', label: 'Fără răspuns', count: 30, tone: 'warn' },
    { key: 'undeliverable', label: 'Nelivrabile', count: 4, tone: 'bad' },
    { key: 'withdrawn', label: 'Retrase', count: 2, tone: 'muted' },
];

const ROW_STYLES = {
    good: { label: 'Participă', dot: 'bg-teal', text: 'text-teal-ink', bg: 'bg-teal-soft' },
    muted: { label: 'Nu participă', dot: 'bg-ink-muted', text: 'text-ink-soft', bg: 'bg-paper-sunken' },
    warn: { label: 'Fără răspuns', dot: 'bg-amber-600', text: 'text-amber-700', bg: 'bg-amber-50' },
    withdrawn: { label: 'Retrasă', dot: 'bg-ink-muted', text: 'text-ink-muted', bg: 'bg-paper-sunken' },
};

const SAMPLE_ROWS = [
    { name: 'Andrei Popescu', email: 'andrei.popescu@exemplu.ro', status: 'good', at: '04 sep.' },
    { name: 'Maria Ionescu', email: 'maria.ionescu@exemplu.ro', status: 'good', at: '03 sep.' },
    { name: 'Radu Constantin', email: 'radu.constantin@exemplu.ro', status: 'muted', at: '02 sep.' },
    { name: 'Elena Vasile', email: 'elena.vasile@exemplu.ro', status: 'warn', at: '—' },
    { name: 'Cristian Dumitru', email: 'cristian.dumitru@exemplu.ro', status: 'withdrawn', at: '28 aug.' },
];

export default function StatusBoard({ event }) {
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

                <div className="mb-7 grid grid-cols-2 gap-3 sm:grid-cols-4 lg:grid-cols-8">
                    {STATUS_GROUPS.map((group) => (
                        <div key={group.key} className="rounded-lg bg-white p-4 shadow-sm">
                            <div className="text-[11px] font-semibold uppercase leading-tight tracking-wide text-ink-muted">
                                {group.label}
                            </div>
                            <div className="mt-1.5 text-2xl font-semibold text-ink">{group.count}</div>
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

                    {SAMPLE_ROWS.map((row) => {
                        const style = ROW_STYLES[row.status];

                        return (
                            <div
                                key={row.email}
                                className="grid grid-cols-[1.4fr_1.6fr_1fr_1fr] items-center gap-3 border-b border-line px-5 py-3.5 text-sm last:border-0 hover:bg-paper-raised"
                            >
                                <span className="font-medium text-ink">{row.name}</span>
                                <span className="text-ink-muted">{row.email}</span>
                                <span className={`inline-flex w-fit items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-semibold ${style.bg} ${style.text}`}>
                                    <span className={`h-1.5 w-1.5 rounded-full ${style.dot}`} />
                                    {style.label}
                                </span>
                                <span className="text-ink-muted">{row.at}</span>
                            </div>
                        );
                    })}
                </div>

                <p className="mt-4 text-xs text-ink-muted">
                    Previzualizare cu date exemplu — se leagă de participanți reali odată cu AP10-13.
                </p>
            </div>
        </AppLayout>
    );
}
