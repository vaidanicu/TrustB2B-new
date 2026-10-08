import BrandMark from '@/Components/BrandMark';
import { Link, usePage } from '@inertiajs/react';

function initials(name) {
    return (name ?? '')
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part[0]?.toUpperCase())
        .join('');
}

/**
 * Shared chrome for the TrustBridge-specific pages (Dashboard, Organizations,
 * Events, Audit log) — a direct translation of the Faza 1 design canvas
 * mockups into real markup, reading real auth/tenancy data instead of the
 * mockups' sample names. Deliberately separate from Breeze's own
 * AuthenticatedLayout, which Profile still uses unchanged.
 *
 * @param {'dashboard'|'organizations'|'audit-log'|null} active
 */
export default function AppLayout({ title, active = null, actions = null, children }) {
    const { auth } = usePage().props;
    const user = auth?.user;

    const isOrganizationScope = Boolean(user?.organization_id);
    const isFranchiseScope = !user?.organization_id && Boolean(user?.franchise_partner_id);

    const navItems = isOrganizationScope
        ? [
              { key: 'dashboard', label: 'Dashboard', href: route('dashboard') },
              { key: 'audit-log', label: 'Jurnal audit', href: route('audit-log.index') },
          ]
        : isFranchiseScope
          ? [
                { key: 'organizations', label: 'Clienți', href: route('organizations.index') },
                { key: 'audit-log', label: 'Jurnal audit', href: route('audit-log.index') },
            ]
          : [];

    const contextLabel = isOrganizationScope
        ? user.organization?.name
        : isFranchiseScope
          ? user.franchise_partner?.name
          : (user?.roles ?? [])[0];

    return (
        <div className="min-h-screen bg-paper">
            <nav className="border-b border-line bg-white">
                <div className="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
                    <div className="flex items-center gap-10">
                        <Link href="/" className="flex items-center gap-2.5">
                            <BrandMark className="text-ink" />
                            <span className="font-display text-[18px] font-semibold text-ink">TrustBridge</span>
                        </Link>

                        <div className="hidden gap-8 sm:flex">
                            {navItems.map((item) => (
                                <Link
                                    key={item.key}
                                    href={item.href}
                                    className={
                                        'flex h-16 items-center border-b-2 text-sm font-medium transition duration-150 ease-in-out ' +
                                        (active === item.key
                                            ? 'border-accent text-ink'
                                            : 'border-transparent text-ink-muted hover:border-line hover:text-ink-soft')
                                    }
                                >
                                    {item.label}
                                </Link>
                            ))}
                        </div>
                    </div>

                    {user && (
                        <div className="flex items-center gap-2 rounded-md px-3 py-2 text-sm text-ink-muted">
                            <span className="flex h-7 w-7 items-center justify-center rounded-full bg-accent-soft text-xs font-semibold text-accent-ink">
                                {initials(user.name)}
                            </span>
                            <span>
                                <span className="text-ink-soft">{user.name}</span>
                                {contextLabel && <span className="text-ink-muted"> · {contextLabel}</span>}
                            </span>
                            <Link
                                href={route('profile.edit')}
                                className="ms-2 text-xs text-ink-muted underline hover:text-ink-soft"
                            >
                                Profil
                            </Link>
                            <Link
                                href={route('logout')}
                                method="post"
                                as="button"
                                className="text-xs text-ink-muted underline hover:text-ink-soft"
                            >
                                Ieșire
                            </Link>
                        </div>
                    )}
                </div>
            </nav>

            {title && (
                <header className="bg-white shadow-[0_1px_3px_0_rgba(23,33,43,0.06)]">
                    <div className="mx-auto flex max-w-7xl items-center justify-between px-4 py-6 sm:px-6 lg:px-8">
                        <h1 className="font-display text-2xl font-semibold text-ink">{title}</h1>
                        {actions}
                    </div>
                </header>
            )}

            <main>{children}</main>
        </div>
    );
}
