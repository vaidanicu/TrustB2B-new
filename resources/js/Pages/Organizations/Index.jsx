import AppLayout from '@/Layouts/AppLayout';
import { useAppTranslations } from '@/i18n/appTranslations';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';

function NewOrganizationButton({ label }) {
    return (
        <Link
            href={route('organizations.create')}
            className="inline-flex items-center rounded-md border border-transparent bg-ink px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white transition hover:bg-ink-soft"
        >
            {label}
        </Link>
    );
}

function StatusSelect({ organization, style, t }) {
    function change(e) {
        router.patch(route('organizations.update-status', organization.id), { contract_status: e.target.value }, { preserveScroll: true });
    }

    return (
        <select
            value={organization.status}
            onChange={change}
            className={`w-fit rounded-full border-0 py-0.5 pl-2.5 pr-7 text-xs font-semibold focus:ring-2 focus:ring-accent ${style.bg} ${style.text}`}
        >
            <option value="onboarding">{t.statusOrganization.onboarding}</option>
            <option value="active">{t.statusOrganization.active}</option>
            <option value="suspended">{t.statusOrganization.suspended}</option>
        </select>
    );
}

function NewAdminCredentialsBanner({ email, password, emailSent, t }) {
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
            <p className="font-medium">{emailSent ? t.organizations.index.emailSent : t.organizations.index.emailFailed}</p>
            <div className="mt-1.5 flex flex-wrap items-center gap-3">
                <span>
                    {t.organizations.index.emailLabel}: <strong>{email}</strong> · {t.organizations.index.passwordLabel}: <strong>{password}</strong>
                </span>
                <button type="button" onClick={copy} className="shrink-0 rounded-md border border-current/30 px-3 py-1 text-xs font-semibold uppercase tracking-wide">
                    {copied ? t.common.copied : t.common.copy}
                </button>
            </div>
        </div>
    );
}

export default function Index({ organizations }) {
    const { flash } = usePage().props;
    const { t } = useAppTranslations();
    const statusStyles = {
        active: { label: t.statusOrganization.active, dot: 'bg-teal', text: 'text-teal-ink', bg: 'bg-teal-soft' },
        onboarding: { label: t.statusOrganization.onboarding, dot: 'bg-amber-600', text: 'text-amber-700', bg: 'bg-amber-50' },
        suspended: { label: t.statusOrganization.suspended, dot: 'bg-danger', text: 'text-danger-ink', bg: 'bg-danger-soft' },
    };

    return (
        <AppLayout title={t.organizations.index.title} active="organizations" actions={<NewOrganizationButton label={t.organizations.index.newOrganization} />}>
            <Head title={t.organizations.index.title} />

            <div className="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                {flash?.status && (
                    <div className="mb-5 rounded-md border border-teal bg-teal-soft px-4 py-3 text-sm text-teal-ink">
                        {flash.status}
                    </div>
                )}
                {flash?.newAdminPassword && (
                    <NewAdminCredentialsBanner email={flash.newAdminEmail} password={flash.newAdminPassword} emailSent={flash.newAdminEmailSent} t={t} />
                )}

                <p className="-mt-2 mb-6 text-sm text-ink-muted">
                    {organizations.length} {organizations.length === 1 ? t.organizations.index.countOne : t.organizations.index.countOther}
                </p>

                <div className="overflow-hidden rounded-lg bg-white shadow-sm">
                    <div className="grid grid-cols-[2.4fr_1fr_1fr_auto] gap-3 border-b border-line px-5 py-3 text-xs font-semibold uppercase tracking-wide text-ink-muted">
                        <span>{t.organizations.index.colOrg}</span>
                        <span>{t.organizations.index.colCountry}</span>
                        <span>{t.organizations.index.colStatus}</span>
                        <span></span>
                    </div>

                    {organizations.length === 0 && (
                        <p className="px-5 py-6 text-sm text-ink-muted">{t.organizations.index.empty}</p>
                    )}

                    {organizations.map((organization) => {
                        const style = statusStyles[organization.status] ?? statusStyles.onboarding;

                        return (
                            <div
                                key={organization.id}
                                className="grid grid-cols-[2.4fr_1fr_1fr_auto] items-center gap-3 border-b border-line px-5 py-4 text-sm last:border-0 hover:bg-paper-raised"
                            >
                                <span className="font-medium text-ink">{organization.name}</span>
                                <span className="text-ink-soft">{organization.country}</span>
                                <StatusSelect organization={organization} style={style} t={t} />
                                <span className="flex items-center justify-end">
                                    <Link href={route('organizations.events', organization.id)} className="text-xs text-accent underline">
                                        {t.organizations.events.title}
                                    </Link>
                                </span>
                            </div>
                        );
                    })}
                </div>
            </div>
        </AppLayout>
    );
}
