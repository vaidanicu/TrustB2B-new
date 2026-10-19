import AppLayout from '@/Layouts/AppLayout';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import { useAppTranslations } from '@/i18n/appTranslations';
import { Head, Link, useForm } from '@inertiajs/react';

const ASSIGNABLE_ROLES = ['customer-admin', 'event-manager', 'data-protection-officer', 'photographer'];

export default function Create() {
    const { t } = useAppTranslations();
    const c = t.users.create;

    const { data, setData, post, processing, errors } = useForm({
        name: '',
        email: '',
        role: 'photographer',
    });

    function submit(e) {
        e.preventDefault();
        post(route('users.store'));
    }

    return (
        <AppLayout title={c.title}>
            <Head title={c.title} />

            <form onSubmit={submit} className="mx-auto flex max-w-xl flex-col gap-6 px-4 py-8 sm:px-6 lg:px-8">
                <section className="rounded-lg bg-white p-7 shadow-sm">
                    <h2 className="text-base font-semibold text-ink">{c.sectionTitle}</h2>
                    <p className="mt-0.5 text-sm text-ink-muted">{c.sectionBody}</p>

                    <div className="mt-5 flex flex-col gap-4">
                        <div>
                            <InputLabel value={c.nameLabel} />
                            <TextInput
                                className="mt-1 block w-full"
                                value={data.name}
                                onChange={(e) => setData('name', e.target.value)}
                                placeholder={c.namePlaceholder}
                            />
                            <InputError message={errors.name} className="mt-1" />
                        </div>
                        <div>
                            <InputLabel value={c.emailLabel} />
                            <TextInput
                                type="email"
                                className="mt-1 block w-full"
                                value={data.email}
                                onChange={(e) => setData('email', e.target.value)}
                                placeholder={c.emailPlaceholder}
                            />
                            <InputError message={errors.email} className="mt-1" />
                        </div>
                        <div>
                            <InputLabel value={c.roleLabel} />
                            <select
                                value={data.role}
                                onChange={(e) => setData('role', e.target.value)}
                                className="mt-1 block w-full rounded-md border-line text-sm shadow-sm focus:border-accent focus:ring-accent"
                            >
                                {ASSIGNABLE_ROLES.map((role) => (
                                    <option key={role} value={role}>{t.users.roles[role]}</option>
                                ))}
                            </select>
                            <InputError message={errors.role} className="mt-1" />
                        </div>
                    </div>
                </section>

                <div className="flex items-center justify-end gap-3 pb-4">
                    <Link href={route('users.index')} className="text-sm text-ink-muted hover:text-ink">
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
