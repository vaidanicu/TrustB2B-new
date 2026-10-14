// Real translations, not machine output left unreviewed — RO is the
// source (matches the roadmap's own positioning, §12), EN and DE are
// written to the same register, not a literal word-for-word pass.
export const WELCOME_TRANSLATIONS = {
    ro: {
        htmlTitle: 'TrustBridge',
        login: 'Autentificare',
        eyebrow: 'Event Privacy & Media Workflow',
        headline: 'Invitația, galeria foto și dreptul la ștergere — într-un singur flux, cu dovadă la fiecare pas.',
        subheadline:
            'TrustBridge leagă ce platformele de evenimente și portalurile foto fac separat: cine a fost invitat, ce a acceptat, ce fotografii apar și cum dispar, la timp, fără o cerere de ștergere pierdută pe email.',
        ctaSecondary: 'Vezi cum funcționează',
        gapTitle: 'Lipsa pe care o acoperim',
        gapSubtitle: 'Niciun segment de piață verificat public nu acoperă tot lanțul, de la invitație la ștergerea fotografiei.',
        gapHeaders: ['Segment', 'Punct forte', 'Lipsă față de TrustBridge'],
        gapRows: [
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
        ],
        principlesTitle: 'Cum funcționează, pe scurt',
        principles: [
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
        ],
        packagesTitle: 'Trei pachete, în ordinea de construit',
        packageBadge: 'Pachet',
        packages: [
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
        ],
        audiencesTitle: 'Pentru cine',
        audiences: ['Agenții de evenimente', 'Hoteluri și locații', 'Firme cu evenimente de clienți', 'Asociații și organizații'],
        footerCta: 'Vrei să vezi platforma pe un eveniment real?',
        footerDisclaimer:
            'TrustBridge sprijină procese orientate spre protecția datelor și documentate — nu oferă o garanție juridică automată. Textele, bazele legale și termenele trebuie aprobate pentru fiecare țară și tip de eveniment înainte de operare productivă.',
    },
    en: {
        htmlTitle: 'TrustBridge',
        login: 'Sign in',
        eyebrow: 'Event Privacy & Media Workflow',
        headline: 'The invitation, the photo gallery and the right to erasure — one workflow, with proof at every step.',
        subheadline:
            "TrustBridge connects what event platforms and photo portals handle separately: who was invited, what they consented to, which photos go live, and how they disappear on time — without a deletion request getting lost in an inbox.",
        ctaSecondary: 'See how it works',
        gapTitle: 'The gap we close',
        gapSubtitle: 'No publicly verified market segment covers the full chain, from invitation to photo deletion.',
        gapHeaders: ['Segment', 'Strength', 'Gap vs. TrustBridge'],
        gapRows: [
            {
                segment: 'Event platforms',
                strength: 'Invitation, registration, communication, check-in',
                gap: 'No individual photo rights, no 90-day cycle',
            },
            {
                segment: 'Event photo portals',
                strength: 'QR access, private gallery, download',
                gap: 'No proof of participant consent before the event',
            },
            {
                segment: 'GDPR compliance tools',
                strength: 'Registers, policies, documented evidence',
                gap: 'No real event workflow, no participant gallery',
            },
        ],
        principlesTitle: 'How it works, in short',
        principles: [
            {
                title: 'One event file',
                body: 'Invitation, responses, document version, photo access, rights request, deadline — all linked, not scattered across separate tools.',
            },
            {
                title: 'Deletion tied to one photo',
                body: 'A deletion request starts from a specific photo, not a generic email to support.',
            },
            {
                title: 'A mandatory decision at 90 days',
                body: 'Every event has a clear deadline: irreversible deletion or controlled handover to the client — never left undefined.',
            },
            {
                title: 'Fully separated clients',
                body: 'Each organization sees only its own events and participants — tested automatically, not just promised.',
            },
        ],
        packagesTitle: 'Three packages, in build order',
        packageBadge: 'Package',
        packages: [
            {
                name: 'Privacy Setup & RSVP',
                description: 'Versioned templates, personalized invitations, yes/no responses, separate participation preferences.',
            },
            {
                name: 'Secure Event Gallery',
                description: 'Protected photo gallery, QR access, signed URLs, deletion requests tied to a specific photo.',
            },
            {
                name: 'Complete Event Privacy File',
                description: 'The full chain, linked: invitation, gallery, GDPR cases, the 60/90-day cycle, complete audit trail.',
            },
        ],
        audiencesTitle: 'Who it’s for',
        audiences: ['Event agencies', 'Hotels and venues', 'Companies with client events', 'Associations and organizations'],
        footerCta: 'Want to see the platform on a real event?',
        footerDisclaimer:
            'TrustBridge supports privacy-oriented, documented processes — it does not provide an automatic legal guarantee. Final texts, legal bases and deadlines must be approved per country and event type before production use.',
    },
    de: {
        htmlTitle: 'TrustBridge',
        login: 'Anmelden',
        eyebrow: 'Event Privacy & Media Workflow',
        headline: 'Einladung, Fotogalerie und Recht auf Löschung — ein durchgängiger Workflow, mit Nachweis bei jedem Schritt.',
        subheadline:
            'TrustBridge verbindet, was Eventplattformen und Fotoportale getrennt abdecken: wer eingeladen wurde, was zugestimmt wurde, welche Fotos sichtbar sind und wie sie rechtzeitig verschwinden — ohne eine Löschanfrage, die im Posteingang verloren geht.',
        ctaSecondary: 'So funktioniert es',
        gapTitle: 'Die Lücke, die wir schließen',
        gapSubtitle: 'Kein öffentlich geprüftes Marktsegment deckt die gesamte Kette ab — von der Einladung bis zur Foto-Löschung.',
        gapHeaders: ['Segment', 'Stärke', 'Lücke gegenüber TrustBridge'],
        gapRows: [
            {
                segment: 'Eventplattformen',
                strength: 'Einladung, Registrierung, Kommunikation, Check-in',
                gap: 'Kein Recht am einzelnen Foto, kein 90-Tage-Zyklus',
            },
            {
                segment: 'Eventfoto-Portale',
                strength: 'QR-Zugang, private Galerie, Download',
                gap: 'Kein Nachweis der Teilnehmereinwilligung vor dem Event',
            },
            {
                segment: 'DSGVO-Compliance-Tools',
                strength: 'Verzeichnisse, Richtlinien, dokumentierte Nachweise',
                gap: 'Kein echter Eventablauf, keine Teilnehmergalerie',
            },
        ],
        principlesTitle: 'So funktioniert es, kurz erklärt',
        principles: [
            {
                title: 'Eine einzige Eventakte',
                body: 'Einladung, Antworten, Dokumentversion, Fotozugang, Rechteantrag, Frist — alles verknüpft, nicht über verschiedene Tools verstreut.',
            },
            {
                title: 'Löschung am konkreten Foto',
                body: 'Ein Löschantrag beginnt bei einem bestimmten Foto, nicht bei einer allgemeinen E-Mail an den Support.',
            },
            {
                title: 'Verpflichtende Entscheidung nach 90 Tagen',
                body: 'Jedes Event hat eine klare Frist: unwiderrufliche Löschung oder kontrollierte Übergabe an den Kunden — nie unbestimmt.',
            },
            {
                title: 'Vollständig getrennte Kunden',
                body: 'Jede Organisation sieht nur ihre eigenen Events und Teilnehmer — automatisch getestet, nicht nur versprochen.',
            },
        ],
        packagesTitle: 'Drei Pakete, in Bau-Reihenfolge',
        packageBadge: 'Paket',
        packages: [
            {
                name: 'Privacy Setup & RSVP',
                description: 'Versionierte Vorlagen, personalisierte Einladung, Ja/Nein-Antwort, getrennte Teilnahmepräferenzen.',
            },
            {
                name: 'Secure Event Gallery',
                description: 'Geschützte Fotogalerie, QR-Zugang, signierte URLs, Löschantrag am konkreten Foto.',
            },
            {
                name: 'Complete Event Privacy File',
                description: 'Die gesamte Kette verknüpft: Einladung, Galerie, DSGVO-Fälle, 60/90-Tage-Zyklus, vollständiges Audit.',
            },
        ],
        audiencesTitle: 'Für wen',
        audiences: ['Eventagenturen', 'Hotels und Locations', 'Unternehmen mit Kundenveranstaltungen', 'Verbände und Organisationen'],
        footerCta: 'Möchten Sie die Plattform an einem echten Event sehen?',
        footerDisclaimer:
            'TrustBridge unterstützt datenschutzorientierte, dokumentierte Abläufe — es bietet keine automatische Rechtsgarantie. Endgültige Texte, Rechtsgrundlagen und Fristen müssen vor dem produktiven Einsatz pro Land und Veranstaltungstyp freigegeben werden.',
    },
};

export const LOCALE_LABELS = { ro: 'RO', en: 'EN', de: 'DE' };
