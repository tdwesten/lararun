import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { ChartContainer, ChartTooltip, ChartTooltipContent } from '@/components/ui/chart';
import { Area, AreaChart, CartesianGrid, XAxis, YAxis } from 'recharts';
import { useTranslations } from '@/hooks/use-translations';
import { useMemo } from 'react';
import { StreamData } from '@/types';

interface ActivityChartsProps {
    streamData: StreamData[] | null;
}

export default function ActivityCharts({ streamData }: ActivityChartsProps) {
    const { t } = useTranslations();

    const formatPace = (seconds: number) => {
        const numSeconds = Number(seconds);
        if (isNaN(numSeconds)) return '--:--';
        const totalSeconds = Math.round(numSeconds);
        const mins = Math.floor(totalSeconds / 60);
        const secs = totalSeconds % 60;
        return `${mins}:${secs.toString().padStart(2, '0')}`;
    };

    const formatDuration = (seconds: number) => {
        const numSeconds = Number(seconds);
        if (isNaN(numSeconds)) return '0:00';
        const totalSeconds = Math.round(numSeconds);
        const minutes = Math.floor(totalSeconds / 60);
        const remainingSeconds = totalSeconds % 60;
        return `${minutes}:${remainingSeconds.toString().padStart(2, '0')}`;
    };

    const data = useMemo(() => {
        if (!streamData) {
            // Placeholder data if no real data is available
            return Array.from({ length: 60 }, (_, i) => ({
                time: i,
                heartRate: 130 + (Math.sin(i / 5) * 10) + (i > 20 && i < 40 ? 20 : 0),
                pace: 330 - (Math.cos(i / 3) * 15) - (i > 40 ? 20 : 0), // seconds per km
            }));
        }

        const timeStream = streamData.find(s => s.type === 'time')?.data || [];
        const heartRateStream = streamData.find(s => s.type === 'heartrate')?.data || [];
        const velocityStream = streamData.find(s => s.type === 'velocity_smooth')?.data || [];

        // Use the shortest stream length to avoid index out of bounds
        const length = Math.min(timeStream.length, heartRateStream.length || timeStream.length, velocityStream.length || timeStream.length);

        const rawData = Array.from({ length }).map((_, index) => {
            const velocity = velocityStream[index];
            // Calculate pace in seconds per km. Clamp to max 20:00/km (1200s) to avoid outliers from stops.
            // If velocity is 0 or very low, pace is high.
            const pace = velocity && velocity > 0.83 ? (1000 / velocity) : null; // 0.83 m/s is ~20:00/km

            return {
                time: timeStream[index],
                heartRate: heartRateStream[index] || null,
                pace: pace && pace < 1200 ? pace : null, // Clamp to 20 min/km
            };
        });

        // Downsample if too many points
        if (rawData.length > 500) {
            const step = Math.ceil(rawData.length / 500);
            return rawData.filter((_, i) => i % step === 0);
        }

        return rawData;
    }, [streamData]);

    const hrConfig = {
        heartRate: {
            label: t('Heart Rate'),
            color: 'hsl(var(--chart-1))',
        },
    };

    const paceConfig = {
        pace: {
            label: t('Pace'),
            color: 'hsl(var(--chart-2))',
        },
    };

    return (
        <div className="grid gap-6 md:grid-cols-2">
            <Card>
                <CardHeader>
                    <CardTitle>{t('Heart Rate Analysis')}</CardTitle>
                    <CardDescription>{t('Heart rate over time')}</CardDescription>
                </CardHeader>
                <CardContent>
                    <ChartContainer config={hrConfig} className="h-[200px] w-full">
                        <AreaChart data={data} margin={{ top: 10, right: 10, left: 0, bottom: 0 }}>
                            <CartesianGrid vertical={false} />
                            <XAxis
                                dataKey="time"
                                hide
                            />
                            <YAxis
                                domain={['dataMin - 5', 'dataMax + 5']}
                                hide
                            />
                            <ChartTooltip
                                content={
                                    <ChartTooltipContent
                                        labelFormatter={(value) => formatDuration(value as number)}
                                    />
                                }
                            />
                            <Area
                                type="natural"
                                dataKey="heartRate"
                                stroke="var(--color-heartRate)"
                                fill="var(--color-heartRate)"
                                fillOpacity={0.2}
                            />
                        </AreaChart>
                    </ChartContainer>
                </CardContent>
            </Card>
             <Card>
                <CardHeader>
                    <CardTitle>{t('Pace Analysis')}</CardTitle>
                    <CardDescription>{t('Pace over time')}</CardDescription>
                </CardHeader>
                <CardContent>
                    <ChartContainer config={paceConfig} className="h-[200px] w-full">
                        <AreaChart data={data} margin={{ top: 10, right: 10, left: 0, bottom: 0 }}>
                            <CartesianGrid vertical={false} />
                            <XAxis
                                dataKey="time"
                                hide
                            />
                            <YAxis
                                domain={['dataMin', 'dataMax']}
                                hide
                                reversed
                            />
                            <ChartTooltip
                                content={
                                    <ChartTooltipContent
                                        labelFormatter={(value) => formatDuration(value as number)}
                                        formatter={(value, name, item) => (
                                            <>
                                                <div
                                                    className="h-2.5 w-2.5 shrink-0 rounded-[2px]"
                                                    style={{ backgroundColor: item.color }}
                                                />
                                                <div className="flex flex-1 justify-between leading-none items-center">
                                                    <span className="text-muted-foreground">{t('Pace')}</span>
                                                    <span className="font-mono font-medium tabular-nums text-foreground">
                                                        {formatPace(value as number)}
                                                        <span className="font-normal text-muted-foreground ml-1">
                                                            /km
                                                        </span>
                                                    </span>
                                                </div>
                                            </>
                                        )}
                                    />
                                }
                            />
                            <Area
                                type="natural"
                                dataKey="pace"
                                stroke="var(--color-pace)"
                                fill="var(--color-pace)"
                                fillOpacity={0.2}
                            />
                        </AreaChart>
                    </ChartContainer>
                </CardContent>
            </Card>
        </div>
    );
}

