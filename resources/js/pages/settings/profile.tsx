import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import { send } from '@/routes/verification';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Transition } from '@headlessui/react';
import { Form, Head, Link, usePage } from '@inertiajs/react';

import DeleteUser from '@/components/delete-user';
import HeadingSmall from '@/components/heading-small';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';


export default function Profile({
    mustVerifyEmail,
    status,
}: {
    mustVerifyEmail: boolean;
    status?: string;
}) {
    const { auth } = usePage<SharedData>().props;
    const { t } = useTranslations();

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: t('Profile settings'),
            href: edit().url,
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={t('Profile settings')} />

            <SettingsLayout>
                <div className="space-y-6">
                    <HeadingSmall
                        title={t('Profile information')}
                        description={t('Update your name and email address')}
                    />

                    <Form
                        {...ProfileController.update.form()}
                        options={{
                            preserveScroll: true,
                        }}
                        className="space-y-6"
                    >
                        {({ processing, recentlySuccessful, errors }) => (
                            <>
                                <div className="grid gap-2">
                                    <Label htmlFor="name">{t('Name')}</Label>
                                    <Input
                                        id="name"
                                        className="mt-1 block w-full"
                                        defaultValue={auth.user.name}
                                        name="name"
                                        required
                                        autoComplete="name"
                                        placeholder={t('Name')}
                                    />
                                    <InputError
                                        className="mt-2"
                                        message={errors.name}
                                    />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="email">{t('Email address')}</Label>
                                    <Input
                                        id="email"
                                        type="email"
                                        className="mt-1 block w-full"
                                        defaultValue={auth.user.email}
                                        name="email"
                                        required
                                        autoComplete="username"
                                        placeholder={t('Email address')}
                                    />
                                    <InputError
                                        className="mt-2"
                                        message={errors.email}
                                    />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="locale">{t('Language')}</Label>
                                    <Select
                                        name="locale"
                                        defaultValue={auth.user.locale}
                                    >
                                        <SelectTrigger className="mt-1 block w-full">
                                            <SelectValue placeholder={t('Select a language')} />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="en">{t('English')}</SelectItem>
                                            <SelectItem value="nl">{t('Dutch')}</SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <InputError
                                        className="mt-2"
                                        message={errors.locale}
                                    />
                                </div>

                                {mustVerifyEmail &&
                                    auth.user.email_verified_at === null && (
                                        <div>
                                            <p className="-mt-4 text-sm text-muted-foreground">
                                                Your email address is
                                                unverified.{' '}
                                                <Link
                                                    href={send()}
                                                    as="button"
                                                    className="text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current! dark:decoration-neutral-500"
                                                >
                                                    Click here to resend the
                                                    verification email.
                                                </Link>
                                            </p>

                                            {status ===
                                                'verification-link-sent' && (
                                                <div className="mt-2 text-sm font-medium text-green-600">
                                                    A new verification link has
                                                    been sent to your email
                                                    address.
                                                </div>
                                            )}
                                        </div>
                                    )}
                            </>
                        )}
                    </Form>
                </div>

                <div className="space-y-6 pt-8 border-t">
                    <HeadingSmall
                        title={t('Runner Profile')}
                        description={t('Help the AI coach personalize your training plans')}
                    />

                    <Form
                        {...ProfileController.update.form()}
                        options={{
                            preserveScroll: true,
                        }}
                        className="space-y-6"
                    >
                        {({ processing, recentlySuccessful, errors }) => (
                            <>
                                <div className="grid gap-4 md:grid-cols-2">
                                    <div className="grid gap-2">
                                        <Label htmlFor="age">{t('Age')}</Label>
                                        <Input
                                            id="age"
                                            type="number"
                                            className="mt-1 block w-full"
                                            defaultValue={auth.user.age}
                                            name="age"
                                            min="13"
                                            max="120"
                                            placeholder={t('Age')}
                                        />
                                        <InputError
                                            className="mt-2"
                                            message={errors.age}
                                        />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="weight_kg">{t('Weight (kg)')}</Label>
                                        <Input
                                            id="weight_kg"
                                            type="number"
                                            step="0.1"
                                            className="mt-1 block w-full"
                                            defaultValue={auth.user.weight_kg}
                                            name="weight_kg"
                                            min="30"
                                            max="300"
                                            placeholder={t('Weight in kg')}
                                        />
                                        <InputError
                                            className="mt-2"
                                            message={errors.weight_kg}
                                        />
                                    </div>
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="fitness_level">{t('Fitness Level')}</Label>
                                    <Select
                                        name="fitness_level"
                                        defaultValue={auth.user.fitness_level || 'intermediate'}
                                    >
                                        <SelectTrigger className="mt-1 block w-full">
                                            <SelectValue placeholder={t('Select your fitness level')} />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="beginner">{t('Beginner')}</SelectItem>
                                            <SelectItem value="intermediate">{t('Intermediate')}</SelectItem>
                                            <SelectItem value="advanced">{t('Advanced')}</SelectItem>
                                            <SelectItem value="elite">{t('Elite')}</SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <InputError
                                        className="mt-2"
                                        message={errors.fitness_level}
                                    />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="injury_history">{t('Injury History')}</Label>
                                    <Textarea
                                        id="injury_history"
                                        className="mt-1 block w-full min-h-20"
                                        defaultValue={auth.user.injury_history || ''}
                                        name="injury_history"
                                        placeholder={t('Describe any past injuries or concerns (e.g., knee issues, shin splints, etc.)')}
                                    />
                                    <InputError
                                        className="mt-2"
                                        message={errors.injury_history}
                                    />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="training_preferences">{t('Training Preferences')}</Label>
                                    <Textarea
                                        id="training_preferences"
                                        className="mt-1 block w-full min-h-20"
                                        defaultValue={auth.user.training_preferences || ''}
                                        name="training_preferences"
                                        placeholder={t('Any preferences for your training (e.g., prefer outdoor running, like interval training, etc.)')}
                                    />
                                    <InputError
                                        className="mt-2"
                                        message={errors.training_preferences}
                                    />
                                </div>

                                <div className="flex items-center gap-4">
                                    <Button
                                        disabled={processing}
                                        data-test="update-runner-profile-button"
                                    >
                                        {t('Save')}
                                    </Button>

                                    <Transition
                                        show={recentlySuccessful}
                                        enter="transition ease-in-out"
                                        enterFrom="opacity-0"
                                        leave="transition ease-in-out"
                                        leaveTo="opacity-0"
                                    >
                                        <p className="text-sm text-neutral-600">
                                            {t('Saved')}
                                        </p>
                                    </Transition>
                                </div>
                            </>
                        )}
                    </Form>
                </div>

                <DeleteUser />
            </SettingsLayout>
        </AppLayout>
    );
}
