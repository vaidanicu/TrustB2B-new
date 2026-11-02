import Logo from '@/Components/Logo';
import { Head, useForm } from '@inertiajs/react';
import { useEffect, useState } from 'react';

const VIDEO_EXTENSIONS = ['mp4', 'mov', 'webm'];
const LOCALES = ['ro', 'en', 'de'];
const LOCALE_STORAGE_KEY = 'trustbridge-gallery-locale';

const STRINGS = {
    ro: {
        pageHint: 'Apasă pe o fotografie sau un video ca să-l vezi mai mare — poți naviga cu săgețile. Dacă nu vrei să apari într-o fotografie, apasă „Nu vreau să apar” și spune-ne motivul — o analizăm și, dacă e întemeiat, o eliminăm.',
        emptyState: 'Nicio fotografie publicată încă pentru acest eveniment.',
        closeLabel: 'Închide',
        prevLabel: 'Fotografia anterioară',
        nextLabel: 'Fotografia următoare',
        dontWantToAppear: 'Nu vreau să apar',
        removalPending: 'Cerere de eliminare trimisă — în analiză.',
        namePlaceholder: 'Numele tău (opțional)',
        emailPlaceholder: 'Emailul tău — obligatoriu, ca organizatorul să te poată contacta',
        reasonPlaceholder: 'De ce vrei să fie eliminată această fotografie?',
        submitButton: 'Trimite cererea',
    },
    en: {
        pageHint: "Tap a photo or video to see it larger — use the arrows to move between them. If you don't want to appear in a photo, tap \"I don't want to appear\" and tell us why — we'll review it and remove it if it's a reasonable request.",
        emptyState: 'No photos published yet for this event.',
        closeLabel: 'Close',
        prevLabel: 'Previous photo',
        nextLabel: 'Next photo',
        dontWantToAppear: "I don't want to appear",
        removalPending: 'Removal request sent — under review.',
        namePlaceholder: 'Your name (optional)',
        emailPlaceholder: 'Your email — required, so the organizer can reach you',
        reasonPlaceholder: 'Why do you want this photo removed?',
        submitButton: 'Send request',
    },
    de: {
        pageHint: 'Tippen Sie auf ein Foto oder Video, um es größer zu sehen — mit den Pfeilen wechseln Sie zwischen ihnen. Wenn Sie auf einem Foto nicht erscheinen möchten, tippen Sie auf „Ich möchte nicht erscheinen” und nennen Sie uns den Grund — wir prüfen es und entfernen es, wenn es berechtigt ist.',
        emptyState: 'Für dieses Event wurden noch keine Fotos veröffentlicht.',
        closeLabel: 'Schließen',
        prevLabel: 'Vorheriges Foto',
        nextLabel: 'Nächstes Foto',
        dontWantToAppear: 'Ich möchte nicht erscheinen',
        removalPending: 'Entfernungsantrag gesendet — wird geprüft.',
        namePlaceholder: 'Ihr Name (optional)',
        emailPlaceholder: 'Ihre E-Mail — erforderlich, damit der Veranstalter Sie erreichen kann',
        reasonPlaceholder: 'Warum möchten Sie, dass dieses Foto entfernt wird?',
        submitButton: 'Anfrage senden',
    },
};

/**
 * No account here (public, no-login page — see GalleryController::publicGallery()),
 * so there's no auth.user.locale to read the way useAppTranslations() does
 * for the authenticated app. Detects the visitor's own browser language
 * instead, remembers an explicit override in THIS browser only
 * (localStorage, never read back by the server or by Claude).
 */
function detectInitialLocale() {
    try {
        const saved = localStorage.getItem(LOCALE_STORAGE_KEY);
        if (LOCALES.includes(saved)) {
            return saved;
        }
    } catch {
        // ignore — falls through to browser-language detection
    }

    const browserLanguage = (navigator.language || 'ro').toLowerCase();

    return LOCALES.find((locale) => browserLanguage.startsWith(locale)) ?? 'ro';
}

