import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { cn } from '@/lib/utils';
import AppLayout from '@/layouts/app-layout';
import { create, edit, index } from '@/routes/objectives';
import { BreadcrumbItem, Objective } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';
import { Plus, Target, Trash2 } from 'lucide-react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Objectives',
        href: index().url,
    },
];

function RunningDays({ days }: { days: string[] | null }) {
    if (!days || days.length === 0) return null;

    const dayLetters = ['M', 'T', 'W', 'T', 'F', 'S', 'S'];
    const dayNames = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

    return (
        <div className="flex gap-1 mt-2">
            {dayLetters.map((letter, i) => {
                const isActive = days.includes(dayNames[i]);
                return (
                    <div
                        key={i}
                        className={cn(
                            "flex h-5 w-5 items-center justify-center rounded-full text-[9px] font-bold border",
                            isActive
                                ? "bg-primary text-primary-foreground border-primary"
                                : "bg-muted text-muted-foreground border-transparent opacity-40"
                        )}
                        title={dayNames[i]}
                    >
                        {letter}
                    </div>
                );
            })}
        </div>
    );
}

export default function Index({ objectives, currentObjective }: { objectives: Objective[]; currentObjective: Objective | null }) {
    const { delete: destroy } = useForm();

    const handleDelete = (id: number) => {
        if (confirm('Are you sure you want to delete this objective?')) {
            destroy(index().url + '/' + id);
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Objectives" />

            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex items-center justify-between">
                    <div>
                        <h2 className="text-2xl font-bold tracking-tight">Objectives</h2>
                        <p className="text-muted-foreground">Manage your running goals and track your progress.</p>
                    </div>
                    <Button asChild>
                        <Link href={create().url}>
                            <Plus className="mr-2 h-4 w-4" />
                            New Objective
                        </Link>
                    </Button>
                </div>

                {currentObjective && (
                    <Card className="border-primary/50">
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Current Active Objective</CardTitle>
                            <Target className="h-4 w-4 text-primary" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{currentObjective.type}</div>
                            <p className="text-xs text-muted-foreground">
                                Target Date: {new Date(currentObjective.target_date).toLocaleDateString()}
                            </p>
                            <RunningDays days={currentObjective.running_days} />
                            <div className="mt-4 flex gap-2">
                                <Button variant="outline" size="sm" asChild>
                                    <Link href={edit(currentObjective.id).url}>Edit</Link>
                                </Button>
                            </div>
                        </CardContent>
                    </Card>
                )}

                <div className="mt-6">
                    <h3 className="mb-4 text-lg font-medium">History</h3>
                    <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                        {objectives.map((objective) => (
                            <Card key={objective.id} className={objective.status !== 'active' ? 'opacity-70' : ''}>
                                <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                    <CardTitle className="text-sm font-medium">{objective.type}</CardTitle>
                                    <Badge
                                        variant={
                                            objective.status === 'active'
                                                ? 'default'
                                                : objective.status === 'completed'
                                                  ? 'success'
                                                  : 'secondary'
                                        }
                                    >
                                        {objective.status}
                                    </Badge>
                                </CardHeader>
                                <CardContent>
                                    <div className="text-xs text-muted-foreground">
                                        Target: {new Date(objective.target_date).toLocaleDateString()}
                                    </div>
                                    <RunningDays days={objective.running_days} />
                                    {objective.description && <p className="mt-2 line-clamp-2 text-sm text-muted-foreground">{objective.description}</p>}
                                    <div className="mt-4 flex items-center justify-between">
                                        <div className="flex gap-2">
                                            <Button variant="ghost" size="sm" asChild>
                                                <Link href={edit(objective.id).url}>Edit</Link>
                                            </Button>
                                        </div>
                                        <Button variant="ghost" size="icon" onClick={() => handleDelete(objective.id)}>
                                            <Trash2 className="h-4 w-4 text-destructive" />
                                        </Button>
                                    </div>
                                </CardContent>
                            </Card>
                        ))}

                        {objectives.length === 0 && (
                            <div className="col-span-full flex h-40 items-center justify-center rounded-lg border border-dashed">
                                <p className="text-muted-foreground">No objectives found. Create your first one!</p>
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
