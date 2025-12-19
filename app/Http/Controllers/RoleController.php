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
    public function index(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'business_id' => 'nullable|exists:businesses,id'
        ], $this->validationMessage());

        if ($validator->fails()) {
            return $this->validationResponse($validator->errors());
        }
        $wh = [];
        if (!empty($request->business_id)) {
            $wh[] = ['business_id', $request->business_id];
        }
        $roles = Role::with('permissions')->where($wh)->get();

        return $this->success(ResponseMessage::FETCHED, $roles, 200);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'business_id' => 'nullable',
            'name' => 'required|string|max:191|unique:roles,name,NULL,id,business_id,' . $request->business_id,
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,name'
        ], $this->validationMessage());

        if ($validator->fails()) {
            return $this->validationResponse($validator->errors());
        }

        // Authorization check - ensure user can only create roles for their own business
        if (!auth()->user()->hasRole('Super Admin') && $request->business_id && $request->business_id != auth()->user()->business_id) {
            return $this->error('You are not authorized to create roles for another business', 403);
        }

        DB::beginTransaction();

        try {
            $obj = [
                'name' => $request->name,
                'guard_name' => 'api',
                'business_id' => $request->business_id
            ];
            $role = Role::create($obj);
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
    public function getById($role_id)
    {
        $role = Role::with('permissions')->find($role_id);

        return $this->success(ResponseMessage::FETCHED_DETAIL, $role, 200);
    }


    public function update(Request $request)
    {
        $role = Role::find($request->id);

        if (!$role) {
            return $this->error('Role not found', 404);
        }

        // Authorization check - ensure user can only update roles from their business
        if (!auth()->user()->hasRole('Super Admin') && $role->business_id != auth()->user()->business_id) {
            return $this->error('You are not authorized to update this role', 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:191|unique:roles,name,' . $request->id . ',id,business_id,' . $role->business_id,
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

        // Authorization check - ensure user can only delete roles from their business
        if (!auth()->user()->hasRole('Super Admin') && $role->business_id != auth()->user()->business_id) {
            return $this->error('You are not authorized to delete this role', 403);
        }

        $role->delete();

        return $this->success(ResponseMessage::DELETE, [], 200);
    }
}
