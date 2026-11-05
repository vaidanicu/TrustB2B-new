import AppLayout from '@/Layouts/AppLayout';
import { useAppTranslations } from '@/i18n/appTranslations';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';

function NewPartnerButton({ label }) {
    return (
        <Link
            href={route('partners.create')}
            className="inline-flex items-center rounded-md border border-transparent bg-ink px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white transition hover:bg-ink-soft"
        >
            {label}
        </Link>
    );
}

function NewAccountCredentialsBanner({ email, password, emailSent, t }) {
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
            <p className="font-medium">{emailSent ? t.partners.index.emailSent : t.partners.index.emailFailed}</p>
            <div className="mt-1.5 flex flex-wrap items-center gap-3">
                <span>
                    {t.partners.index.emailLabel}: <strong>{email}</strong> · {t.partners.index.passwordLabel}: <strong>{password}</strong>
                </span>
                <button type="button" onClick={copy} className="shrink-0 rounded-md border border-current/30 px-3 py-1 text-xs font-semibold uppercase tracking-wide">
                    {copied ? t.common.copied : t.common.copy}
                </button>
            </div>
        </div>
    );
}

export default function Index({ partners }) {
    const { flash } = usePage().props;
    const { t } = useAppTranslations();
    const p = t.partners.index;

    return (
        <AppLayout title={p.title} active="partners" actions={<NewPartnerButton label={p.newPartner} />}>
            <Head title={p.title} />

            <div className="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                {flash?.status && (
                    <div className="mb-5 rounded-md border border-teal bg-teal-soft px-4 py-3 text-sm text-teal-ink">
                        {flash.status}
                    </div>
                )}
                {flash?.newAccountPassword && (
                    <NewAccountCredentialsBanner email={flash.newAccountEmail} password={flash.newAccountPassword} emailSent={flash.accountEmailSent} t={t} />
                )}
                {flash?.reusedExistingAccount && (
                    <div className="mb-5 rounded-md border border-line bg-paper-sunken px-4 py-3 text-sm text-ink-soft">
                        {p.reusedAccount(flash.reusedExistingAccount)}
                    </div>
                )}

                <p className="-mt-2 mb-6 text-sm text-ink-muted">
                    {partners.length} {partners.length === 1 ? p.countOne : p.countOther}
                </p>

                <div className="overflow-hidden rounded-lg bg-white shadow-sm">
                    <div className="grid grid-cols-[2fr_1fr_auto] gap-3 border-b border-line px-5 py-3 text-xs font-semibold uppercase tracking-wide text-ink-muted">
                        <span>{p.colName}</span>
                        <span>{p.colCountry}</span>
                        <span></span>
                    </div>

                    {partners.length === 0 && (
                        <p className="px-5 py-6 text-sm text-ink-muted">{p.empty}</p>
                    )}

                    {partners.map((partner) => {
                        function destroy() {
                            if (confirm(p.confirmDelete)) {
                                router.delete(route('partners.destroy', partner.id));
                            }
                        }

                        return (
                            <div
                                key={partner.id}
                                className="grid grid-cols-[2fr_1fr_auto] items-center gap-3 border-b border-line px-5 py-4 text-sm last:border-0 hover:bg-paper-raised"
                            >
                                <span className="font-medium text-ink">{partner.name}</span>
                                <span className="text-ink-soft">{partner.country}</span>
                                <span className="flex items-center justify-end">
                                    <button type="button" onClick={destroy} className="text-xs text-ink-muted underline hover:text-danger-ink">
                                        {p.delete}
                                    </button>
                                </span>
                            </div>
                        );
                    })}
                </div>
            </div>
        </AppLayout>
    );
}
