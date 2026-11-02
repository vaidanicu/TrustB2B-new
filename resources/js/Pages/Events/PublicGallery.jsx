import Logo from '@/Components/Logo';
import { Head, useForm } from '@inertiajs/react';
import { useState } from 'react';

const VIDEO_EXTENSIONS = ['mp4', 'mov', 'webm'];

function isVideo(url) {
    const ext = url.split('?')[0].split('.').pop()?.toLowerCase();
    return VIDEO_EXTENSIONS.includes(ext);
}

function RemovalRequestForm({ photo, signedUrl, onDone }) {
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
                placeholder="Numele tău (opțional)"
                className="block w-full rounded-md border-line text-xs shadow-sm focus:border-accent focus:ring-accent"
            />
            <div>
                <input
                    type="email"
                    value={data.requester_email}
                    onChange={(e) => setData('requester_email', e.target.value)}
                    placeholder="Emailul tău — obligatoriu, ca organizatorul să te poată contacta"
                    className="block w-full rounded-md border-line text-xs shadow-sm focus:border-accent focus:ring-accent"
                />
                {errors.requester_email && <p className="mt-1 text-xs text-danger-ink">{errors.requester_email}</p>}
            </div>
            <textarea
                value={data.reason}
                onChange={(e) => setData('reason', e.target.value)}
                placeholder="De ce vrei să fie eliminată această fotografie?"
                rows={2}
                className="block w-full rounded-md border-line text-xs shadow-sm focus:border-accent focus:ring-accent"
            />
            {errors.reason && <p className="text-xs text-danger-ink">{errors.reason}</p>}
            <button
                type="submit"
                disabled={processing}
                className="self-start rounded-md bg-ink px-3 py-1.5 text-xs font-semibold uppercase tracking-wide text-white disabled:opacity-50"
            >
                Trimite cererea
            </button>
        </form>
    );
}

function Lightbox({ photo, onClose }) {
    return (
        <div
            className="fixed inset-0 z-50 flex items-center justify-center bg-ink/90 p-4"
            onClick={onClose}
        >
            <button
                type="button"
                onClick={onClose}
                className="absolute right-4 top-4 flex h-9 w-9 items-center justify-center rounded-full bg-white/10 text-white hover:bg-white/20"
                aria-label="Închide"
            >
                ✕
            </button>
            <div className="flex max-h-full max-w-full flex-col items-center gap-3" onClick={(e) => e.stopPropagation()}>
                {isVideo(photo.url) ? (
                    <video src={photo.url} controls autoPlay className="max-h-[80vh] max-w-full rounded-md" />
                ) : (
                    <img src={photo.url} alt="" className="max-h-[80vh] max-w-full rounded-md object-contain" />
                )}
            </div>
        </div>
    );
}

function PhotoCard({ photo, signedUrl, onOpen }) {
    const [requesting, setRequesting] = useState(false);

    return (
        <div className="overflow-hidden rounded-lg bg-white shadow-sm">
            <button type="button" onClick={() => onOpen(photo)} className="block aspect-square w-full">
                {isVideo(photo.url) ? (
                    <video src={photo.url} className="h-full w-full object-cover" />
                ) : (
                    <img src={photo.url} alt="" className="h-full w-full object-cover" />
                )}
            </button>
            {!photo.removalRequested && !requesting && (
                <div className="p-2.5">
                    <button type="button" onClick={() => setRequesting(true)} className="text-xs text-ink-muted underline hover:text-danger-ink">
                        Nu vreau să apar
                    </button>
                </div>
            )}
            {photo.removalRequested ? (
                <p className="p-2.5 text-xs text-ink-muted">Cerere de eliminare trimisă — în analiză.</p>
            ) : requesting ? (
                <div className="p-2.5">
                    <RemovalRequestForm photo={photo} signedUrl={signedUrl} onDone={() => setRequesting(false)} />
                </div>
            ) : null}
        </div>
    );
}

export default function PublicGallery({ eventName, photos, signedUrl }) {
    const [openPhoto, setOpenPhoto] = useState(null);

    return (
        <div className="min-h-screen bg-paper pb-10">
            <Head title={eventName} />

            <div className="flex items-center justify-center pt-7 pb-5">
                <Logo height={20} />
            </div>

            <div className="mx-auto w-full max-w-4xl px-4">
                <div className="mb-5 rounded-lg bg-white p-5 text-center shadow-sm">
                    <h1 className="font-display text-lg font-semibold text-ink">{eventName}</h1>
                    <p className="mt-1 text-sm text-ink-muted">
                        Apasă pe o fotografie sau un video ca să-l vezi mai mare. Dacă nu vrei să apari într-o fotografie, apasă „Nu vreau să
                        apar" și spune-ne motivul — o analizăm și, dacă e întemeiat, o eliminăm.
                    </p>
                </div>

                {photos.length === 0 ? (
                    <p className="rounded-lg bg-white px-5 py-10 text-center text-sm text-ink-muted shadow-sm">
                        Nicio fotografie publicată încă pentru acest eveniment.
                    </p>
                ) : (
                    <div className="grid grid-cols-2 gap-3 sm:grid-cols-3">
                        {photos.map((photo) => (
                            <PhotoCard key={photo.id} photo={photo} signedUrl={signedUrl} onOpen={setOpenPhoto} />
                        ))}
                    </div>
                )}
            </div>

            {openPhoto && <Lightbox photo={openPhoto} onClose={() => setOpenPhoto(null)} />}
        </div>
    );
}
