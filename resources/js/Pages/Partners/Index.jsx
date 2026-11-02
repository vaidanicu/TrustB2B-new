import AppLayout from '@/Layouts/AppLayout';
import { useAppTranslations } from '@/i18n/appTranslations';
import { Head, Link } from '@inertiajs/react';

function NewPartnerButton({ label }) {
    return (
        <Link
            href={route('partners.create')}
            className="inline-flex items-center rounded-md border border-transparent bg-ink px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white transition hover:bg-ink-soft"
        >
            {label}
        </Link>
    );
}

export default function Index({ partners }) {
    const { t } = useAppTranslations();
    const p = t.partners.index;

    return (
        <AppLayout title={p.title} active="partners" actions={<NewPartnerButton label={p.newPartner} />}>
            <Head title={p.title} />

            <div className="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                <p className="-mt-2 mb-6 text-sm text-ink-muted">
                    {partners.length} {partners.length === 1 ? p.countOne : p.countOther}
                </p>

                <div className="overflow-hidden rounded-lg bg-white shadow-sm">
                    <div className="grid grid-cols-[2fr_1fr] gap-3 border-b border-line px-5 py-3 text-xs font-semibold uppercase tracking-wide text-ink-muted">
                        <span>{p.colName}</span>
                        <span>{p.colCountry}</span>
                    </div>

                    {partners.length === 0 && (
                        <p className="px-5 py-6 text-sm text-ink-muted">{p.empty}</p>
                    )}

                    {partners.map((partner) => (
                        <div
                            key={partner.id}
                            className="grid grid-cols-[2fr_1fr] items-center gap-3 border-b border-line px-5 py-4 text-sm last:border-0 hover:bg-paper-raised"
                        >
                            <span className="font-medium text-ink">{partner.name}</span>
                            <span className="text-ink-soft">{partner.country}</span>
                        </div>
                    ))}
                </div>
            </div>
        </AppLayout>
    );
}
