/**
 * TrustBridge has no real logo asset yet — this is a placeholder mark
 * (two bridge towers over a deck line) used consistently wherever the
 * Laravel/Breeze default logo used to sit, so it is easy to swap out once
 * a real one exists.
 */
export default function BrandMark({ size = 26, className = 'text-gray-800' }) {
    return (
        <svg
            width={size}
            height={size}
            viewBox="0 0 28 28"
            fill="none"
            xmlns="http://www.w3.org/2000/svg"
            className={className}
        >
            <path d="M4 20V12a4 4 0 014-4 4 4 0 014 4v8" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" />
            <path d="M16 20V12a4 4 0 014-4 4 4 0 014 4v8" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" />
            <path d="M2 20h24" stroke="currentColor" strokeWidth="2" strokeLinecap="round" />
        </svg>
    );
}
