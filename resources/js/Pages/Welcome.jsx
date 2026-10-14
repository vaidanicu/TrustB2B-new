import Logo from '@/Components/Logo';
import { Head, Link } from '@inertiajs/react';
import { LOCALE_LABELS, WELCOME_TRANSLATIONS } from './welcomeTranslations';

const LOCALE_PATHS = { ro: '/', en: '/en', de: '/de' };

function LanguageSwitcher({ current }) {
    return (
        <div className="flex items-center gap-1 text-xs font-medium">
            {Object.entries(LOCALE_LABELS).map(([locale, label], index) => (
                <span key={locale} className="flex items-center gap-1">
                    {index > 0 && <span className="text-line">·</span>}
                    {locale === current ? (
                        <span className="text-ink">{label}</span>
                    ) : (
                        <a href={LOCALE_PATHS[locale]} className="text-ink-muted hover:text-ink">
                            {label}
                        </a>
                    )}
                </span>
            ))}
        </div>
    );
}

export default function Welcome({ locale = 'ro' }) {
    const t = WELCOME_TRANSLATIONS[locale] ?? WELCOME_TRANSLATIONS.ro;

    return (
        <div className="min-h-screen bg-paper">
            <Head title={t.htmlTitle} />

            <header className="border-b border-line">
                <div className="mx-auto flex max-w-6xl items-center justify-between px-6 py-5">
                    <Logo height={26} />
                    <div className="flex items-center gap-5">
                        <LanguageSwitcher current={locale} />
                        <Link
                            href={route('login')}
                            className="rounded-md border border-transparent bg-ink px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white transition hover:bg-ink-soft"
                        >
                            {t.login}
                        </Link>
                    </div>
                </div>
            </header>

            <section className="relative overflow-hidden">
                <div
                    className="absolute inset-0 bg-cover bg-[center_30%]"
                    style={{ backgroundImage: 'url(/images/background.jpg)' }}
                    aria-hidden="true"
                />
                <div
                    className="absolute inset-0 bg-gradient-to-b from-paper/97 via-paper/94 to-paper"
                    aria-hidden="true"
                />

                <div className="relative mx-auto max-w-4xl px-6 pb-16 pt-20 text-center">
                    <p className="font-mono text-xs font-semibold uppercase tracking-[0.15em] text-accent">{t.eyebrow}</p>
                    <h1 className="mt-5 text-balance font-display text-4xl font-semibold leading-tight text-ink sm:text-5xl">
                        {t.headline}
                    </h1>
                    <p className="mx-auto mt-5 max-w-2xl text-balance text-base leading-relaxed text-ink-soft">{t.subheadline}</p>
                    <div className="mt-8 flex items-center justify-center gap-4">
                        <Link
                            href={route('login')}
                            className="rounded-md border border-transparent bg-ink px-6 py-3 text-xs font-semibold uppercase tracking-widest text-white transition hover:bg-ink-soft"
                        >
                            {t.login}
                        </Link>
                        <a href="#cum-functioneaza" className="text-sm font-medium text-accent underline underline-offset-4">
                            {t.ctaSecondary}
                        </a>
                    </div>
                </div>
            </section>

            <section className="border-t border-line bg-paper-raised py-16">
                <div className="mx-auto max-w-5xl px-6">
                    <h2 className="font-display text-2xl font-semibold text-ink">{t.gapTitle}</h2>
                    <p className="mt-2 max-w-2xl text-sm text-ink-soft">{t.gapSubtitle}</p>

                    <div className="mt-8 overflow-hidden rounded-lg border border-line bg-white">
                        <div className="grid grid-cols-[1.1fr_1.3fr_1.3fr] gap-4 border-b border-line bg-paper-sunken px-5 py-3 text-xs font-semibold uppercase tracking-wide text-ink-muted">
                            {t.gapHeaders.map((header) => (
                                <span key={header}>{header}</span>
                            ))}
                        </div>
                        {t.gapRows.map((row) => (
                            <div
                                key={row.segment}
                                className="grid grid-cols-[1.1fr_1.3fr_1.3fr] gap-4 border-b border-line px-5 py-4 text-sm last:border-0"
                            >
                                <span className="font-medium text-ink">{row.segment}</span>
                                <span className="text-ink-soft">{row.strength}</span>
                                <span className="text-ink-muted">{row.gap}</span>
                            </div>
                        ))}
                    </div>
                </div>
            </section>

            <section id="cum-functioneaza" className="py-16">
                <div className="mx-auto max-w-5xl px-6">
                    <h2 className="font-display text-2xl font-semibold text-ink">{t.principlesTitle}</h2>
                    <div className="mt-8 grid grid-cols-1 gap-4 sm:grid-cols-2">
                        {t.principles.map((item, index) => (
                            <div key={item.title} className="rounded-lg border border-line bg-white p-6">
                                <span className="font-mono text-xs font-semibold text-accent">
                                    {String(index + 1).padStart(2, '0')}
                                </span>
                                <h3 className="mt-2 text-base font-semibold text-ink">{item.title}</h3>
                                <p className="mt-1.5 text-sm leading-relaxed text-ink-soft">{item.body}</p>
                            </div>
                        ))}
                    </div>
                </div>
            </section>

            <section className="border-t border-line bg-paper-raised py-16">
                <div className="mx-auto max-w-5xl px-6">
                    <h2 className="font-display text-2xl font-semibold text-ink">{t.packagesTitle}</h2>
                    <div className="mt-8 grid grid-cols-1 gap-4 sm:grid-cols-3">
                        {t.packages.map((pkg, index) => (
                            <div key={pkg.name} className="flex flex-col gap-2 rounded-lg border border-line bg-white p-6">
                                <span className="font-mono text-xs font-semibold uppercase tracking-wide text-accent">
                                    {t.packageBadge} {String.fromCharCode(65 + index)}
                                </span>
                                <h3 className="text-base font-semibold text-ink">{pkg.name}</h3>
                                <p className="text-sm leading-relaxed text-ink-soft">{pkg.description}</p>
                            </div>
                        ))}
                    </div>
                </div>
            </section>

            <section className="py-16">
                <div className="mx-auto max-w-5xl px-6">
                    <h2 className="font-display text-2xl font-semibold text-ink">{t.audiencesTitle}</h2>
                    <div className="mt-6 flex flex-wrap gap-3">
                        {t.audiences.map((audience) => (
                            <span key={audience} className="rounded-full border border-line bg-white px-4 py-2 text-sm text-ink-soft">
                                {audience}
                            </span>
                        ))}
                    </div>
                </div>
            </section>

            <footer className="relative overflow-hidden border-t border-line bg-ink py-20 text-center">
                <div
                    className="absolute inset-0 bg-cover bg-center opacity-40"
                    style={{ backgroundImage: 'url(/images/background.jpg)' }}
                    aria-hidden="true"
                />
                <div className="absolute inset-0 bg-gradient-to-t from-ink via-ink/90 to-ink/60" aria-hidden="true" />

                <div className="relative">
                    <p className="font-display text-xl font-semibold text-white">{t.footerCta}</p>
                    <div className="mt-5">
                        <Link
                            href={route('login')}
                            className="rounded-md bg-white px-6 py-3 text-xs font-semibold uppercase tracking-widest text-ink transition hover:bg-paper"
                        >
                            {t.login}
                        </Link>
                    </div>
                    <p className="mx-auto mt-8 max-w-xl px-6 text-xs leading-relaxed text-white/50">{t.footerDisclaimer}</p>
                </div>
            </footer>
        </div>
    );
}
