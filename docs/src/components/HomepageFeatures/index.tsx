import type {ReactNode} from 'react';
import clsx from 'clsx';
import Heading from '@theme/Heading';
import styles from './styles.module.css';

type FeatureItem = {
  title: string;
  description: ReactNode;
};

const FeatureList: FeatureItem[] = [
  {
    title: 'Automatic Routing',
    description: (
      <>
        Automatically generate API routes for your database entities using simple PHP 8 attributes.
        No more manual route definitions for standard CRUD operations.
      </>
    ),
  },
  {
    title: 'Deep Integration',
    description: (
      <>
        Built specifically for jinya-database and jinya-router.
        Seamlessly connects your data layer to your API endpoints.
      </>
    ),
  },
  {
    title: 'Highly Extensible',
    description: (
      <>
        Add PSR-15 middlewares to your routes, customize paths, and handle complex
        requirements while keeping the simplicity of attribute-based configuration.
      </>
    ),
  },
];

function Feature({title, description}: FeatureItem) {
  return (
    <div className={clsx('col col--4')}>
      <div className="text--center padding-horiz--md">
        <Heading as="h3">{title}</Heading>
        <p>{description}</p>
      </div>
    </div>
  );
}

export default function HomepageFeatures(): ReactNode {
  return (
    <section className={styles.features}>
      <div className="container">
        <div className="row">
          {FeatureList.map((props, idx) => (
            <Feature key={idx} {...props} />
          ))}
        </div>
      </div>
    </section>
  );
}
