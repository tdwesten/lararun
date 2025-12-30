import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Trophy, Zap, TrendingUp } from 'lucide-react';
import { useTranslations } from '@/hooks/use-translations';

export interface PersonalRecord {
    id: number;
    record_type: string;
    value: number;
    achieved_date: string;
    activity: {
        name: string;
    };
}

interface PersonalRecordsWidgetProps {
    records: PersonalRecord[];
}

export default function PersonalRecordsWidget({ records }: PersonalRecordsWidgetProps) {
    const { t } = useTranslations();

    const formatRecordValue = (type: string, value: number): string => {
        if (type === 'longest_run') {
            return `${(value / 1000).toFixed(2)} km`;
        }
        if (type === 'fastest_pace') {
            const minutes = Math.floor(value / 60);
            const seconds = Math.round(value % 60);
            return `${minutes}:${seconds.toString().padStart(2, '0')} /km`;
        }
        // For time-based records (5k, 10k, etc.)
        const hours = Math.floor(value / 3600);
        const minutes = Math.floor((value % 3600) / 60);
        const seconds = Math.round(value % 60);
        if (hours > 0) {
            return `${hours}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
        }
        return `${minutes}:${seconds.toString().padStart(2, '0')}`;
    };

    const getRecordLabel = (type: string): string => {
        const labels: Record<string, string> = {
            fastest_5k: t('Fastest 5K'),
            fastest_10k: t('Fastest 10K'),
            fastest_half_marathon: t('Fastest Half Marathon'),
            fastest_marathon: t('Fastest Marathon'),
            longest_run: t('Longest Run'),
            fastest_pace: t('Fastest Pace'),
        };
        return labels[type] || type;
    };

    const getIcon = (type: string) => {
        if (type === 'fastest_pace') return <Zap className="h-4 w-4 text-yellow-500" />;
        if (type === 'longest_run') return <TrendingUp className="h-4 w-4 text-blue-500" />;
        return <Trophy className="h-4 w-4 text-amber-500" />;
    };

    if (records.length === 0) {
        return (
            <Card>
                <CardHeader>
                    <CardTitle className="flex items-center gap-2 text-sm">
                        <Trophy className="h-4 w-4 text-muted-foreground" />
                        {t('Personal Records')}
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <p className="text-sm text-muted-foreground">
                        {t('Start running to set your first records!')}
                    </p>
                </CardContent>
            </Card>
        );
    }

    return (
        <Card>
            <CardHeader>
                <CardTitle className="flex items-center gap-2 text-sm">
                    <Trophy className="h-4 w-4 text-amber-500" />
                    {t('Personal Records')}
                </CardTitle>
            </CardHeader>
            <CardContent>
                <div className="space-y-3">
                    {records.map((record) => (
                        <div key={record.id} className="flex items-center justify-between border-b pb-2 last:border-0">
                            <div className="flex items-center gap-2">
                                {getIcon(record.record_type)}
                                <div>
                                    <div className="text-sm font-medium">{getRecordLabel(record.record_type)}</div>
                                    <div className="text-xs text-muted-foreground">
                                        {new Date(record.achieved_date).toLocaleDateString()}
                                    </div>
                                </div>
                            </div>
                            <Badge variant="outline" className="font-mono">
                                {formatRecordValue(record.record_type, record.value)}
                            </Badge>
                        </div>
                    ))}
                </div>
            </CardContent>
        </Card>
    );
}
