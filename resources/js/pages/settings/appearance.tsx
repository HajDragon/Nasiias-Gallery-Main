import { Head } from '@inertiajs/react';
import AppearanceTabs from '@/components/appearance-tabs';
import Heading from '@/components/heading';
import { useTranslation } from '@/lib/i18n';
import { edit as editAppearance } from '@/routes/appearance';

export default function Appearance() {
    const { t } = useTranslation();

    return (
        <>
            <Head title={t('appearance.title')} />

            <h1 className="sr-only">{t('appearance.heading')}</h1>

            <div className="space-y-6">
                <Heading
                    variant="small"
                    title={t('appearance.heading')}
                    description={t('appearance.description')}
                />
                <AppearanceTabs />
            </div>
        </>
    );
}

Appearance.layout = {
    breadcrumbs: [
        {
            title: 'appearance.title',
            href: editAppearance(),
        },
    ],
};
