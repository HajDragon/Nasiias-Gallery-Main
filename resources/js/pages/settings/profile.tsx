import { Form, Head, usePage } from '@inertiajs/react';
import { Link } from '@inertiajs/react';
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import DeleteUser from '@/components/delete-user';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { edit } from '@/routes/profile';
import { send } from '@/routes/verification';
import { useTranslation } from '@/lib/i18n';

export default function Profile({
    mustVerifyEmail,
    status,
}: {
    mustVerifyEmail: boolean;
    status?: string;
}) {
    const { auth, locale, availableLocales } = usePage().props;
    const { t } = useTranslation();

    return (
        <>
            <Head title={t('profile.title')} />

            <h1 className="sr-only">{t('profile.title')}</h1>

            <div className="space-y-6">
                <Heading
                    variant="small"
                    title={t('profile.heading')}
                    description={t('profile.description')}
                />

                <Form
                    {...ProfileController.update.form()}
                    options={{
                        preserveScroll: true,
                    }}
                    className="space-y-6"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="display_name">
                                    {t('profile.display_name')}
                                </Label>

                                <Input
                                    id="display_name"
                                    className="mt-1 block w-full"
                                    defaultValue={auth.user.display_name}
                                    name="display_name"
                                    required
                                    autoComplete="name"
                                    placeholder={t(
                                        'profile.display_name_placeholder',
                                    )}
                                />

                                <InputError
                                    className="mt-2"
                                    message={errors.display_name}
                                />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="email">
                                    {t('profile.email')}
                                </Label>

                                <Input
                                    id="email"
                                    type="email"
                                    className="mt-1 block w-full"
                                    defaultValue={auth.user.email}
                                    name="email"
                                    required
                                    autoComplete="username"
                                    placeholder={t('profile.email_placeholder')}
                                />

                                <InputError
                                    className="mt-2"
                                    message={errors.email}
                                />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="locale">
                                    {t('profile.language')}
                                </Label>

                                <select
                                    id="locale"
                                    name="locale"
                                    defaultValue={auth.user.locale ?? locale}
                                    className="border-input focus-visible:border-ring focus-visible:ring-ring/50 mt-1 block h-9 w-full rounded-md border bg-transparent px-3 py-1 text-base shadow-xs transition-[color,box-shadow] outline-none focus-visible:ring-[3px] md:text-sm"
                                >
                                    {Object.entries(availableLocales).map(
                                        ([code, label]) => (
                                            <option key={code} value={code}>
                                                {label}
                                            </option>
                                        ),
                                    )}
                                </select>

                                <p className="text-muted-foreground text-sm">
                                    {t('profile.language_hint')}
                                </p>

                                <InputError
                                    className="mt-2"
                                    message={errors.locale}
                                />
                            </div>

                            {mustVerifyEmail &&
                                auth.user.email_verified_at === null && (
                                    <div>
                                        <p className="text-muted-foreground -mt-4 text-sm">
                                            {t('profile.unverified')}{' '}
                                            <Link
                                                href={send()}
                                                as="button"
                                                className="text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current! dark:decoration-neutral-500"
                                            >
                                                {t('profile.resend')}
                                            </Link>
                                        </p>

                                        {status ===
                                            'verification-link-sent' && (
                                            <div className="mt-2 text-sm font-medium text-green-600">
                                                {t('profile.verification_sent')}
                                            </div>
                                        )}
                                    </div>
                                )}

                            <div className="flex items-center gap-4">
                                <Button
                                    disabled={processing}
                                    data-test="update-profile-button"
                                >
                                    {t('profile.save')}
                                </Button>
                            </div>
                        </>
                    )}
                </Form>
            </div>

            <DeleteUser />
        </>
    );
}

Profile.layout = {
    breadcrumbs: [
        {
            title: 'profile.title',
            href: edit(),
        },
    ],
};
