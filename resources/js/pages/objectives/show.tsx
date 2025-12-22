import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { index } from '@/routes/objectives';
import { BreadcrumbItem, DailyRecommendation, Objective } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, Calendar, Info, Target } from 'lucide-react';
import { cn } from '@/lib/utils';

export default function Show({ objective }: { objective: Objective }) {
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
