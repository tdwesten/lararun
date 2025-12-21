import { Activity } from '@/types';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { format } from 'date-fns';
import { Activity as ActivityIcon, Clock, MapPin, Quote, Zap } from 'lucide-react';
import { cn } from '@/lib/utils';

interface LastRunWidgetProps {
    activity: Activity | null;
}

export default function LastRunWidget({ activity }: LastRunWidgetProps) {
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
        if (numScore < 40) return 'text-emerald-500'; // Easy
        if (numScore < 80) return 'text-sky-500';    // Moderate
        if (numScore < 120) return 'text-amber-500';  // Heavy
        return 'text-rose-500';                      // Very Heavy
    };

    if (!activity) {
        return (
            <Card className="flex h-full flex-col justify-between border-dashed">
                <CardHeader>
                    <CardTitle className="text-lg">Last Run</CardTitle>
                    <CardDescription>No activities found yet.</CardDescription>
                </CardHeader>
                <CardContent className="flex items-center justify-center py-6">
                    <ActivityIcon className="h-12 w-12 text-muted-foreground/20" />
                </CardContent>
            </Card>
        );
    }

    return (
        <Card className="flex h-full flex-col justify-between overflow-hidden relative">
             <div className={cn(
                "absolute top-0 right-0 p-4 opacity-10",
                getIntensityColor(activity.intensity_score)
            )}>
                <ActivityIcon className="h-24 w-24" />
            </div>
            <CardHeader className="pb-2">
                <div className="flex items-center justify-between">
                    <CardTitle className="text-lg">Last Run</CardTitle>
                    <div className="text-xs text-muted-foreground bg-muted px-2 py-1 rounded-md">
                        {format(new Date(activity.start_date), 'MMM d, yyyy')}
                    </div>
                </div>
                <CardDescription className="line-clamp-1">{activity.name}</CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
                <div className="grid grid-cols-3 gap-2">
                    <div className="flex flex-col">
                        <span className="text-[10px] uppercase text-muted-foreground font-semibold flex items-center gap-1">
                            <MapPin className="h-3 w-3" /> Distance
                        </span>
                        <span className="text-sm font-bold">{formatDistance(activity.distance)}</span>
                    </div>
                    <div className="flex flex-col">
                        <span className="text-[10px] uppercase text-muted-foreground font-semibold flex items-center gap-1">
                            <Clock className="h-3 w-3" /> Time
                        </span>
                        <span className="text-sm font-bold">{formatDuration(activity.moving_time)}</span>
                    </div>
                    <div className="flex flex-col">
                        <span className="text-[10px] uppercase text-muted-foreground font-semibold flex items-center gap-1">
                            <Zap className="h-3 w-3" /> Pace
                        </span>
                        <span className="text-sm font-bold">{formatPace(activity.distance, activity.moving_time)}</span>
                    </div>
                </div>

                {activity.short_evaluation && (
                    <div className="bg-primary/5 border border-primary/10 rounded-lg p-3 relative mt-2">
                        <Quote className="h-3 w-3 text-primary/40 absolute -top-1.5 -left-1.5 bg-background rounded-full" />
                        <p className="text-sm italic text-foreground leading-relaxed">
                            {activity.short_evaluation}
                        </p>
                    </div>
                )}
            </CardContent>
        </Card>
    );
}
