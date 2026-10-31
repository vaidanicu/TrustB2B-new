import AppLayout from '@/Layouts/AppLayout';
import Checkbox from '@/Components/Checkbox';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import { useAppTranslations } from '@/i18n/appTranslations';
import { Head, Link, useForm } from '@inertiajs/react';

function RadioCard({ name, value, checked, onChange, title, hint }) {
    return (
        <label
            className={
                'flex cursor-pointer items-start gap-2.5 rounded-lg border px-4 py-3.5 ' +
                (checked ? 'border-accent bg-accent-soft' : 'border-line')
            }
        >
            <input
                type="radio"
                name={name}
                value={value}
                checked={checked}
                onChange={onChange}
                className="mt-0.5 text-accent focus:ring-accent"
            />
            <span>
                <span className="block text-sm font-medium text-ink">{title}</span>
                <span className="block text-xs text-ink-muted">{hint}</span>
            </span>
        </label>
    );
}

function Field({ label, field, errors, hint, required, ...props }) {
    return (
        <div>
            <InputLabel value={label} required={required} />
            <TextInput className="mt-1 block w-full" {...field} {...props} />
            {hint && <p className="mt-1 text-xs text-ink-muted">{hint}</p>}
            <InputError message={errors} className="mt-1" />
        </div>
    );
}

function ErrorSummary({ errors, title }) {
    const messages = Object.values(errors).filter(Boolean);

    if (messages.length === 0) {
        return null;
    }

    return (
        <div className="rounded-lg border border-danger bg-danger-soft px-5 py-4">
            <p className="text-sm font-semibold text-danger-ink">{title}</p>
            <ul className="mt-2 list-disc pl-5 text-sm text-danger-ink">
                {messages.map((message, index) => (
                    <li key={index}>{message}</li>
                ))}
            </ul>
        </div>
    );
}

