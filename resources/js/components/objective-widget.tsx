import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { create } from '@/routes/objectives';
import { Objective } from '@/types';
import { Link } from '@inertiajs/react';
import { Calendar, Target } from 'lucide-react';

export default function ObjectiveWidget({ objective }: { objective: Objective | null }) {
    if (!objective) {
        return (
            <Card className="flex h-full flex-col justify-between">
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
        <Card className="flex h-full flex-col justify-between border-primary/20">
            <CardHeader>
                <div className="flex items-center justify-between">
                    <CardTitle className="text-lg">Current Goal</CardTitle>
                    <Target className="h-5 w-5 text-primary" />
                </div>
                <CardDescription>{objective.type}</CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
                <div className="flex items-center gap-2 text-sm text-muted-foreground">
                    <Calendar className="h-4 w-4" />
                    <span>Target: {new Date(objective.target_date).toLocaleDateString()}</span>
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
                    <Link href="/objectives">View Details</Link>
                </Button>
            </CardContent>
        </Card>
    );
}
