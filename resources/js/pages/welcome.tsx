import { Head, Link } from '@inertiajs/react';
import AppLogoIcon from '@/components/app-logo-icon';
import { Button } from '@/components/ui/button';
import { login } from '@/routes';
import { useTranslations } from '@/hooks/use-translations';

export default function Welcome() {
    const { t } = useTranslations();

    return (
        <>
            <Head title={t('Welcome')} />
            <div className="flex min-h-screen flex-col items-center justify-center bg-background text-foreground">
                <div className="flex flex-col items-center space-y-6">
                    <div className="flex items-center justify-center rounded-full bg-sidebar-primary p-4">
                        <AppLogoIcon className="h-16 w-16 text-white" />
                    </div>
                    <h1 className="text-2xl font-bold">Lararun</h1>

                    <Link href={login.url()}>
                        <Button size="lg">{t('Log in')}</Button>
                    </Link>
                </div>
                <div className="absolute bottom-6 text-sm text-muted-foreground">
                    <Link href="/privacy" className="hover:underline">
                        Privacy Policy
                    </Link>
                </div>
            </div>
        </>
    );
}
