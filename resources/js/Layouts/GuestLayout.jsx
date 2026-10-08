import BrandMark from '@/Components/BrandMark';
import { Link } from '@inertiajs/react';

export default function GuestLayout({ children }) {
    return (
        <div className="flex min-h-screen flex-col items-center bg-paper pt-6 sm:justify-center sm:pt-0 dark:bg-gray-900">
            <Link href="/" className="flex items-center gap-3">
                <BrandMark size={40} className="text-ink-muted" />
                <span className="font-display text-[24px] font-semibold tracking-tight text-ink dark:text-gray-200">
                    TrustBridge
                </span>
            </Link>

            <div className="mt-6 w-full overflow-hidden bg-white px-6 py-4 shadow-md sm:max-w-md sm:rounded-lg dark:bg-gray-800">
                {children}
            </div>

            <p className="mt-5 flex items-center gap-1.5 text-xs text-ink-muted">
                <svg width="14" height="14" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M10 2l6 3v5c0 4.2-2.6 7.4-6 8-3.4-.6-6-3.8-6-8V5l6-3z" stroke="currentColor" strokeWidth="1.4" strokeLinejoin="round" />
                </svg>
                Cont protejat prin verificare în doi pași
            </p>
        </div>
    );
}
