import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import { index, store } from '@/routes/objectives';
import { BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Objectives',
        href: index().url,
    },
    {
        title: 'Create',
        href: '/objectives/create',
    },
];

export default function Create() {
    const { data, setData, post, processing, errors } = useForm({
        type: '',
        target_date: '',
        description: '',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(store().url);
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Create Objective" />

            <div className="mx-auto max-w-2xl p-4">
                <Card>
                    <CardHeader>
                        <CardTitle>Create New Objective</CardTitle>
                        <CardDescription>Define your next running goal. Note: Creating a new objective will abandon any currently active one.</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={handleSubmit} className="space-y-6">
                            <div className="space-y-2">
                                <Label htmlFor="type">Objective Type</Label>
                                <Select onValueChange={(value) => setData('type', value)} value={data.type}>
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
                                <Label htmlFor="target_date">Target Date</Label>
                                <Input
                                    id="target_date"
                                    type="date"
                                    value={data.target_date}
                                    onChange={(e) => setData('target_date', e.target.value)}
                                    min={new Date().toISOString().split('T')[0]}
                                />
                                <InputError message={errors.target_date} />
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="description">Description (Optional)</Label>
                                <Input
                                    id="description"
                                    value={data.description}
                                    onChange={(e) => setData('description', e.target.value)}
                                    placeholder="e.g. Finish my first 10K under 50 minutes"
                                />
                                <InputError message={errors.description} />
                            </div>

                            <div className="flex justify-end gap-4">
                                <Button type="submit" disabled={processing}>
                                    Create Objective
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
