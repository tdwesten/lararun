import LastRunWidget from '@/components/last-run-widget';
import ObjectiveWidget from '@/components/objective-widget';
import TodayRecommendationWidget from '@/components/today-recommendation-widget';
import RecentActivities from '@/components/recent-activities';
import ActivityStreakWidget from '@/components/activity-streak-widget';
import RecoveryScoreWidget from '@/components/recovery-score-widget';
import PersonalRecordsWidget, { PersonalRecord } from '@/components/personal-records-widget';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import { Activity, BreadcrumbItem, Objective, DailyRecommendation } from '@/types';
import { Head } from '@inertiajs/react';
import { useTranslations } from '@/hooks/use-translations';

export default function Dashboard({
    activities,
    currentObjective,
    todayRecommendation,
    activityStreak,
    recoveryScore,
    personalRecords,
}: {
    activities: Activity[];
    currentObjective: Objective | null;
    todayRecommendation: DailyRecommendation | null;
    activityStreak: number;
    recoveryScore: number;
    personalRecords: PersonalRecord[];
}) {
    const { t } = useTranslations();
    const latestActivity = activities.length > 0 ? activities[0] : null;

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: t('Dashboard'),
            href: dashboard().url,
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={t('Dashboard')} />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <div className="grid auto-rows-min gap-4 md:grid-cols-3 lg:grid-cols-5">
                    <ObjectiveWidget objective={currentObjective} />
                    <TodayRecommendationWidget recommendation={todayRecommendation} />
                    <LastRunWidget activity={latestActivity} />
                    <ActivityStreakWidget streak={activityStreak} />
                    <RecoveryScoreWidget recoveryScore={recoveryScore} />
                </div>
                <div className="grid gap-4 md:grid-cols-3">
                    <div className="md:col-span-2">
                        <RecentActivities activities={activities} />
                    </div>
                    <div className="md:col-span-1">
                        <PersonalRecordsWidget records={personalRecords} />
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
