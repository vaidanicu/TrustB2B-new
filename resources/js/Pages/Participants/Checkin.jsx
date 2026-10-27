import AppLayout from '@/Layouts/AppLayout';
import InputError from '@/Components/InputError';
import { useAppTranslations } from '@/i18n/appTranslations';
import { Head, Link, useForm, usePage } from '@inertiajs/react';

export default function Checkin({ event }) {
    const { flash } = usePage().props;
    const { t: all } = useAppTranslations();
    const t = all.participants.checkin;

    const { data, setData, post, processing, errors, reset } = useForm({ code: '' });

    function submit(e) {
        e.preventDefault();
        post(route('participants.checkin.store', event.id), {
            preserveScroll: true,
            onSuccess: () => reset(),
        });
    }

    return (
        <AppLayout title={`${t.title} — ${event.name}`} active="dashboard">
            <Head title={`${t.title} — ${event.name}`} />

            <div className="mx-auto max-w-md px-4 py-8 sm:px-6 lg:px-8">
                <Link href={route('participants.status', event.id)} className="mb-6 inline-block text-sm text-ink-muted hover:text-ink">
                    {t.back}
                </Link>

                <div className="rounded-lg bg-white p-6 shadow-sm">
                    <h1 className="font-display text-lg font-semibold text-ink">{t.title}</h1>
                    <p className="mt-1 text-sm text-ink-muted">{t.body}</p>

                    {flash?.checkinStatus === 'success' && (
                        <div className="mt-4 rounded-md border border-teal bg-teal-soft px-4 py-3 text-sm text-teal-ink">
                            {t.success(flash.checkinParticipantName)}
                        </div>
                    )}
                    {flash?.checkinStatus === 'already' && (
                        <div className="mt-4 rounded-md border border-amber-600 bg-amber-50 px-4 py-3 text-sm text-amber-700">
                            {t.already(flash.checkinParticipantName)}
                        </div>
                    )}

                    <form onSubmit={submit} className="mt-5">
                        <label className="block text-sm font-medium text-ink-soft">{t.codeLabel}</label>
                        <input
                            type="text"
                            autoFocus
                            value={data.code}
                            onChange={(e) => setData('code', e.target.value.toUpperCase())}
                            placeholder={t.codePlaceholder}
                            className="mt-1 block min-h-[48px] w-full rounded-md border-line text-center text-xl font-semibold uppercase tracking-[0.3em] shadow-sm focus:border-accent focus:ring-accent"
                        />
                        <InputError message={errors.code} className="mt-2" />

                        <button
                            type="submit"
                            disabled={processing}
                            className="mt-4 flex min-h-[48px] w-full items-center justify-center rounded-md bg-ink px-4 text-sm font-semibold uppercase tracking-widest text-white disabled:opacity-50"
                        >
                            {t.submit}
                        </button>
                    </form>
                </div>
            </div>
        </AppLayout>
    );
}
