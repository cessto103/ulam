export interface User {
    id: number;
    name: string;
    username?: string;
    email: string;
    email_verified_at?: string;
    avatar?: string;
    bio?: string;
    plan?: 'libre' | 'premium';
    household_size?: number;
    barangay?: string;
    municipality?: string;
    province?: string;
    xp?: number;
    level?: number;
    streak_days?: number;
    ai_plans_remaining?: number | null;
}

export type PageProps<
    T extends Record<string, unknown> = Record<string, unknown>,
> = T & {
    auth: {
        user: User;
    };
    locale: string;
    messages: Record<string, string>;
};
