<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AirlineCommission;
use App\Models\PricingRule;
use App\Models\User;
use Illuminate\Contracts\View\View;

class AdminDashboardController extends Controller
{
    public function __invoke(): View
    {
        $activeRulesQuery = PricingRule::query()->where('active', true);
        $commissionRules = PricingRule::query()
            ->where('active', true)
            ->whereIn('usage', [
                PricingRule::USAGE_COMMISSION_BASE,
                PricingRule::USAGE_COMMISSION_DISCOUNT_BASE,
            ])
            ->count();

        $discountRules = PricingRule::query()
            ->where('active', true)
            ->whereIn('usage', [
                PricingRule::USAGE_DISCOUNT_BASE,
                PricingRule::USAGE_DISCOUNT_TOTAL_PROMO,
                PricingRule::USAGE_COMMISSION_DISCOUNT_BASE,
            ])
            ->count();

        return view('admin.dashboard', [
            'metrics' => [
                'active_rules' => (clone $activeRulesQuery)->count(),
                'commission_rules' => $commissionRules,
                'discount_rules' => $discountRules,
                'carriers_with_rules' => PricingRule::whereNotNull('carrier')->distinct('carrier')->count('carrier'),
                'users_total' => User::count(),
                'bookings_total' => 0,
                'payments_total' => 0,
                'referral_clicks' => 0,
            ],
            'recentRules' => PricingRule::latest()->take(5)->get(),
            'recentCommissions' => AirlineCommission::latest()->take(5)->get(),
        ]);
    }
}
