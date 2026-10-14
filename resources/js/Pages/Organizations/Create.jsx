import AppLayout from '@/Layouts/AppLayout';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import { Head, Link, useForm } from '@inertiajs/react';

function Field({ label, field, errors, ...props }) {
    return (
        <div>
            <InputLabel value={label} />
            <TextInput className="mt-1 block w-full" {...field} {...props} />
            <InputError message={errors} className="mt-1" />
        </div>
    );
}

export default function Create({ franchisePartners }) {
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        country: 'RO',
        data_protection_contact_email: '',
        franchise_partner_id: franchisePartners?.[0]?.id ?? '',
        admin_name: '',
        admin_email: '',
    });

    function submit(e) {
        e.preventDefault();
        post(route('organizations.store'));
    }

    return (
        <AppLayout title="Client nou">
            <Head title="Client nou" />

            <form onSubmit={submit} className="mx-auto flex max-w-3xl flex-col gap-6 px-4 py-8 sm:px-6 lg:px-8">
                <section className="rounded-lg bg-white p-7 shadow-sm">
                    <h2 className="text-base font-semibold text-ink">Organizație client</h2>
                    <p className="mt-0.5 text-sm text-ink-muted">
                        Datele companiei care va organiza evenimente pe platformă.
                    </p>

                    <div className="mt-5 flex flex-col gap-4">
                        <Field
                            label="Nume organizație"
                            placeholder="ex. Hotel Aurora"
                            field={{ value: data.name, onChange: (e) => setData('name', e.target.value) }}
                            errors={errors.name}
                        />
                        <div className="grid grid-cols-2 gap-4">
                            <Field
                                label="Țară"
                                placeholder="RO"
                                maxLength={2}
                                field={{ value: data.country, onChange: (e) => setData('country', e.target.value.toUpperCase()) }}
                                errors={errors.country}
                            />
                            <Field
                                label="Contact protecția datelor"
                                type="email"
                                placeholder="opțional"
                                field={{ value: data.data_protection_contact_email, onChange: (e) => setData('data_protection_contact_email', e.target.value) }}
                                errors={errors.data_protection_contact_email}
                            />
                        </div>

                        {franchisePartners?.length > 0 && (
                            <div>
                                <InputLabel value="Franciză parteneră" />
                                <select
                                    value={data.franchise_partner_id}
                                    onChange={(e) => setData('franchise_partner_id', e.target.value)}
                                    className="mt-1 block w-full rounded-md border-line text-sm shadow-sm focus:border-accent focus:ring-accent"
                                >
                                    {franchisePartners.map((partner) => (
                                        <option key={partner.id} value={partner.id}>{partner.name}</option>
                                    ))}
                                </select>
                                <InputError message={errors.franchise_partner_id} className="mt-1" />
                            </div>
                        )}
                    </div>
                </section>

                <section className="rounded-lg bg-white p-7 shadow-sm">
                    <h2 className="text-base font-semibold text-ink">Primul admin al organizației</h2>
                    <p className="mt-0.5 text-sm text-ink-muted">
                        Acest cont va gestiona evenimentele și participanții organizației. Parola generată se
                        afișează o singură dată — nu există încă trimitere automată de email.
                    </p>

                    <div className="mt-5 grid grid-cols-2 gap-4">
                        <Field
                            label="Nume"
                            placeholder="ex. Bianca Ionescu"
                            field={{ value: data.admin_name, onChange: (e) => setData('admin_name', e.target.value) }}
                            errors={errors.admin_name}
                        />
                        <Field
                            label="Email"
                            type="email"
                            placeholder="ex. bianca@hotelaurora.ro"
                            field={{ value: data.admin_email, onChange: (e) => setData('admin_email', e.target.value) }}
                            errors={errors.admin_email}
                        />
                    </div>
                </section>

                <div className="flex items-center justify-end gap-3 pb-4">
                    <Link href={route('organizations.index')} className="text-sm text-ink-muted hover:text-ink">
                        Anulează
                    </Link>
                    <button
                        type="submit"
                        disabled={processing}
                        className="inline-flex items-center rounded-md border border-transparent bg-ink px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white transition hover:bg-ink-soft disabled:opacity-50"
                    >
                        {processing ? 'Se creează…' : 'Creează client'}
                    </button>
                </div>
            </form>
        </AppLayout>
    );
}
