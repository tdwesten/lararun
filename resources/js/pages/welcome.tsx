import { Head, Link } from '@inertiajs/react';
import AppLogoIcon from '@/components/app-logo-icon';
import { Button } from '@/components/ui/button';
import { PageProps } from '@/types';
import { login } from '@/routes';

export default function Welcome({ auth }: PageProps) {
    return (
        <>
            <Head title="Welcome" />
            <div className="flex min-h-screen flex-col items-center justify-center bg-background text-foreground">
                <div className="flex flex-col items-center space-y-6">
                    <div className="flex items-center justify-center rounded-full bg-sidebar-primary p-4">
                        <AppLogoIcon className="h-16 w-16 text-white" />
                    </div>
                    <h1 className="text-2xl font-bold">Lararun</h1>

                    <Link href={login.url()}>
                        <Button size="lg">
                            Log in
                        </Button>
                    </Link>
                </div>
            </div>
        </>
    );
}

