import { Form, Head, router, useForm, usePage } from '@inertiajs/react';
import { Link } from '@inertiajs/react';
import { useRef, useState } from 'react';
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import DeleteUser from '@/components/delete-user';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { useInitials } from '@/hooks/use-initials';
import { useTranslation } from '@/lib/i18n';
import { edit } from '@/routes/profile';
import {
    destroy as destroyAvatar,
    update as updateAvatar,
} from '@/routes/profile/avatar';
import { send } from '@/routes/verification';

export default function Profile({
    mustVerifyEmail,
    status,
}: {
    mustVerifyEmail: boolean;
    status?: string;
}) {
    const { auth, locale, availableLocales } = usePage().props;
    const user = auth.user!;
    const { t } = useTranslation();
    const isAdmin = user.role === 'admin' || user.role === 'super_admin';
    const [contactEmails, setContactEmails] = useState(
        user.notify_on_contact_message,
    );
    const getInitials = useInitials();
    const photoInputRef = useRef<HTMLInputElement>(null);
    const [photoPreview, setPhotoPreview] = useState<string | null>(null);
    const [removingPhoto, setRemovingPhoto] = useState(false);
    const photoForm = useForm<{ avatar: File | null }>({ avatar: null });

    const avatarUrl = photoPreview ?? user.avatar ?? null;

    const handlePhotoSelected = (
        event: React.ChangeEvent<HTMLInputElement>,
    ) => {
        const file = event.target.files?.[0];

        // Reset the input so selecting the same file again still triggers
        // a change event.
        event.target.value = '';

        if (!file) {
            return;
        }

        const objectUrl = URL.createObjectURL(file);
        setPhotoPreview(objectUrl);

        photoForm.setData('avatar', file);
        photoForm.post(updateAvatar.url(), {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => setPhotoPreview(null),
            onError: () => setPhotoPreview(null),
            onFinish: () => {
                photoForm.setData('avatar', null);
                URL.revokeObjectURL(objectUrl);
            },
        });
    };

    const handleRemovePhoto = () => {
        if (removingPhoto) {
            return;
        }

        setRemovingPhoto(true);
        router.delete(destroyAvatar.url(), {
            preserveScroll: true,
            onFinish: () => setRemovingPhoto(false),
        });
    };

    return (
        <>
            <Head title={t('profile.title')} />

            <div className="mx-auto max-w-2xl space-y-6">
                <Heading
                    variant="small"
                    title={t('profile.heading')}
                    description={t('profile.description')}
                />

                <div className="flex flex-col gap-4 rounded-lg border border-border bg-muted/50 p-4 sm:flex-row sm:items-center">
                    <Avatar className="size-20 shrink-0">
                        <AvatarImage
                            src={avatarUrl ?? undefined}
                            alt={user.display_name}
                        />
                        <AvatarFallback className="bg-primary-blue/35 text-lg font-semibold">
                            {getInitials(user.display_name ?? '')}
                        </AvatarFallback>
                    </Avatar>

                    <div className="min-w-0 flex-1 space-y-2">
                        <div>
                            <p className="text-sm font-medium text-foreground">
                                {t('profile.photo')}
                            </p>
                            <p className="text-sm text-muted-foreground">
                                {t('profile.photo_hint')}
                            </p>
                        </div>

                        <div className="flex flex-wrap items-center gap-2">
                            <input
                                ref={photoInputRef}
                                type="file"
                                accept="image/jpeg,image/png,image/webp"
                                className="hidden"
                                onChange={handlePhotoSelected}
                            />
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                disabled={photoForm.processing || removingPhoto}
                                onClick={() => photoInputRef.current?.click()}
                            >
                                {photoForm.processing && <Spinner />}
                                {avatarUrl
                                    ? t('profile.photo_change')
                                    : t('profile.photo_upload')}
                            </Button>
                            {avatarUrl && (
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="sm"
                                    disabled={
                                        photoForm.processing || removingPhoto
                                    }
                                    onClick={handleRemovePhoto}
                                >
                                    {removingPhoto && <Spinner />}
                                    {t('profile.photo_remove')}
                                </Button>
                            )}
                        </div>

                        <InputError message={photoForm.errors.avatar} />
                    </div>
                </div>

                <Form
                    {...ProfileController.update.form()}
                    transform={(data) =>
                        isAdmin
                            ? {
                                  ...data,
                                  notify_on_contact_message: contactEmails,
                              }
                            : data
                    }
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
                                    className="mt-1 block w-full border-border bg-background focus-visible:border-primary"
                                    defaultValue={user.display_name}
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
                                    className="mt-1 block w-full border-border bg-background focus-visible:border-primary"
                                    defaultValue={user.email}
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
                                    defaultValue={user.locale ?? locale}
                                    className="mt-1 block h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-base text-foreground shadow-xs transition-[color,box-shadow] outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 md:text-sm"
                                >
                                    {Object.entries(availableLocales).map(
                                        ([code, label]) => (
                                            <option key={code} value={code}>
                                                {label}
                                            </option>
                                        ),
                                    )}
                                </select>

                                <p className="text-sm text-muted-foreground">
                                    {t('profile.language_hint')}
                                </p>

                                <InputError
                                    className="mt-2"
                                    message={errors.locale}
                                />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="notify_on_new_artwork">
                                    {t('profile.notifications')}
                                </Label>

                                <label
                                    htmlFor="notify_on_new_artwork"
                                    className="mt-1 flex cursor-pointer items-start gap-3 rounded-lg border border-border bg-muted/50 p-4"
                                >
                                    <Checkbox
                                        id="notify_on_new_artwork"
                                        name="notify_on_new_artwork"
                                        value="1"
                                        defaultChecked={
                                            user.notify_on_new_artwork === true
                                        }
                                        className="mt-0.5"
                                    />
                                    <span className="space-y-1">
                                        <span className="block text-sm font-medium text-foreground">
                                            {t('profile.notify_new_artwork')}
                                        </span>
                                        <span className="block text-sm text-muted-foreground">
                                            {t(
                                                'profile.notify_new_artwork_hint',
                                            )}
                                        </span>
                                    </span>
                                </label>

                                <InputError
                                    className="mt-2"
                                    message={errors.notify_on_new_artwork}
                                />
                            </div>

                            {mustVerifyEmail &&
                                user.email_verified_at === null && (
                                    <div>
                                        <p className="-mt-4 text-sm text-muted-foreground">
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
                                            <div className="mt-2 text-sm font-medium text-green-600 dark:text-green-400">
                                                {t('profile.verification_sent')}
                                            </div>
                                        )}
                                    </div>
                                )}

                            {isAdmin && (
                                <div className="grid gap-2">
                                    <label className="flex items-center gap-3 text-sm font-medium">
                                        <input
                                            type="checkbox"
                                            role="switch"
                                            checked={contactEmails}
                                            onChange={(event) =>
                                                setContactEmails(
                                                    event.target.checked,
                                                )
                                            }
                                            className="h-4 w-4 accent-primary"
                                        />
                                        {t('profile.notify_contact')}
                                    </label>
                                    <p className="text-sm text-muted-foreground">
                                        {t('profile.notify_contact_hint')}
                                    </p>
                                    <InputError
                                        message={
                                            errors.notify_on_contact_message
                                        }
                                    />
                                </div>
                            )}

                            <div className="flex items-center gap-4">
                                <Button
                                    disabled={processing}
                                    data-test="update-profile-button"
                                    className="bg-primary text-primary-foreground transition-colors hover:bg-primary/90"
                                >
                                    {t('profile.save')}
                                </Button>
                            </div>
                            <hr className="my-5 border-border" />
                        </>
                    )}
                </Form>
            </div>

            <DeleteUser user={user} />
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
