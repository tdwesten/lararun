import { ChartContainer, ChartTooltip, ChartTooltipContent } from '@/components/ui/chart';
import { useTranslations } from '@/hooks/use-translations';
import { Bar, BarChart, CartesianGrid, XAxis, YAxis, Cell } from 'recharts';

interface HeartRateZonesChartProps {
    zones: {
        name: string;
        time: number;
        color: string;
    }[];
}

export default function HeartRateZonesChart({ zones }: HeartRateZonesChartProps) {
    const { t } = useTranslations();

    const chartData = zones.map((zone) => ({
        name: zone.name,
        time: Math.round(zone.time / 60), // Convert to minutes
        fill: zone.color.replace('bg-', 'var(--color-').replace('-400', '-400)').replace('-500', '-500)').replace('-600', '-600)'),
        originalColor: zone.color
    }));

    // Map tailwind classes to CSS variables or hex codes if possible, or use a mapping.
    // Since we are using shadcn charts, we should define a config.

    const chartConfig = {
        time: {
            label: t('Time (min)'),
            color: 'hsl(var(--primary))',
        },
        z1: { label: t('Zone 1'), color: 'hsl(var(--emerald-400))' },
        z2: { label: t('Zone 2'), color: 'hsl(var(--emerald-600))' },
        z3: { label: t('Zone 3'), color: 'hsl(var(--amber-500))' },
        z4: { label: t('Zone 4'), color: 'hsl(var(--orange-600))' },
        z5: { label: t('Zone 5'), color: 'hsl(var(--rose-600))' },
    };

    // Helper to get color for bar
    const getBarColor = (index: number) => {
        const colors = [
            '#34d399', // emerald-400
            '#059669', // emerald-600
            '#f59e0b', // amber-500
            '#ea580c', // orange-600
            '#e11d48', // rose-600
        ];
        return colors[index] || '#000000';
    };

    return (
        <ChartContainer config={chartConfig}>
            <BarChart
                accessibilityLayer
                data={chartData}
                layout="vertical"
                margin={{
                    left: 0,
                }}
            >
                <CartesianGrid horizontal={false} />
                <YAxis
                    dataKey="name"
                    type="category"
                    tickLine={false}
                    tickMargin={10}
                    axisLine={false}
                    width={60}
                />
                <XAxis dataKey="time" type="number" hide />
                <ChartTooltip
                    cursor={false}
                    content={<ChartTooltipContent hideLabel />}
                />
                <Bar dataKey="time" layout="vertical" radius={5}>
                    {chartData.map((entry, index) => (
                        <Cell key={`cell-${index}`} fill={getBarColor(index)} />
                    ))}
                </Bar>
            </BarChart>
        </ChartContainer>
    );
}

