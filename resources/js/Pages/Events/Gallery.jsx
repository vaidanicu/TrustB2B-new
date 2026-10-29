import AppLayout from '@/Layouts/AppLayout';
import { useAppTranslations } from '@/i18n/appTranslations';
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { useState } from 'react';

const VIDEO_EXTENSIONS = ['mp4', 'mov', 'webm'];

function isVideo(fileName) {
    const ext = (fileName ?? '').split('.').pop()?.toLowerCase();
    return VIDEO_EXTENSIONS.includes(ext);
}

function UploadForm({ eventId, t }) {
    const { data, setData, post, processing, errors, reset } = useForm({ photos: [] });

    function submit(e) {
        e.preventDefault();
        post(route('gallery.store', eventId), {
            forceFormData: true,
            onSuccess: () => reset(),
        });
    }

    return (
        <form onSubmit={submit} className="mb-6 flex flex-wrap items-end gap-3 rounded-lg bg-white p-4 shadow-sm">
            <div className="min-w-[260px] flex-1">
                <label className="block text-xs font-medium text-ink-soft">{t.gallery.uploadLabel}</label>
                <input
                    type="file"
                    accept="image/jpeg,image/png,image/webp,video/mp4,video/quicktime,video/webm"
                    multiple
                    onChange={(e) => setData('photos', Array.from(e.target.files))}
                    className="mt-1 block w-full text-sm text-ink-soft file:mr-3 file:rounded-md file:border-0 file:bg-ink file:px-3 file:py-1.5 file:text-xs file:font-semibold file:uppercase file:text-white"
                />
                {errors.photos && <p className="mt-1 text-xs text-danger-ink">{errors.photos}</p>}
            </div>
            <button
                type="submit"
                disabled={processing || data.photos.length === 0}
                className="inline-flex h-[38px] items-center rounded-md border border-transparent bg-ink px-4 text-xs font-semibold uppercase tracking-widest text-white transition hover:bg-ink-soft disabled:opacity-50"
            >
                {processing ? t.gallery.uploading : t.gallery.uploadButton}
            </button>
        </form>
    );
}

function PublicGalleryCard({ url, qrSvg, t }) {
    const [copied, setCopied] = useState(false);

    async function copy() {
        try {
            await navigator.clipboard.writeText(url);
            setCopied(true);
        } catch {
            setCopied(false);
        }
    }

    return (
        <div className="mb-6 flex flex-wrap items-center gap-5 rounded-lg bg-white p-4 shadow-sm">
            <div className="h-24 w-24 shrink-0" dangerouslySetInnerHTML={{ __html: qrSvg }} />
            <div className="min-w-[220px] flex-1">
                <p className="text-sm font-medium text-ink">{t.gallery.publicGalleryTitle}</p>
                <p className="mt-0.5 text-xs text-ink-muted">{t.gallery.publicGalleryHint}</p>
                <div className="mt-2 flex items-center gap-2">
                    <span className="break-all text-xs text-ink-soft">{url}</span>
                    <button type="button" onClick={copy} className="shrink-0 rounded-md border border-line px-2.5 py-1 text-xs font-semibold uppercase tracking-wide text-ink-soft">
                        {copied ? t.common.copied : t.common.copy}
                    </button>
                </div>
            </div>
        </div>
    );
}

