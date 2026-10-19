import Logo from '@/Components/Logo';
import { Head } from '@inertiajs/react';

export default function DeletionRequested({ event, participantName }) {
    return (
        <div className="min-h-screen bg-paper pb-10">
            <Head title="Cerere de ștergere trimisă" />

            <div className="flex items-center justify-center pt-7 pb-5">
                <Logo height={20} />
            </div>

            <div className="mx-auto w-full max-w-md px-4">
                <div className="rounded-lg bg-white p-6 text-center shadow-sm">
                    <span className="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-amber-50">
                        <svg width="24" height="24" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M10 6v4.5M10 14h.01" stroke="#b45309" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" />
                        </svg>
                    </span>

                    <h1 className="mt-4 font-display text-lg font-semibold text-ink">Cerere înregistrată, {participantName}</h1>

                    <p className="mt-2 text-sm text-ink-soft">
                        Am primit cererea ta de ștergere a datelor pentru <strong className="text-ink">{event.name}</strong>.
                        Organizatorul o va verifica și îți va șterge definitiv datele.
                    </p>

                    <p className="mt-4 text-xs text-ink-muted">
                        Acest link nu mai poate fi folosit pentru a răspunde la invitație între timp.
                    </p>
                </div>
            </div>
        </div>
    );
}
