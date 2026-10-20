import GuestLayout from '@/Layouts/GuestLayout';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import { Head, useForm } from '@inertiajs/react';
import { useState } from 'react';

export default function TwoFactorChallenge() {
    const [usingRecoveryCode, setUsingRecoveryCode] = useState(false);
    const { data, setData, post, processing, errors, reset } = useForm({
        code: '',
        recovery_code: '',
    });

    function toggleRecoveryCode() {
        setUsingRecoveryCode((using) => !using);
        reset('code', 'recovery_code');
    }

    function submit(e) {
        e.preventDefault();
        post(route('two-factor.login.store'));
    }

    return (
        <GuestLayout>
            <Head title="Verificare în doi pași" />

            <div className="mb-2 flex items-center gap-2.5">
                <span className="flex h-8 w-8 flex-none items-center justify-center rounded-lg bg-accent-soft">
                    <svg width="18" height="18" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M10 2l6 3v5c0 4.2-2.6 7.4-6 8-3.4-.6-6-3.8-6-8V5l6-3z" stroke="#a97a35" strokeWidth="1.5" strokeLinejoin="round" />
                    </svg>
                </span>
                <h1 className="font-display text-lg font-semibold text-ink">Verificare în doi pași</h1>
            </div>

            <p className="mb-5 text-sm leading-relaxed text-ink-soft">
                {usingRecoveryCode
                    ? 'Introdu unul dintre codurile de recuperare primite la activarea 2FA.'
                    : 'Introdu codul din aplicația de autentificare pentru a continua.'}
            </p>

            <form onSubmit={submit}>
                {usingRecoveryCode ? (
                    <>
                        <InputLabel htmlFor="recovery_code" value="Cod de recuperare" />
                        <TextInput
                            id="recovery_code"
                            autoComplete="one-time-code"
                            autoFocus
                            value={data.recovery_code}
                            onChange={(e) => setData('recovery_code', e.target.value)}
                            className="mt-1 block w-full text-center text-lg tracking-widest"
                        />
                        <InputError message={errors.recovery_code} className="mt-2" />
                    </>
                ) : (
                    <>
                        <InputLabel htmlFor="code" value="Cod de verificare" />
                        <TextInput
                            id="code"
                            inputMode="numeric"
                            autoComplete="one-time-code"
                            autoFocus
                            value={data.code}
                            onChange={(e) => setData('code', e.target.value)}
                            className="mt-1 block w-full text-center text-xl tracking-[0.3em]"
                        />
                        <InputError message={errors.code} className="mt-2" />
                    </>
                )}

                <div className="mt-5 flex items-center justify-between">
                    <button type="button" onClick={toggleRecoveryCode} className="text-sm text-ink-soft underline hover:text-ink">
                        {usingRecoveryCode ? 'Folosește codul din aplicație' : 'Folosește un cod de recuperare'}
                    </button>

                    <PrimaryButton type="submit" disabled={processing}>
                        Confirmă
                    </PrimaryButton>
                </div>
            </form>
        </GuestLayout>
    );
}
