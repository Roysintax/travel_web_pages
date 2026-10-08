<?php

namespace App\Providers;

use App\Models\Page;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Gate::define('access-admin', fn (User $user): bool => (bool) $user->is_admin);
        RateLimiter::for('admin-login', fn (Request $request): array => [Limit::perMinute(5)->by(strtolower((string) $request->input('email')).'|'.$request->ip()), Limit::perMinute(30)->by($request->ip())]);
        View::composer(['home', 'about', 'contact', 'destinations', 'packages', 'bookings', 'booking-review', 'booking-ready'], function (\Illuminate\View\View $view): void {
            $page = $view->getData()['page'] ?? Page::where('slug', $view->name() === 'home' ? 'index' : $view->name())->first();
            $page?->loadMissing('sections');
            $view->with('cmsPage', $page)->with('sectionHeadings', $page?->sections->pluck('heading', 'section_key')->all() ?? []);
        });
    }
}
