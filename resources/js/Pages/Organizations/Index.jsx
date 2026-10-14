import AppLayout from '@/Layouts/AppLayout';
import { Head, Link, usePage } from '@inertiajs/react';
import { useState } from 'react';

const STATUS_STYLES = {
    active: { label: 'Activ', dot: 'bg-teal', text: 'text-teal-ink', bg: 'bg-teal-soft' },
    onboarding: { label: 'În activare', dot: 'bg-amber-600', text: 'text-amber-700', bg: 'bg-amber-50' },
    suspended: { label: 'Suspendat', dot: 'bg-danger', text: 'text-danger-ink', bg: 'bg-danger-soft' },
};

function StatusPill({ status }) {
    const style = STATUS_STYLES[status] ?? STATUS_STYLES.onboarding;

    return (
        <span className={`inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-semibold ${style.bg} ${style.text}`}>
            <span className={`h-1.5 w-1.5 rounded-full ${style.dot}`} />
            {style.label}
        </span>
    );
}

function NewOrganizationButton() {
    return (
        <Link
            href={route('organizations.create')}
            className="inline-flex items-center rounded-md border border-transparent bg-ink px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white transition hover:bg-ink-soft"
        >
            Client nou
        </Link>
    );
}

function NewAdminCredentialsBanner({ email, password }) {
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
        <div className="mb-5 rounded-md border border-teal bg-teal-soft px-4 py-3 text-sm text-teal-ink">
            <p className="font-medium">Datele de autentificare pentru noul admin (se afișează o singură dată):</p>
            <div className="mt-1.5 flex flex-wrap items-center gap-3">
                <span>
                    Email: <strong>{email}</strong> · Parolă: <strong>{password}</strong>
                </span>
                <button type="button" onClick={copy} className="rounded-md border border-teal-ink/30 px-3 py-1 text-xs font-semibold uppercase tracking-wide">
                    {copied ? 'Copiat ✓' : 'Copiază'}
                </button>
            </div>
        </div>
    );
}

export default function Index({ organizations }) {
    const { flash } = usePage().props;

    return (
        <AppLayout title="Clienți" active="organizations" actions={<NewOrganizationButton />}>
            <Head title="Clienți" />

            <div className="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                {flash?.status && (
                    <div className="mb-5 rounded-md border border-teal bg-teal-soft px-4 py-3 text-sm text-teal-ink">
                        {flash.status}
                    </div>
                )}
                {flash?.newAdminPassword && (
                    <NewAdminCredentialsBanner email={flash.newAdminEmail} password={flash.newAdminPassword} />
                )}

                <p className="-mt-2 mb-6 text-sm text-ink-muted">
                    {organizations.length} organiza{organizations.length === 1 ? 'ție' : 'ții'} client
                </p>

                <div className="overflow-hidden rounded-lg bg-white shadow-sm">
                    <div className="grid grid-cols-[2.4fr_1fr_1fr] gap-3 border-b border-line px-5 py-3 text-xs font-semibold uppercase tracking-wide text-ink-muted">
                        <span>Organizație</span>
                        <span>Țară</span>
                        <span>Stare</span>
                    </div>

                    {organizations.length === 0 && (
                        <p className="px-5 py-6 text-sm text-ink-muted">Niciun client încă.</p>
                    )}

                    {organizations.map((organization) => (
                        <div
                            key={organization.id}
                            className="grid grid-cols-[2.4fr_1fr_1fr] items-center gap-3 border-b border-line px-5 py-4 text-sm last:border-0 hover:bg-paper-raised"
                        >
                            <span className="font-medium text-ink">{organization.name}</span>
                            <span className="text-ink-soft">{organization.country}</span>
                            <StatusPill status={organization.status} />
                        </div>
                    ))}
                </div>
            </div>
        </AppLayout>
    );
}
