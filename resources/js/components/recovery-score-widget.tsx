import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Progress } from '@/components/ui/progress';
import { Heart, AlertTriangle } from 'lucide-react';
import { useTranslations } from '@/hooks/use-translations';

interface RecoveryScoreWidgetProps {
    recoveryScore: number;
}

export default function RecoveryScoreWidget({ recoveryScore }: RecoveryScoreWidgetProps) {
    const { t } = useTranslations();

    const getRecoveryStatus = (score: number): { label: string; color: string } => {
        if (score >= 8) return { label: t('Fully Recovered'), color: 'text-green-600' };
        if (score >= 6) return { label: t('Good Recovery'), color: 'text-blue-600' };
        if (score >= 4) return { label: t('Moderate Recovery'), color: 'text-yellow-600' };
        return { label: t('Need Rest'), color: 'text-red-600' };
    };

    const status = getRecoveryStatus(recoveryScore);
    const progressValue = (recoveryScore / 10) * 100;

    return (
        <Card className="col-span-1">
            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                <CardTitle className="text-sm font-medium">{t('Recovery Status')}</CardTitle>
                {recoveryScore < 4 ? (
                    <AlertTriangle className="h-4 w-4 text-red-500" />
                ) : (
                    <Heart className="h-4 w-4 text-pink-500" />
                )}
            </CardHeader>
            <CardContent>
                <div className="space-y-2">
                    <div className="flex items-baseline gap-2">
                        <div className="text-2xl font-bold">{recoveryScore.toFixed(1)}</div>
                        <span className="text-sm text-muted-foreground">/10</span>
                    </div>
                    <Progress value={progressValue} className="h-2" />
                    <p className={`text-xs font-semibold ${status.color}`}>
                        {status.label}
                    </p>
                    {recoveryScore < 6 && (
                        <p className="text-xs text-muted-foreground">
                            {t('Consider a rest day or easy run')}
                        </p>
                    )}
                </div>
            </CardContent>
        </Card>
    );
}
