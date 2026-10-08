export default function InputError({ message, className = '', ...props }) {
    return message ? (
        <p
            {...props}
            className={'text-sm text-danger dark:text-red-400 ' + className}
        >
            {message}
        </p>
    ) : null;
}
