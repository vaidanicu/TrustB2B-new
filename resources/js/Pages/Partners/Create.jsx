import AppLayout from '@/Layouts/AppLayout';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import { useAppTranslations } from '@/i18n/appTranslations';
import { Head, Link, useForm } from '@inertiajs/react';

function Field({ label, field, errors, hint, ...props }) {
    return (
        <div>
            <InputLabel value={label} />
            <TextInput className="mt-1 block w-full" {...field} {...props} />
            {hint && <p className="mt-1 text-xs text-ink-muted">{hint}</p>}
            <InputError message={errors} className="mt-1" />
        </div>
    );
}

export default function Create({ franchisePartners }) {
    const { t } = useAppTranslations();
    const c = t.partners.create;

    const { data, setData, post, processing, errors } = useForm({
        name: '',
        country: 'RO',
        email: '',
        franchise_partner_id: franchisePartners?.[0]?.id ?? '',
    });

    function submit(e) {
        e.preventDefault();
        post(route('partners.store'));
    }

    return (
        <AppLayout title={c.title}>
            <Head title={c.title} />

            <form onSubmit={submit} className="mx-auto flex max-w-xl flex-col gap-6 px-4 py-8 sm:px-6 lg:px-8">
                <section className="rounded-lg bg-white p-7 shadow-sm">
                    <h2 className="text-base font-semibold text-ink">{c.sectionTitle}</h2>
                    <p className="mt-0.5 text-sm text-ink-muted">{c.sectionBody}</p>

                    <div className="mt-5 flex flex-col gap-4">
                        <Field
                            label={c.nameLabel}
                            placeholder={c.namePlaceholder}
                            field={{ value: data.name, onChange: (e) => setData('name', e.target.value) }}
                            errors={errors.name}
                        />
                        <Field
                            label={c.countryLabel}
                            placeholder="RO"
                            maxLength={2}
                            field={{ value: data.country, onChange: (e) => setData('country', e.target.value.toUpperCase()) }}
                            errors={errors.country}
                            hint={c.countryHint}
                        />
                        <Field
                            label={c.emailLabel}
                            type="email"
                            placeholder={c.optional}
                            field={{ value: data.email, onChange: (e) => setData('email', e.target.value) }}
                            errors={errors.email}
                            hint={c.emailHint}
                        />

                        {franchisePartners?.length > 0 && (
                            <div>
                                <InputLabel value={c.franchiseLabel} />
                                <select
                                    value={data.franchise_partner_id}
                                    onChange={(e) => setData('franchise_partner_id', e.target.value)}
                                    className="mt-1 block w-full rounded-md border-line text-sm shadow-sm focus:border-accent focus:ring-accent"
                                >
                                    {franchisePartners.map((franchisePartner) => (
                                        <option key={franchisePartner.id} value={franchisePartner.id}>{franchisePartner.name}</option>
                                    ))}
                                </select>
                                <InputError message={errors.franchise_partner_id} className="mt-1" />
                            </div>
                        )}
                    </div>
                </section>

                <div className="flex items-center justify-end gap-3 pb-4">
                    <Link href={route('partners.index')} className="text-sm text-ink-muted hover:text-ink">
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
