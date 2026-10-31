export default function InputLabel({
    value,
    className = '',
    required = false,
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
            {required && <span className="ml-0.5 text-danger-ink">*</span>}
        </label>
    );
}
