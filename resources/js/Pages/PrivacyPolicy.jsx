import Logo from '@/Components/Logo';
import { Head, Link } from '@inertiajs/react';

function Section({ n, title, children }) {
    return (
        <section className="border-t border-line pt-8 first:border-0 first:pt-0">
            <h2 className="font-display text-lg font-semibold text-ink">
                {n}. {title}
            </h2>
            <div className="mt-3 space-y-3 text-sm leading-relaxed text-ink-soft">{children}</div>
        </section>
    );
}

function SubSection({ n, title, children }) {
    return (
        <div className="mt-5">
            <h3 className="text-sm font-semibold text-ink">
                {n} {title}
            </h3>
            <div className="mt-2 space-y-3 text-sm leading-relaxed text-ink-soft">{children}</div>
        </div>
    );
}

function List({ items }) {
    return (
        <ul className="list-disc space-y-1 pl-5">
            {items.map((item) => (
                <li key={item}>{item}</li>
            ))}
        </ul>
    );
}

export default function PrivacyPolicy() {
    return (
        <div className="min-h-screen bg-paper">
            <Head title="Privacy Policy — TrustBridge B2B" />

            <header className="border-b border-line bg-white">
                <div className="mx-auto flex h-16 max-w-3xl items-center px-4 sm:px-6">
                    <Link href="/" className="flex items-center">
                        <Logo height={24} />
                    </Link>
                </div>
            </header>

            <main className="mx-auto max-w-3xl px-4 py-10 sm:px-6">
                <div className="mb-10">
                    <p className="text-xs font-semibold uppercase tracking-widest text-ink-muted">Privacy Policy</p>
                    <h1 className="font-display mt-1 text-2xl font-semibold text-ink">TrustBridge B2B</h1>
                    <p className="mt-1 text-sm text-ink-muted">Event Organisation &amp; Digital Image Management Platform</p>
                    <p className="mt-4 text-sm text-ink-muted">
                        Version: 1.0
                        <br />
                        Effective Date: 01 November 2026
                        <br />
                        Last Updated: 01 November 2026
                    </p>
                </div>

                <div className="flex flex-col gap-8 rounded-lg bg-white p-6 shadow-sm sm:p-10">
                    <Section n={1} title="Introduction">
                        <p>Welcome to TrustBridge B2B.</p>
                        <p>
                            TrustBridge B2B is a business-to-business platform operated by M&amp;M Kft., designed to support the professional
                            organisation, coordination and management of business events and related digital content.
                        </p>
                        <p>
                            The platform provides functionalities for event organisation, participant coordination, customer management, digital
                            image administration, photograph galleries and controlled access to event-related visual materials.
                        </p>
                        <p>Protecting personal data and respecting the privacy rights of individuals are fundamental principles of our business operations.</p>
                        <p>
                            This Privacy Policy explains how personal data is collected, processed, stored, accessed, shared and protected when
                            using the TrustBridge B2B website and platform.
                        </p>
                        <p>Our data protection framework is based on the General Data Protection Regulation (EU) 2016/679 (GDPR) and other applicable data protection legislation.</p>
                    </Section>

                    <Section n={2} title="Data Controller and Platform Operator">
                        <p>The TrustBridge B2B platform is operated by:</p>
                        <p className="font-semibold text-ink">M&amp;M Kft.</p>
                        <p>
                            Registered location: 2724 Újlengyel, Petőfi Sándor utca 48.
                            <br />
                            Hungarian Tax Number (Adószám): HU10880054
                            <br />
                            Website:{' '}
                            <a href="https://www.trustbridgeb2b.com" className="text-accent underline">
                                https://www.trustbridgeb2b.com
                            </a>
                        </p>
                        <p>M&amp;M Kft. is responsible for the operation, administration and management of the TrustBridge B2B platform.</p>
                        <p>Depending on the specific processing activity, M&amp;M Kft. acts either as:</p>
                        <List
                            items={[
                                'Data Controller for its own business operations, website administration, account management and customer relationships.',
                                'Data Processor when processing personal data on behalf of business customers and event organisers.',
                            ]}
                        />
                        <p>The applicable role is determined by the actual purposes and means of each processing activity.</p>
                    </Section>

                    <Section n={3} title="Regional Partner Network">
                        <p>TrustBridge B2B operates through an authorised regional partner network to provide local customer management, business coordination and operational support.</p>
                        <p>The regional network currently covers:</p>
                        <List items={['Hungary (HU)', 'Germany (DE)', 'Romania (RO)', 'Additional markets as approved by M&M Kft.']} />
                        <p>Regional partners manage customer information exclusively on the documented instructions of M&amp;M Kft. and within the scope of their assigned responsibilities.</p>
                        <p>
                            Where regional partners process personal data on behalf of M&amp;M Kft. in connection with customer-managed event
                            information, they act as authorised sub-processors.
                        </p>
                        <p>Regional partners are not authorised to independently determine the purposes of processing customer personal data obtained through TrustBridge B2B.</p>
                        <p>M&amp;M Kft. remains responsible for ensuring that appropriate contractual and organisational safeguards are established before regional partners receive access to personal data.</p>
                        <p>All regional partners are required to comply with the applicable GDPR requirements and the contractual data protection obligations imposed by M&amp;M Kft.</p>
                    </Section>

                    <Section n={4} title="Scope of this Privacy Policy">
                        <p>This Privacy Policy applies to:</p>
                        <List
                            items={[
                                'Visitors to the TrustBridge B2B website.',
                                'Registered business customers.',
                                'Authorised platform users and administrators.',
                                'Event organisers and project managers.',
                                'Event participants and invited guests.',
                                'Individuals appearing in event photographs and visual materials.',
                                'Authorised regional partner personnel.',
                                'Business contacts and representatives of partner organisations.',
                            ]}
                        />
                        <p>It covers personal data processed in connection with website use, business relationships, platform access, event organisation, participant administration and digital image management.</p>
                    </Section>

                    <Section n={5} title="Categories of Personal Data">
                        <p>Depending on the services used, TrustBridge B2B may process the following categories of personal data.</p>

                        <SubSection n="5.1" title="Business and Account Information">
                            <List
                                items={[
                                    'First and last name.',
                                    'Business email address.',
                                    'Company name.',
                                    'Professional position and department.',
                                    'Business contact details.',
                                    'User account information.',
                                    'Login and authentication information.',
                                    'User roles and access permissions.',
                                    'Customer communication and support records.',
                                ]}
                            />
                        </SubSection>

                        <SubSection n="5.2" title="Event and Participant Information">
                            <List
                                items={[
                                    'Participant names and contact details.',
                                    'Event invitations and registrations.',
                                    'Attendance and participation information.',
                                    'Event-related communication.',
                                    'Organisational preferences.',
                                    'Event-specific information supplied by the customer or organiser.',
                                ]}
                            />
                        </SubSection>

                        <SubSection n="5.3" title="Photographs and Digital Image Content">
                            <p>TrustBridge B2B supports the digital management and organisation of event-related photographs and visual materials.</p>
                            <p>These may include:</p>
                            <List
                                items={[
                                    'Photographs of event participants, guests and speakers.',
                                    'Business event galleries.',
                                    'Images associated with specific events or companies.',
                                    'Uploaded digital photographs and visual documents.',
                                    'Image descriptions and associated metadata.',
                                    'Image access and sharing permissions.',
                                    'Records relating to image administration and access.',
                                ]}
                            />
                            <p>Photographs showing identifiable individuals may constitute personal data under the GDPR.</p>
                            <p>Where applicable, the organisation uploading or using photographs is responsible for ensuring that the processing has an appropriate legal basis and that individuals receive the required privacy information.</p>
                        </SubSection>

                        <SubSection n="5.4" title="Technical Information">
                            <p>For the operation, security and maintenance of the platform, technical information may be processed, including:</p>
                            <List items={['IP addresses.', 'Browser and device information.', 'Access logs.', 'Login timestamps.', 'Technical identifiers.', 'Security-related records.']} />
                        </SubSection>
                    </Section>

                    <Section n={6} title="Purposes of Processing">
                        <p>Personal data may be processed for the following purposes:</p>
                        <List
                            items={[
                                'Providing and maintaining the TrustBridge B2B platform.',
                                'Managing business customer accounts.',
                                'Managing authorised platform users.',
                                'Planning and organising business events.',
                                'Managing event invitations and registrations.',
                                'Coordinating event participants.',
                                'Organising and managing event photographs.',
                                'Providing access to digital image galleries.',
                                'Managing user permissions and access rights.',
                                'Supporting communication between customers and authorised regional partners.',
                                'Providing technical and customer support.',
                                'Maintaining platform security and availability.',
                                'Meeting legal and contractual obligations.',
                                'Improving platform performance and service quality.',
                            ]}
                        />
                        <p>Personal data will not be processed for purposes incompatible with the original purposes for which it was collected.</p>
                    </Section>

                    <Section n={7} title="Legal Basis for Processing">
                        <p>Personal data is processed only where an applicable legal basis exists under Article 6 GDPR.</p>
                        <p>Depending on the processing activity, the legal basis may include:</p>

                        <SubSection n="7.1" title="Consent – Article 6(1)(a) GDPR">
                            <p>Where an individual has freely given specific, informed and unambiguous consent to a particular processing activity.</p>
                            <p>This may apply to certain uses of event photographs or image publication, where consent is the appropriate legal basis.</p>
                        </SubSection>

                        <SubSection n="7.2" title="Contractual Necessity – Article 6(1)(b) GDPR">
                            <p>Where processing is necessary to perform a contract or take steps at the request of an individual before entering into a contract.</p>
                        </SubSection>

                        <SubSection n="7.3" title="Legal Obligations – Article 6(1)(c) GDPR">
                            <p>Where processing is necessary to comply with applicable legal obligations.</p>
                        </SubSection>

                        <SubSection n="7.4" title="Legitimate Interests – Article 6(1)(f) GDPR">
                            <p>Where processing is necessary for legitimate business interests, provided that these interests do not override the fundamental rights and freedoms of the individuals concerned.</p>
                            <p>The responsible Data Controller must determine and document the appropriate legal basis for each processing activity.</p>
                        </SubSection>
                    </Section>

                    <Section n={8} title="Event Photography and Digital Image Management">
                        <p>Responsible image management is a central component of the TrustBridge B2B platform.</p>
                        <p>The platform supports the organisation, storage, administration and controlled sharing of event-related photographs and visual materials.</p>

                        <SubSection n="8.1" title="Responsibility for Uploaded Images">
                            <p>The business customer or event organiser acting as Data Controller is responsible for ensuring that photographs are collected and processed lawfully.</p>
                            <p>This includes:</p>
                            <List
                                items={[
                                    'Establishing an appropriate legal basis.',
                                    'Informing identifiable individuals about photography.',
                                    'Providing information about the intended use of images.',
                                    'Obtaining consent where consent is the applicable legal basis.',
                                    'Respecting applicable image rights and other legal requirements.',
                                ]}
                            />
                        </SubSection>

                        <SubSection n="8.2" title="Image Access and Permissions">
                            <p>Access to event-related photographs should be restricted to authorised users.</p>
                            <p>Depending on the available platform configuration, access permissions may be assigned according to user roles and organisational responsibilities.</p>
                        </SubSection>

                        <SubSection n="8.3" title="Sharing and Publication">
                            <p>Photographs may only be shared, downloaded, distributed or published for authorised purposes.</p>
                            <p>
                                Uploading photographs to TrustBridge B2B does not automatically grant permission for publication on external
                                websites, social media channels, marketing materials or other public communication platforms.
                            </p>
                            <p>Any external publication must have an appropriate legal basis and comply with applicable privacy and image rights requirements.</p>
                        </SubSection>

                        <SubSection n="8.4" title="Requests Concerning Photographs">
                            <p>Individuals may request access to, correction of, restriction of or deletion of their personal data, including identifiable photographs, subject to applicable legal requirements.</p>
                            <p>Such requests should generally be directed to the relevant business customer or event organiser acting as Data Controller.</p>
                            <p>TrustBridge and M&amp;M Kft. will provide the contractual and technical assistance required to support the responsible Data Controller.</p>
                        </SubSection>

                        <SubSection n="8.5" title="Image Retention">
                            <p>Event-related photographs are retained according to the instructions of the relevant Data Controller and the applicable contractual retention arrangements.</p>
                            <p>When photographs are no longer required, they will be deleted or anonymised in accordance with the applicable retention and deletion procedures.</p>
                        </SubSection>
                    </Section>

                    <Section n={9} title="Data Processing Roles and Responsibilities">
                        <p>TrustBridge B2B operates under a centralised data governance model.</p>

                        <SubSection n="9.1" title="Business Customers and Event Organisers">
                            <p>Business customers and event organisers generally act as Data Controllers for participant information, event registrations, photographs and other event-related personal data.</p>
                            <p>They determine the purposes and legal basis for processing and are responsible for providing the required privacy information.</p>
                        </SubSection>

                        <SubSection n="9.2" title="M&M Kft.">
                            <p>When processing event and customer data on behalf of its business customers, M&amp;M Kft. acts as Data Processor.</p>
                            <p>M&amp;M Kft. processes such personal data only on documented instructions from the relevant Data Controller, in accordance with the applicable Data Processing Agreement.</p>
                            <p>For its own business administration, website operations, customer relationships and contractual management, M&amp;M Kft. acts as Data Controller where it independently determines the purposes and means of processing.</p>
                        </SubSection>

                        <SubSection n="9.3" title="Regional Partners">
                            <p>Regional partner companies act as authorised sub-processors when managing customer information exclusively on the documented instructions of M&amp;M Kft.</p>
                            <p>They are not permitted to use such information for their own independent marketing or commercial purposes.</p>
                        </SubSection>

                        <SubSection n="9.4" title="Contractual Arrangements">
                            <p>The respective data protection responsibilities are governed by appropriate contractual arrangements, including:</p>
                            <List
                                items={[
                                    'Data Processing Agreements pursuant to Article 28 GDPR.',
                                    'Sub-processing agreements with authorised regional partners.',
                                    'Confidentiality and information security obligations.',
                                    'Documented instructions and access management procedures.',
                                ]}
                            />
                        </SubSection>
                    </Section>

                    <Section n={10} title="Regional Partner Access and Sub-processing">
                        <p>Regional partners may receive access to personal data only where necessary to perform their assigned responsibilities.</p>
                        <p>They are required to:</p>
                        <List
                            items={[
                                'Process personal data exclusively on documented instructions.',
                                'Access only the information necessary for their assigned activities.',
                                'Maintain strict confidentiality.',
                                'Apply appropriate technical and organisational security measures.',
                                'Prevent unauthorised access and disclosure.',
                                'Notify M&M Kft. without undue delay of any personal data breach.',
                                'Assist with data subject requests where required.',
                                'Not appoint further sub-processors without the required prior written authorisation.',
                                'Return or securely delete personal data when instructed or when their contractual services end, subject to applicable legal retention requirements.',
                            ]}
                        />
                        <p>M&amp;M Kft. remains responsible for ensuring that the necessary sub-processing arrangements and safeguards are in place.</p>
                    </Section>

                    <Section n={11} title="Data Sharing and Third-Party Service Providers">
                        <p>Personal data may be made available to the following categories of recipients where necessary:</p>
                        <List
                            items={[
                                'Authorised business customers and event organisers.',
                                'M&M Kft. personnel.',
                                'Authorised regional partner personnel.',
                                'Hosting and infrastructure provider: https://tarhely.eu/',
                                'Cloud storage providers.',
                                'IT maintenance and technical support providers.',
                                'Security and monitoring service providers.',
                                'Professional advisers.',
                                'Competent public authorities where legally required.',
                            ]}
                        />
                        <p>All third-party service providers processing personal data on behalf of M&amp;M Kft. must be subject to appropriate contractual data protection obligations.</p>
                        <p>TrustBridge B2B does not sell personal data to third parties.</p>
                    </Section>

                    <Section n={12} title="International Data Transfers">
                        <p>TrustBridge B2B operates within an international European business environment.</p>
                        <p>
                            The involvement of authorised regional partners established in Hungary, Germany and Romania does not, by itself,
                            constitute an international transfer to a country outside the European Economic Area, as all three countries are
                            within the EEA.
                        </p>
                        <p>Where personal data is transferred outside the EEA, the transfer must comply with Chapter V GDPR.</p>
                        <p>Depending on the circumstances, appropriate safeguards may include:</p>
                        <List
                            items={[
                                'An adequacy decision of the European Commission.',
                                'Standard Contractual Clauses approved by the European Commission.',
                                'Additional technical and organisational safeguards where required.',
                            ]}
                        />
                    </Section>

                    <Section n={13} title="Data Retention and Deletion">
                        <p>Personal data is retained only for as long as necessary to fulfil the purposes for which it was collected or processed.</p>
                        <p>Retention periods depend on:</p>
                        <List
                            items={[
                                'The duration of the business relationship.',
                                'The duration and requirements of an event.',
                                'Contractual obligations.',
                                'Applicable legal retention requirements.',
                                'The instructions of the relevant Data Controller.',
                                'The requirements concerning event photographs and digital galleries.',
                                'Legal claims and dispute resolution requirements.',
                            ]}
                        />
                        <p>Where M&amp;M Kft. acts as Data Processor, personal data will be returned or deleted in accordance with the applicable Data Processing Agreement and documented instructions of the Data Controller, unless applicable law requires further retention.</p>
                    </Section>

                    <Section n={14} title="Data Security">
                        <p>M&amp;M Kft. is committed to maintaining appropriate technical and organisational measures to protect personal data.</p>
                        <p>Depending on the applicable technical implementation, such measures may include:</p>
                        <List
                            items={[
                                'Role-based access management.',
                                'Authentication and account protection.',
                                'Controlled access to event information and image galleries.',
                                'Encryption during data transmission.',
                                'Encryption of stored data where implemented.',
                                'System monitoring and security logging.',
                                'Backup and recovery procedures.',
                                'Access restrictions for authorised personnel.',
                                'Confidentiality obligations.',
                                'Appropriate security arrangements with service providers.',
                            ]}
                        />
                        <p>Security measures are reviewed and adapted according to the nature, scope, context and risks of the processing activities.</p>
                        <p>No digital platform or electronic storage system can guarantee absolute security.</p>
                    </Section>

                    <Section n={15} title="Personal Data Breaches">
                        <p>In the event of a personal data breach, M&amp;M Kft. will follow the applicable incident management and notification procedures.</p>
                        <p>Where M&amp;M Kft. acts as Data Processor, it will notify the relevant Data Controller without undue delay after becoming aware of a personal data breach.</p>
                        <p>Authorised regional sub-processors must notify M&amp;M Kft. without undue delay after becoming aware of a personal data breach involving data processed on M&amp;M Kft.'s instructions.</p>
                        <p>The relevant Data Controller remains responsible for assessing and fulfilling applicable notification obligations towards supervisory authorities and affected individuals.</p>
                    </Section>

                    <Section n={16} title="Your Data Protection Rights">
                        <p>Under the GDPR, individuals have the following rights, subject to applicable legal requirements:</p>
                        <List
                            items={[
                                'Right of access.',
                                'Right to rectification.',
                                'Right to erasure.',
                                'Right to restriction of processing.',
                                'Right to object.',
                                'Right to data portability, where applicable.',
                                'Right to withdraw consent at any time where processing is based on consent.',
                                'Right to lodge a complaint with a competent supervisory authority.',
                            ]}
                        />
                        <p>Individuals may exercise their rights by contacting the relevant Data Controller.</p>
                        <p>Where TrustBridge B2B processes personal data on behalf of a business customer, requests relating to event participants and photographs should generally be directed to the relevant event organiser or business customer.</p>
                        <p>M&amp;M Kft. will assist the relevant Data Controller in fulfilling applicable GDPR obligations.</p>
                    </Section>

                    <Section n={17} title="Children's Data">
                        <p>TrustBridge B2B is primarily intended for professional and business-related use.</p>
                        <p>The platform is not specifically designed for children.</p>
                        <p>Where children appear in event photographs or other visual materials, the responsible Data Controller must ensure compliance with all applicable legal requirements, including requirements concerning parental responsibility and consent where relevant.</p>
                    </Section>

                    <Section n={18} title="Cookies and Similar Technologies">
                        <p>The TrustBridge B2B website may use cookies and similar technologies to ensure essential functionality, maintain security and improve the user experience.</p>
                        <p>Where non-essential cookies or tracking technologies are used, appropriate information and consent mechanisms will be provided in accordance with applicable legislation.</p>
                        <p>Further information may be included in a separate Cookie Policy.</p>
                    </Section>

                    <Section n={19} title="Responsibilities of Business Customers">
                        <p>Business customers using TrustBridge B2B are responsible for:</p>
                        <List
                            items={[
                                'Determining the purposes and legal basis for processing participant information.',
                                'Providing appropriate privacy notices.',
                                'Informing individuals about event photography.',
                                'Obtaining consent where consent is the applicable legal basis.',
                                'Ensuring that uploaded photographs are processed lawfully.',
                                'Managing image publication rights.',
                                'Assigning appropriate access permissions.',
                                'Responding to data subject requests.',
                                'Complying with their obligations as Data Controllers.',
                            ]}
                        />
                        <p>Business customers must enter into an appropriate Data Processing Agreement with M&amp;M Kft. where required under Article 28 GDPR.</p>
                    </Section>

                    <Section n={20} title="Data Protection by Design and Default">
                        <p>TrustBridge B2B supports a data protection approach based on the principles of privacy by design and privacy by default.</p>
                        <p>Personal data access should be limited to what is necessary for the relevant operational purpose.</p>
                        <p>Event organisers and authorised users should use the platform's available access management and image administration capabilities responsibly.</p>
                        <p>M&amp;M Kft. considers data protection requirements when managing and developing the platform and its related services.</p>
                    </Section>

                    <Section n={21} title="Automated Decision-Making and Profiling">
                        <p>TrustBridge B2B is intended for event organisation, business coordination and digital image management.</p>
                        <p>Personal data is not intended to be used for automated decision-making producing legal or similarly significant effects on individuals.</p>
                        <p>If this changes, appropriate information and safeguards will be provided in accordance with applicable GDPR requirements.</p>
                    </Section>

                    <Section n={22} title="Changes to this Privacy Policy">
                        <p>M&amp;M Kft. may update this Privacy Policy to reflect changes in the platform, business operations, contractual relationships, technical processes or applicable legislation.</p>
                        <p>The latest version will be published on:</p>
                        <p>
                            <a href="https://www.trustbridgeb2b.com" className="text-accent underline">
                                https://www.trustbridgeb2b.com
                            </a>
                        </p>
                        <p>The version number and effective date will be indicated in each published version.</p>
                    </Section>

                    <Section n={23} title="Contact and Privacy Enquiries">
                        <p>For questions concerning this Privacy Policy, the processing of personal data or data protection rights, please contact:</p>
                        <p className="font-semibold text-ink">M&amp;M Kft. – TrustBridge B2B</p>
                        <p>
                            Registered location: 2724 Újlengyel, Petőfi Sándor utca 48.
                            <br />
                            Hungarian Tax Number (Adószám): HU10880054
                            <br />
                            Website:{' '}
                            <a href="https://www.trustbridgeb2b.com" className="text-accent underline">
                                https://www.trustbridgeb2b.com
                            </a>
                            <br />
                            Privacy contact email:{' '}
                            <a href="mailto:dataprotection@ttc-group.hu" className="text-accent underline">
                                dataprotection@ttc-group.hu
                            </a>
                        </p>
                        <p>Where applicable, details of the designated Data Protection Officer or EU representative will be published or provided separately.</p>
                    </Section>
                </div>

                <div className="mt-10 rounded-lg bg-paper-sunken p-6 text-sm text-ink-muted">
                    <p className="text-xs font-semibold uppercase tracking-widest text-ink-muted">Document Control</p>
                    <p className="mt-2">
                        Document: TrustBridge B2B Privacy Policy
                        <br />
                        Version: 1.0
                        <br />
                        Effective Date: 01 November 2026
                        <br />
                        Responsible Legal Entity: M&amp;M Kft., Hungary
                        <br />
                        Platform: www.trustbridgeb2b.com
                        <br />
                        Applicable Framework: Regulation (EU) 2016/679 – General Data Protection Regulation (GDPR)
                    </p>
                </div>

                <div className="mt-8 text-center">
                    <p className="text-sm font-semibold text-ink">TrustBridge B2B</p>
                    <p className="text-sm text-ink-muted">Professional Event Organisation. Structured Image Management. Responsible Data Protection.</p>
                </div>
            </main>
        </div>
    );
}
