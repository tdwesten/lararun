import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { ChartContainer, ChartTooltip, ChartTooltipContent } from '@/components/ui/chart';
import { useTranslations } from '@/hooks/use-translations';
import { Area, AreaChart, CartesianGrid, XAxis } from 'recharts';

export interface ChartDataPoint {
    date: string;
    distance: number;
    count: number;
}

interface RunningProgressChartProps {
    data: ChartDataPoint[];
    period?: 'week' | 'month';
}

export default function RunningProgressChart({ data, period = 'week' }: RunningProgressChartProps) {
    const { t } = useTranslations();

    const chartConfig = {
        distance: {
            label: t('Distance (km)'),
            color: 'hsl(var(--chart-1))',
        },
        count: {
            label: t('Runs'),
            color: 'hsl(var(--chart-2))',
        },
    };

    return (
        <Card>
            <CardHeader>
                <CardTitle>{t('Running Progress')}</CardTitle>
                <CardDescription>
                    {period === 'week' ? t('Last 7 days') : t('Last 30 days')}
                </CardDescription>
            </CardHeader>
            <CardContent>
                <ChartContainer config={chartConfig}>
                    <AreaChart
                        accessibilityLayer
                        data={data}
                        margin={{
                            left: 12,
                            right: 12,
                        }}
                    >
                        <CartesianGrid vertical={false} />
                        <XAxis
                            dataKey="date"
                            tickLine={false}
                            axisLine={false}
                            tickMargin={8}
                            tickFormatter={(value) => {
                                const date = new Date(value);
                                return date.toLocaleDateString('en-US', {
                                    month: 'short',
                                    day: 'numeric',
                                });
                            }}
                        />
                        <ChartTooltip cursor={false} content={<ChartTooltipContent />} />
                        <defs>
                            <linearGradient id="fillDistance" x1="0" y1="0" x2="0" y2="1">
                                <stop offset="5%" stopColor="var(--color-distance)" stopOpacity={0.8} />
                                <stop offset="95%" stopColor="var(--color-distance)" stopOpacity={0.1} />
                            </linearGradient>
                        </defs>
                        <Area
                            dataKey="distance"
                            type="natural"
                            fill="url(#fillDistance)"
                            fillOpacity={0.4}
                            stroke="var(--color-distance)"
                            stackId="a"
                        />
                    </AreaChart>
                </ChartContainer>
            </CardContent>
        </Card>
    );
}
