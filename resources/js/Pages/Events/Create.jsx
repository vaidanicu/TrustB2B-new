import AppLayout from '@/Layouts/AppLayout';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import { Head, Link } from '@inertiajs/react';
import { useState } from 'react';

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

function Field({ label, ...props }) {
    return (
        <div>
            <InputLabel value={label} />
            <TextInput className="mt-1 block w-full" {...props} />
        </div>
    );
}

export default function Create() {
    const [retentionDays, setRetentionDays] = useState('90');
    const [endOfCycle, setEndOfCycle] = useState('delete');

    return (
        <AppLayout title="Eveniment nou">
            <Head title="Eveniment nou" />

            <div className="mx-auto flex max-w-3xl flex-col gap-6 px-4 py-8 sm:px-6 lg:px-8">
                <section className="rounded-lg bg-white p-7 shadow-sm">
                    <h2 className="text-base font-semibold text-gray-900">Detalii eveniment</h2>
                    <p className="mt-0.5 text-sm text-gray-400">
                        Scopul, țara și perioada determină ce reguli legale se aplică.
                    </p>

                    <div className="mt-5 flex flex-col gap-4">
                        <Field label="Nume eveniment" placeholder="ex. Turneu de fotbal — clienți 2026" />
                        <Field label="Scop" placeholder="ex. Eveniment de fidelizare pentru clienți B2B" />
                        <div className="grid grid-cols-2 gap-4">
                            <Field label="Data început" placeholder="zz.ll.aaaa" />
                            <Field label="Data sfârșit" placeholder="zz.ll.aaaa" />
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
                                checked={retentionDays === '60'}
                                onChange={(e) => setRetentionDays(e.target.value)}
                                title="60 de zile"
                                hint="Necesită scop documentat"
                            />
                            <RadioCard
                                name="retention"
                                value="90"
                                checked={retentionDays === '90'}
                                onChange={(e) => setRetentionDays(e.target.value)}
                                title="90 de zile"
                                hint="Termen standard"
                            />
                        </div>
                    </div>

                    <div className="mt-5">
                        <span className="mb-2 block text-sm font-medium text-gray-700">La finalul ciclului</span>
                        <div className="grid grid-cols-2 gap-3">
                            <RadioCard
                                name="end-of-cycle"
                                value="delete"
                                checked={endOfCycle === 'delete'}
                                onChange={(e) => setEndOfCycle(e.target.value)}
                                title="Ștergere ireversibilă"
                                hint="Fără pachet de export"
                            />
                            <RadioCard
                                name="end-of-cycle"
                                value="export"
                                checked={endOfCycle === 'export'}
                                onChange={(e) => setEndOfCycle(e.target.value)}
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
                        <Field label="Organizator" />
                        <Field label="Co-organizator" placeholder="opțional" />
                        <Field label="Locație" />
                        <Field label="Agenție" placeholder="opțional" />
                        <Field label="Fotograf" placeholder="opțional" />
                        <Field label="Contact protecția datelor" type="email" />
                        <div className="col-span-2">
                            <Field label="Destinatar pachet final" />
                        </div>
                    </div>
                </section>

                <div className="flex items-center justify-between rounded-lg border border-amber-200 bg-amber-50 px-4 py-3">
                    <p className="text-sm text-amber-800">
                        Evenimentele nu pot fi încă salvate — urmează odată cu AP07/AP08.
                    </p>
                </div>

                <div className="flex items-center justify-end gap-3 pb-4">
                    <Link href={route('dashboard')} className="text-sm text-gray-500 hover:text-gray-800">
                        Anulează
                    </Link>
                    <button
                        type="button"
                        disabled
                        title="Disponibil după ce se construiește modelul de eveniment"
                        className="inline-flex cursor-not-allowed items-center rounded-md border border-transparent bg-gray-800 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white opacity-40"
                    >
                        Continuă
                    </button>
                </div>
            </div>
        </AppLayout>
    );
}
