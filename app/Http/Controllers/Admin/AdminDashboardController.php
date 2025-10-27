<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AirlineCommission;
use App\Models\User;
use Illuminate\Contracts\View\View;

class AdminDashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('admin.dashboard', [
            'metrics' => [
                'airlines_with_commissions' => AirlineCommission::count(),
                'active_commissions' => AirlineCommission::where('is_active', true)->count(),
                'users_total' => User::count(),
                'bookings_total' => 0,
                'payments_total' => 0,
                'referral_clicks' => 0,
            ],
            'recentCommissions' => AirlineCommission::latest()->take(5)->get(),
        ]);
    }
}
