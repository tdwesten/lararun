import { Activity, BreadcrumbItem, DailyRecommendation } from '@/types';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { format } from 'date-fns';
import { Clock, MapPin, Zap, Quote, Coffee, Dumbbell, Info, Sparkles, ChevronLeft, Calendar } from 'lucide-react';
import { cn } from '@/lib/utils';
import AppLayout from '@/layouts/app-layout';
import { Head, Link, usePoll } from '@inertiajs/react';
import { index } from '@/routes/activities';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import ReactMarkdown from 'react-markdown';
import { Spinner } from '@/components/ui/spinner';

interface ActivityShowProps {
    activity: Activity & {
        z1_time?: number;
        z2_time?: number;
        z3_time?: number;
        z4_time?: number;
        z5_time?: number;
    };
    recommendation: DailyRecommendation | null;
}

export default function ActivityShow({ activity, recommendation }: ActivityShowProps) {
    const isEvaluating = !activity.short_evaluation || !activity.extended_evaluation;

    usePoll(3000, {
        only: ['activity'],
        enabled: isEvaluating,
    });
    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Activities',
            href: index().url,
        },
        {
            title: activity.name,
            href: '',
        },
    ];

    const formatDistance = (meters: number) => {
        return (meters / 1000).toFixed(2) + ' km';
    };

    const formatDuration = (seconds: number) => {
        const hours = Math.floor(seconds / 3600);
        const minutes = Math.floor((seconds % 3600) / 60);
        const remainingSeconds = seconds % 60;

        if (hours > 0) {
            return `${hours}:${minutes.toString().padStart(2, '0')}:${remainingSeconds.toString().padStart(2, '0')}`;
        }
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

    const getIntensityText = (score: string | null) => {
        if (!score) return 'N/A';
        const numScore = parseFloat(score);
        if (numScore < 40) return 'Easy';
        if (numScore < 80) return 'Moderate';
        if (numScore < 120) return 'Heavy';
        return 'Very Heavy';
    };

    const getRecommendationIcon = (type: string) => {
        const t = type.toLowerCase();
        if (t.includes('rest') || t.includes('recovery')) return <Coffee className="h-5 w-5 text-sky-500" />;
        if (t.includes('interval') || t.includes('speed')) return <Zap className="h-5 w-5 text-amber-500" />;
        if (t.includes('long')) return <Dumbbell className="h-5 w-5 text-emerald-500" />;
        return <Dumbbell className="h-5 w-5 text-primary" />;
    };

    const zones = [
        { name: 'Zone 1', time: activity.z1_time || 0, color: 'bg-emerald-400' },
        { name: 'Zone 2', time: activity.z2_time || 0, color: 'bg-emerald-600' },
        { name: 'Zone 3', time: activity.z3_time || 0, color: 'bg-amber-500' },
        { name: 'Zone 4', time: activity.z4_time || 0, color: 'bg-orange-600' },
        { name: 'Zone 5', time: activity.z5_time || 0, color: 'bg-rose-600' },
    ];

    const totalZoneTime = zones.reduce((acc, zone) => acc + zone.time, 0);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={activity.name} />

            <div className="flex h-full flex-1 flex-col gap-6 p-4 md:p-8">
                <div className="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div className="space-y-1">
                        <div className="flex items-center gap-2">
                            <Button variant="ghost" size="icon" asChild className="-ml-2 h-8 w-8">
                                <Link href={index().url}>
                                    <ChevronLeft className="h-4 w-4" />
                                </Link>
                            </Button>
                            <h2 className="text-3xl font-bold tracking-tight">{activity.name}</h2>
                        </div>
                        <div className="flex items-center gap-2 text-muted-foreground ml-8">
                            <Calendar className="h-4 w-4" />
                            <span>{format(new Date(activity.start_date), 'EEEE, MMMM d, yyyy')}</span>
                            <span>•</span>
                            <span>{format(new Date(activity.start_date), 'h:mm a')}</span>
                        </div>
                    </div>
                    <div className="flex items-center gap-2 ml-8 md:ml-0">
                        <Badge variant="outline" className="text-sm py-1 px-3">
                            {activity.type}
                        </Badge>
                        {activity.intensity_score && (
                            <Badge className={cn("text-white border-none", getIntensityColor(activity.intensity_score))}>
                                {getIntensityText(activity.intensity_score)} Intensity ({Math.round(parseFloat(activity.intensity_score))})
                            </Badge>
                        )}
                    </div>
                </div>

                <div className="grid gap-6 md:grid-cols-3">
                    {/* Primary Stats */}
                    <Card className="md:col-span-2">
                        <CardHeader>
                            <CardTitle>Activity Summary</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="grid grid-cols-2 sm:grid-cols-4 gap-8">
                                <div className="space-y-1">
                                    <p className="text-sm font-medium text-muted-foreground flex items-center gap-2">
                                        <MapPin className="h-4 w-4" /> Distance
                                    </p>
                                    <p className="text-2xl font-bold">{formatDistance(activity.distance)}</p>
                                </div>
                                <div className="space-y-1">
                                    <p className="text-sm font-medium text-muted-foreground flex items-center gap-2">
                                        <Clock className="h-4 w-4" /> Moving Time
                                    </p>
                                    <p className="text-2xl font-bold">{formatDuration(activity.moving_time)}</p>
                                </div>
                                <div className="space-y-1">
                                    <p className="text-sm font-medium text-muted-foreground flex items-center gap-2">
                                        <Zap className="h-4 w-4" /> Avg Pace
                                    </p>
                                    <p className="text-2xl font-bold">{formatPace(activity.distance, activity.moving_time)}</p>
                                </div>
                                <div className="space-y-1">
                                    <p className="text-sm font-medium text-muted-foreground flex items-center gap-2">
                                        <Clock className="h-4 w-4" /> Elapsed Time
                                    </p>
                                    <p className="text-2xl font-bold">{formatDuration(activity.elapsed_time)}</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Today's Recommendation comparison */}
                    <Card className={cn("border-primary/20 relative overflow-hidden", !recommendation && "border-dashed opacity-60")}>
                        <div className="absolute top-0 right-0 p-4 opacity-5">
                            <Sparkles className="h-24 w-24" />
                        </div>
                        <CardHeader className="pb-2">
                            <div className="flex items-center justify-between">
                                <CardTitle className="text-lg">Planned Training</CardTitle>
                                {recommendation ? getRecommendationIcon(recommendation.type) : <Info className="h-5 w-5 text-muted-foreground" />}
                            </div>
                            {recommendation && <CardDescription className="font-semibold text-primary">{recommendation.type}</CardDescription>}
                        </CardHeader>
                        <CardContent>
                            {recommendation ? (
                                <div className="space-y-4">
                                    <div className="space-y-1">
                                        <h4 className="font-bold text-base leading-tight">{recommendation.title}</h4>
                                        <p className="text-sm text-muted-foreground line-clamp-3">
                                            {recommendation.description}
                                        </p>
                                    </div>
                                    <div className="bg-muted/50 rounded-lg p-3 border border-border/50">
                                        <div className="flex items-start gap-2">
                                            <Info className="h-4 w-4 text-primary/60 mt-0.5 shrink-0" />
                                            <p className="text-xs text-muted-foreground italic leading-relaxed line-clamp-2">
                                                {recommendation.reasoning}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            ) : (
                                <div className="flex flex-col items-center justify-center py-8 text-center">
                                    <p className="text-sm text-muted-foreground">No specific recommendation found for this day.</p>
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </div>

                <div className="grid gap-6 md:grid-cols-2">
                    {/* Evaluation */}
                    <Card className="h-fit">
                        <CardHeader>
                            <div className="flex items-center justify-between">
                                <CardTitle>Coach AI Evaluation</CardTitle>
                                <Quote className="h-5 w-5 text-primary" />
                            </div>
                        </CardHeader>
                        <CardContent className="space-y-6">
                            {isEvaluating ? (
                                <div className="py-12 flex flex-col items-center justify-center space-y-4 border rounded-lg border-dashed bg-primary/5">
                                    <Spinner className="h-8 w-8 text-primary" />
                                    <div className="text-center space-y-1">
                                        <p className="font-medium text-foreground">Generating AI Evaluation</p>
                                        <p className="text-sm text-muted-foreground">Your coach is analyzing your run. This usually takes 10-20 seconds.</p>
                                    </div>
                                </div>
                            ) : (
                                <>
                                    {activity.short_evaluation && (
                                        <div className="bg-primary/5 border border-primary/10 rounded-lg p-4 relative">
                                            <p className="text-lg italic font-medium text-foreground leading-relaxed">
                                                "{activity.short_evaluation}"
                                            </p>
                                        </div>
                                    )}

                                    {activity.extended_evaluation ? (
                                        <div className="space-y-2">
                                            <h4 className="font-semibold text-sm uppercase text-muted-foreground tracking-wider">Detailed Analysis</h4>
                                            <div className="prose prose-sm dark:prose-invert max-w-none text-foreground leading-relaxed">
                                                <ReactMarkdown>{activity.extended_evaluation}</ReactMarkdown>
                                            </div>
                                        </div>
                                    ) : (
                                        <div className="py-8 text-center border rounded-lg border-dashed">
                                            <p className="text-muted-foreground">Detailed evaluation not available for this activity.</p>
                                        </div>
                                    )}
                                </>
                            )}
                        </CardContent>
                    </Card>

                    {/* Zone Data */}
                    <Card className="h-fit">
                        <CardHeader>
                            <CardTitle>Heart Rate Zones</CardTitle>
                            <CardDescription>Time spent in each effort zone.</CardDescription>
                        </CardHeader>
                        <CardContent>
                            {!activity.zone_data_available ? (
                                <div className="flex flex-col items-center justify-center py-12 text-center">
                                    <Info className="h-12 w-12 text-muted-foreground/20 mb-4" />
                                    <p className="text-muted-foreground font-medium">No heart rate data available.</p>
                                    <p className="text-sm text-muted-foreground/70">Ensure your device records heart rate and Strava permissions are correct.</p>
                                </div>
                            ) : (
                                <div className="space-y-6">
                                    <div className="space-y-4">
                                        {zones.map((zone) => (
                                            <div key={zone.name} className="space-y-1.5">
                                                <div className="flex items-center justify-between text-sm">
                                                    <span className="font-medium">{zone.name}</span>
                                                    <span className="text-muted-foreground">{formatDuration(zone.time)} ({totalZoneTime > 0 ? Math.round((zone.time / totalZoneTime) * 100) : 0}%)</span>
                                                </div>
                                                <div className="h-2 w-full bg-muted rounded-full overflow-hidden">
                                                    <div
                                                        className={cn("h-full", zone.color)}
                                                        style={{ width: `${totalZoneTime > 0 ? (zone.time / totalZoneTime) * 100 : 0}%` }}
                                                    />
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                    <div className="pt-4 border-t text-xs text-muted-foreground">
                                        Total heart rate recorded: {formatDuration(totalZoneTime)}
                                    </div>
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}
