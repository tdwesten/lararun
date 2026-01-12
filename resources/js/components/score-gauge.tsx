import { ChartContainer } from '@/components/ui/chart';
import { useTranslations } from '@/hooks/use-translations';
import { Label, PolarRadiusAxis, RadialBar, RadialBarChart } from 'recharts';

interface ScoreGaugeProps {
    value: number;
    max: number;
    label: string;
    color: string;
    description?: string;
}

export default function ScoreGauge({ value, max, label, color, description }: ScoreGaugeProps) {
    const { t } = useTranslations();

    // Ensure value doesn't exceed max for the chart visualization (but display real value)
    const chartValue = Math.min(value, max);

    const chartData = [{ name: label, value: chartValue, fill: color }];

    const chartConfig = {
        score: {
            label: label,
            color: color,
        },
    };

    return (
        <ChartContainer config={chartConfig} className="mx-auto aspect-square max-h-[250px]">
            <RadialBarChart
                data={chartData}
                startAngle={180}
                endAngle={0}
                innerRadius={80}
                outerRadius={110}
            >
                <RadialBar
                    dataKey="value"
                    background
                    cornerRadius={10}
                />
                <PolarRadiusAxis tick={false} tickLine={false} axisLine={false}>
                    <Label
                        content={({ viewBox }) => {
                            if (viewBox && "cx" in viewBox && "cy" in viewBox) {
                                return (
                                    <text
                                        x={viewBox.cx}
                                        y={viewBox.cy}
                                        textAnchor="middle"
                                        dominantBaseline="middle"
                                    >
                                        <tspan
                                            x={viewBox.cx}
                                            y={viewBox.cy}
                                            className="fill-foreground text-4xl font-bold"
                                        >
                                            {value}
                                        </tspan>
                                        <tspan
                                            x={viewBox.cx}
                                            y={(viewBox.cy || 0) + 24}
                                            className="fill-muted-foreground text-sm"
                                        >
                                            {description || t('Score')}
                                        </tspan>
                                    </text>
                                );
                            }
                        }}
                    />
                </PolarRadiusAxis>
            </RadialBarChart>
        </ChartContainer>
    );
}

