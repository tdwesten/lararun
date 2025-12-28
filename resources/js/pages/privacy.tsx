import { Head, Link } from '@inertiajs/react';
import AppLogoIcon from '@/components/app-logo-icon';

export default function Privacy() {
    return (
        <>
            <Head title="Privacy Policy" />
            <div className="flex min-h-screen flex-col bg-background text-foreground">
                <header className="border-b px-6 py-4">
                    <div className="mx-auto flex max-w-4xl items-center justify-between">
                        <Link href="/" className="flex items-center gap-2">
                            <AppLogoIcon className="h-8 w-8 text-sidebar-primary" />
                            <span className="text-xl font-bold tracking-tight">Lararun</span>
                        </Link>
                    </div>
                </header>

                <main className="mx-auto flex-1 max-w-4xl px-6 py-12">
                    <h1 className="mb-8 text-4xl font-extrabold tracking-tight lg:text-5xl">Privacy Policy</h1>

                    <div className="prose prose-neutral dark:prose-invert max-w-none space-y-6">
                        <section>
                            <h2 className="text-2xl font-bold">1. Introduction</h2>
                            <p className="mt-2 text-muted-foreground">
                                Welcome to Lararun, a service provided by Codesmiths ("we", "us", or "our"). We value your privacy and are committed to protecting your personal data. This privacy policy explains how we handle your information when you use our service.
                            </p>
                        </section>

                        <section>
                            <h2 className="text-2xl font-bold">2. Data We Collect</h2>
                            <p className="mt-2 text-muted-foreground">
                                When you connect your Strava account, we collect information about your running activities, including distance, pace, date, duration, and heart rate data if available. We also collect your email address for account management and to send you daily recommendations.
                            </p>
                        </section>

                        <section>
                            <h2 className="text-2xl font-bold">3. How We Use Your Data</h2>
                            <p className="mt-2 text-muted-foreground">
                                We use your Strava activity data to generate personalized AI training recommendations. Your email is used to send you these daily recommendations and important service updates. We use Lettermint B.V. as our service provider to deliver these emails to you.
                            </p>
                        </section>

                        <section>
                            <h2 className="text-2xl font-bold">4. AI Processing</h2>
                            <p className="mt-2 text-muted-foreground">
                                We use AI services (like OpenAI via Prism) to process your activity data and generate training plans. Your data is processed securely and only used for the purpose of providing you with coaching recommendations.
                            </p>
                        </section>

                        <section>
                            <h2 className="text-2xl font-bold">5. Data Hosting</h2>
                            <p className="mt-2 text-muted-foreground">
                                Our services are hosted on DigitalOcean. Your data is stored on their secure servers, ensuring high availability and protection.
                            </p>
                        </section>

                        <section>
                            <h2 className="text-2xl font-bold">6. Your Rights</h2>
                            <p className="mt-2 text-muted-foreground">
                                You can disconnect your Strava account or delete your Lararun account at any time through the settings page. Deleting your account will remove your personal data from our systems.
                            </p>
                        </section>

                        <section>
                            <h2 className="text-2xl font-bold">7. Contact Us</h2>
                            <p className="mt-2 text-muted-foreground">
                                If you have any questions about this privacy policy or our data practices, please contact us at:
                            </p>
                            <div className="mt-4 text-muted-foreground">
                                <p>Codesmiths</p>
                                <p>Email: info@codesmiths.nl</p>
                                <p>Website: codesmiths.nl</p>
                                <p>Attn: Thomas van der Westen</p>
                                <p>Thorbeckestraat 45</p>
                                <p>6971DB Brummen</p>
                                <p>The Netherlands</p>
                            </div>
                        </section>
                    </div>
                </main>

                <footer className="border-t px-6 py-8">
                    <div className="mx-auto max-w-4xl text-center text-sm text-muted-foreground">
                        <p>&copy; {new Date().getFullYear()} Codesmiths. All rights reserved.</p>
                    </div>
                </footer>
            </div>
        </>
    );
}