function useGalleryLocale() {
    const [locale, setLocaleState] = useState(detectInitialLocale);

    function setLocale(next) {
        setLocaleState(next);

        try {
            localStorage.setItem(LOCALE_STORAGE_KEY, next);
        } catch {
            // per-viewer convenience only — fine if it doesn't persist
        }
    }

    return [locale, setLocale];
}

function LocaleSwitcher({ locale, onChange }) {
    return (
        <div className="flex items-center gap-1 text-xs font-semibold">
            {LOCALES.map((option) => (
                <button
                    key={option}
                    type="button"
                    disabled={option === locale}
                    onClick={() => onChange(option)}
                    className={option === locale ? 'text-accent' : 'text-ink-muted hover:text-ink-soft'}
                >
                    {option.toUpperCase()}
                </button>
            ))}
        </div>
    );
}

function isVideo(url) {
    const ext = url.split('?')[0].split('.').pop()?.toLowerCase();
    return VIDEO_EXTENSIONS.includes(ext);
}

function RemovalRequestForm({ photo, signedUrl, onDone, t }) {
    const { data, setData, post, processing, errors } = useForm({ media_id: photo.id, reason: '', requester_name: '', requester_email: '' });

    function submit(e) {
        e.preventDefault();
        post(signedUrl, { preserveScroll: true, onSuccess: onDone });
    }

    return (
        <form onSubmit={submit} className="mt-2 flex flex-col gap-2">
            <input
                type="text"
                value={data.requester_name}
                onChange={(e) => setData('requester_name', e.target.value)}
                placeholder={t.namePlaceholder}
                className="block w-full rounded-md border-line text-xs shadow-sm focus:border-accent focus:ring-accent"
            />
            <div>
                <input
                    type="email"
                    value={data.requester_email}
                    onChange={(e) => setData('requester_email', e.target.value)}
                    placeholder={t.emailPlaceholder}
                    className="block w-full rounded-md border-line text-xs shadow-sm focus:border-accent focus:ring-accent"
                />
                {errors.requester_email && <p className="mt-1 text-xs text-danger-ink">{errors.requester_email}</p>}
            </div>
            <textarea
                value={data.reason}
                onChange={(e) => setData('reason', e.target.value)}
                placeholder={t.reasonPlaceholder}
                rows={2}
                className="block w-full rounded-md border-line text-xs shadow-sm focus:border-accent focus:ring-accent"
            />
            {errors.reason && <p className="text-xs text-danger-ink">{errors.reason}</p>}
            <button
                type="submit"
                disabled={processing}
                className="self-start rounded-md bg-ink px-3 py-1.5 text-xs font-semibold uppercase tracking-wide text-white disabled:opacity-50"
            >
                {t.submitButton}
            </button>
        </form>
    );
}

function NavButton({ side, label, onClick }) {
    return (
        <button
            type="button"
            onClick={(e) => {
                e.stopPropagation();
                onClick();
            }}
            aria-label={label}
            className={
                `absolute top-1/2 -translate-y-1/2 flex h-11 w-11 items-center justify-center rounded-full bg-white/10 text-white hover:bg-white/20 ` +
                (side === 'left' ? 'left-2 sm:left-4' : 'right-2 sm:right-4')
            }
        >
            <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                {side === 'left' ? (
                    <path d="M12.5 15l-5-5 5-5" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" />
                ) : (
                    <path d="M7.5 15l5-5-5-5" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" />
                )}
            </svg>
        </button>
    );
}

/**
 * Was one-photo-at-a-time with no way to move on — reported as cumbersome
 * ("umständlich"). Arrow keys (and on-screen chevrons, for touch) step
 * through the whole published list without closing and reopening.
 */
