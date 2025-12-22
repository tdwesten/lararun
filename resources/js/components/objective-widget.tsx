import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { cn } from '@/lib/utils';
import { create, show } from '@/routes/objectives';
import { Objective } from '@/types';
import { Link } from '@inertiajs/react';
import { Calendar, Target } from 'lucide-react';

export default function ObjectiveWidget({ objective }: { objective: Objective | null }) {
    if (!objective) {
        return (
            <Card className="flex h-full flex-col justify-between border-dashed">
                <CardHeader>
                    <CardTitle className="text-lg">No Active Objective</CardTitle>
                    <CardDescription>You haven't set a goal yet.</CardDescription>
                </CardHeader>
                <CardContent>
                    <Button asChild className="w-full">
                        <Link href={create().url}>Set a Goal</Link>
                    </Button>
                </CardContent>
            </Card>
        );
    }

    const daysLeft = Math.ceil((new Date(objective.target_date).getTime() - new Date().getTime()) / (1000 * 60 * 60 * 24));

    return (
        <Card className="flex h-full flex-col justify-between border-primary/20 relative overflow-hidden">
            <div className="absolute top-0 right-0 p-4 opacity-5">
                <Target className="h-24 w-24" />
            </div>
            <CardHeader className="pb-2">
                <div className="flex items-center justify-between">
                    <CardTitle className="text-lg">Current Goal</CardTitle>
                    <Target className="h-5 w-5 text-primary" />
                </div>
                <CardDescription className="font-semibold text-primary">{objective.type}</CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
                <div className="space-y-2">
                    <div className="flex items-center gap-2 text-sm text-muted-foreground">
                        <Calendar className="h-4 w-4" />
                        <span>Target: {new Date(objective.target_date).toLocaleDateString()}</span>
                    </div>

                    {objective.running_days && objective.running_days.length > 0 && (
                        <div className="flex gap-1">
                            {['M', 'T', 'W', 'T', 'F', 'S', 'S'].map((letter, i) => {
                                const days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
                                const dayName = days[i];
                                const isActive = objective.running_days?.includes(dayName);
                                return (
                                    <div
                                        key={`${dayName}-${i}`}
                                        className={cn(
                                            "flex h-6 w-6 items-center justify-center rounded-full text-[10px] font-bold border",
                                            isActive
                                                ? "bg-primary text-primary-foreground border-primary"
                                                : "bg-muted text-muted-foreground border-transparent opacity-50"
                                        )}
                                        title={dayName}
                                    >
                                        {letter}
                                    </div>
                                );
                            })}
                        </div>
                    )}
                </div>

                <div className="space-y-1">
                    <div className="text-2xl font-bold">{daysLeft > 0 ? daysLeft : 0} Days Left</div>
                    <div className="h-2 w-full overflow-hidden rounded-full bg-secondary">
                        <div
                            className="h-full bg-primary"
                            style={{
                                width: `${Math.max(0, Math.min(100, (30 - daysLeft) * (100 / 30)))}%`, // Dummy progress for now
                            }}
                        />
                    </div>
                </div>
                <Button asChild variant="outline" className="w-full">
                    <Link href={show(objective.id).url}>View Details</Link>
                </Button>
            </CardContent>
        </Card>
    );
}
