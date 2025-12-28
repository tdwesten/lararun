import { Button } from '@/components/ui/button';
import AuthLayout from '@/layouts/auth-layout';
import { redirect as stravaRedirect } from '@/routes/auth/strava';
import { Head, Link, usePage } from '@inertiajs/react';
import { logout } from '@/routes';
import { useTranslations } from '@/hooks/use-translations';

export default function StravaConnect() {
    const { t } = useTranslations();
    const { flash } = usePage<{ flash: { error?: string } }>().props;

    return (
        <AuthLayout
            title={t('Connect Strava')}
            description={t('To use Lararun, you need to connect your Strava account.')}
        >
            <Head title={t('Connect Strava')} />

            {flash.error && (
                <div className="mb-4 text-center text-sm font-medium text-destructive">
                    {flash.error}
                </div>
            )}

            <div className="flex flex-col gap-4">
                <Button
                    className="w-full"
                    asChild
                >
                    <a href={stravaRedirect.url()}>
                        {t('Connect with Strava')}
                    </a>
                </Button>

                <div className="text-center">
                    <Link
                        href={logout.url()}
                        method="post"
                        as="button"
                        className="text-sm text-muted-foreground underline hover:text-foreground"
                    >
                        {t('Log out')}
                    </Link>
                </div>
            </div>
        </AuthLayout>
    );
}
