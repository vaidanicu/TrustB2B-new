import AppLayout from '@/Layouts/AppLayout';
import { useAppTranslations } from '@/i18n/appTranslations';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';

function NewUserButton({ label }) {
    return (
        <Link
            href={route('users.create')}
            className="inline-flex items-center rounded-md border border-transparent bg-ink px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white transition hover:bg-ink-soft"
        >
            {label}
        </Link>
    );
}

function NewStaffCredentialsBanner({ email, password, emailSent, t }) {
    const [copied, setCopied] = useState(false);

    async function copy() {
        try {
            await navigator.clipboard.writeText(`${email} / ${password}`);
            setCopied(true);
        } catch {
            setCopied(false);
        }
    }

    return (
        <div className={'mb-5 rounded-md border px-4 py-3 text-sm ' + (emailSent ? 'border-teal bg-teal-soft text-teal-ink' : 'border-amber-600 bg-amber-50 text-amber-700')}>
            <p className="font-medium">{emailSent ? t.users.index.emailSent : t.users.index.emailFailed}</p>
            <div className="mt-1.5 flex flex-wrap items-center gap-3">
                <span>
                    {t.users.index.emailLabel}: <strong>{email}</strong> · {t.users.index.passwordLabel}: <strong>{password}</strong>
                </span>
                <button type="button" onClick={copy} className="shrink-0 rounded-md border border-current/30 px-3 py-1 text-xs font-semibold uppercase tracking-wide">
                    {copied ? t.common.copied : t.common.copy}
                </button>
            </div>
        </div>
    );
}

export default function Index({ users }) {
    const { flash } = usePage().props;
    const { t } = useAppTranslations();

    function destroy(user) {
        if (confirm(t.users.index.confirmDelete)) {
            router.delete(route('users.destroy', user.id));
        }
    }

    return (
        <AppLayout title={t.users.index.title} active="users" actions={<NewUserButton label={t.users.index.newUser} />}>
            <Head title={t.users.index.title} />

            <div className="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                {flash?.status && (
                    <div className="mb-5 rounded-md border border-teal bg-teal-soft px-4 py-3 text-sm text-teal-ink">
                        {flash.status}
                    </div>
                )}
                {flash?.newStaffPassword && (
                    <NewStaffCredentialsBanner email={flash.newStaffEmail} password={flash.newStaffPassword} emailSent={flash.newStaffEmailSent} t={t} />
                )}

                <div className="overflow-hidden rounded-lg bg-white shadow-sm">
                    <div className="grid grid-cols-[1.6fr_1.8fr_1.2fr_auto] gap-3 border-b border-line px-5 py-3 text-xs font-semibold uppercase tracking-wide text-ink-muted">
                        <span>{t.users.index.colName}</span>
                        <span>{t.users.index.colEmail}</span>
                        <span>{t.users.index.colRole}</span>
                        <span></span>
                    </div>

                    {users.length === 0 && (
                        <p className="px-5 py-6 text-sm text-ink-muted">{t.users.index.empty}</p>
                    )}

                    {users.map((user) => (
                        <div
                            key={user.id}
                            className="grid grid-cols-[1.6fr_1.8fr_1.2fr_auto] items-center gap-3 border-b border-line px-5 py-3.5 text-sm last:border-0 hover:bg-paper-raised"
                        >
                            <span className="font-medium text-ink">{user.name} {user.isSelf && <span className="text-ink-muted">{t.users.index.you}</span>}</span>
                            <span className="text-ink-muted">{user.email}</span>
                            <span className="text-ink-soft">{t.users.roles[user.role] ?? user.role}</span>
                            {user.isSelf ? <span /> : (
                                <button type="button" onClick={() => destroy(user)} className="text-xs text-ink-muted underline hover:text-danger-ink">
                                    {t.users.index.delete}
                                </button>
                            )}
                        </div>
                    ))}
                </div>
            </div>
        </AppLayout>
    );
}
