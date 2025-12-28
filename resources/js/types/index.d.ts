import { InertiaLinkProps } from '@inertiajs/react';
import { LucideIcon } from 'lucide-react';

export interface Auth {
    user: User;
}

export type PageProps<T extends Record<string, unknown> = Record<string, unknown>> = T & {
    auth: Auth;
};

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface NavGroup {
    title: string;
    items: NavItem[];
}

export interface NavItem {
    title: string;
    href: NonNullable<InertiaLinkProps['href']>;
    icon?: LucideIcon | null;
    isActive?: boolean;
}

export interface SharedData {
    name: string;
    quote: { message: string; author: string };
    auth: Auth;
    sidebarOpen: boolean;
    locale: string;
    translations: Record<string, string>;
    [key: string]: unknown;
}

export interface User {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    locale: string;
    email_verified_at: string | null;
    two_factor_enabled?: boolean;
    created_at: string;
    updated_at: string;
    [key: string]: unknown; // This allows for additional properties...
}

export interface Activity {
    id: number;
    user_id: number;
    strava_id: number;
    name: string;
    type: string;
    distance: number;
    moving_time: number;
    elapsed_time: number;
    start_date: string;
    intensity_score: string | null;
    zone_data_available: boolean;
    short_evaluation: string | null;
    extended_evaluation: string | null;
    created_at: string;
    updated_at: string;
}

export interface Objective {
    id: number;
    user_id: number;
    type: '5 km' | '10 km' | '21.1 km' | '42.2 km' | 'Speed';
    target_date: string;
    status: 'active' | 'completed' | 'abandoned';
    description: string | null;
    enhancement_prompt: string | null;
    running_days: string[] | null;
    daily_recommendations?: DailyRecommendation[];
    created_at: string;
    updated_at: string;
}

export interface DailyRecommendation {
    id: number;
    user_id: number;
    objective_id: number;
    date: string;
    type: string;
    title: string;
    description: string;
    reasoning: string;
    created_at: string;
    updated_at: string;
}

export interface RunningStats {
    total_distance_km: number;
    total_time_seconds: number;
    total_time_formatted: string;
    total_runs: number;
    average_pace_per_km: string | null;
    best_pace_per_km: string | null;
    fastest_run: {
        name: string;
        distance_km: number;
        pace: string;
        date: string;
    } | null;
}
