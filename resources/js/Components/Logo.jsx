/**
 * The real TrustBridge wordmark (tightly cropped from the source asset,
 * which shipped with a lot of empty canvas around the actual logo).
 * Already carries "TrustBridge" in its own type, so nothing renders a
 * separate text label next to it — that would just repeat the name.
 */
export default function Logo({ height = 28, className = '' }) {
    return (
        <img
            src="/images/logo.png"
            alt="TrustBridge B2B Network"
            height={height}
            className={className}
            style={{ height, width: 'auto' }}
        />
    );
}
