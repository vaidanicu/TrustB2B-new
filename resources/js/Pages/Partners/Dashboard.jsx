import AppLayout from '@/Layouts/AppLayout';
import { useAppTranslations } from '@/i18n/appTranslations';
import { Head } from '@inertiajs/react';

function formatDate(iso, locale) {
    if (!iso) {
        return '';
    }

    try {
        return new Intl.DateTimeFormat(locale === 'de' ? 'de-DE' : locale === 'en' ? 'en-GB' : 'ro-RO', {
            day: '2-digit',
            month: 'short',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        }).format(new Date(iso));
    } catch {
        return iso;
    }
}

export default function Dashboard({ flaggedPhotos }) {
    const { t, locale } = useAppTranslations();
    const pd = t.partners.dashboard;

    return (
        <AppLayout title={pd.title} active="dashboard">
            <Head title={pd.title} />

            <div className="mx-auto max-w-5xl px-4 py-8 sm:px-6 lg:px-8">
                <p className="-mt-2 mb-6 text-sm text-ink-muted">{pd.subtitle}</p>

                {flaggedPhotos.length === 0 ? (
                    <p className="rounded-lg bg-white px-5 py-10 text-center text-sm text-ink-muted shadow-sm">{pd.empty}</p>
                ) : (
                    <div className="flex flex-col gap-3">
                        {flaggedPhotos.map((photo) => (
                            <div key={photo.id} className="flex gap-4 rounded-lg bg-white p-4 shadow-sm">
                                <img src={photo.url} alt="" className="h-20 w-20 shrink-0 rounded-md object-cover" />
                                <div className="min-w-0 flex-1">
                                    <p className="text-sm font-semibold text-ink">{photo.eventName}</p>
                                    <p className="mt-0.5 text-xs text-ink-muted">
                                        {photo.requestedBy || pd.unnamed}
                                        {photo.requestedByEmail && <span> · {photo.requestedByEmail}</span>}
                                        {photo.requestedAt && <span> · {formatDate(photo.requestedAt, locale)}</span>}
                                    </p>
                                    {photo.reason && <p className="mt-1 text-sm text-ink-soft">{photo.reason}</p>}
                                </div>
                            </div>
                        ))}
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
