import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { DailyRecommendation } from '@/types';
import { Sparkles, Dumbbell, Info, Zap, Coffee } from 'lucide-react';

interface TodayRecommendationWidgetProps {
    recommendation: DailyRecommendation | null;
}

export default function TodayRecommendationWidget({ recommendation }: TodayRecommendationWidgetProps) {
    const getIcon = (type: string) => {
        const t = type.toLowerCase();
        if (t.includes('rest') || t.includes('recovery')) return <Coffee className="h-5 w-5 text-sky-500" />;
        if (t.includes('interval') || t.includes('speed')) return <Zap className="h-5 w-5 text-amber-500" />;
        if (t.includes('long')) return <Dumbbell className="h-5 w-5 text-emerald-500" />;
        return <Dumbbell className="h-5 w-5 text-primary" />;
    };

    if (!recommendation) {
        return (
            <Card className="flex h-full flex-col justify-between border-dashed">
                <CardHeader>
                    <CardTitle className="text-lg">Today's Training</CardTitle>
                    <CardDescription>No plan generated for today yet.</CardDescription>
                </CardHeader>
                <CardContent className="flex items-center justify-center py-6">
                    <Sparkles className="h-12 w-12 text-muted-foreground/20" />
                </CardContent>
            </Card>
        );
    }

    return (
        <Card className="flex h-full flex-col justify-between border-primary/20 relative overflow-hidden">
            <div className="absolute top-0 right-0 p-4 opacity-5">
                <Sparkles className="h-24 w-24" />
            </div>
            <CardHeader className="pb-2">
                <div className="flex items-center justify-between">
                    <CardTitle className="text-lg">Today's Training</CardTitle>
                    {getIcon(recommendation.type)}
                </div>
                <CardDescription className="font-semibold text-primary">{recommendation.type}</CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
                <div className="space-y-1">
                    <h4 className="font-bold text-base leading-tight">{recommendation.title}</h4>
                    <p className="text-sm text-muted-foreground">
                        {recommendation.description}
                    </p>
                </div>

                <div className="bg-muted/50 rounded-lg p-3 border border-border/50">
                    <div className="flex items-start gap-2">
                        <Info className="h-4 w-4 text-primary/60 mt-0.5 shrink-0" />
                        <p className="text-xs text-muted-foreground italic leading-relaxed">
                            {recommendation.reasoning}
                        </p>
                    </div>
                </div>
            </CardContent>
        </Card>
    );
}
