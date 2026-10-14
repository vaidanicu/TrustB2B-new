import AppLayout from '@/Layouts/AppLayout';
import { APP_INTL_LOCALES, useAppTranslations } from '@/i18n/appTranslations';
import { Head } from '@inertiajs/react';

function formatTimestamp(iso, locale) {
    return new Intl.DateTimeFormat(APP_INTL_LOCALES[locale] ?? 'ro-RO', {
        day: '2-digit',
        month: 'short',
        hour: '2-digit',
        minute: '2-digit',
    }).format(new Date(iso));
}

export default function Index({ entries }) {
    const { t, locale } = useAppTranslations();
    const a = t.auditLog;

    return (
        <AppLayout title={a.title} active="audit-log">
            <Head title={a.title} />

            <div className="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                <p className="-mt-2 mb-6 text-sm text-ink-muted">
                    {a.subtitlePrefix} {entries.length} {a.subtitleSuffix}
                </p>

                <div className="overflow-hidden rounded-lg bg-white shadow-sm">
                    <div className="grid grid-cols-[1.1fr_1.2fr_2fr_1.6fr] gap-3 border-b border-line px-5 py-3 text-xs font-semibold uppercase tracking-wide text-ink-muted">
                        <span>{a.colDateTime}</span>
                        <span>{a.colActor}</span>
                        <span>{a.colAction}</span>
                        <span>{a.colObject}</span>
                    </div>

                    {entries.length === 0 && (
                        <p className="px-5 py-6 text-sm text-ink-muted">{a.empty}</p>
                    )}

                    {entries.map((entry) => (
                        <div
                            key={entry.id}
                            className="grid grid-cols-[1.1fr_1.2fr_2fr_1.6fr] items-center gap-3 border-b border-line px-5 py-3.5 text-sm last:border-0 hover:bg-paper-raised"
                        >
                            <span className="font-mono tabular-nums text-ink-muted">{formatTimestamp(entry.at, locale)}</span>
                            <span className="text-ink">{entry.causer}</span>
                            <span className="text-ink-soft">
                                {entry.description}
                                {entry.role && <span className="text-ink-muted"> · {a.role}: {entry.role}</span>}
                            </span>
                            <span className="text-ink-muted">{entry.subject}</span>
                        </div>
                    ))}
                </div>
            </div>
        </AppLayout>
    );
}