export default function Create({ organizations, selectedOrganizationId }) {
    const { t } = useAppTranslations();
    const c = t.events.create;

    const { data, setData, post, processing, errors } = useForm({
        organization_id: selectedOrganizationId ? String(selectedOrganizationId) : (organizations[0]?.id ? String(organizations[0].id) : ''),
        name: '',
        purpose: '',
        country: 'RO',
        language: 'ro',
        starts_at: '',
        ends_at: '',
        retention_days: '90',
        end_of_cycle_action: 'delete',
        organizer: '',
        co_organizer: '',
        location: '',
        agency: '',
        photographer: '',
        photographer_email: '',
        data_protection_contact: '',
        final_package_recipient: '',
        is_restaurant: false,
        gdpr_contract_text: '',
        gdpr_contract_file: null,
        client_contact_name: '',
        client_contact_email: '',
        client_address: '',
        client_tax_number: '',
    });

    function submit(e) {
        e.preventDefault();
        post(route('events.store'));
    }

    return (
        <AppLayout title={c.title}>
            <Head title={c.title} />

            <form onSubmit={submit} className="mx-auto flex max-w-3xl flex-col gap-6 px-4 py-8 sm:px-6 lg:px-8">
                <ErrorSummary errors={errors} title={c.errorSummaryTitle} />

                <section className="rounded-lg bg-white p-7 shadow-sm">
                    <h2 className="text-base font-semibold text-ink">{c.sectionDetailsTitle}</h2>
                    <p className="mt-0.5 text-sm text-ink-muted">{c.sectionDetailsBody}</p>

                    <div className="mt-5 flex flex-col gap-4">
                        <div>
                            <InputLabel value={c.organizationLabel} required />
                            <select
                                value={data.organization_id}
                                onChange={(e) => setData('organization_id', e.target.value)}
                                className="mt-1 block w-full rounded-md border-line shadow-sm focus:border-accent focus:ring-accent"
                            >
                                <option value="" disabled>{c.organizationPlaceholder}</option>
                                {organizations.map((organization) => (
                                    <option key={organization.id} value={organization.id}>{organization.name}</option>
                                ))}
                            </select>
                            <p className="mt-1 text-xs text-ink-muted">{c.organizationHint}</p>
                            <InputError message={errors.organization_id} className="mt-1" />
                        </div>

                        <label className="flex cursor-pointer items-start gap-2.5 rounded-lg border border-line px-4 py-3.5">
                            <Checkbox
                                checked={data.is_restaurant}
                                onChange={(e) => setData('is_restaurant', e.target.checked)}
                                className="mt-0.5"
                            />
                            <span>
                                <span className="block text-sm font-medium text-ink">{c.restaurantLabel}</span>
                                <span className="block text-xs text-ink-muted">{c.restaurantHint}</span>
                            </span>
                        </label>

                        <Field
                            label={c.nameLabel}
                            placeholder={c.namePlaceholder}
                            field={{ value: data.name, onChange: (e) => setData('name', e.target.value) }}
                            errors={errors.name}
                            hint={c.nameHint}
                            required
                        />
                        <Field
                            label={c.purposeLabel}
                            placeholder={c.purposePlaceholder}
                            field={{ value: data.purpose, onChange: (e) => setData('purpose', e.target.value) }}
                            errors={errors.purpose}
                            hint={c.purposeHint}
                        />
                        <div className="grid grid-cols-2 gap-4">
                            <Field
                                label={c.startLabel}
                                type="date"
                                field={{ value: data.starts_at, onChange: (e) => setData('starts_at', e.target.value) }}
                                errors={errors.starts_at}
                                hint={c.startHint}
                                required
                            />
                            <Field
                                label={c.endLabel}
                                type="date"
                                field={{ value: data.ends_at, onChange: (e) => setData('ends_at', e.target.value) }}
                                errors={errors.ends_at}
                                hint={c.endHint}
                                required
                            />
                        </div>
                    </div>
                </section>

                <section className="rounded-lg bg-white p-7 shadow-sm">
                    <h2 className="text-base font-semibold text-ink">{c.sectionClientTitle}</h2>
                    <p className="mt-0.5 text-sm text-ink-muted">{c.sectionClientBody}</p>

                    <div className="mt-5 grid grid-cols-2 gap-x-4 gap-y-4">
                        <Field label={c.clientContactNameLabel} placeholder={c.optional} field={{ value: data.client_contact_name, onChange: (e) => setData('client_contact_name', e.target.value) }} errors={errors.client_contact_name} hint={c.clientContactNameHint} />
                        <Field label={c.clientContactEmailLabel} type="email" placeholder={c.optional} field={{ value: data.client_contact_email, onChange: (e) => setData('client_contact_email', e.target.value) }} errors={errors.client_contact_email} hint={c.clientContactEmailHint} />
                        <Field label={c.clientAddressLabel} placeholder={c.optional} field={{ value: data.client_address, onChange: (e) => setData('client_address', e.target.value) }} errors={errors.client_address} hint={c.clientAddressHint} />
                        <Field label={c.clientTaxNumberLabel} placeholder={c.optional} field={{ value: data.client_tax_number, onChange: (e) => setData('client_tax_number', e.target.value) }} errors={errors.client_tax_number} hint={c.clientTaxNumberHint} />
                    </div>
                </section>

                <section className="rounded-lg bg-white p-7 shadow-sm">
                    <h2 className="text-base font-semibold text-ink">{c.sectionRetentionTitle}</h2>
                    <p className="mt-0.5 text-sm text-ink-muted">{c.sectionRetentionBody}</p>

                    <div className="mt-5">
                        <span className="mb-2 block text-sm font-medium text-ink-soft">{c.retentionLabel}</span>
                        <div className="grid grid-cols-2 gap-3">
                            <RadioCard
                                name="retention"
                                value="60"
                                checked={data.retention_days === '60'}
                                onChange={(e) => setData('retention_days', e.target.value)}
                                title={c.retention60Title}
                                hint={c.retention60Hint}
                            />
                            <RadioCard
                                name="retention"
                                value="90"
                                checked={data.retention_days === '90'}
                                onChange={(e) => setData('retention_days', e.target.value)}
                                title={c.retention90Title}
                                hint={c.retention90Hint}
                            />
                        </div>
                        <InputError message={errors.retention_days} className="mt-1" />
                    </div>

                    <div className="mt-5">
                        <span className="mb-2 block text-sm font-medium text-ink-soft">{c.endCycleLabel}</span>
                        <div className="grid grid-cols-2 gap-3">
                            <RadioCard
                                name="end-of-cycle"
                                value="delete"
                                checked={data.end_of_cycle_action === 'delete'}
                                onChange={(e) => setData('end_of_cycle_action', e.target.value)}
                                title={c.deleteTitle}
                                hint={c.deleteHint}
                            />
                            <RadioCard
                                name="end-of-cycle"
                                value="export"
                                checked={data.end_of_cycle_action === 'export'}
                                onChange={(e) => setData('end_of_cycle_action', e.target.value)}
                                title={c.exportTitle}
                                hint={c.exportHint}
                            />
                        </div>
                    </div>
                </section>

                <section className="rounded-lg bg-white p-7 shadow-sm">
                    <h2 className="text-base font-semibold text-ink">{c.sectionRolesTitle}</h2>
                    <p className="mt-0.5 text-sm text-ink-muted">{c.sectionRolesBody}</p>

                    <div className="mt-5 grid grid-cols-2 gap-x-4 gap-y-4">
                        <Field label={c.organizerLabel} field={{ value: data.organizer, onChange: (e) => setData('organizer', e.target.value) }} errors={errors.organizer} hint={c.organizerHint} />
                        <Field label={c.coOrganizerLabel} placeholder={c.optional} field={{ value: data.co_organizer, onChange: (e) => setData('co_organizer', e.target.value) }} errors={errors.co_organizer} hint={c.coOrganizerHint} />
                        <Field label={c.locationLabel} field={{ value: data.location, onChange: (e) => setData('location', e.target.value) }} errors={errors.location} hint={c.locationHint} />
                        <Field label={c.agencyLabel} placeholder={c.optional} field={{ value: data.agency, onChange: (e) => setData('agency', e.target.value) }} errors={errors.agency} hint={c.agencyHint} />
                        <Field label={c.photographerLabel} placeholder={c.optional} field={{ value: data.photographer, onChange: (e) => setData('photographer', e.target.value) }} errors={errors.photographer} hint={c.photographerHint} />
                        <Field
                            label={c.photographerEmailLabel}
                            type="email"
                            placeholder={c.optional}
                            field={{ value: data.photographer_email, onChange: (e) => setData('photographer_email', e.target.value) }}
                            errors={errors.photographer_email}
                            hint={c.photographerEmailHint}
                        />
                        <Field label={c.dpoContactLabel} type="email" field={{ value: data.data_protection_contact, onChange: (e) => setData('data_protection_contact', e.target.value) }} errors={errors.data_protection_contact} hint={c.dpoContactHint} />
                        <div className="col-span-2">
                            <Field label={c.finalRecipientLabel} field={{ value: data.final_package_recipient, onChange: (e) => setData('final_package_recipient', e.target.value) }} errors={errors.final_package_recipient} hint={c.finalRecipientHint} />
                        </div>
                    </div>
                </section>

                {!data.is_restaurant && (
                    <section className="rounded-lg bg-white p-7 shadow-sm">
                        <h2 className="text-base font-semibold text-ink">{c.sectionGdprTitle}</h2>
                        <p className="mt-0.5 text-sm text-ink-muted">{c.sectionGdprBody}</p>

                        <div className="mt-5">
                            <InputLabel value={c.gdprContractLabel} required />
                            <textarea
                                value={data.gdpr_contract_text}
                                onChange={(e) => setData('gdpr_contract_text', e.target.value)}
                                placeholder={c.gdprContractPlaceholder}
                                rows={8}
                                className="mt-1 block w-full rounded-md border-line text-sm shadow-sm focus:border-accent focus:ring-accent"
                            />
                            <InputError message={errors.gdpr_contract_text} className="mt-1" />
                        </div>

                        <div className="mt-4">
                            <InputLabel value={c.gdprContractFileLabel} />
                            <input
                                type="file"
                                accept="application/pdf"
                                onChange={(e) => setData('gdpr_contract_file', e.target.files[0] ?? null)}
                                className="mt-1 block w-full text-sm text-ink-soft file:mr-3 file:rounded-md file:border-0 file:bg-ink file:px-3 file:py-1.5 file:text-xs file:font-semibold file:uppercase file:text-white"
                            />
                            <p className="mt-1 text-xs text-ink-muted">{c.gdprContractFileHint}</p>
                            <InputError message={errors.gdpr_contract_file} className="mt-1" />
                        </div>
                    </section>
                )}

                <div className="flex items-center justify-end gap-3 pb-4">
                    <Link
                        href={data.organization_id ? route('organizations.events', data.organization_id) : route('organizations.index')}
                        className="text-sm text-ink-muted hover:text-ink"
                    >
                        {t.common.cancel}
                    </Link>
                    <button
                        type="submit"
                        disabled={processing}
                        className="inline-flex items-center rounded-md border border-transparent bg-ink px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white transition hover:bg-ink-soft disabled:opacity-50"
                    >
                        {processing ? c.submitting : c.submit}
                    </button>
                </div>
            </form>
        </AppLayout>
    );
}
