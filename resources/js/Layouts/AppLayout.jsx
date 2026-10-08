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
        <div className="min-h-screen bg-gray-100">
            <nav className="border-b border-gray-100 bg-white">
                <div className="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
                    <div className="flex items-center gap-10">
                        <Link href="/" className="flex items-center gap-2.5">
                            <BrandMark />
                            <span className="text-[17px] font-semibold text-gray-800">TrustBridge</span>
                        </Link>

                        <div className="hidden gap-8 sm:flex">
                            {navItems.map((item) => (
                                <Link
                                    key={item.key}
                                    href={item.href}
                                    className={
                                        'flex h-16 items-center border-b-2 text-sm font-medium transition duration-150 ease-in-out ' +
                                        (active === item.key
                                            ? 'border-indigo-400 text-gray-900'
                                            : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700')
                                    }
                                >
                                    {item.label}
                                </Link>
                            ))}
                        </div>
                    </div>

                    {user && (
                        <div className="flex items-center gap-2 rounded-md px-3 py-2 text-sm text-gray-600">
                            <span className="flex h-7 w-7 items-center justify-center rounded-full bg-gray-200 text-xs font-semibold text-gray-600">
                                {initials(user.name)}
                            </span>
                            <span>
                                {user.name}
                                {contextLabel && <span className="text-gray-400"> · {contextLabel}</span>}
                            </span>
                            <Link
                                href={route('profile.edit')}
                                className="ms-2 text-xs text-gray-400 underline hover:text-gray-700"
                            >
                                Profil
                            </Link>
                            <Link
                                href={route('logout')}
                                method="post"
                                as="button"
                                className="text-xs text-gray-400 underline hover:text-gray-700"
                            >
                                Ieșire
                            </Link>
                        </div>
                    )}
                </div>
            </nav>

            {title && (
                <header className="bg-white shadow-[0_1px_3px_0_rgba(0,0,0,0.06)]">
                    <div className="mx-auto flex max-w-7xl items-center justify-between px-4 py-6 sm:px-6 lg:px-8">
                        <h1 className="text-xl font-semibold text-gray-800">{title}</h1>
                        {actions}
                    </div>
                </header>
            )}

            <main>{children}</main>
        </div>
    );
}
