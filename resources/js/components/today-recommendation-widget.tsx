import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { DailyRecommendation } from '@/types';
import { Sparkles, Dumbbell, Zap, Coffee, Info } from 'lucide-react';
import { useTranslations } from '@/hooks/use-translations';

interface TodayRecommendationWidgetProps {
    recommendation: DailyRecommendation | null;
}

export default function TodayRecommendationWidget({ recommendation }: TodayRecommendationWidgetProps) {
    const { t } = useTranslations();

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
                    <CardTitle className="text-lg">{t("Today's Training")}</CardTitle>
                    <CardDescription>{t('No plan generated for today yet.')}</CardDescription>
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
                    <CardTitle className="text-lg">{t("Today's Training")}</CardTitle>
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

                <Alert variant="default" className="bg-muted/50 border-none">
                    <Info className="h-4 w-4" />
                    <AlertTitle className="text-xs font-semibold uppercase text-muted-foreground">{t("Coach's Notes")}</AlertTitle>
                    <AlertDescription className="text-xs italic text-muted-foreground">
                        {recommendation.reasoning}
                    </AlertDescription>
                </Alert>
            </CardContent>
        </Card>
    );
}
