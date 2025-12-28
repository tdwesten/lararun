import { Button } from '@/components/ui/button';
import AuthLayout from '@/layouts/auth-layout';
import { redirect as stravaRedirect } from '@/routes/auth/strava';
import { Form, Head } from '@inertiajs/react';
import { store } from '@/routes/login';
import { register } from '@/routes';
import { request as forgotPassword } from '@/routes/password';
import InputError from '@/components/input-error';
import TextLink from '@/components/text-link';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { useTranslations } from '@/hooks/use-translations';

export default function Login({ status }: { status?: string }) {
    const { t } = useTranslations();

    return (
        <AuthLayout
            title={t('Log in to your account')}
            description={t('Enter your email and password to log in')}
        >
            <Head title={t('Log in')} />

            {status && (
                <div className="mb-4 text-center text-sm font-medium text-green-600">
                    {status}
                </div>
            )}

            <Form
                {...store.form()}
                resetOnSuccess={['password']}
                disableWhileProcessing
                className="flex flex-col gap-6"
            >
                {({ processing, errors }) => (
                    <>
                        <div className="grid gap-6">
                            <div className="grid gap-2">
                                <Label htmlFor="email">{t('Email address')}</Label>
                                <Input
                                    id="email"
                                    type="email"
                                    required
                                    autoFocus
                                    tabIndex={1}
                                    autoComplete="email"
                                    name="email"
                                    placeholder={t('email@example.com')}
                                />
                                <InputError message={errors.email} />
                            </div>

                            <div className="grid gap-2">
                                <div className="flex items-center justify-between">
                                    <Label htmlFor="password">{t('Password')}</Label>
                                    <TextLink
                                        href={forgotPassword()}
                                        className="text-sm"
                                        tabIndex={5}
                                    >
                                        {t('Forgot your password?')}
                                    </TextLink>
                                </div>
                                <Input
                                    id="password"
                                    type="password"
                                    required
                                    tabIndex={2}
                                    autoComplete="current-password"
                                    name="password"
                                    placeholder={t('Password')}
                                />
                                <InputError message={errors.password} />
                            </div>

                            <div className="flex items-center gap-2">
                                <Checkbox id="remember" name="remember" tabIndex={3} />
                                <Label htmlFor="remember" className="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70">
                                    {t('Remember me')}
                                </Label>
                            </div>

                            <Button
                                type="submit"
                                className="mt-2 w-full"
                                tabIndex={4}
                                data-test="login-button"
                            >
                                {processing && <Spinner />}
                                {t('Log in')}
                            </Button>
                        </div>

                        <div className="relative">
                            <div className="absolute inset-0 flex items-center">
                                <span className="w-full border-t" />
                            </div>
                            <div className="relative flex justify-center text-xs uppercase">
                                <span className="bg-background px-2 text-muted-foreground">
                                    {t('Or continue with')}
                                </span>
                            </div>
                        </div>

                        <Button
                            variant="outline"
                            className="w-full"
                            asChild
                        >
                            <a href={stravaRedirect.url()}>
                                {t('Log in with Strava')}
                            </a>
                        </Button>

                        <div className="text-center text-sm text-muted-foreground">
                            {t("Don't have an account?")}{' '}
                            <TextLink href={register()} tabIndex={6}>
                                {t('Register')}
                            </TextLink>
                        </div>
                    </>
                )}
            </Form>
        </AuthLayout>
    );
}
