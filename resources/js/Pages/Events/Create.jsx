import AppLayout from '@/Layouts/AppLayout';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import { Head, Link, useForm } from '@inertiajs/react';

function RadioCard({ name, value, checked, onChange, title, hint }) {
    return (
        <label
            className={
                'flex cursor-pointer items-start gap-2.5 rounded-lg border px-4 py-3.5 ' +
                (checked ? 'border-indigo-500 bg-indigo-50' : 'border-gray-300')
            }
        >
            <input
                type="radio"
                name={name}
                value={value}
                checked={checked}
                onChange={onChange}
                className="mt-0.5 text-indigo-600 focus:ring-indigo-500"
            />
            <span>
                <span className="block text-sm font-medium text-gray-900">{title}</span>
                <span className="block text-xs text-gray-400">{hint}</span>
            </span>
        </label>
    );
}

function Field({ label, field, errors, ...props }) {
    return (
        <div>
            <InputLabel value={label} />
            <TextInput className="mt-1 block w-full" {...field} {...props} />
            <InputError message={errors} className="mt-1" />
        </div>
    );
}

export default function Create() {
    const { data, setData, post, processing, errors } = useForm({
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
        data_protection_contact: '',
        final_package_recipient: '',
    });

    function submit(e) {
        e.preventDefault();
        post(route('events.store'));
    }

    return (
        <AppLayout title="Eveniment nou">
            <Head title="Eveniment nou" />

            <form onSubmit={submit} className="mx-auto flex max-w-3xl flex-col gap-6 px-4 py-8 sm:px-6 lg:px-8">
                <section className="rounded-lg bg-white p-7 shadow-sm">
                    <h2 className="text-base font-semibold text-gray-900">Detalii eveniment</h2>
                    <p className="mt-0.5 text-sm text-gray-400">
                        Scopul, țara și perioada determină ce reguli legale se aplică.
                    </p>

                    <div className="mt-5 flex flex-col gap-4">
                        <Field
                            label="Nume eveniment"
                            placeholder="ex. Turneu de fotbal — clienți 2026"
                            field={{ value: data.name, onChange: (e) => setData('name', e.target.value) }}
                            errors={errors.name}
                        />
                        <Field
                            label="Scop"
                            placeholder="ex. Eveniment de fidelizare pentru clienți B2B"
                            field={{ value: data.purpose, onChange: (e) => setData('purpose', e.target.value) }}
                            errors={errors.purpose}
                        />
                        <div className="grid grid-cols-2 gap-4">
                            <Field
                                label="Data început"
                                type="date"
                                field={{ value: data.starts_at, onChange: (e) => setData('starts_at', e.target.value) }}
                                errors={errors.starts_at}
                            />
                            <Field
                                label="Data sfârșit"
                                type="date"
                                field={{ value: data.ends_at, onChange: (e) => setData('ends_at', e.target.value) }}
                                errors={errors.ends_at}
                            />
                        </div>
                    </div>
                </section>

                <section className="rounded-lg bg-white p-7 shadow-sm">
                    <h2 className="text-base font-semibold text-gray-900">Fotografii și retenție</h2>
                    <p className="mt-0.5 text-sm text-gray-400">
                        Termenul standard e 90 de zile de la finalul evenimentului — abateri necesită scop documentat.
                    </p>

                    <div className="mt-5">
                        <span className="mb-2 block text-sm font-medium text-gray-700">Termen de ștergere foto</span>
                        <div className="grid grid-cols-2 gap-3">
                            <RadioCard
                                name="retention"
                                value="60"
                                checked={data.retention_days === '60'}
                                onChange={(e) => setData('retention_days', e.target.value)}
                                title="60 de zile"
                                hint="Necesită scop documentat"
                            />
                            <RadioCard
                                name="retention"
                                value="90"
                                checked={data.retention_days === '90'}
                                onChange={(e) => setData('retention_days', e.target.value)}
                                title="90 de zile"
                                hint="Termen standard"
                            />
                        </div>
                        <InputError message={errors.retention_days} className="mt-1" />
                    </div>

                    <div className="mt-5">
                        <span className="mb-2 block text-sm font-medium text-gray-700">La finalul ciclului</span>
                        <div className="grid grid-cols-2 gap-3">
                            <RadioCard
                                name="end-of-cycle"
                                value="delete"
                                checked={data.end_of_cycle_action === 'delete'}
                                onChange={(e) => setData('end_of_cycle_action', e.target.value)}
                                title="Ștergere ireversibilă"
                                hint="Fără pachet de export pentru client"
                            />
                            <RadioCard
                                name="end-of-cycle"
                                value="export"
                                checked={data.end_of_cycle_action === 'export'}
                                onChange={(e) => setData('end_of_cycle_action', e.target.value)}
                                title="Pachet export criptat"
                                hint="Livrat, confirmat, apoi șters"
                            />
                        </div>
                    </div>
                </section>

                <section className="rounded-lg bg-white p-7 shadow-sm">
                    <h2 className="text-base font-semibold text-gray-900">Responsabili eveniment</h2>
                    <p className="mt-0.5 text-sm text-gray-400">
                        Confirmă cine ocupă fiecare rol — sistemul nu deduce automat rolurile juridice.
                    </p>

                    <div className="mt-5 grid grid-cols-2 gap-x-4 gap-y-4">
                        <Field label="Organizator" field={{ value: data.organizer, onChange: (e) => setData('organizer', e.target.value) }} errors={errors.organizer} />
                        <Field label="Co-organizator" placeholder="opțional" field={{ value: data.co_organizer, onChange: (e) => setData('co_organizer', e.target.value) }} errors={errors.co_organizer} />
                        <Field label="Locație" field={{ value: data.location, onChange: (e) => setData('location', e.target.value) }} errors={errors.location} />
                        <Field label="Agenție" placeholder="opțional" field={{ value: data.agency, onChange: (e) => setData('agency', e.target.value) }} errors={errors.agency} />
                        <Field label="Fotograf" placeholder="opțional" field={{ value: data.photographer, onChange: (e) => setData('photographer', e.target.value) }} errors={errors.photographer} />
                        <Field label="Contact protecția datelor" type="email" field={{ value: data.data_protection_contact, onChange: (e) => setData('data_protection_contact', e.target.value) }} errors={errors.data_protection_contact} />
                        <div className="col-span-2">
                            <Field label="Destinatar pachet final" field={{ value: data.final_package_recipient, onChange: (e) => setData('final_package_recipient', e.target.value) }} errors={errors.final_package_recipient} />
                        </div>
                    </div>
                </section>

                <div className="flex items-center justify-end gap-3 pb-4">
                    <Link href={route('dashboard')} className="text-sm text-gray-500 hover:text-gray-800">
                        Anulează
                    </Link>
                    <button
                        type="submit"
                        disabled={processing}
                        className="inline-flex items-center rounded-md border border-transparent bg-gray-800 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white transition hover:bg-gray-700 disabled:opacity-50"
                    >
                        {processing ? 'Se salvează…' : 'Continuă'}
                    </button>
                </div>
            </form>
        </AppLayout>
    );
}
