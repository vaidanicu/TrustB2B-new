const paths = {
    shield: 'M12 3 4 6v6c0 5 8 9 8 9s8-4 8-9V6l-8-3Z M8 12l3 3 5-6',
    image: 'M4 4h16v16H4z M4 16l5-5 4 4 3-3 4 4 M8 8h.01',
    check: 'M5 12l4 4L19 6',
    arrow: 'M4 12h16 M14 6l6 6-6 6',
    mail: 'M3 5h18v14H3z M3 5l9 7 9-7',
    file: 'M14 2H5v20h14V7l-5-5Z M14 2v5h5 M8 12h8 M8 16h5',
    users: 'M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2 M9 3a4 4 0 1 0 0 8 4 4 0 0 0 0-8 M17 4a4 4 0 0 1 0 8 M22 21v-2a4 4 0 0 0-3-3.87',
    calendar: 'M4 5h16v16H4z M8 2v6 M16 2v6 M4 11h16',
    lock: 'M5 11h14v10H5z M8 11V7a4 4 0 0 1 8 0v4 M12 15v2',
    menu: 'M4 6h16 M4 12h16 M4 18h16',
    close: 'm6 6 12 12 M6 18 18 6',
};

export default function MarketingIcon({ name, className = '' }) {
    return <svg className={className} width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.6" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true"><path d={paths[name] ?? paths.shield} /></svg>;
}
