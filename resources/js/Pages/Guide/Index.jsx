import AppLayout from '@/Layouts/AppLayout';
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

function Section({ eyebrow, title, children }) {
    return (
        <section className="rounded-lg bg-white p-7 shadow-sm">
            <p className="text-xs font-semibold uppercase tracking-wide text-ink-muted">{eyebrow}</p>
            <h2 className="mt-1 text-lg font-semibold text-ink">{title}</h2>
            <ol className="mt-5 flex flex-col gap-5">{children}</ol>
        </section>
    );
}

export default function Index() {
    return (
        <AppLayout title="Ghid de utilizare" active="guide">
            <Head title="Ghid" />

            <div className="mx-auto flex max-w-3xl flex-col gap-6 px-4 py-8 sm:px-6 lg:px-8">
                <p className="-mt-2 text-sm text-ink-muted">
                    Pașii reali pentru fluxul platformei, de la adăugarea unui client până la răspunsul unui
                    participant. Fiecare pas se referă la un buton care chiar există acum în aplicație.
                </p>

                <Section eyebrow="Admin de franciză / platformă" title="Cum adaugi un client nou">
                    <Step number={1} title="Mergi la „Clienți” din meniu">
                        E pagina care listează toate organizațiile client existente.
                    </Step>
                    <Step number={2} title="Apasă „Client nou”">
                        Completezi numele organizației, țara și contactul pentru protecția datelor.
                    </Step>
                    <Step number={3} title="Completezi primul admin al clientului">
                        Numele și emailul persoanei care va administra evenimentele acelui client.
                    </Step>
                    <Step number={4} title="Copiezi parola generată și o trimiți clientului">
                        Parola apare o singură dată, pe pagina „Clienți”, imediat după creare — nu există încă
                        trimitere automată de email, deci trebuie transmisă manual (telefon, email, etc.).
                    </Step>
                </Section>

                <Section eyebrow="Admin-ul unui client" title="Cum organizezi un eveniment, de la zero la răspunsuri">
                    <Step number={1} title="Autentifică-te cu contul primit de la franciză">
                        Vei ajunge direct pe Dashboard — lista evenimentelor organizației tale.
                    </Step>
                    <Step number={2} title="Apasă „Eveniment nou”">
                        Completezi numele, perioada, termenul de păstrare a fotografiilor (60 sau 90 de zile) și
                        responsabilii evenimentului.
                    </Step>
                    <Step number={3} title="Deschide evenimentul și apasă „Participanți”">
                        De aici vezi lista participanților și poți adăuga unii noi.
                    </Step>
                    <Step number={4} title="Invită un participant (nume + email)">
                        Se generează automat un link personal, unic pentru acea persoană — apare imediat pe
                        ecran, cu buton de copiere.
                    </Step>
                    <Step number={5} title="Trimite linkul participantului">
                        Manual, pe orice canal (email, WhatsApp etc.) — linkul e personal și expiră automat
                        înainte de eveniment.
                    </Step>
                    <Step number={6} title="Participantul răspunde pe linkul lui">
                        Alege dacă participă și dacă e de acord să apară în fotografii. Răspunsul se salvează
                        direct, fără să mai faci nimic.
                    </Step>
                    <Step number={7} title="Urmărești răspunsurile în timp real">
                        Pe pagina „Participanți” a evenimentului vezi imediat cine a răspuns și ce a ales.
                    </Step>
                </Section>

                <Section eyebrow="Oricine" title="Jurnalul de audit">
                    <Step number={1} title="Mergi la „Jurnal audit” din meniu">
                        Vezi cine a făcut ce și când — creare evenimente, participanți invitați, roluri
                        atribuite. Un admin de platformă vede tot; un admin de client vede doar ce s-a
                        întâmplat în organizația lui.
                    </Step>
                </Section>

                <section className="rounded-lg border border-line bg-paper-sunken p-7">
                    <h2 className="text-sm font-semibold text-ink">Ce nu există încă în platformă</h2>
                    <ul className="mt-3 list-disc pl-5 text-sm text-ink-soft">
                        <li>Galerie foto privată — doar concept, pe pagina de prezentare</li>
                        <li>Cereri de ștergere a datelor (dreptul GDPR la ștergere)</li>
                        <li>Trimitere automată de email pentru invitații sau parole</li>
                        <li>Activare doi factori (2FA) din propriul profil</li>
                    </ul>
                </section>

                <p className="text-center text-xs text-ink-muted">
                    Ai nevoie de ceva ce nu există încă?{' '}
                    <Link href={route('audit-log.index')} className="underline">
                        Verifică jurnalul de audit
                    </Link>{' '}
                    sau spune-i direct dezvoltatorului.
                </p>
            </div>
        </AppLayout>
    );
}
