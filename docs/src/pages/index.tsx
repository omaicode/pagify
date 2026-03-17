import type {ReactNode} from 'react';
import Link from '@docusaurus/Link';
import useDocusaurusContext from '@docusaurus/useDocusaurusContext';
import Layout from '@theme/Layout';
import Heading from '@theme/Heading';

import styles from './index.module.css';

const currentCapabilities = [
  'Modular architecture with Core, Content, Media, Page Builder, and Updater domains',
  'Admin authentication and password reset workflow',
  'Role, permission, admin-group, and API token management',
  'Structured content schema builder with revision and publishing workflow',
  'Media library with upload sessions, preview, and download flow',
  'Theme management, Twig sandbox, and frontend fallback rendering',
  'Plugin lifecycle operations and extension-point discovery',
  'Updater execution flow with dry-run, update, and rollback support',
];

const quickLinks = [
  {
    title: 'Runbook',
    description: 'Daily setup, queue workflow, and troubleshooting commands.',
    to: '/docs/operations/runbook',
  },
  {
    title: 'API Reference',
    description: 'Search classes and functions by module and layer.',
    to: '/api-reference',
  },
  {
    title: 'Module Guides',
    description: 'Core, Content, Media, Page Builder, and Updater documentation.',
    to: '/docs/modules/core-module',
  },
];

function HomepageHero() {
  const {siteConfig} = useDocusaurusContext();

  return (
    <header className={styles.heroBanner}>
      <div className="container">
        <div className={styles.heroInner}>
          <p className={styles.kicker}>Pagify Documentation</p>
          <Heading as="h1" className={styles.heroTitle}>
            Pagify CMS
          </Heading>
          <p className={styles.heroSubtitle}>Official Documentation Portal</p>
          <p className={styles.heroDescription}>
            A modular Laravel CMS platform for multi-site operations, structured
            authoring workflows, and extensible frontend delivery.
          </p>
          <div className={styles.actions}>
            <Link className={styles.primaryCta} to="/docs/">
              Open Documentation
            </Link>
            <Link className={styles.secondaryCta} to="/api-reference">
              Browse API Reference
            </Link>
          </div>
        </div>
      </div>
    </header>
  );
}

function QuickLinksSection() {
  return (
    <section className={styles.quickLinksSection}>
      <div className="container">
        <Heading as="h2" className={styles.sectionTitle}>
          Quick Links
        </Heading>
        <div className={styles.quickLinksGrid}>
          {quickLinks.map((item) => (
            <Link key={item.title} className={styles.quickLinkCard} to={item.to}>
              <h3>{item.title}</h3>
              <p>{item.description}</p>
            </Link>
          ))}
        </div>
      </div>
    </section>
  );
}

function IntroSection() {
  return (
    <section className={styles.sectionBlock}>
      <div className="container">
        <Heading as="h2" className={styles.sectionTitle}>
          Introduction
        </Heading>
        <p className={styles.sectionText}>
          Pagify is a production-oriented CMS with clear module boundaries,
          operational safety, and developer-friendly extension points. This docs
          portal helps teams ship faster by centralizing guides, contracts,
          reference data, and implementation standards.
        </p>
      </div>
    </section>
  );
}

function ProjectDescriptionSection() {
  return (
    <section className={styles.sectionBlockAlt}>
      <div className="container">
        <Heading as="h2" className={styles.sectionTitle}>
          Project Description
        </Heading>
        <div className={styles.descriptionGrid}>
          <article className={styles.card}>
            <h3>Purpose</h3>
            <p>
              Deliver a reliable content platform for admin teams, developers,
              and operators with a predictable workflow from modeling to
              publishing.
            </p>
          </article>
          <article className={styles.card}>
            <h3>Technology Stack</h3>
            <p>
              Laravel 12, Inertia-based admin UI, modular service design,
              queued workflows, and theme rendering with sandboxed Twig helpers.
            </p>
          </article>
          <article className={styles.card}>
            <h3>Operational Focus</h3>
            <p>
              Built-in runbook coverage, update workflows, audit support,
              permission hardening, and test-backed module behaviors.
            </p>
          </article>
        </div>
      </div>
    </section>
  );
}

function FeaturesSection() {
  return (
    <section className={styles.sectionBlock}>
      <div className="container">
        <Heading as="h2" className={styles.sectionTitle}>
          Current Platform Features
        </Heading>
        <p className={styles.sectionText}>
          The following capabilities are currently implemented and covered in the
          active documentation and test matrix.
        </p>
        <div className={styles.featureGrid}>
          {currentCapabilities.map((feature) => (
            <article className={styles.featureCard} key={feature}>
              <p>{feature}</p>
            </article>
          ))}
        </div>
      </div>
    </section>
  );
}

export default function Home(): ReactNode {
  const {siteConfig} = useDocusaurusContext();

  return (
    <Layout
      title={siteConfig.title}
      description="Official Pagify CMS documentation portal for architecture, operations, API reference, and module guides.">
      <div className={styles.pageBrand}>
        <HomepageHero />
        <main>
          <QuickLinksSection />
          <IntroSection />
          <ProjectDescriptionSection />
          <FeaturesSection />
        </main>
      </div>
    </Layout>
  );
}
