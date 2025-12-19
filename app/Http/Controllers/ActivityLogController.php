<?php

namespace App\Http\Controllers;

use App\Enums\ResponseMessage;
use App\Models\ActivityLog;
use App\Traits\ResponseAPI;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ActivityLogController extends Controller
{
    use ResponseAPI;

    /**
     * Get paginated activity logs with filters
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|in:10,25,50,100',
            'search' => 'nullable|string|max:255',
            'category' => 'nullable|string|max:50',
            'action' => 'nullable|string|max:100',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
        ], $this->validationMessage());

        if ($validator->fails()) {
            return $this->validationResponse($validator->errors());
        }

        try {
            $query = ActivityLog::query();

            $user = auth()->user();

            // Only admins can view all logs, others see only their own
            if (!$user->hasRole('admin')) {
                $query->where('user_id', $user->id);
            }

            // Apply search
            if ($request->has('search') && $request->search) {
                $query->search($request->search);
            }

            // Apply filters
            if ($request->has('category') && $request->category) {
                $query->byCategory($request->category);
            }

            if ($request->has('action') && $request->action) {
                $query->byAction($request->action);
            }

            // Apply date range
            if ($request->has('date_from') || $request->has('date_to')) {
                $query->dateRange($request->date_from, $request->date_to);
            }

            // Order by latest first
            $query->orderBy('created_at', 'desc');

            // Paginate
            $perPage = $request->get('per_page', 25);
            $logs = $query->paginate($perPage);

            return $this->success(ResponseMessage::FETCHED, $logs, 200);
        } catch (Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Get specific activity log details
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        try {
            $log = ActivityLog::findOrFail($id);

            $user = auth()->user();

            // Permission check
            if (!$user->hasRole('admin') && $log->user_id !== $user->id) {
                return $this->error(ResponseMessage::UNAUTHORIZED, 403);
            }

            return $this->success(ResponseMessage::FETCHED_DETAIL, $log, 200);
        } catch (Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Export activity logs as CSV
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function export(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'search' => 'nullable|string|max:255',
            'category' => 'nullable|string|max:50',
            'action' => 'nullable|string|max:100',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
        ], $this->validationMessage());

        if ($validator->fails()) {
            return $this->validationResponse($validator->errors());
        }

        try {
            $query = ActivityLog::query();

            $user = auth()->user();

            // Only admins can export all logs
            if (!$user->hasRole('admin')) {
                $query->where('user_id', $user->id);
            }

            // Apply same filters as index
            if ($request->has('search') && $request->search) {
                $query->search($request->search);
            }

            if ($request->has('category') && $request->category) {
                $query->byCategory($request->category);
            }

            if ($request->has('action') && $request->action) {
                $query->byAction($request->action);
            }

            if ($request->has('date_from') || $request->has('date_to')) {
                $query->dateRange($request->date_from, $request->date_to);
            }

            $logs = $query->orderBy('created_at', 'desc')->get();

            // Generate CSV
            $filename = 'activity_logs_' . date('Y-m-d_His') . '.csv';
            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => "attachment; filename=\"{$filename}\"",
                'Pragma' => 'no-cache',
                'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
                'Expires' => '0',
            ];

            $callback = function () use ($logs) {
                $file = fopen('php://output', 'w');

                // CSV headers
                fputcsv($file, [
                    'Date/Time',
                    'User',
                    'Email',
                    'Category',
                    'Action',
                    'Description',
                    'Model Type',
                    'Model ID',
                    'IP Address',
                    'User Agent'
                ]);

                // CSV rows
                foreach ($logs as $log) {
                    fputcsv($file, [
                        $log->created_at->format('Y-m-d H:i:s'),
                        $log->user_name,
                        $log->user_email,
                        $log->category,
                        $log->action,
                        $log->description,
                        $log->model_type,
                        $log->model_id,
                        $log->ip_address,
                        substr($log->user_agent, 0, 100) // Truncate long user agents
                    ]);
                }

                fclose($file);
            };

            return response()->stream($callback, 200, $headers);
        } catch (Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Get available filter options (categories and actions)
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function filters()
    {
        try {
            $user = auth()->user();
            $query = ActivityLog::query();

            // Filter by user if not admin
            if (!$user->hasRole('admin')) {
                $query->where('user_id', $user->id);
            }

            $categories = $query->distinct()->pluck('category')->filter()->sort()->values();
            $actions = ActivityLog::query()
                ->when(!$user->hasRole('admin'), function ($q) use ($user) {
                    $q->where('user_id', $user->id);
                })
                ->distinct()
                ->pluck('action')
                ->filter()
                ->sort()
                ->values();

            return $this->success(
                ResponseMessage::FETCHED,
                [
                    'categories' => $categories,
                    'actions' => $actions,
                ],
                200
            );
        } catch (Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Cleanup old activity logs (Super Admin only)
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function cleanup(Request $request)
    {
        // Only admins can cleanup logs
        if (!auth()->user()->hasRole('admin')) {
            return $this->error(ResponseMessage::UNAUTHORIZED, 403);
        }

        $validator = Validator::make($request->all(), [
            'before_date' => 'required|date',
        ], $this->validationMessage());

        if ($validator->fails()) {
            return $this->validationResponse($validator->errors());
        }

        try {
            $count = ActivityLog::where('created_at', '<', $request->before_date)->delete();

            return $this->success(
                "{$count} old activity logs deleted successfully",
                [
                    'deleted_count' => $count,
                ],
                200
            );
        } catch (Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }
}
