import Checkbox from '@/Components/Checkbox';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import GuestLayout from '@/Layouts/GuestLayout';
import { useTranslation } from '@/hooks/useTranslation';
import { Head, Link, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

export default function Login({
    status,
    canResetPassword,
}: {
    status?: string;
    canResetPassword: boolean;
}) {
    const { t } = useTranslation();
    const { data, setData, post, processing, errors, reset } = useForm({
        login: '',
        password: '',
        remember: false as boolean,
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('login'), { onFinish: () => reset('password') });
    };

    return (
        <GuestLayout>
            <Head title={t('login_page_title')} />

            <h1 className="text-xl font-bold text-gray-900 mb-1">{t('login_title')}</h1>
            <p className="text-xs text-gray-500 mb-6">{t('login_subtitle')}</p>

            {status && (
                <div className="mb-4 rounded-lg bg-green-50 px-3 py-2 text-xs font-medium text-green-700">
                    {status}
                </div>
            )}

            <form onSubmit={submit} className="space-y-4">
                <div>
                    <InputLabel htmlFor="login" value={t('email_or_username')} className="text-xs font-medium text-gray-700" />
                    <TextInput
                        id="login"
                        type="text"
                        name="login"
                        value={data.login}
                        className="mt-1 block w-full rounded-lg border-gray-200 text-sm shadow-none focus:border-green-500 focus:ring-green-500"
                        autoComplete="username"
                        isFocused={true}
                        onChange={(e) => setData('login', e.target.value)}
                    />
                    <InputError message={errors.login} className="mt-1 text-xs" />
                </div>

                <div>
                    <InputLabel htmlFor="password" value={t('password')} className="text-xs font-medium text-gray-700" />
                    <TextInput
                        id="password"
                        type="password"
                        name="password"
                        value={data.password}
                        className="mt-1 block w-full rounded-lg border-gray-200 text-sm shadow-none focus:border-green-500 focus:ring-green-500"
                        autoComplete="current-password"
                        onChange={(e) => setData('password', e.target.value)}
                    />
                    <InputError message={errors.password} className="mt-1 text-xs" />
                </div>

                <div className="flex items-center justify-between">
                    <label className="flex items-center gap-2 cursor-pointer">
                        <Checkbox
                            name="remember"
                            checked={data.remember}
                            onChange={(e) => setData('remember', (e.target.checked || false) as false)}
                        />
                        <span className="text-xs text-gray-600">{t('remember_me')}</span>
                    </label>

                    {canResetPassword && (
                        <Link
                            href={route('password.request')}
                            className="text-xs text-green-600 hover:text-green-800"
                        >
                            {t('forgot_password')}
                        </Link>
                    )}
                </div>

                <button
                    type="submit"
                    disabled={processing}
                    className="w-full rounded-lg bg-green-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 disabled:opacity-60 transition-colors"
                >
                    {processing ? t('logging_in') : t('login_button')}
                </button>
            </form>

            <p className="mt-6 text-center text-xs text-gray-500">
                {t('no_account')}{' '}
                <Link href={route('register')} className="font-semibold text-green-600 hover:text-green-800">
                    {t('sign_up')}
                </Link>
            </p>
        </GuestLayout>
    );
}
