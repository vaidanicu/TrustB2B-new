import AppLayout from '@/Layouts/AppLayout';
import { useAppTranslations } from '@/i18n/appTranslations';
import { Head, Link } from '@inertiajs/react';

function Field({ label, value }) {
    if (!value) {
        return null;
    }

    return (
        <div>
            <p className="text-xs font-semibold uppercase tracking-wide text-ink-muted">{label}</p>
            <p className="mt-0.5 text-sm text-ink">{value}</p>
        </div>
    );
}

function Section({ title, children }) {
    return (
        <section className="rounded-lg bg-white p-6 shadow-sm">
            <h2 className="mb-4 text-sm font-semibold uppercase tracking-wide text-ink-muted">{title}</h2>
            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">{children}</div>
        </section>
    );
}

export default function Show({ backUrl, event }) {
    const { t } = useAppTranslations();
    const es = t.events.show;
    const statusStyles = {
        active: { label: t.statusEvent.active, text: 'text-teal-ink', bg: 'bg-teal-soft' },
        draft: { label: t.statusEvent.draft, text: 'text-ink-soft', bg: 'bg-paper-sunken' },
        locked: { label: t.statusEvent.locked, text: 'text-amber-700', bg: 'bg-amber-50' },
        archived: { label: t.statusEvent.archived, text: 'text-ink-muted', bg: 'bg-paper-sunken' },
    };
    const style = statusStyles[event.status] ?? statusStyles.draft;

    return (
        <AppLayout title={event.name}>
            <Head title={event.name} />

            <div className="mx-auto flex max-w-3xl flex-col gap-6 px-4 py-8 sm:px-6 lg:px-8">
                <div className="-mt-2 flex items-center justify-between">
                    <Link href={backUrl} className="text-sm text-ink-muted hover:text-ink">
                        {es.back}
                    </Link>
                    <div className="flex items-center gap-2">
                        {event.isRestaurant && (
                            <span className="rounded-full bg-paper-sunken px-2.5 py-0.5 text-xs font-semibold text-ink-muted">
                                {es.restaurantBadge}
                            </span>
                        )}
                        <span className={`rounded-full px-2.5 py-0.5 text-xs font-semibold ${style.bg} ${style.text}`}>{style.label}</span>
                    </div>
                </div>

                <div>
                    <h1 className="font-display text-2xl font-semibold text-ink">{event.name}</h1>
                    {event.organizationName && <p className="mt-1 text-sm text-ink-muted">{event.organizationName}</p>}
                </div>

                <div className="flex flex-wrap gap-3">
                    {!event.isRestaurant && (
                        <>
                            <Link href={route('participants.status', event.id)} className="text-sm text-accent underline">
                                {es.participants}
                            </Link>
                            <Link href={route('participants.checkin', event.id)} className="text-sm text-accent underline">
                                {es.checkin}
                            </Link>
                        </>
                    )}
                    <Link href={route('gallery.index', event.id)} className="text-sm text-accent underline">
                        {es.gallery}
                    </Link>
                </div>

                <Section title={es.sectionDetails}>
                    <Field label={es.startsAt} value={event.startsAt} />
                    <Field label={es.endsAt} value={event.endsAt} />
                    <Field label={es.purpose} value={event.purpose} />
                    <Field label={es.country} value={event.country} />
                    <Field label={es.language} value={event.language} />
                    <Field label={es.retention} value={`${event.retentionDays} ${es.days}`} />
                    <Field label={es.endOfCycle} value={event.endOfCycleAction === 'export' ? es.endOfCycleExport : es.endOfCycleDelete} />
                    <Field label={es.partner} value={event.partnerName} />
                    <Field label={es.createdBy} value={event.createdByName} />
                </Section>

                <Section title={es.sectionRoles}>
                    <Field label={es.organizer} value={event.organizer} />
                    <Field label={es.coOrganizer} value={event.coOrganizer} />
                    <Field label={es.location} value={event.location} />
                    <Field label={es.agency} value={event.agency} />
                    <Field label={es.photographer} value={event.photographer} />
                    <Field label={es.photographerAccount} value={event.photographerAccountEmail} />
                    <Field label={es.dpoContact} value={event.dataProtectionContact} />
                    <Field label={es.finalRecipient} value={event.finalPackageRecipient} />
                </Section>

                {(event.clientCompanyName || event.clientContactName || event.clientContactEmail || event.clientAddress || event.clientTaxNumber) && (
                    <Section title={es.sectionClient}>
                        <Field label={es.clientCompanyName} value={event.clientCompanyName} />
                        <Field label={es.clientContactName} value={event.clientContactName} />
                        <Field label={es.clientContactEmail} value={event.clientContactEmail} />
                        <Field label={es.clientAddress} value={event.clientAddress} />
                        <Field label={es.clientTaxNumber} value={event.clientTaxNumber} />
                    </Section>
                )}

                {(event.gdprContractText || event.gdprContractFileUrl) && (
                    <section className="rounded-lg bg-white p-6 shadow-sm">
                        <h2 className="mb-4 text-sm font-semibold uppercase tracking-wide text-ink-muted">{es.sectionGdpr}</h2>
                        {event.gdprContractText && <p className="whitespace-pre-wrap text-sm text-ink">{event.gdprContractText}</p>}
                        {event.gdprContractFileUrl && (
                            <a href={event.gdprContractFileUrl} target="_blank" rel="noreferrer" className="mt-3 inline-block text-sm text-accent underline">
                                {es.gdprContractFile}
                            </a>
                        )}
                    </section>
                )}
            </div>
        </AppLayout>
    );
}
