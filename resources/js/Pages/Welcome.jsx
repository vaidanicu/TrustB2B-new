import BrandMark from '@/Components/BrandMark';
import { Head, Link } from '@inertiajs/react';

const GAP_ROWS = [
    {
        segment: 'Platforme de evenimente',
        strength: 'Invitație, înregistrare, comunicare, check-in',
        gap: 'Fără drept pe fotografie individuală și fără ciclu de 90 de zile',
    },
    {
        segment: 'Portaluri foto de eveniment',
        strength: 'Acces QR, galerie privată, download',
        gap: 'Fără dovadă de consimțământ a participantului dinainte de eveniment',
    },
    {
        segment: 'Unelte de conformitate GDPR',
        strength: 'Registre, politici, dovezi documentate',
        gap: 'Fără flux real de eveniment și fără galerie pentru participanți',
    },
];

const PACKAGES = [
    {
        name: 'Privacy Setup & RSVP',
        description: 'Șabloane versionate, invitație personalizată, răspuns da/nu, preferințe separate de participare.',
    },
    {
        name: 'Secure Event Gallery',
        description: 'Galerie foto protejată, acces prin QR, URL-uri semnate, cerere de ștergere la fotografia concretă.',
    },
    {
        name: 'Complete Event Privacy File',
        description: 'Tot lanțul legat: invitație, galerie, cazuri GDPR, ciclul de 60/90 de zile, audit complet.',
    },
];

const AUDIENCES = [
    'Agenții de evenimente',
    'Hoteluri și locații',
    'Firme cu evenimente de clienți',
    'Asociații și organizații',
];

const PRINCIPLES = [
    {
        title: 'Un singur dosar de eveniment',
        body: 'Invitație, răspunsuri, versiune de document, acces foto, cerere de drept, termen — toate legate, nu împrăștiate în unelte diferite.',
    },
    {
        title: 'Ștergere la fotografia concretă',
        body: 'Cererea de ștergere pornește de la o fotografie anume, nu de la un email general către suport.',
    },
    {
        title: 'Decizie obligatorie la 90 de zile',
        body: 'Fiecare eveniment are un termen clar: ștergere ireversibilă sau predare controlată către client — niciodată nedefinit.',
    },
    {
        title: 'Clienți complet separați',
        body: 'Fiecare organizație își vede doar propriile evenimente și participanți — testat automat, nu doar promis.',
    },
];

export default function Welcome() {
    return (
        <div className="min-h-screen bg-paper">
            <Head title="TrustBridge" />

            <header className="border-b border-line">
                <div className="mx-auto flex max-w-6xl items-center justify-between px-6 py-5">
                    <div className="flex items-center gap-2.5">
                        <BrandMark size={26} className="text-ink" />
                        <span className="font-display text-lg font-semibold text-ink">TrustBridge</span>
                    </div>
                    <Link
                        href={route('login')}
                        className="rounded-md border border-transparent bg-ink px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white transition hover:bg-ink-soft"
                    >
                        Autentificare
                    </Link>
                </div>
            </header>

            <section className="mx-auto max-w-4xl px-6 pb-16 pt-20 text-center">
                <p className="font-mono text-xs font-semibold uppercase tracking-[0.15em] text-accent">
                    Event Privacy &amp; Media Workflow
                </p>
                <h1 className="mt-5 text-balance font-display text-4xl font-semibold leading-tight text-ink sm:text-5xl">
                    Invitația, galeria foto și dreptul la ștergere — într-un singur flux, cu dovadă la fiecare pas.
                </h1>
                <p className="mx-auto mt-5 max-w-2xl text-balance text-base leading-relaxed text-ink-soft">
                    TrustBridge leagă ce platformele de evenimente și portalurile foto fac separat: cine a fost invitat, ce a
                    acceptat, ce fotografii apar și cum dispar, la timp, fără o cerere de ștergere pierdută pe email.
                </p>
                <div className="mt-8 flex items-center justify-center gap-4">
                    <Link
                        href={route('login')}
                        className="rounded-md border border-transparent bg-ink px-6 py-3 text-xs font-semibold uppercase tracking-widest text-white transition hover:bg-ink-soft"
                    >
                        Autentificare
                    </Link>
                    <a href="#cum-functioneaza" className="text-sm font-medium text-accent underline underline-offset-4">
                        Vezi cum funcționează
                    </a>
                </div>
            </section>

            <section className="border-t border-line bg-paper-raised py-16">
                <div className="mx-auto max-w-5xl px-6">
                    <h2 className="font-display text-2xl font-semibold text-ink">Lipsa pe care o acoperim</h2>
                    <p className="mt-2 max-w-2xl text-sm text-ink-soft">
                        Niciun segment de piață verificat public nu acoperă tot lanțul, de la invitație la ștergerea fotografiei.
                    </p>

                    <div className="mt-8 overflow-hidden rounded-lg border border-line bg-white">
                        <div className="grid grid-cols-[1.1fr_1.3fr_1.3fr] gap-4 border-b border-line bg-paper-sunken px-5 py-3 text-xs font-semibold uppercase tracking-wide text-ink-muted">
                            <span>Segment</span>
                            <span>Punct forte</span>
                            <span>Lipsă față de TrustBridge</span>
                        </div>
                        {GAP_ROWS.map((row) => (
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
                    <h2 className="font-display text-2xl font-semibold text-ink">Cum funcționează, pe scurt</h2>
                    <div className="mt-8 grid grid-cols-1 gap-4 sm:grid-cols-2">
                        {PRINCIPLES.map((item, index) => (
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
                    <h2 className="font-display text-2xl font-semibold text-ink">Trei pachete, în ordinea de construit</h2>
                    <div className="mt-8 grid grid-cols-1 gap-4 sm:grid-cols-3">
                        {PACKAGES.map((pkg, index) => (
                            <div key={pkg.name} className="flex flex-col gap-2 rounded-lg border border-line bg-white p-6">
                                <span className="font-mono text-xs font-semibold uppercase tracking-wide text-accent">
                                    Pachet {String.fromCharCode(65 + index)}
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
                    <h2 className="font-display text-2xl font-semibold text-ink">Pentru cine</h2>
                    <div className="mt-6 flex flex-wrap gap-3">
                        {AUDIENCES.map((audience) => (
                            <span
                                key={audience}
                                className="rounded-full border border-line bg-white px-4 py-2 text-sm text-ink-soft"
                            >
                                {audience}
                            </span>
                        ))}
                    </div>
                </div>
            </section>

            <footer className="border-t border-line bg-ink py-12 text-center">
                <p className="font-display text-xl font-semibold text-white">Vrei să vezi platforma pe un eveniment real?</p>
                <div className="mt-5">
                    <Link
                        href={route('login')}
                        className="rounded-md bg-white px-6 py-3 text-xs font-semibold uppercase tracking-widest text-ink transition hover:bg-paper"
                    >
                        Autentificare
                    </Link>
                </div>
                <p className="mx-auto mt-8 max-w-xl px-6 text-xs leading-relaxed text-white/50">
                    TrustBridge sprijină procese orientate spre protecția datelor și documentate — nu oferă o garanție
                    juridică automată. Textele, bazele legale și termenele trebuie aprobate pentru fiecare țară și tip de
                    eveniment înainte de operare productivă.
                </p>
            </footer>
        </div>
    );
}
