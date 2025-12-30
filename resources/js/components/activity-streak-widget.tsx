import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Flame } from 'lucide-react';
import { useTranslations } from '@/hooks/use-translations';

interface ActivityStreakWidgetProps {
    streak: number;
}

export default function ActivityStreakWidget({ streak }: ActivityStreakWidgetProps) {
    const { t } = useTranslations();

    return (
        <Card className="col-span-1">
            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                <CardTitle className="text-sm font-medium">{t('Activity Streak')}</CardTitle>
                <Flame className={`h-4 w-4 ${streak > 0 ? 'text-orange-500' : 'text-muted-foreground'}`} />
            </CardHeader>
            <CardContent>
                <div className="text-2xl font-bold">{streak}</div>
                <p className="text-xs text-muted-foreground">
                    {streak === 0 && t('Start your streak today!')}
                    {streak === 1 && t('day in a row')}
                    {streak > 1 && t('days in a row')}
                </p>
                {streak >= 7 && (
                    <div className="mt-2 flex items-center gap-1">
                        <Flame className="h-3 w-3 text-orange-500" />
                        <span className="text-xs font-semibold text-orange-500">
                            {t('On fire!')}
                        </span>
                    </div>
                )}
            </CardContent>
        </Card>
    );
}
