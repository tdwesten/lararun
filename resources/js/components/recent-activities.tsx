import { Activity } from '@/types';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { format } from 'date-fns';
import { Activity as ActivityIcon, Clock, MapPin, ChevronRight } from 'lucide-react';
import { cn } from '@/lib/utils';
import { Link } from '@inertiajs/react';
import { show } from '@/routes/activities';
import { Button } from './ui/button';
import { index } from '@/routes/activities';

interface RecentActivitiesProps {
    activities: Activity[];
}

export default function RecentActivities({ activities }: RecentActivitiesProps) {
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
        <Card className="flex flex-col flex-1">
            <CardHeader className="flex flex-row items-center justify-between">
                <div>
                    <CardTitle>Recent Activities</CardTitle>
                    <CardDescription>Your last 10 runs from Strava.</CardDescription>
                </div>
                {activities.length > 0 && (
                    <Button variant="ghost" size="sm" asChild>
                        <Link href={index().url}>View All</Link>
                    </Button>
                )}
            </CardHeader>
            <CardContent>
                {activities.length === 0 ? (
                    <div className="flex flex-col items-center justify-center py-8 text-center">
                        <ActivityIcon className="h-12 w-12 text-muted-foreground/50 mb-4" />
                        <p className="text-muted-foreground">No activities found yet.</p>
                        <p className="text-sm text-muted-foreground/70">Activities are synced automatically every hour.</p>
                    </div>
                ) : (
                    <div className="space-y-4">
                        {activities.map((activity) => (
                            <Link
                                key={activity.id}
                                href={show(activity.id).url}
                                className="flex items-center justify-between p-3 rounded-lg border bg-card text-card-foreground shadow-sm relative overflow-hidden hover:border-primary/50 transition-colors group"
                            >
                                <div className={cn(
                                    "absolute left-0 top-0 bottom-0 w-1",
                                    getIntensityColor(activity.intensity_score)
                                )} />
                                <div className="flex items-center gap-4 pl-2">
                                    <div className="bg-primary/10 p-2 rounded-full group-hover:bg-primary/20 transition-colors">
                                        <ActivityIcon className="h-5 w-5 text-primary" />
                                    </div>
                                    <div>
                                        <h4 className="font-semibold text-sm leading-none mb-1 group-hover:text-primary transition-colors">{activity.name}</h4>
                                        <div className="flex items-center gap-3 text-xs text-muted-foreground mb-1.5">
                                            <span className="flex items-center gap-1">
                                                <MapPin className="h-3 w-3" />
                                                {formatDistance(activity.distance)}
                                            </span>
                                            <span className="flex items-center gap-1">
                                                <Clock className="h-3 w-3" />
                                                {formatDuration(activity.moving_time)}
                                            </span>
                                            <span>
                                                {formatPace(activity.distance, activity.moving_time)}
                                            </span>
                                        </div>
                                        {activity.short_evaluation && (
                                            <p className="text-xs text-muted-foreground border-l-2 border-primary/20 pl-2 py-0.5 line-clamp-1">
                                                {activity.short_evaluation}
                                            </p>
                                        )}
                                    </div>
                                </div>
                                <div className="flex items-center gap-4">
                                    <div className="text-right">
                                        <div className="text-xs text-muted-foreground">
                                            {format(new Date(activity.start_date), 'MMM d, yyyy')}
                                        </div>
                                        <div className="flex items-center justify-end gap-2 mt-1">
                                            <div className="text-xs font-medium text-primary">
                                                {activity.type}
                                            </div>
                                            {activity.intensity_score && (
                                                <div className="text-[10px] px-1.5 py-0.5 rounded-full bg-muted font-mono text-muted-foreground">
                                                    {Math.round(parseFloat(activity.intensity_score))}
                                                </div>
                                            )}
                                        </div>
                                    </div>
                                    <ChevronRight className="h-4 w-4 text-muted-foreground group-hover:text-primary transition-colors" />
                                </div>
                            </Link>
                        ))}
                    </div>
                )}
            </CardContent>
        </Card>
    );
}
