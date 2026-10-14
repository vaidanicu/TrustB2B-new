import { useState } from 'react';
import { Link } from '@inertiajs/react';
import Icon from './MarketingIcon';

export default function PlatformPreview({ t }) {
    const [active, setActive] = useState(0);
    const icons = ['calendar', 'users', 'file'];
    return (
        <aside className="tb-preview" aria-label={t.previewTitle}>
            <div className="tb-preview-bar"><span className="tb-preview-brand"><Icon name="shield" /> TrustBridge <small>B2B</small></span><span className="tb-demo">{t.demo}</span></div>
            <div className="tb-preview-workspace">
                <div className="tb-preview-nav">
                    <span className="tb-org"><span>BF</span>{t.previewOrg}</span>
                    <div className="tb-preview-switcher" role="group" aria-label={t.previewTitle}>
                        {t.previewTabs.map((tab, index) => <button key={tab} type="button" aria-pressed={active === index} aria-controls="preview-content" onClick={() => setActive(index)}><Icon name={icons[index]} />{tab}</button>)}
                    </div>
                </div>
                <div className="tb-preview-content" id="preview-content" aria-live="polite">
                    <div className="tb-preview-heading"><p>{t.previewTitle}</p><h2>{t.previewGreeting}</h2><span>{t.previewSub}</span></div>
                    <div className="tb-metrics">{t.previewMetrics.map((metric, index) => <div key={metric}><span>{metric}</span><strong>{[1, 24, 18][index]}</strong></div>)}</div>
                    {active === 0 && <div className="tb-event-card"><div className="tb-event-cover"><img src="/images/business-event.jpg" alt={t.photoAlt} width="640" height="360" fetchPriority="high" /><span className="tb-status">{t.draft}</span></div><div className="tb-event-details"><h3>{t.eventName}</h3><p><Icon name="calendar" />{t.eventDate}</p><div className="tb-event-meta"><span><Icon name="users" />24 {t.participantsLabel.toLowerCase()}</span><span><Icon name="shield" />18 {t.sampleStatus[1].toLowerCase()}</span></div></div></div>}
                    {active === 1 && <div className="tb-people">{['Alex Morgan', 'Maria Weber', 'Andrei Popescu'].map((name, index) => <div key={name}><span className="tb-avatar">{name.split(' ').map(part => part[0]).join('')}</span><div><strong>{name}</strong><small>{t.sampleStatus[0]}</small></div><span className={index === 2 ? 'tb-preference-neutral' : 'tb-preference'}>{t.sampleStatus[index === 2 ? 2 : 1]}</span></div>)}</div>}
                    {active === 2 && <div className="tb-audit"><h3>{t.auditTitle}</h3>{t.auditItems.map((item, index) => <div key={item}><Icon name="check" /><span>{item}<small>18.11.2026 · {['09:00', '09:15', '09:30'][index]}</small></span></div>)}<p>{t.auditNote}</p></div>}
                    <div className="tb-gallery-summary"><span className="tb-icon-small"><Icon name="lock" /></span><div><strong>{t.gallery}</strong><p>{t.galleryNote}</p></div><span className="tb-status tb-status-planned">{t.planned}</span></div>
                    <Link className="tb-preview-link" href={route('participants.invitation')}>{t.demoLink}<Icon name="arrow" /></Link>
                </div>
            </div>
        </aside>
    );
}
