import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Activity } from '@/types';
import { MessageSquare, Quote } from 'lucide-react';

export default function CoachEvaluationWidget({ activity }: { activity: Activity | null }) {
    if (!activity || !activity.short_evaluation) {
        return (
            <Card className="flex h-full flex-col justify-between">
                <CardHeader>
                    <CardTitle className="text-lg">Coach Advice</CardTitle>
                    <CardDescription>Waiting for your next run...</CardDescription>
                </CardHeader>
                <CardContent className="flex items-center justify-center py-6">
                    <MessageSquare className="h-12 w-12 text-muted-foreground/20" />
                </CardContent>
            </Card>
        );
    }

    return (
        <Card className="flex h-full flex-col justify-between border-primary/20">
            <CardHeader>
                <div className="flex items-center justify-between">
                    <CardTitle className="text-lg">Latest Run Feedback</CardTitle>
                    <Quote className="h-5 w-5 text-primary" />
                </div>
                <CardDescription>{activity.name}</CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
                <div className="relative">
                    <p className="text-sm italic text-foreground leading-relaxed">
                        "{activity.short_evaluation}"
                    </p>
                </div>
                <div className="text-xs text-muted-foreground pt-2">
                    Coach AI is analyzing your performance and fatigue.
                </div>
            </CardContent>
        </Card>
    );
}
