import { Head } from '@inertiajs/react';

interface SEOData {
    title?: string | null;
    description?: string | null;
    author?: string | null;
    image?: string | null;
    url?: string | null;
    site_name?: string | null;
    type?: string;
    twitter_username?: string | null;
    published_time?: string | null;
    modified_time?: string | null;
    section?: string | null;
    tags?: string[] | null;
    robots?: string | null;
    canonical_url?: string | null;
    locale?: string | null;
    favicon?: string | null;
}

interface SEOHeadProps {
    seo?: SEOData | null;
}

export default function SEOHead({ seo }: SEOHeadProps) {
    if (!seo) return null;

    const title = seo.title || '';
    const author = seo.author || '';
    const description = seo.description || '';
    const image = seo.image || '';
    const url = seo.url || '';
    const siteName = seo.site_name || '';
    const type = seo.type || 'website';
    const twitterUsername = seo.twitter_username || '';
    const publishedTime = seo.published_time || '';
    const modifiedTime = seo.modified_time || '';
    const section = seo.section || '';
    const tags = seo.tags || [];
    const robots = seo.robots || '';
    const canonicalUrl = seo.canonical_url || url;
    const locale = seo.locale || '';
    const favicon = seo.favicon || '';

    return (
        <Head>
            {title && <title>{title}</title>}
            {description && <meta name="description" content={description} />}
            {author && <meta name="author" content={author} />}
            {robots && <meta name="robots" content={robots} />}
            {canonicalUrl && <link rel="canonical" href={canonicalUrl} />}
            {favicon && <link rel="icon" href={favicon} />}

            {/* OpenGraph */}
            {siteName && <meta property="og:site_name" content={siteName} />}
            {title && <meta property="og:title" content={title} />}
            {description && (
                <meta property="og:description" content={description} />
            )}
            {type && <meta property="og:type" content={type} />}
            {url && <meta property="og:url" content={url} />}
            {image && <meta property="og:image" content={image} />}
            {locale && <meta property="og:locale" content={locale} />}
            {publishedTime && (
                <meta
                    property="article:published_time"
                    content={publishedTime}
                />
            )}
            {modifiedTime && (
                <meta property="article:modified_time" content={modifiedTime} />
            )}
            {section && <meta property="article:section" content={section} />}
            {tags.map((tag, index) => (
                <meta key={index} property="article:tag" content={tag} />
            ))}

            {/* Twitter */}
            {twitterUsername && (
                <meta name="twitter:site" content={twitterUsername} />
            )}
            {title && <meta name="twitter:title" content={title} />}
            {description && (
                <meta name="twitter:description" content={description} />
            )}
            {image && <meta name="twitter:image" content={image} />}
            <meta name="twitter:card" content="summary_large_image" />
        </Head>
    );
}
