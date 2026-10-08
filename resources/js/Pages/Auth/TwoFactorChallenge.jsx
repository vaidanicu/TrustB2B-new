import GuestLayout from '@/Layouts/GuestLayout';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import { Head } from '@inertiajs/react';
import { useState } from 'react';

export default function TwoFactorChallenge() {
    const [code, setCode] = useState('');

    return (
        <GuestLayout>
            <Head title="Verificare în doi pași" />

            <div className="mb-2 flex items-center gap-2.5">
                <span className="flex h-8 w-8 flex-none items-center justify-center rounded-lg bg-indigo-50">
                    <svg width="18" height="18" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M10 2l6 3v5c0 4.2-2.6 7.4-6 8-3.4-.6-6-3.8-6-8V5l6-3z" stroke="#4f46e5" strokeWidth="1.5" strokeLinejoin="round" />
                    </svg>
                </span>
                <h1 className="text-lg font-semibold text-gray-900">Verificare în doi pași</h1>
            </div>

            <p className="mb-5 text-sm leading-relaxed text-gray-600">
                Introdu codul din aplicația de autentificare pentru a continua.
            </p>

            <form>
                <InputLabel htmlFor="code" value="Cod de verificare" />
                <TextInput
                    id="code"
                    inputMode="numeric"
                    autoComplete="one-time-code"
                    autoFocus
                    value={code}
                    onChange={(e) => setCode(e.target.value)}
                    className="mt-1 block w-full text-center text-xl tracking-[0.3em]"
                />

                <div className="mt-5 flex items-center justify-between">
                    <button type="button" className="text-sm text-gray-600 underline hover:text-gray-900">
                        Folosește un cod de recuperare
                    </button>

                    <PrimaryButton type="submit" disabled>
                        Confirmă
                    </PrimaryButton>
                </div>
            </form>
        </GuestLayout>
    );
}
