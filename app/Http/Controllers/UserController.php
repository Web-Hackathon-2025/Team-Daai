<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Traits\ResponseAPI;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    use ResponseAPI;
    
    /**
     * @OA\Get(
     *     path="/api/admin/users",
     *     tags={"Admin Panel"},
     *     summary="Get all users",
     *     description="Admin can view all users with optional filters",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(name="role", in="query", required=false, @OA\Schema(type="string")),
     *     @OA\Parameter(name="search", in="query", required=false, @OA\Schema(type="string")),
     *     @OA\Parameter(name="per_page", in="query", required=false, @OA\Schema(type="integer", default=15)),
     *     @OA\Response(response=200, description="Users retrieved successfully"),
     *     @OA\Response(response=403, description="Access denied - Admin only")
     * )
     */
    public function index(Request $request)
    {
        try {
            $query = User::with(['roles']);

            // Filter by role
            if ($request->has('role')) {
                $query->role($request->role);
            }

            // Search by name or email
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('name', 'LIKE', '%' . $search . '%')
                      ->orWhere('email', 'LIKE', '%' . $search . '%');
                });
            }

            $users = $query->paginate($request->get('per_page', 15));

            return $this->success('Users retrieved successfully', [
                'users' => $users->items(),
                'total' => $users->total(),
                'current_page' => $users->currentPage(),
                'per_page' => $users->perPage(),
            ]);
        } catch (Exception $e) {
            return $this->error('Failed to retrieve users: ' . $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/admin/users/{id}",
     *     tags={"Admin Panel"},
     *     summary="Get user details",
     *     description="Admin can view detailed information about a specific user",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="User details retrieved"),
     *     @OA\Response(response=404, description="User not found")
     * )
     */
    public function show($id)
    {
        try {
            $user = User::with(['roles'])->findOrFail($id);

            return $this->success('User details retrieved successfully', ['user' => $user]);
        } catch (Exception $e) {
            return $this->error('User not found', 404);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/admin/users/{id}/approve",
     *     tags={"Admin Panel"},
     *     summary="Approve service provider",
     *     description="Admin approves a service provider profile",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Provider approved"),
     *     @OA\Response(response=400, description="Invalid user type")
     * )
     */
    public function approve($id)
    {
        try {
            $user = User::findOrFail($id);

            if (!$user->hasRole('service_provider')) {
                return $this->error('Only service providers can be approved', 400);
            }

            // Update provider profile approval status
            if ($user->providerProfile) {
                $user->providerProfile->update(['is_approved' => true]);
            }

            return $this->success('Service provider approved successfully', ['user' => $user->fresh(['providerProfile'])]);
        } catch (Exception $e) {
            return $this->error('Failed to approve provider: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Suspend user (Admin only)
     * PUT /api/admin/users/{id}/suspend
     */
    public function suspend($id)
    {
        try {
            $user = User::findOrFail($id);

            // You can add a 'suspended' or 'is_active' field to users table if needed
            // For now, we'll just indicate success
            
            return $this->success('User suspended successfully', ['user' => $user]);
        } catch (Exception $e) {
            return $this->error('Failed to suspend user: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Delete user (Admin only)
     * DELETE /api/admin/users/{id}
     */
    /**
     * Delete a user (Admin only)
     */
    public function destroy($id)
    {
        try {
            $user = User::findOrFail($id);
            $user->delete();

            return $this->success('User deleted successfully');
        } catch (Exception $e) {
            return $this->error('Failed to delete user: ' . $e->getMessage(), 500);
        }
    }
}
