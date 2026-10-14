import AppLayout from '@/Layouts/AppLayout';
import { useAppTranslations } from '@/i18n/appTranslations';
import { Head, Link } from '@inertiajs/react';

function Step({ number, title, children }) {
    return (
        <li className="flex gap-4">
            <span className="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-ink text-xs font-semibold text-white">
                {number}
            </span>
            <div>
                <p className="text-sm font-semibold text-ink">{title}</p>
                <p className="mt-0.5 text-sm text-ink-soft">{children}</p>
            </div>
        </li>
    );
}

function Section({ eyebrow, title, steps }) {
    return (
        <section className="rounded-lg bg-white p-7 shadow-sm">
            <p className="text-xs font-semibold uppercase tracking-wide text-ink-muted">{eyebrow}</p>
            <h2 className="mt-1 text-lg font-semibold text-ink">{title}</h2>
            <ol className="mt-5 flex flex-col gap-5">
                {steps.map((step, index) => (
                    <Step key={step.title} number={index + 1} title={step.title}>{step.body}</Step>
                ))}
            </ol>
        </section>
    );
}

export default function Index() {
    const { t } = useAppTranslations();
    const g = t.guide;

    return (
        <AppLayout title={t.nav.guide} active="guide">
            <Head title={t.nav.guide} />

            <div className="mx-auto flex max-w-3xl flex-col gap-6 px-4 py-8 sm:px-6 lg:px-8">
                <p className="-mt-2 text-sm text-ink-muted">{g.intro}</p>

                <Section eyebrow={g.section1Eyebrow} title={g.section1Title} steps={g.section1Steps} />
                <Section eyebrow={g.section2Eyebrow} title={g.section2Title} steps={g.section2Steps} />
                <Section eyebrow={g.section3Eyebrow} title={g.section3Title} steps={g.section3Steps} />

                <section className="rounded-lg border border-line bg-paper-sunken p-7">
                    <h2 className="text-sm font-semibold text-ink">{g.notYetTitle}</h2>
                    <ul className="mt-3 list-disc pl-5 text-sm text-ink-soft">
                        {g.notYetItems.map((item) => <li key={item}>{item}</li>)}
                    </ul>
                </section>

                <p className="text-center text-xs text-ink-muted">
                    {g.footerPrefix}{' '}
                    <Link href={route('audit-log.index')} className="underline">
                        {g.footerLink}
                    </Link>{' '}
                    {g.footerSuffix}
                </p>
            </div>
        </AppLayout>
    );
}
