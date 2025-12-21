import { Button } from '@/components/ui/button';
import AuthLayout from '@/layouts/auth-layout';
import { redirect as stravaRedirect } from '@/routes/auth/strava';
import { Head } from '@inertiajs/react';

export default function Login() {
    return (
        <AuthLayout
            title="Log in to your account"
            description="Login using your Strava account to continue"
        >
            <Head title="Log in" />

            <div className="flex flex-col gap-6">
                <Button
                    variant="outline"
                    className="w-full"
                    asChild
                >
                    <a href={stravaRedirect.url()}>
                        Log in with Strava
                    </a>
                </Button>
            </div>
        </AuthLayout>
    );
}
