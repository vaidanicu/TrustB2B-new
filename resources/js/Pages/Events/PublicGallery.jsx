import Logo from '@/Components/Logo';
import { Head, useForm } from '@inertiajs/react';
import { useState } from 'react';

const VIDEO_EXTENSIONS = ['mp4', 'mov', 'webm'];

function isVideo(url) {
    const ext = url.split('?')[0].split('.').pop()?.toLowerCase();
    return VIDEO_EXTENSIONS.includes(ext);
}

function RemovalRequestForm({ photo, signedUrl, onDone }) {
    const { data, setData, post, processing, errors } = useForm({ media_id: photo.id, reason: '' });

    function submit(e) {
        e.preventDefault();
        post(signedUrl, { preserveScroll: true, onSuccess: onDone });
    }

    return (
        <form onSubmit={submit} className="mt-2 flex flex-col gap-2">
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

function PhotoCard({ photo, signedUrl }) {
    const [requesting, setRequesting] = useState(false);

    return (
        <div className="overflow-hidden rounded-lg bg-white shadow-sm">
            <div className="aspect-square">
                {isVideo(photo.url) ? (
                    <video src={photo.url} controls className="h-full w-full object-cover" />
                ) : (
                    <img src={photo.url} alt="" className="h-full w-full object-cover" />
                )}
            </div>
            <div className="p-2.5">
                {photo.removalRequested ? (
                    <p className="text-xs text-ink-muted">Cerere de eliminare trimisă — în analiză.</p>
                ) : requesting ? (
                    <RemovalRequestForm photo={photo} signedUrl={signedUrl} onDone={() => setRequesting(false)} />
                ) : (
                    <button type="button" onClick={() => setRequesting(true)} className="text-xs text-ink-muted underline hover:text-danger-ink">
                        Nu vreau să apar în această fotografie
                    </button>
                )}
            </div>
        </div>
    );
}

export default function PublicGallery({ eventName, photos, signedUrl }) {
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
                        Dacă nu vrei să apari într-o fotografie, apasă „Nu vreau să apar" și spune-ne motivul — o analizăm și, dacă e
                        întemeiat, o eliminăm.
                    </p>
                </div>

                {photos.length === 0 ? (
                    <p className="rounded-lg bg-white px-5 py-10 text-center text-sm text-ink-muted shadow-sm">
                        Nicio fotografie publicată încă pentru acest eveniment.
                    </p>
                ) : (
                    <div className="grid grid-cols-2 gap-3 sm:grid-cols-3">
                        {photos.map((photo) => (
                            <PhotoCard key={photo.id} photo={photo} signedUrl={signedUrl} />
                        ))}
                    </div>
                )}
            </div>
        </div>
    );
}
