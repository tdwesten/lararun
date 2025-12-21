import LastRunWidget from '@/components/last-run-widget';
import ObjectiveWidget from '@/components/objective-widget';
import TodayRecommendationWidget from '@/components/today-recommendation-widget';
import RecentActivities from '@/components/recent-activities';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import { Activity, BreadcrumbItem, Objective, DailyRecommendation } from '@/types';
import { Head } from '@inertiajs/react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
];

export default function Dashboard({
    activities,
    currentObjective,
    todayRecommendation
}: {
    activities: Activity[];
    currentObjective: Objective | null;
    todayRecommendation: DailyRecommendation | null;
}) {
    const latestActivity = activities.length > 0 ? activities[0] : null;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Dashboard" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <div className="grid auto-rows-min gap-4 md:grid-cols-3">
                    <ObjectiveWidget objective={currentObjective} />
                    <TodayRecommendationWidget recommendation={todayRecommendation} />
                    <LastRunWidget activity={latestActivity} />
                </div>
                <div className="flex-1">
                    <RecentActivities activities={activities} />
                </div>
            </div>
        </AppLayout>
    );
}
