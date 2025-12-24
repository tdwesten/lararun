import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import { index } from '@/routes/objectives';
import { BreadcrumbItem, DailyRecommendation, Objective, RunningStats } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, Calendar, Info, Target, Activity, TrendingUp, Clock, Zap, Sparkles } from 'lucide-react';
import { cn } from '@/lib/utils';
import { enhanceTrainings } from '@/actions/App/Http/Controllers/ObjectiveController';

export default function Show({ objective, runningStats }: { objective: Objective; runningStats: RunningStats }) {
    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Objectives',
            href: index().url,
        },
        {
            title: objective.type,
            href: '#',
        },
    ];

    const today = new Date().toISOString().split('T')[0];

    const { data, setData, post, processing, errors, wasSuccessful } = useForm({
        enhancement_prompt: objective.enhancement_prompt || '',
    });

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Objective: ${objective.type}`} />

            <div className="flex flex-1 flex-col gap-4 p-4">
                <div className="flex items-center gap-4">
                    <Button variant="ghost" size="icon" asChild>
                        <Link href={index().url}>
                            <ArrowLeft className="h-4 w-4" />
                        </Link>
                    </Button>
                    <div>
                        <h2 className="text-2xl font-bold tracking-tight">{objective.type}</h2>
                        <p className="text-muted-foreground">
                            Target Date: {new Date(objective.target_date).toLocaleDateString()}
                        </p>
                    </div>
                    <Badge className="ml-auto" variant={objective.status === 'active' ? 'default' : 'secondary'}>
                        {objective.status}
                    </Badge>
                </div>

                <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4 mb-4">
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Total Distance</CardTitle>
                            <Activity className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{runningStats.total_distance_km} km</div>
                            <p className="text-xs text-muted-foreground mt-1">
                                {runningStats.total_runs} {runningStats.total_runs === 1 ? 'run' : 'runs'} total
                            </p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Total Time</CardTitle>
                            <Clock className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{runningStats.total_time_formatted}</div>
                            <p className="text-xs text-muted-foreground mt-1">Time spent running</p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Average Pace</CardTitle>
                            <TrendingUp className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">
                                {runningStats.average_pace_per_km || 'N/A'}
                            </div>
                            <p className="text-xs text-muted-foreground mt-1">Per kilometer</p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Best Pace</CardTitle>
                            <Zap className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">
                                {runningStats.best_pace_per_km || 'N/A'}
                            </div>
                            {runningStats.fastest_run && (
                                <p className="text-xs text-muted-foreground mt-1">
                                    {runningStats.fastest_run.distance_km} km on {new Date(runningStats.fastest_run.date).toLocaleDateString()}
                                </p>
                            )}
                            {!runningStats.fastest_run && (
                                <p className="text-xs text-muted-foreground mt-1">No runs yet</p>
                            )}
                        </CardContent>
                    </Card>
                </div>

                <div className="grid gap-4 md:grid-cols-3">
                    <Card className="md:col-span-1">
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2 text-sm">
                                <Target className="h-4 w-4 text-primary" />
                                Goal Details
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            {objective.description && (
                                <div>
                                    <div className="text-xs font-semibold uppercase text-muted-foreground">Description</div>
                                    <p className="mt-1 text-sm">{objective.description}</p>
                                </div>
                            )}
                            <div>
                                <div className="text-xs font-semibold uppercase text-muted-foreground">Running Days</div>
                                <div className="mt-2 flex gap-1">
                                    {['M', 'T', 'W', 'T', 'F', 'S', 'S'].map((letter, i) => {
                                        const dayNames = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
                                        const isActive = objective.running_days?.includes(dayNames[i]);
                                        return (
                                            <div
                                                key={i}
                                                className={cn(
                                                    "flex h-7 w-7 items-center justify-center rounded-full text-[10px] font-bold border",
                                                    isActive
                                                        ? "bg-primary text-primary-foreground border-primary"
                                                        : "bg-muted text-muted-foreground border-transparent opacity-40"
                                                )}
                                                title={dayNames[i]}
                                            >
                                                {letter}
                                            </div>
                                        );
                                    })}
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <div className="md:col-span-2 space-y-4">
                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2 text-sm">
                                    <Sparkles className="h-4 w-4 text-primary" />
                                    Enhance Training Plan
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                <form onSubmit={(e) => {
                                    e.preventDefault();
                                    post(enhanceTrainings(objective.id).url);
                                }} className="space-y-4">
                                    <div className="space-y-2">
                                        <Label htmlFor="enhancement_prompt">
                                            Additional Instructions
                                        </Label>
                                        <Textarea
                                            id="enhancement_prompt"
                                            placeholder="e.g., Focus more on interval training, include hill workouts, or add cross-training days..."
                                            value={data.enhancement_prompt}
                                            onChange={(e) => setData('enhancement_prompt', e.target.value)}
                                            className="min-h-24"
                                            aria-invalid={errors.enhancement_prompt ? 'true' : 'false'}
                                            aria-describedby={`${errors.enhancement_prompt ? 'enhancement_prompt-error ' : ''}enhancement_prompt-description`}
                                        />
                                        {errors.enhancement_prompt && (
                                            <p id="enhancement_prompt-error" className="text-sm text-destructive">{errors.enhancement_prompt}</p>
                                        )}
                                        <p id="enhancement_prompt-description" className="text-xs text-muted-foreground">
                                            Provide specific instructions to customize your training plan. The AI will regenerate the next 7 days with your preferences.
                                        </p>
                                    </div>
                                    <Button type="submit" disabled={processing} className="w-full sm:w-auto">
                                        <Sparkles className="mr-2 h-4 w-4" />
                                        {processing ? 'Regenerating...' : 'Regenerate Training Plan'}
                                    </Button>
                                    {wasSuccessful && (
                                        <p className="text-sm text-green-600 dark:text-green-500">
                                            Training plan regeneration started! Refresh the page in a few moments to see the updated recommendations.
                                        </p>
                                    )}
                                </form>
                            </CardContent>
                        </Card>

                        <h3 className="text-lg font-semibold">Training Recommendations</h3>
                        <div className="space-y-4">
                            {objective.daily_recommendations?.map((recommendation: DailyRecommendation) => {
                                const isToday = recommendation.date.startsWith(today);

                                return (
                                    <Card key={recommendation.id} className={cn(
                                        "transition-all",
                                        isToday ? "border-primary ring-1 ring-primary/20 shadow-md scale-[1.01]" : ""
                                    )}>
                                        <CardHeader className="pb-2">
                                            <div className="flex items-center justify-between">
                                                <div className="flex items-center gap-2">
                                                    <Calendar className={cn("h-4 w-4", isToday ? "text-primary" : "text-muted-foreground")} />
                                                    <span className={cn("text-sm font-medium", isToday ? "text-primary" : "")}>
                                                        {new Date(recommendation.date).toLocaleDateString(undefined, {
                                                            weekday: 'long',
                                                            month: 'long',
                                                            day: 'numeric'
                                                        })}
                                                        {isToday && " (Today)"}
                                                    </span>
                                                </div>
                                                <Badge variant="outline">{recommendation.type}</Badge>
                                            </div>
                                            <CardTitle className="mt-2">{recommendation.title}</CardTitle>
                                        </CardHeader>
                                        <CardContent className="space-y-3">
                                            <p className="text-sm text-muted-foreground">{recommendation.description}</p>

                                            <Alert variant="default" className="bg-muted/50 border-none">
                                                <Info className="h-4 w-4" />
                                                <AlertTitle className="text-xs font-semibold uppercase text-muted-foreground">Coach's Notes</AlertTitle>
                                                <AlertDescription className="text-xs italic text-muted-foreground">
                                                    {recommendation.reasoning}
                                                </AlertDescription>
                                            </Alert>
                                        </CardContent>
                                    </Card>
                                );
                            })}

                            {(!objective.daily_recommendations || objective.daily_recommendations.length === 0) && (
                                <div className="flex h-32 flex-col items-center justify-center rounded-lg border border-dashed text-muted-foreground">
                                    <p>No recommendations yet.</p>
                                    <p className="text-xs">They will appear here once generated.</p>
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
