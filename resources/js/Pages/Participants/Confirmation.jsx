import BrandMark from '@/Components/BrandMark';
import { Head, Link } from '@inertiajs/react';

export default function Confirmation({ event, participantName, attending, photoConsent }) {
    return (
        <div className="min-h-screen bg-gray-100 pb-10">
            <Head title="Răspuns înregistrat" />

            <div className="flex items-center justify-center gap-2 pt-7 pb-5">
                <BrandMark size={24} className="text-gray-500" />
                <span className="text-[15px] font-semibold text-gray-700">TrustBridge</span>
            </div>

            <div className="mx-auto w-full max-w-md px-4">
                <div className="rounded-lg bg-white p-6 text-center shadow-sm">
                    <span className="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-green-50">
                        <svg width="24" height="24" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M4 10.5l4 4 8-9" stroke="#16a34a" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" />
                        </svg>
                    </span>

                    <h1 className="mt-4 text-lg font-semibold text-gray-900">Mulțumim, {participantName}!</h1>

                    <p className="mt-2 text-sm text-gray-600">
                        Am înregistrat răspunsul tău:{' '}
                        <strong className="text-gray-900">{attending ? 'participi' : 'nu participi'}</strong> la{' '}
                        <strong className="text-gray-900">{event.name}</strong>.
                    </p>

                    {attending && (
                        <p className="mt-2 text-sm text-gray-500">
                            Preferință foto: {photoConsent ? 'ești de acord să apari în fotografiile oficiale.' : 'ai ales să nu apari în fotografiile oficiale.'}
                        </p>
                    )}

                    <p className="mt-4 text-xs text-gray-400">
                        Poți reveni oricând la acest link ca să-ți schimbi răspunsul, până pe {event.linkExpires}.
                    </p>
                </div>

                <Link href={route('participants.invitation')} className="mt-4 block text-center text-sm text-gray-500 underline">
                    Înapoi la invitație
                </Link>
            </div>
        </div>
    );
}
