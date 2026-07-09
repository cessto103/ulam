import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import GuestLayout from '@/Layouts/GuestLayout';
import { useTranslation } from '@/hooks/useTranslation';
import { Head, Link, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

export default function Register() {
    const { t } = useTranslation();
    const { data, setData, post, processing, errors, reset } = useForm({
        name: '',
        username: '',
        email: '',
        password: '',
        password_confirmation: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('register'), {
            onFinish: () => reset('password', 'password_confirmation'),
        });
    };

    return (
        <GuestLayout>
            <Head title={t('register_page_title')} />

            <h1 className="text-xl font-bold text-gray-900 mb-1">{t('register_title')}</h1>
            <p className="text-xs text-gray-500 mb-6">{t('register_subtitle')}</p>

            <form onSubmit={submit} className="space-y-4">
                <div className="grid grid-cols-2 gap-3">
                    <div>
                        <InputLabel htmlFor="name" value={t('full_name')} className="text-xs font-medium text-gray-700" />
                        <TextInput
                            id="name"
                            name="name"
                            value={data.name}
                            className="mt-1 block w-full rounded-lg border-gray-200 text-sm shadow-none focus:border-green-500 focus:ring-green-500"
                            autoComplete="name"
                            isFocused={true}
                            onChange={(e) => setData('name', e.target.value)}
                            required
                        />
                        <InputError message={errors.name} className="mt-1 text-xs" />
                    </div>

                    <div>
                        <InputLabel htmlFor="username" value={t('username')} className="text-xs font-medium text-gray-700" />
                        <TextInput
                            id="username"
                            name="username"
                            value={data.username}
                            className="mt-1 block w-full rounded-lg border-gray-200 text-sm shadow-none focus:border-green-500 focus:ring-green-500"
                            autoComplete="username"
                            onChange={(e) => setData('username', e.target.value.toLowerCase().replace(/[^a-z0-9_]/g, ''))}
                            required
                        />
                        <InputError message={errors.username} className="mt-1 text-xs" />
                    </div>
                </div>

                <div>
                    <InputLabel htmlFor="email" value={t('email')} className="text-xs font-medium text-gray-700" />
                    <TextInput
                        id="email"
                        type="email"
                        name="email"
                        value={data.email}
                        className="mt-1 block w-full rounded-lg border-gray-200 text-sm shadow-none focus:border-green-500 focus:ring-green-500"
                        autoComplete="email"
                        onChange={(e) => setData('email', e.target.value)}
                        required
                    />
                    <InputError message={errors.email} className="mt-1 text-xs" />
                </div>

                <div>
                    <InputLabel htmlFor="password" value={t('password')} className="text-xs font-medium text-gray-700" />
                    <TextInput
                        id="password"
                        type="password"
                        name="password"
                        value={data.password}
                        className="mt-1 block w-full rounded-lg border-gray-200 text-sm shadow-none focus:border-green-500 focus:ring-green-500"
                        autoComplete="new-password"
                        onChange={(e) => setData('password', e.target.value)}
                        required
                    />
                    <InputError message={errors.password} className="mt-1 text-xs" />
                </div>

                <div>
                    <InputLabel htmlFor="password_confirmation" value={t('confirm_password')} className="text-xs font-medium text-gray-700" />
                    <TextInput
                        id="password_confirmation"
                        type="password"
                        name="password_confirmation"
                        value={data.password_confirmation}
                        className="mt-1 block w-full rounded-lg border-gray-200 text-sm shadow-none focus:border-green-500 focus:ring-green-500"
                        autoComplete="new-password"
                        onChange={(e) => setData('password_confirmation', e.target.value)}
                        required
                    />
                    <InputError message={errors.password_confirmation} className="mt-1 text-xs" />
                </div>

                <button
                    type="submit"
                    disabled={processing}
                    className="w-full rounded-lg bg-green-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 disabled:opacity-60 transition-colors"
                >
                    {processing ? t('registering') : t('register_button')}
                </button>
            </form>

            <p className="mt-6 text-center text-xs text-gray-500">
                {t('have_account')}{' '}
                <Link href={route('login')} className="font-semibold text-green-600 hover:text-green-800">
                    {t('login_link')}
                </Link>
            </p>
        </GuestLayout>
    );
}
