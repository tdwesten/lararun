import { Activity, BreadcrumbItem } from '@/types';
import { Card, CardContent } from '@/components/ui/card';
import { format } from 'date-fns';
import { Activity as ActivityIcon, Clock, MapPin, ChevronRight, ChevronLeft } from 'lucide-react';
import { cn } from '@/lib/utils';
import AppLayout from '@/layouts/app-layout';
import { Head, Link } from '@inertiajs/react';
import { show, index } from '@/routes/activities';

interface PaginatedActivities {
    data: Activity[];
    current_page: number;
    last_page: number;
    prev_page_url: string | null;
    next_page_url: string | null;
    links: { url: string | null; label: string; active: boolean }[];
}

interface ActivitiesIndexProps {
    activities: PaginatedActivities;
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Activities',
        href: index().url,
    },
];

export default function ActivitiesIndex({ activities }: ActivitiesIndexProps) {
    const formatDistance = (meters: number) => {
        return (meters / 1000).toFixed(2) + ' km';
    };

    const formatDuration = (seconds: number) => {
        const minutes = Math.floor(seconds / 60);
        const remainingSeconds = seconds % 60;
        return `${minutes}:${remainingSeconds.toString().padStart(2, '0')}`;
    };

    const formatPace = (distanceMeters: number, timeSeconds: number) => {
        if (distanceMeters === 0) return '0:00 /km';
        const distanceKm = distanceMeters / 1000;
        const paceMinPerKm = (timeSeconds / 60) / distanceKm;
        const minutes = Math.floor(paceMinPerKm);
        const seconds = Math.floor((paceMinPerKm - minutes) * 60);
        return `${minutes}:${seconds.toString().padStart(2, '0')} /km`;
    };

    const getIntensityColor = (score: string | null) => {
        if (!score) return 'bg-muted';
        const numScore = parseFloat(score);
        if (numScore < 40) return 'bg-emerald-500'; // Easy
        if (numScore < 80) return 'bg-sky-500';    // Moderate
        if (numScore < 120) return 'bg-amber-500';  // Heavy
        return 'bg-rose-500';                      // Very Heavy
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Activities" />

            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex items-center justify-between">
                    <div>
                        <h2 className="text-2xl font-bold tracking-tight">Activities</h2>
                        <p className="text-muted-foreground">Your synced running activities from Strava.</p>
                    </div>
                </div>

                <div className="grid gap-4">
                    {activities.data.length === 0 ? (
                        <Card>
                            <CardContent className="flex flex-col items-center justify-center py-12 text-center">
                                <ActivityIcon className="h-12 w-12 text-muted-foreground/50 mb-4" />
                                <p className="text-muted-foreground font-medium">No activities found yet.</p>
                                <p className="text-sm text-muted-foreground/70">Activities are synced automatically from your Strava account.</p>
                            </CardContent>
                        </Card>
                    ) : (
                        <>
                            <div className="grid gap-4">
                                {activities.data.map((activity) => (
                                    <Link
                                        key={activity.id}
                                        href={show(activity.id).url}
                                        className="block group"
                                    >
                                        <Card className="transition-colors group-hover:border-primary/50 relative overflow-hidden">
                                            <div className={cn(
                                                "absolute left-0 top-0 bottom-0 w-1",
                                                getIntensityColor(activity.intensity_score)
                                            )} />
                                            <CardContent className="p-4 flex items-center justify-between">
                                                <div className="flex items-center gap-4 pl-2">
                                                    <div className="bg-primary/10 p-2 rounded-full group-hover:bg-primary/20 transition-colors">
                                                        <ActivityIcon className="h-5 w-5 text-primary" />
                                                    </div>
                                                    <div>
                                                        <h4 className="font-semibold text-base mb-1 group-hover:text-primary transition-colors">{activity.name}</h4>
                                                        <div className="flex items-center gap-4 text-xs text-muted-foreground">
                                                            <span className="flex items-center gap-1">
                                                                <MapPin className="h-3.3 w-3.3" />
                                                                {formatDistance(activity.distance)}
                                                            </span>
                                                            <span className="flex items-center gap-1">
                                                                <Clock className="h-3.3 w-3.3" />
                                                                {formatDuration(activity.moving_time)}
                                                            </span>
                                                            <span className="font-medium">
                                                                {formatPace(activity.distance, activity.moving_time)}
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div className="flex items-center gap-6">
                                                    <div className="text-right hidden sm:block">
                                                        <div className="text-sm font-medium">
                                                            {format(new Date(activity.start_date), 'EEEE, MMM d')}
                                                        </div>
                                                        <div className="text-xs text-muted-foreground">
                                                            {format(new Date(activity.start_date), 'h:mm a')}
                                                        </div>
                                                    </div>
                                                    <ChevronRight className="h-5 w-5 text-muted-foreground group-hover:text-primary transition-colors" />
                                                </div>
                                            </CardContent>
                                        </Card>
                                    </Link>
                                ))}
                            </div>

                            {activities.last_page > 1 && (
                                <div className="flex items-center justify-center gap-2 mt-4">
                                    <Link
                                        href={activities.prev_page_url || '#'}
                                        className={cn(
                                            "p-2 rounded-md border text-sm flex items-center gap-1 transition-colors",
                                            !activities.prev_page_url ? "opacity-50 cursor-not-allowed" : "hover:bg-muted"
                                        )}
                                        only={['activities']}
                                        preserveScroll
                                    >
                                        <ChevronLeft className="h-4 w-4" />
                                        Previous
                                    </Link>

                                    <div className="text-sm text-muted-foreground px-4">
                                        Page {activities.current_page} of {activities.last_page}
                                    </div>

                                    <Link
                                        href={activities.next_page_url || '#'}
                                        className={cn(
                                            "p-2 rounded-md border text-sm flex items-center gap-1 transition-colors",
                                            !activities.next_page_url ? "opacity-50 cursor-not-allowed" : "hover:bg-muted"
                                        )}
                                        only={['activities']}
                                        preserveScroll
                                    >
                                        Next
                                        <ChevronRight className="h-4 w-4" />
                                    </Link>
                                </div>
                            )}
                        </>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
