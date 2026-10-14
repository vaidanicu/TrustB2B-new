import Logo from '@/Components/Logo';
import { APP_LOCALE_LABELS, APP_LOCALES, useAppTranslations } from '@/i18n/appTranslations';
import { Link, router, usePage } from '@inertiajs/react';

function initials(name) {
    return (name ?? '')
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part[0]?.toUpperCase())
        .join('');
}

function LocaleSwitcher({ current }) {
    return (
        <div className="flex items-center gap-1 text-xs font-semibold">
            {APP_LOCALES.map((locale) => (
                <button
                    key={locale}
                    type="button"
                    disabled={locale === current}
                    onClick={() => router.patch(route('locale.update'), { locale }, { preserveScroll: true })}
                    className={locale === current ? 'text-accent' : 'text-ink-muted hover:text-ink-soft'}
                >
                    {APP_LOCALE_LABELS[locale]}
                </button>
            ))}
        </div>
    );
}

/**
 * Shared chrome for the TrustBridge-specific pages (Dashboard, Organizations,
 * Events, Audit log) — a direct translation of the Faza 1 design canvas
 * mockups into real markup, reading real auth/tenancy data instead of the
 * mockups' sample names. Deliberately separate from Breeze's own
 * AuthenticatedLayout, which Profile still uses unchanged.
 *
 * @param {'dashboard'|'organizations'|'audit-log'|'guide'|null} active
 */
export default function AppLayout({ title, active = null, actions = null, children }) {
    const { auth } = usePage().props;
    const user = auth?.user;
    const { t, locale } = useAppTranslations();

    const isOrganizationScope = Boolean(user?.organization_id);
    // Everyone else lands on the same "Clienți" nav: a franchise-partner-admin
    // (their own franchise_partner_id set) and a true system operator
    // (platform-admin/franchisor-admin, neither organization_id nor
    // franchise_partner_id set) are both handled identically by
    // OrganizationController/AuditLogController — this used to require
    // franchise_partner_id too, which silently left a system operator with
    // no navigation at all beyond Profil/Ieșire.
    const navItems = isOrganizationScope
        ? [
              { key: 'dashboard', label: t.nav.dashboard, href: route('dashboard') },
              { key: 'audit-log', label: t.nav.auditLog, href: route('audit-log.index') },
              { key: 'guide', label: t.nav.guide, href: route('guide.index') },
          ]
        : [
              { key: 'organizations', label: t.nav.organizations, href: route('organizations.index') },
              { key: 'audit-log', label: t.nav.auditLog, href: route('audit-log.index') },
              { key: 'guide', label: t.nav.guide, href: route('guide.index') },
          ];

    const contextLabel = isOrganizationScope
        ? user.organization?.name
        : (user?.franchise_partner?.name ?? (user?.roles ?? [])[0]);

    return (
        <div className="min-h-screen bg-paper">
            <nav className="border-b border-line bg-white">
                <div className="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
                    <div className="flex items-center gap-10">
                        <Link href="/" className="flex items-center">
                            <Logo height={24} />
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
                        <div className="flex items-center gap-3 rounded-md px-3 py-2 text-sm text-ink-muted">
                            <LocaleSwitcher current={locale} />
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
                                {t.nav.profile}
                            </Link>
                            <Link
                                href={route('logout')}
                                method="post"
                                as="button"
                                className="text-xs text-ink-muted underline hover:text-ink-soft"
                            >
                                {t.nav.logout}
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
