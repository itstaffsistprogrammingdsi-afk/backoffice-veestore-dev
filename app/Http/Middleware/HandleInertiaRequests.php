<?php

namespace App\Http\Middleware;

use App\Support\LoyaltyModuleNavigation;
use App\Support\MasterDataModuleNavigation;
use App\Support\Navigation;
use App\Support\PromoModuleNavigation;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request)
    {
        return parent::version($request);
    }

    public function share(Request $request)
    {
        return array_merge(parent::share($request), [
            'branding' => [
                'name' => 'VeeStore',
                'tagline' => 'Master Data & Report',
                'logo_url' => asset('assets/img/logo.png'),
            ],
            'auth' => [
                'user' => $request->user()?->only(['id', 'name', 'username', 'email']),
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
            'locale' => app()->getLocale(),
            'available_locales' => [
                ['value' => 'id', 'label' => 'ID'],
                ['value' => 'en', 'label' => 'EN'],
            ],
            'notificationSummaryUrl' => route('notifications.summary'),
            'navigation' => fn () => Navigation::forUser($request->user()),
            'promoModule' => fn () => PromoModuleNavigation::forRequest($request),
            'masterDataModule' => fn () => MasterDataModuleNavigation::forRequest($request),
            'loyaltyModule' => fn () => LoyaltyModuleNavigation::forRequest($request),
        ]);
    }
}
