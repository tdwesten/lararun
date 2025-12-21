import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import { index, update } from '@/routes/objectives';
import { BreadcrumbItem, Objective } from '@/types';
import { Head, useForm } from '@inertiajs/react';

export default function Edit({ objective }: { objective: Objective }) {
    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Objectives',
            href: index().url,
        },
        {
            title: 'Edit',
            href: `/objectives/${objective.id}/edit`,
        },
    ];

    const { data, setData, put, processing, errors } = useForm({
        type: objective.type,
        target_date: objective.target_date.split('T')[0],
        status: objective.status,
        description: objective.description || '',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        put(update(objective.id).url);
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Edit Objective" />

            <div className="mx-auto max-w-2xl p-4">
                <Card>
                    <CardHeader>
                        <CardTitle>Edit Objective</CardTitle>
                        <CardDescription>Update your running goal details.</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={handleSubmit} className="space-y-6">
                            <div className="space-y-2">
                                <Label htmlFor="type">Objective Type</Label>
                                <Select onValueChange={(value) => setData('type', value as Objective['type'])} value={data.type}>
                                    <SelectTrigger id="type">
                                        <SelectValue placeholder="Select a goal" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="5 km">5 km</SelectItem>
                                        <SelectItem value="10 km">10 km</SelectItem>
                                        <SelectItem value="21.1 km">21.1 km (Half Marathon)</SelectItem>
                                        <SelectItem value="42.2 km">42.2 km (Full Marathon)</SelectItem>
                                        <SelectItem value="Speed">Run Faster (Improve Pace)</SelectItem>
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.type} />
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="status">Status</Label>
                                <Select onValueChange={(value) => setData('status', value as Objective['status'])} value={data.status}>
                                    <SelectTrigger id="status">
                                        <SelectValue placeholder="Select status" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="active">Active</SelectItem>
                                        <SelectItem value="completed">Completed</SelectItem>
                                        <SelectItem value="abandoned">Abandoned</SelectItem>
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.status} />
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="target_date">Target Date</Label>
                                <Input
                                    id="target_date"
                                    type="date"
                                    value={data.target_date}
                                    onChange={(e) => setData('target_date', e.target.value)}
                                />
                                <InputError message={errors.target_date} />
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="description">Description (Optional)</Label>
                                <Input
                                    id="description"
                                    value={data.description}
                                    onChange={(e) => setData('description', e.target.value)}
                                />
                                <InputError message={errors.description} />
                            </div>

                            <div className="flex justify-end gap-4">
                                <Button type="submit" disabled={processing}>
                                    Update Objective
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
