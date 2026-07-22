<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\Activitylog\Models\Activity;
use Illuminate\Support\Facades\Gate;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $isSuperadmin = $user->hasRole('Superadmin') || $user->hasRole('Developer');
        $isAdmin = $user->hasRole('Admin');
        
        $query = Activity::with(['causer', 'subject']);

        if (!$isSuperadmin) {
            if ($isAdmin) {
                // Scope to Admin's division using JSON properties or causer's division
                $query->where(function($q) use ($user) {
                    $q->whereJsonContains('properties->division_id', $user->division_id)
                      ->orWhereHasMorph('causer', [\App\Models\User::class], function($causerQuery) use ($user) {
                          $causerQuery->where('division_id', $user->division_id);
                      });
                });
            } else {
                // Not authorized
                abort(403);
            }
        }

        // Global Search
        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function($q) use ($searchTerm) {
                $q->where('description', 'like', "%{$searchTerm}%")
                  ->orWhere('log_name', 'like', "%{$searchTerm}%")
                  ->orWhere('event', 'like', "%{$searchTerm}%")
                  ->orWhere('subject_type', 'like', "%{$searchTerm}%")
                  ->orWhereHasMorph('causer', [\App\Models\User::class], function($causerQuery) use ($searchTerm) {
                      $causerQuery->where('first_name', 'like', "%{$searchTerm}%")
                                  ->orWhere('last_name', 'like', "%{$searchTerm}%")
                                  ->orWhere('username', 'like', "%{$searchTerm}%");
                  });
            });
        }

        // Filter by Action Type (Event)
        if ($request->filled('action_type') && $request->action_type !== 'All') {
            $actionType = strtolower($request->action_type);
            if (in_array($actionType, ['login', 'logout'])) {
                $query->where('description', ucfirst($actionType));
            } else {
                $query->where('event', $actionType);
            }
        }

        // Filter by Date Range
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $perPage = $request->input('per_page', 10);
        $validPerPages = [10, 25, 50, 100];
        if (!in_array($perPage, $validPerPages)) {
            $perPage = 10;
        }

        $activities = $query->latest()->paginate($perPage)->withQueryString();

        return Inertia::render('ActivityLogs/Index', [
            'activities' => $activities,
            'filters' => $request->only(['search', 'action_type', 'per_page', 'date_from', 'date_to']),
        ]);
    }

    public function destroy(Request $request)
    {
        $user = auth()->user();
        if (!$user->hasRole('Developer') && !$user->can('delete_activity_logs')) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        Activity::whereBetween('created_at', [
            $request->start_date . ' 00:00:00',
            $request->end_date . ' 23:59:59'
        ])->delete();

        return redirect()->back()->with('success', 'Logs permanently deleted.');
    }
}