function PhotoCard({ photo, eventId, canUpload, canPublish, canModerateRemovals, t }) {
    function publish() {
        router.patch(route('gallery.publish', [eventId, photo.id]), {}, { preserveScroll: true });
    }

    function destroy() {
        if (confirm(t.gallery.confirmDelete)) {
            router.delete(route('gallery.destroy', [eventId, photo.id]), { preserveScroll: true });
        }
    }

    function approveRemoval() {
        if (confirm(t.gallery.confirmApproveRemoval)) {
            router.patch(route('gallery.approve-removal', [eventId, photo.id]), {}, { preserveScroll: true });
        }
    }

    function denyRemoval() {
        router.patch(route('gallery.deny-removal', [eventId, photo.id]), {}, { preserveScroll: true });
    }

    return (
        <div className="overflow-hidden rounded-lg bg-white shadow-sm">
            <div className="relative aspect-square">
                {isVideo(photo.fileName) ? (
                    <video src={photo.url} controls className="h-full w-full object-cover" />
                ) : (
                    <img src={photo.url} alt="" className="h-full w-full object-cover" />
                )}
                <span
                    className={
                        'absolute left-2 top-2 rounded-full px-2 py-0.5 text-xs font-semibold ' +
                        (photo.published ? 'bg-teal-soft text-teal-ink' : 'bg-amber-50 text-amber-700')
                    }
                >
                    {photo.published ? t.gallery.published : t.gallery.draft}
                </span>
            </div>

            {photo.removalRequested && (
                <div className="border-t border-danger-soft bg-danger-soft/40 px-2.5 py-2">
                    <p className="text-xs font-semibold text-danger-ink">{t.gallery.removalRequestedBadge}</p>
                    {photo.removalReason && <p className="mt-0.5 text-xs text-ink-soft">{photo.removalReason}</p>}
                    {canModerateRemovals && (
                        <div className="mt-1.5 flex items-center gap-3">
                            <button type="button" onClick={approveRemoval} className="text-xs font-semibold text-danger-ink underline">
                                {t.gallery.approveRemoval}
                            </button>
                            <button type="button" onClick={denyRemoval} className="text-xs text-ink-muted underline hover:text-ink-soft">
                                {t.gallery.denyRemoval}
                            </button>
                        </div>
                    )}
                </div>
            )}

            <div className="flex items-center justify-between gap-2 p-2.5">
                {canPublish && !photo.published ? (
                    <button type="button" onClick={publish} className="text-xs font-semibold uppercase tracking-wide text-accent">
                        {t.gallery.publish}
                    </button>
                ) : <span />}
                <div className="flex items-center gap-2">
                    <a href={photo.url} download={photo.fileName} className="text-xs text-ink-muted underline hover:text-ink">
                        {t.gallery.download}
                    </a>
                    {canUpload && (
                        <button type="button" onClick={destroy} className="text-xs text-ink-muted underline hover:text-danger-ink">
                            {t.gallery.delete}
                        </button>
                    )}
                </div>
            </div>
        </div>
    );
}

export default function Gallery({ event, backUrl, photos, canUpload, canPublish, canModerateRemovals, publicGalleryUrl, publicGalleryQrSvg }) {
    const { flash } = usePage().props;
    const { t } = useAppTranslations();

    return (
        <AppLayout title={`${t.gallery.title} — ${event.name}`} active="dashboard">
            <Head title={`${t.gallery.title} — ${event.name}`} />

            <div className="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                <div className="-mt-2 mb-6 flex items-center justify-between">
                    <Link href={backUrl} className="text-sm text-ink-muted hover:text-ink">
                        {t.gallery.back}
                    </Link>
                </div>

                {flash?.status && (
                    <div className="mb-5 rounded-md border border-teal bg-teal-soft px-4 py-3 text-sm text-teal-ink">
                        {flash.status}
                    </div>
                )}

                <PublicGalleryCard url={publicGalleryUrl} qrSvg={publicGalleryQrSvg} t={t} />

                {canUpload && <UploadForm eventId={event.id} t={t} />}

                {photos.length === 0 ? (
                    <p className="rounded-lg bg-white px-5 py-10 text-center text-sm text-ink-muted shadow-sm">
                        {t.gallery.empty}
                    </p>
                ) : (
                    <div className="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
                        {photos.map((photo) => (
                            <PhotoCard
                                key={photo.id}
                                photo={photo}
                                eventId={event.id}
                                canUpload={canUpload}
                                canPublish={canPublish}
                                canModerateRemovals={canModerateRemovals}
                                t={t}
                            />
                        ))}
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