function Lightbox({ photos, index, onClose, onNavigate, t }) {
    const photo = photos[index];

    useEffect(() => {
        function onKeyDown(e) {
            if (e.key === 'Escape') {
                onClose();
            } else if (e.key === 'ArrowLeft' && index > 0) {
                onNavigate(index - 1);
            } else if (e.key === 'ArrowRight' && index < photos.length - 1) {
                onNavigate(index + 1);
            }
        }

        window.addEventListener('keydown', onKeyDown);

        return () => window.removeEventListener('keydown', onKeyDown);
    }, [index, photos.length, onClose, onNavigate]);

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-ink/90 p-4" onClick={onClose}>
            <button
                type="button"
                onClick={onClose}
                className="absolute right-4 top-4 flex h-9 w-9 items-center justify-center rounded-full bg-white/10 text-white hover:bg-white/20"
                aria-label={t.closeLabel}
            >
                ✕
            </button>

            {index > 0 && <NavButton side="left" label={t.prevLabel} onClick={() => onNavigate(index - 1)} />}
            {index < photos.length - 1 && <NavButton side="right" label={t.nextLabel} onClick={() => onNavigate(index + 1)} />}

            <div className="flex max-h-full max-w-full flex-col items-center gap-3" onClick={(e) => e.stopPropagation()}>
                {isVideo(photo.url) ? (
                    <video key={photo.id} src={photo.url} controls autoPlay className="max-h-[80vh] max-w-full rounded-md" />
                ) : (
                    <img key={photo.id} src={photo.url} alt="" className="max-h-[80vh] max-w-full rounded-md object-contain" />
                )}
            </div>
        </div>
    );
}

function PhotoCard({ photo, signedUrl, onOpen, t }) {
    const [requesting, setRequesting] = useState(false);

    return (
        <div className="overflow-hidden rounded-lg bg-white shadow-sm">
            <button type="button" onClick={onOpen} className="block aspect-square w-full">
                {isVideo(photo.url) ? (
                    <video src={photo.url} className="h-full w-full object-cover" />
                ) : (
                    <img src={photo.url} alt="" className="h-full w-full object-cover" />
                )}
            </button>
            {!photo.removalRequested && !requesting && (
                <div className="p-2.5">
                    <button type="button" onClick={() => setRequesting(true)} className="text-xs text-ink-muted underline hover:text-danger-ink">
                        {t.dontWantToAppear}
                    </button>
                </div>
            )}
            {photo.removalRequested ? (
                <p className="p-2.5 text-xs text-ink-muted">{t.removalPending}</p>
            ) : requesting ? (
                <div className="p-2.5">
                    <RemovalRequestForm photo={photo} signedUrl={signedUrl} onDone={() => setRequesting(false)} t={t} />
                </div>
            ) : null}
        </div>
    );
}

export default function PublicGallery({ eventName, photos, signedUrl }) {
    const [openIndex, setOpenIndex] = useState(null);
    const [locale, setLocale] = useGalleryLocale();
    const t = STRINGS[locale];

    return (
        <div className="min-h-screen bg-paper pb-10">
            <Head title={eventName} />

            <div className="flex items-center justify-between px-4 pt-6">
                <span className="w-12" />
                <Logo height={20} />
                <LocaleSwitcher locale={locale} onChange={setLocale} />
            </div>

            <div className="mx-auto w-full max-w-4xl px-4 pt-3">
                <div className="mb-5 rounded-lg bg-white p-5 text-center shadow-sm">
                    <h1 className="font-display text-lg font-semibold text-ink">{eventName}</h1>
                    <p className="mt-1 text-sm text-ink-muted">{t.pageHint}</p>
                </div>

                {photos.length === 0 ? (
                    <p className="rounded-lg bg-white px-5 py-10 text-center text-sm text-ink-muted shadow-sm">{t.emptyState}</p>
                ) : (
                    <div className="grid grid-cols-2 gap-3 sm:grid-cols-3">
                        {photos.map((photo, index) => (
                            <PhotoCard key={photo.id} photo={photo} signedUrl={signedUrl} onOpen={() => setOpenIndex(index)} t={t} />
                        ))}
                    </div>
                )}
            </div>

            {openIndex !== null && (
                <Lightbox photos={photos} index={openIndex} onClose={() => setOpenIndex(null)} onNavigate={setOpenIndex} t={t} />
            )}
        </div>
    );
}
