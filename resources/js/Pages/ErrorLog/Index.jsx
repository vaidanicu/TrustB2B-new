import AppLayout from '@/Layouts/AppLayout';
import { useAppTranslations } from '@/i18n/appTranslations';
import { Head } from '@inertiajs/react';
import { useState } from 'react';

const LEVEL_STYLES = {
    EMERGENCY: 'bg-danger-soft text-danger-ink',
    ALERT: 'bg-danger-soft text-danger-ink',
    CRITICAL: 'bg-danger-soft text-danger-ink',
    ERROR: 'bg-danger-soft text-danger-ink',
    WARNING: 'bg-amber-50 text-amber-700',
    NOTICE: 'bg-paper-sunken text-ink-soft',
    INFO: 'bg-accent-soft text-accent',
    DEBUG: 'bg-paper-sunken text-ink-muted',
};

function EntryCard({ entry, t }) {
    const [expanded, setExpanded] = useState(false);
    const style = LEVEL_STYLES[entry.level] ?? LEVEL_STYLES.NOTICE;

    return (
        <div className="rounded-lg bg-white p-4 shadow-sm">
            <div className="flex items-start gap-3">
                <span className={`shrink-0 rounded-full px-2 py-0.5 text-xs font-semibold ${style}`}>{entry.level}</span>
                <div className="min-w-0 flex-1">
                    <p className="font-mono text-xs tabular-nums text-ink-muted">{entry.at}</p>
                    <p className="mt-0.5 break-words text-sm text-ink">{entry.message}</p>
                    {entry.details && (
                        <button
                            type="button"
                            onClick={() => setExpanded((value) => !value)}
                            className="mt-1.5 text-xs font-semibold uppercase tracking-wide text-accent"
                        >
                            {expanded ? t.errorLog.hideDetails : t.errorLog.showDetails}
                        </button>
                    )}
                    {expanded && entry.details && (
                        <pre className="mt-2 overflow-x-auto rounded-md bg-paper-sunken p-3 text-xs text-ink-soft">{entry.details}</pre>
                    )}
                </div>
            </div>
        </div>
    );
}

export default function Index({ entries }) {
    const { t } = useAppTranslations();
    const e = t.errorLog;

    return (
        <AppLayout title={e.title} active="error-log">
            <Head title={e.title} />

            <div className="mx-auto max-w-5xl px-4 py-8 sm:px-6 lg:px-8">
                <p className="-mt-2 mb-6 text-sm text-ink-muted">
                    {e.subtitlePrefix} {entries.length} {e.subtitleSuffix}
                </p>

                {entries.length === 0 ? (
                    <p className="rounded-lg bg-white px-5 py-10 text-center text-sm text-ink-muted shadow-sm">{e.empty}</p>
                ) : (
                    <div className="flex flex-col gap-3">
                        {entries.map((entry, index) => (
                            <EntryCard key={index} entry={entry} t={t} />
                        ))}
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
