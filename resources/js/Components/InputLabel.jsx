export default function InputLabel({
    value,
    className = '',
    children,
    ...props
}) {
    return (
        <label
            {...props}
            className={
                `block text-sm font-medium text-ink-soft dark:text-gray-300 ` +
                className
            }
        >
            {value ? value : children}
        </label>
    );
}
