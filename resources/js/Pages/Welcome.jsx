import Logo from '@/Components/Logo';
import Icon from '@/Components/MarketingIcon';
import PlatformPreview from '@/Components/PlatformPreview';
import { Head, Link } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';
import { LOCALE_LABELS, WELCOME_TRANSLATIONS } from './welcomeTranslations';
import '../../css/marketing.css';

const LOCALE_PATHS = { ro: '/', en: '/en', de: '/de' };
const SECTION_PATHS = ['#platforma', '#cum-functioneaza', '#organizatori'];

function LanguageSwitcher({ current }) {
    return <div className="tb-languages">{Object.entries(LOCALE_LABELS).map(([locale, label]) => <a key={locale} href={LOCALE_PATHS[locale]} lang={locale} hrefLang={locale} aria-current={locale === current ? 'page' : undefined}>{label}</a>)}</div>;
}

function SectionHeading({ eyebrow, title, body }) {
    return <div className="tb-section-heading"><p className="tb-eyebrow">{eyebrow}</p><h2>{title}</h2>{body && <p className="tb-section-description">{body}</p>}</div>;
}

export default function Welcome({ locale = 'ro' }) {
    const language = WELCOME_TRANSLATIONS[locale] ? locale : 'ro';
    const t = WELCOME_TRANSLATIONS[language];
    const [menuOpen, setMenuOpen] = useState(false);
    const menuButton = useRef(null);
    const root = useRef(null);

    useEffect(() => {
        const previous = document.documentElement.lang;
        document.documentElement.lang = language;
        return () => { document.documentElement.lang = previous; };
    }, [language]);

    useEffect(() => {
        if (!menuOpen) return;
        const close = (event) => {
            if (event.key === 'Escape') {
                setMenuOpen(false);
                menuButton.current?.focus();
            }
        };
        document.addEventListener('keydown', close);
        return () => document.removeEventListener('keydown', close);
    }, [menuOpen]);

    useEffect(() => {
        if (!('IntersectionObserver' in window) || window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('tb-revealed');
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.08 });
        root.current?.querySelectorAll('[data-reveal]').forEach(element => observer.observe(element));
        return () => observer.disconnect();
    }, []);

    return (
        <div className="tb-marketing" ref={root}>
            <Head title={t.htmlTitle} />
            <a className="tb-skip" href="#main-content">{t.skip}</a>
            <header className="tb-header">
                <div className="tb-container tb-header-inner">
                    <a href={LOCALE_PATHS[language]} className="tb-logo"><Logo height={32} /></a>
                    <nav className="tb-desktop-nav" aria-label={t.menu}>{t.nav.map((label, index) => <a href={SECTION_PATHS[index]} key={label}>{label}</a>)}</nav>
                    <div className="tb-header-actions"><LanguageSwitcher current={language} /><a href={route('login')} className="tb-button tb-button-login">{t.login}<Icon name="arrow" /></a><button ref={menuButton} className="tb-menu-toggle" type="button" aria-label={t.menu} aria-expanded={menuOpen} aria-controls="marketing-menu" onClick={() => setMenuOpen(!menuOpen)}><Icon name={menuOpen ? 'close' : 'menu'} /></button></div>
                </div>
                <nav id="marketing-menu" className="tb-mobile-nav" aria-label={t.menu} hidden={!menuOpen}>{t.nav.map((label, index) => <a href={SECTION_PATHS[index]} key={label} onClick={() => setMenuOpen(false)}>{label}<Icon name="arrow" /></a>)}</nav>
            </header>
            <main id="main-content" lang={language}>
                <section className="tb-hero">
                    <div className="tb-container tb-hero-grid">
                        <div className="tb-hero-copy"><p className="tb-eyebrow"><span className="tb-eyebrow-line" />{t.eyebrow}</p><h1>{t.headline}</h1><p className="tb-hero-description">{t.subheadline}</p><div className="tb-hero-actions"><a className="tb-button tb-button-primary" href="#platforma">{t.primary}<Icon name="arrow" /></a><a className="tb-button tb-button-secondary" href="#cum-functioneaza">{t.ctaSecondary}</a></div><p className="tb-hero-note"><Icon name="shield" />{t.heroNote}</p><p className="tb-scope">{t.scope}</p></div>
                        <PlatformPreview t={t} />
                    </div>
                </section>
                <section className="tb-benefits tb-section" data-reveal>
                    <div className="tb-container"><SectionHeading eyebrow={t.benefitsEyebrow} title={t.benefitsTitle} /><div className="tb-benefits-grid">{t.benefits.map((item, index) => <article key={item.title}><span className="tb-icon"><Icon name={['shield', 'lock', 'file'][index]} /></span><div><h3>{item.title}</h3><p>{item.body}</p><span className="tb-caption">{item.status}</span></div></article>)}</div></div>
                </section>
                <section id="platforma" className="tb-section tb-platform" data-reveal>
                    <div className="tb-container"><SectionHeading eyebrow={t.platformEyebrow} title={t.platformTitle} body={t.platformSub} /><div className="tb-feature-grid">{t.features.map((feature, index) => <article className="tb-feature" key={feature.title}><div className="tb-feature-top"><span className="tb-icon"><Icon name={['mail', 'image', 'file'][index]} /></span><span className="tb-feature-number">0{index + 1}</span></div><h3>{feature.title}</h3><p>{feature.body}</p><ul>{feature.points.map(point => <li key={point}><Icon name="check" />{point}</li>)}</ul><span className="tb-caption">{feature.status}</span>{index === 1 ? <a className="tb-text-link" href="#cum-functioneaza">{feature.link}<Icon name="arrow" /></a> : index === 0 ? <Link className="tb-text-link" href={route('participants.invitation')}>{feature.link}<Icon name="arrow" /></Link> : <a className="tb-text-link" href={route('audit-log.index')}>{feature.link}<Icon name="arrow" /></a>}</article>)}</div></div>
                </section>
                <section id="cum-functioneaza" className="tb-section tb-workflow" data-reveal>
                    <div className="tb-container"><SectionHeading eyebrow={t.workflowEyebrow} title={t.workflowTitle} /><div className="tb-steps">{t.steps.map((step, index) => <article key={step.title}><span className="tb-step-number">0{index + 1}</span><h3>{step.title}</h3><p>{step.body}</p><span className="tb-caption">{step.status}</span></article>)}</div></div>
                </section>
                <section className="tb-section tb-comparison" data-reveal>
                    <div className="tb-container"><SectionHeading eyebrow={t.comparisonEyebrow} title={t.comparisonTitle} body={t.comparisonSub} /><div className="tb-comparison-table"><table><thead><tr>{t.comparisonHeaders.map(header => <th key={header} scope="col">{header}</th>)}</tr></thead><tbody>{t.comparisonRows.map(row => <tr key={row[0]}><th scope="row">{row[0]}</th><td data-label={t.comparisonHeaders[1]}>{row[1]}</td><td data-label={t.comparisonHeaders[2]}><Icon name="arrow" />{row[2]}</td></tr>)}</tbody></table></div></div>
                </section>
                <section id="organizatori" className="tb-section tb-audiences" data-reveal><div className="tb-container tb-audience-layout"><div><SectionHeading eyebrow={t.audiencesEyebrow} title={t.audiencesTitle} body={t.audiencesSub} /><a className="tb-text-link" href={route('login')}>{t.login}<Icon name="arrow" /></a></div><div className="tb-audience-grid">{t.audiences.map((audience, index) => <div key={audience}><span>0{index + 1}</span><Icon name={['calendar', 'image', 'users', 'shield'][index]} /><h3>{audience}</h3></div>)}</div></div></section>
            </main>
            <footer className="tb-footer"><div className="tb-container"><div className="tb-footer-top"><div><p className="tb-eyebrow">TrustBridge B2B</p><h2>{t.footerCta}</h2><p>{t.footerLabel}</p></div><a className="tb-button tb-button-light" href={route('login')}>{t.login}<Icon name="arrow" /></a></div><div className="tb-footer-bottom"><span>TrustBridge B2B Network</span><p>{t.footerDisclaimer}</p><LanguageSwitcher current={language} /></div></div></footer>
        </div>
    );
}
