import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { useForm } from '@inertiajs/react';
import { useTranslations } from '@/hooks/use-translations';
import { Star } from 'lucide-react';

interface WorkoutFeedbackModalProps {
    recommendationId: number;
    isOpen: boolean;
    onClose: () => void;
}

export default function WorkoutFeedbackModal({
    recommendationId,
    isOpen,
    onClose,
}: WorkoutFeedbackModalProps) {
    const { t } = useTranslations();
    const [difficultyRating, setDifficultyRating] = useState<number | null>(null);
    const [enjoymentRating, setEnjoymentRating] = useState<number | null>(null);

    const { data, setData, post, processing, reset } = useForm({
        status: 'completed' as 'completed' | 'skipped' | 'partially_completed',
        difficulty_rating: null as number | null,
        enjoyment_rating: null as number | null,
        notes: '',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(`/api/workout-feedback/${recommendationId}`, {
            onSuccess: () => {
                reset();
                setDifficultyRating(null);
                setEnjoymentRating(null);
                onClose();
            },
        });
    };

    const renderStars = (
        rating: number | null,
        setRating: (value: number) => void,
        label: string
    ) => {
        return (
            <div className="space-y-2">
                <Label>{label}</Label>
                <div className="flex gap-1">
                    {[1, 2, 3, 4, 5].map((star) => (
                        <button
                            key={star}
                            type="button"
                            onClick={() => {
                                setRating(star);
                                if (label.includes('Difficulty')) {
                                    setData('difficulty_rating', star);
                                } else {
                                    setData('enjoyment_rating', star);
                                }
                            }}
                            className="transition-colors"
                        >
                            <Star
                                className={`h-6 w-6 ${
                                    rating && star <= rating
                                        ? 'fill-yellow-500 text-yellow-500'
                                        : 'text-gray-300'
                                }`}
                            />
                        </button>
                    ))}
                </div>
            </div>
        );
    };

    return (
        <Dialog open={isOpen} onOpenChange={onClose}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>{t('How was your workout?')}</DialogTitle>
                    <DialogDescription>
                        {t('Your feedback helps improve future recommendations')}
                    </DialogDescription>
                </DialogHeader>
                <form onSubmit={handleSubmit} className="space-y-4">
                    <div className="space-y-2">
                        <Label>{t('Status')}</Label>
                        <div className="flex gap-2">
                            {(['completed', 'partially_completed', 'skipped'] as const).map((status) => (
                                <Button
                                    key={status}
                                    type="button"
                                    variant={data.status === status ? 'default' : 'outline'}
                                    onClick={() => setData('status', status)}
                                    className="flex-1"
                                >
                                    {t(status)}
                                </Button>
                            ))}
                        </div>
                    </div>

                    {renderStars(difficultyRating, setDifficultyRating, t('Difficulty (1=too easy, 5=too hard)'))}
                    {renderStars(enjoymentRating, setEnjoymentRating, t('Enjoyment'))}

                    <div className="space-y-2">
                        <Label htmlFor="notes">{t('Notes (optional)')}</Label>
                        <Textarea
                            id="notes"
                            value={data.notes}
                            onChange={(e) => setData('notes', e.target.value)}
                            placeholder={t('Any additional feedback...')}
                            className="min-h-20"
                        />
                    </div>

                    <DialogFooter>
                        <Button type="button" variant="outline" onClick={onClose}>
                            {t('Cancel')}
                        </Button>
                        <Button type="submit" disabled={processing}>
                            {t('Submit Feedback')}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
