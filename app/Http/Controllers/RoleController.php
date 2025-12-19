<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Traits\ResponseAPI;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Enums\ResponseMessage;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    use ResponseAPI;
    
    /**
     * @OA\Get(
     *     path="/api/roles",
     *     tags={"Roles & Permissions"},
     *     summary="Get all roles",
     *     description="List all roles with their associated permissions",
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(response=200, description="Roles retrieved successfully"),
     *     @OA\Response(response=403, description="Access denied")
     * )
     */
    public function index(Request $request)
    {
        $roles = Role::with('permissions')->get();
        return $this->success(ResponseMessage::FETCHED, $roles, 200);
    }

    /**
     * @OA\Post(
     *     path="/api/roles",
     *     tags={"Roles & Permissions"},
     *     summary="Create new role",
     *     description="Admin creates a new role with optional permissions",
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name"},
     *             @OA\Property(property="name", type="string", example="manager"),
     *             @OA\Property(property="permissions", type="array", @OA\Items(type="string"), example={"view_users", "edit_users"})
     *         )
     *     ),
     *     @OA\Response(response=200, description="Role created successfully"),
     *     @OA\Response(response=400, description="Validation error")
     * )
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:191|unique:roles,name',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,name'
        ], $this->validationMessage());

        if ($validator->fails()) {
            return $this->validationResponse($validator->errors());
        }

        DB::beginTransaction();

        try {
            $role = Role::create([
                'name' => $request->name,
                'guard_name' => 'api'
            ]);
            if (!empty($request->permissions)) {
                $role->syncPermissions($request->permissions);
            }
            $data = $role->load('permissions');
            DB::commit();

            return $this->success(
                ResponseMessage::SAVE,
                $data,
                200
            );
        } catch (Exception $e) {
            DB::rollBack();
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Get role by ID
     */
    public function getById($role_id)
    {
        $role = Role::with('permissions')->find($role_id);
        if (!$role) {
            return $this->error('Role not found', 404);
        }
        return $this->success(ResponseMessage::FETCHED_DETAIL, $role, 200);
    }

    /**
     * Update a role (Admin only)
     */
    public function update(Request $request)
    {
        $role = Role::find($request->id);

        if (!$role) {
            return $this->error('Role not found', 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:191|unique:roles,name,' . $request->id,
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,name'
        ], $this->validationMessage());
        if ($validator->fails()) {
            return $this->validationResponse($validator->errors());
        }
        DB::beginTransaction();

        try {
            $obj = [
                'name' => $request->name
            ];
            $role->update($obj);
            if ($request->has('permissions')) {
                $role->syncPermissions($request->permissions);
            }
            DB::commit();

            return $this->success(
                ResponseMessage::UPDATE,
                $role->load('permissions'),
                200
            );
        } catch (Exception $e) {
            DB::rollBack();
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Delete a role (Admin only)
     */
    public function destroy(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:roles,id',
        ], $this->validationMessage());
        
        if ($validator->fails()) {
            return $this->validationResponse($validator->errors());
        }

        $role = Role::find($request->id);

        if (!$role) {
            return $this->error('Role not found', 404);
        }

        // Prevent deletion of core Karigar roles
        if (in_array($role->name, ['admin', 'customer', 'service_provider'])) {
            return $this->error('Cannot delete core Karigar roles', 403);
        }

        $role->delete();

        return $this->success(ResponseMessage::DELETE, [], 200);
    }
}
