import DangerButton from '@/Components/DangerButton';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import Modal from '@/Components/Modal';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import TextInput from '@/Components/TextInput';
import { useRef, useState } from 'react';

/**
 * Self-service management for Fortify's two-factor authentication — enable
 * (shows a QR code + manual setup key), confirm with a code from the
 * authenticator app, view/regenerate recovery codes, and disable. All of
 * this talks directly to Fortify's own already-registered endpoints
 * (two-factor.enable/confirm/disable/qr-code/secret-key/recovery-codes —
 * see routes/web.php and AuthenticatedSessionController for how the real
 * login flow now also checks this state) via axios rather than Inertia's
 * router, since those endpoints return plain JSON, not Inertia pages.
 *
 * Every one of those endpoints sits behind Fortify's 'password.confirm'
 * middleware (RequirePassword), which answers an unconfirmed XHR request
 * with 423 rather than redirecting — ensurePasswordConfirmed() below is
 * what catches that and shows an inline modal instead of the request just
 * silently failing.
 */
export default function TwoFactorAuthenticationForm({ initiallyEnabled, className = '' }) {
    const [enabled, setEnabled] = useState(initiallyEnabled);
    const [settingUp, setSettingUp] = useState(false);
    const [qrCodeSvg, setQrCodeSvg] = useState(null);
    const [setupKey, setSetupKey] = useState(null);
    const [recoveryCodes, setRecoveryCodes] = useState(null);
    const [code, setCode] = useState('');
    const [codeError, setCodeError] = useState('');
    const [processing, setProcessing] = useState(false);

    const [confirmingPassword, setConfirmingPassword] = useState(false);
    const [password, setPassword] = useState('');
    const [passwordError, setPasswordError] = useState('');
    const [confirmingAction, setConfirmingAction] = useState(false);
    const pendingAction = useRef(null);
    const passwordInput = useRef(null);

    function ensurePasswordConfirmed(action) {
        pendingAction.current = action;
        action().catch((error) => {
            if (error.response?.status === 423) {
                setConfirmingPassword(true);
            } else {
                throw error;
            }
        });
    }

    function submitPasswordConfirmation(e) {
        e.preventDefault();
        setConfirmingAction(true);
        setPasswordError('');

        window.axios
            .post(route('password.confirm'), { password })
            .then(() => {
                setConfirmingPassword(false);
                setPassword('');
                setConfirmingAction(false);

                return pendingAction.current?.();
            })
            .catch(() => {
                setConfirmingAction(false);
                setPasswordError('Parolă incorectă.');
                passwordInput.current?.focus();
            });
    }

    function closePasswordModal() {
        setConfirmingPassword(false);
        setPassword('');
        setPasswordError('');
    }

    function startEnabling() {
        setProcessing(true);

        ensurePasswordConfirmed(() =>
            window.axios
                .post(route('two-factor.enable'))
                .then(() =>
                    Promise.all([window.axios.get(route('two-factor.qr-code')), window.axios.get(route('two-factor.secret-key'))]),
                )
                .then(([qr, secret]) => {
                    setQrCodeSvg(qr.data.svg);
                    setSetupKey(secret.data.secretKey);
                    setSettingUp(true);
                    setProcessing(false);
                })
                .catch((error) => {
                    setProcessing(false);
                    throw error;
                }),
        );
    }

    function cancelSetup() {
        setSettingUp(false);
        setQrCodeSvg(null);
        setSetupKey(null);
        setCode('');
        setCodeError('');
        window.axios.delete(route('two-factor.disable')).catch(() => {});
    }

    function confirmCode(e) {
        e.preventDefault();
        setProcessing(true);
        setCodeError('');

        window.axios
            .post(route('two-factor.confirm'), { code })
            .then(() => window.axios.get(route('two-factor.recovery-codes')))
            .then(({ data }) => {
                setRecoveryCodes(data);
                setEnabled(true);
                setSettingUp(false);
                setQrCodeSvg(null);
                setSetupKey(null);
                setCode('');
                setProcessing(false);
            })
            .catch(() => {
                setProcessing(false);
                setCodeError('Codul introdus nu este valid.');
            });
    }

    function showRecoveryCodes() {
        window.axios.get(route('two-factor.recovery-codes')).then(({ data }) => setRecoveryCodes(data));
    }

    function regenerateRecoveryCodes() {
        setProcessing(true);
        window.axios
            .post(route('two-factor.regenerate-recovery-codes'))
            .then(() => window.axios.get(route('two-factor.recovery-codes')))
            .then(({ data }) => {
                setRecoveryCodes(data);
                setProcessing(false);
            })
            .catch(() => setProcessing(false));
    }

    function disable() {
        setProcessing(true);

        ensurePasswordConfirmed(() =>
            window.axios
                .delete(route('two-factor.disable'))
                .then(() => {
                    setEnabled(false);
                    setRecoveryCodes(null);
                    setProcessing(false);
                })
                .catch((error) => {
                    setProcessing(false);
                    throw error;
                }),
        );
    }

    return (
        <section className={`space-y-6 ${className}`}>
            <header>
                <h2 className="text-lg font-medium text-ink">Verificare în doi pași</h2>
                <p className="mt-1 text-sm text-ink-muted">
                    Adaugă un cod suplimentar din aplicația de autentificare (Google Authenticator, Authy etc.) la fiecare conectare.
                </p>
            </header>

            {!enabled && !settingUp && (
                <div>
                    <p className="mb-3 text-sm text-ink-soft">2FA nu este activă pentru contul tău.</p>
                    <PrimaryButton onClick={startEnabling} disabled={processing}>
                        Activează 2FA
                    </PrimaryButton>
                </div>
            )}

            {settingUp && (
                <div className="max-w-md space-y-4 rounded-lg border border-line bg-paper-raised p-4">
                    <p className="text-sm text-ink-soft">
                        Scanează acest cod cu aplicația de autentificare, apoi introdu codul generat ca să confirmi activarea.
                    </p>

                    {qrCodeSvg && <div className="w-fit rounded-md bg-white p-3" dangerouslySetInnerHTML={{ __html: qrCodeSvg }} />}

                    {setupKey && (
                        <p className="text-xs text-ink-muted">
                            Cheie manuală (dacă nu poți scana codul): <span className="font-mono">{setupKey}</span>
                        </p>
                    )}

                    <form onSubmit={confirmCode} className="flex items-end gap-3">
                        <div>
                            <InputLabel htmlFor="two-factor-code" value="Cod de confirmare" />
                            <TextInput
                                id="two-factor-code"
                                inputMode="numeric"
                                autoComplete="one-time-code"
                                autoFocus
                                value={code}
                                onChange={(e) => setCode(e.target.value)}
                                className="mt-1 block w-32 text-center tracking-[0.3em]"
                            />
                            <InputError message={codeError} className="mt-1" />
                        </div>
                        <PrimaryButton type="submit" disabled={processing}>
                            Confirmă
                        </PrimaryButton>
                        <SecondaryButton type="button" onClick={cancelSetup} disabled={processing}>
                            Anulează
                        </SecondaryButton>
                    </form>
                </div>
            )}

            {enabled && (
                <div className="space-y-4">
                    <p className="text-sm font-medium text-teal-ink">2FA este activă pentru contul tău.</p>

                    {recoveryCodes ? (
                        <div className="max-w-md space-y-3 rounded-lg border border-line bg-paper-raised p-4">
                            <p className="text-sm text-ink-soft">
                                Coduri de recuperare — folosește unul dacă pierzi accesul la aplicația de autentificare. Fiecare cod
                                funcționează o singură dată.
                            </p>
                            <div className="grid grid-cols-2 gap-1.5 rounded-md bg-ink px-4 py-3 font-mono text-xs text-white">
                                {recoveryCodes.map((recoveryCode) => (
                                    <span key={recoveryCode}>{recoveryCode}</span>
                                ))}
                            </div>
                            <SecondaryButton onClick={regenerateRecoveryCodes} disabled={processing}>
                                Regenerează codurile
                            </SecondaryButton>
                        </div>
                    ) : (
                        <SecondaryButton onClick={showRecoveryCodes}>Arată codurile de recuperare</SecondaryButton>
                    )}

                    <div>
                        <DangerButton onClick={disable} disabled={processing}>
                            Dezactivează 2FA
                        </DangerButton>
                    </div>
                </div>
            )}

            <Modal show={confirmingPassword} onClose={closePasswordModal}>
                <form onSubmit={submitPasswordConfirmation} className="p-6">
                    <h2 className="text-lg font-medium text-ink">Confirmă-ți parola</h2>
                    <p className="mt-1 text-sm text-ink-muted">Din motive de siguranță, te rugăm să-ți confirmi parola pentru a continua.</p>

                    <div className="mt-6">
                        <InputLabel htmlFor="two-factor-password" value="Parolă" className="sr-only" />
                        <TextInput
                            id="two-factor-password"
                            type="password"
                            ref={passwordInput}
                            value={password}
                            onChange={(e) => setPassword(e.target.value)}
                            className="mt-1 block w-3/4"
                            isFocused
                            placeholder="Parolă"
                        />
                        <InputError message={passwordError} className="mt-2" />
                    </div>

                    <div className="mt-6 flex justify-end">
                        <SecondaryButton onClick={closePasswordModal}>Anulează</SecondaryButton>
                        <PrimaryButton className="ms-3" disabled={confirmingAction}>
                            Confirmă
                        </PrimaryButton>
                    </div>
                </form>
            </Modal>
        </section>
    );
}
