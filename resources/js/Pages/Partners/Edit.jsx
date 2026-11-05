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

export default function Edit({ partner }) {
    const { t } = useAppTranslations();
    const c = t.partners.create;
    const e = t.partners.edit;

    const { data, setData, patch, processing, errors } = useForm({
        name: partner.name,
        country: partner.country,
        email: partner.email ?? '',
    });

    function submit(event) {
        event.preventDefault();
        patch(route('partners.update', partner.id));
    }

    return (
        <AppLayout title={e.title}>
            <Head title={e.title} />

            <form onSubmit={submit} className="mx-auto flex max-w-xl flex-col gap-6 px-4 py-8 sm:px-6 lg:px-8">
                <section className="rounded-lg bg-white p-7 shadow-sm">
                    <h2 className="text-base font-semibold text-ink">{c.sectionTitle}</h2>
                    <p className="mt-0.5 text-sm text-ink-muted">{c.sectionBody}</p>

                    <div className="mt-5 flex flex-col gap-4">
                        <Field
                            label={c.nameLabel}
                            placeholder={c.namePlaceholder}
                            field={{ value: data.name, onChange: (event) => setData('name', event.target.value) }}
                            errors={errors.name}
                        />
                        <Field
                            label={c.countryLabel}
                            placeholder="RO"
                            maxLength={2}
                            field={{ value: data.country, onChange: (event) => setData('country', event.target.value.toUpperCase()) }}
                            errors={errors.country}
                            hint={c.countryHint}
                        />
                        <Field
                            label={c.emailLabel}
                            type="email"
                            placeholder={c.optional}
                            field={{ value: data.email, onChange: (event) => setData('email', event.target.value) }}
                            errors={errors.email}
                            hint={e.emailHint}
                        />
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
                        {processing ? c.submitting : e.submit}
                    </button>
                </div>
            </form>
        </AppLayout>
    );
}
