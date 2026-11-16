import { Form } from '@inertiajs/react';
import { Link } from '@inertiajs/react';
import { useRef } from 'react';
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { useTranslation } from '@/lib/i18n';
import { send } from '@/routes/verification';
import type { User } from '@/types';

type Props = {
    user: User;
};

export default function DeleteUser({ user }: Props) {
    const passwordInput = useRef<HTMLInputElement>(null);
    const { t } = useTranslation();
    const isVerified = user.email_verified_at !== null;

    return (
        <div className="space-y-6">
            <Heading
                variant="small"
                title={t('delete_account.heading')}
                description={t('delete_account.description')}
            />
            <div className="space-y-4 rounded-lg border border-red-100 bg-red-50 p-4 dark:border-red-200/10 dark:bg-red-700/10">
                <div className="relative space-y-0.5 text-red-600 dark:text-red-100">
                    <p className="font-medium">{t('delete_account.warning')}</p>
                    <p className="text-sm">
                        {t('delete_account.warning_description')}
                    </p>
                </div>

                {!isVerified && (
                    <div className="space-y-2 rounded-md border border-amber-200 bg-amber-50 p-3 dark:border-amber-800 dark:bg-amber-900/30">
                        <div className="flex items-start gap-2 text-amber-800 dark:text-amber-200">
                            <svg
                                className="mt-0.5 size-4 shrink-0"
                                xmlns="http://www.w3.org/2000/svg"
                                viewBox="0 0 20 20"
                                fill="currentColor"
                                aria-hidden="true"
                            >
                                <path
                                    fillRule="evenodd"
                                    d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z"
                                    clipRule="evenodd"
                                />
                            </svg>
                            <div>
                                <p className="font-medium">
                                    {t('delete_account.email_not_verified')}
                                </p>
                                <p className="text-sm">
                                    {t('delete_account.verify_required')}{' '}
                                    <Link
                                        href={send()}
                                        as="button"
                                        className="underline hover:no-underline"
                                    >
                                        {t('profile.resend')}
                                    </Link>
                                </p>
                            </div>
                        </div>
                    </div>
                )}

                <Dialog>
                    <DialogTrigger asChild>
                        <Button
                            variant="destructive"
                            data-test="delete-user-button"
                            className="bg-[#C0392B] text-white hover:bg-[#C0392B]/90"
                            disabled={!isVerified}
                            aria-disabled={!isVerified}
                        >
                            {t('delete_account.delete')}
                        </Button>
                    </DialogTrigger>
                    <DialogContent className="border-[#293241]/15 bg-white text-[#293241]">
                        <DialogTitle>
                            {t('delete_account.confirm_title')}
                        </DialogTitle>
                        <DialogDescription>
                            {t('delete_account.confirm_description')}
                        </DialogDescription>

                        <Form
                            {...ProfileController.destroy.form()}
                            options={{
                                preserveScroll: true,
                            }}
                            onError={() => passwordInput.current?.focus()}
                            resetOnSuccess
                            className="space-y-6"
                        >
                            {({ resetAndClearErrors, processing, errors }) => (
                                <>
                                    <div className="grid gap-2">
                                        <Label
                                            htmlFor="password"
                                            className="sr-only"
                                        >
                                            {t('delete_account.password')}
                                        </Label>

                                        <PasswordInput
                                            id="password"
                                            name="password"
                                            ref={passwordInput}
                                            placeholder={t(
                                                'delete_account.password',
                                            )}
                                            autoComplete="current-password"
                                            disabled={!isVerified}
                                        />

                                        <InputError message={errors.password} />
                                    </div>

                                    <DialogFooter className="gap-2">
                                        <DialogClose asChild>
                                            <Button
                                                variant="secondary"
                                                className="border border-[#293241]/25 text-[#293241] hover:bg-[#EEF3F8]"
                                                onClick={() =>
                                                    resetAndClearErrors()
                                                }
                                            >
                                                {t('delete_account.cancel')}
                                            </Button>
                                        </DialogClose>

                                        <Button
                                            variant="destructive"
                                            className="bg-[#C0392B] text-white hover:bg-[#C0392B]/90"
                                            disabled={processing || !isVerified}
                                            asChild
                                        >
                                            <button
                                                type="submit"
                                                data-test="confirm-delete-user-button"
                                                disabled={!isVerified}
                                            >
                                                {t('delete_account.delete')}
                                            </button>
                                        </Button>
                                    </DialogFooter>
                                </>
                            )}
                        </Form>
                    </DialogContent>
                </Dialog>
            </div>
        </div>
    );
}
