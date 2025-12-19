<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\Notification;
use App\Traits\ResponseAPI;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Exception;

class DashboardController extends Controller
{
    use ResponseAPI;

    public function getStats()
    {
        try {
            $user = Auth::user();
            // For Super Admin - show all business stats
            if ($user->hasRole('Super Admin')) {
                $stats = [
                    'total_businesses' => Business::count(),
                    'pending_businesses' => Business::where('is_active', false)->count(),
                    'notifications'=> Notification::forUser($user->id)->byStatus('unread')->count(),
                    'due_payments' => Business::whereNotNull('subscription_end_date')
                        ->where('subscription_end_date', '<', now())
                        ->where('subscription_status', '!=', 'paid')
                        ->count(),
                    'profit' => Business::whereNotNull('paid_via')
                        ->whereNotNull('payment_transaction_id')
                        ->count(), // Can be calculated from actual payment amounts
                ];
            } else {
                // For Business Admin - show their business stats
                $business = Business::find($user->business_id);

                if (!$business) {
                    return $this->error('Business not found', 404);
                }

                $stats = [
                    'total_businesses' => 1,
                    'pending_businesses' => $business->is_active ? 0 : 1,
                    'due_payments' => ($business->subscription_end_date && $business->subscription_end_date < now() && $business->subscription_status != 'paid') ? 1 : 0,
                    'profit' => 0,
                ];
            }

            return $this->success('Dashboard stats loaded successfully', $stats);
        } catch (Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }
}
