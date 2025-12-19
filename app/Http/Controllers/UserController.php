<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Traits\ResponseAPI;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Enums\ResponseMessage;
use App\Models\User;
use App\Models\Business;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    use ResponseAPI;
    public function index(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'business_id' => 'nullable|exists:businesses,id',
            'location_id' => 'nullable|exists:locations,id'
        ], $this->validationMessage());

        if ($validator->fails()) {
            return $this->validationResponse($validator->errors());
        }
        $wh = [];
        if (!empty($request->business_id)) {
            $wh[] = ['business_id', $request->business_id];
        }
        if (!empty($request->location_id)) {
            $wh[] = ['location_id', $request->location_id];
        }
        $roles = User::with(['roles','roles.permissions'])->where($wh)->get();

        return $this->success(ResponseMessage::FETCHED, $roles, 200);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'        => 'required|string|max:100',
            'email'       => 'required|email|unique:users,email',
            'password'    => 'required|string|min:6',
            'business_id' => 'nullable|exists:businesses,id',
            'location_id' => 'nullable|exists:locations,id',
            'role_id'    => 'required|exists:roles,id'
        ], $this->validationMessage());

        if ($validator->fails()) {
            return $this->validationResponse($validator->errors());
        }

        // Check package limits for users
        if ($request->business_id) {
            $business = Business::with('subscription_package')->find($request->business_id);
            if (!$business || !$business->subscription_package) {
                return $this->error('Business or subscription package not found', 404);
            }

            $currentUserCount = User::where('business_id', $request->business_id)->count();
            if ($currentUserCount >= $business->subscription_package->max_users) {
                return $this->error(
                    'User limit exceeded. Your package allows maximum ' . $business->subscription_package->max_users . ' users.',
                    403
                );
            }
        }

        DB::beginTransaction();

        try {
            $obj = [
                'name'        => $request->name,
                'email'       => $request->email,
                'password'    => Hash::make($request->password),
                'business_id' => $request->business_id,
                'location_id' => $request->location_id
            ];
            $user = User::create($obj);
            if (!empty($request->role_id)) {
                $role = Role::where('id', $request->role_id)->pluck('name');
                $user->syncRoles([$role->name]);
            }
            $data = User::with(['roles','roles.permissions'])->find($user->id);
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
    public function getById($user_id)
    {
        $user = User::with(['roles','roles.permissions'])->find($user_id);

        return $this->success(ResponseMessage::FETCHED_DETAIL, $user, 200);
    }


    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id'       => 'required|exists:users,id',
            'name'     => 'sometimes|string|max:100',
            'email'    => 'sometimes|email|unique:users,email,' . $request->id,
            'password' => 'nullable|string|min:6',
            'business_id' => 'nullable|exists:businesses,id',
            'location_id' => 'nullable|exists:locations,id',
            'role_id'  => 'sometimes|exists:roles,id',
        ], $this->validationMessage());
        if ($validator->fails()) {
            return $this->validationResponse($validator->errors());
        }
        DB::beginTransaction();

        try {
            $user = User::findOrFail($request->id);
            $obj = [
                'name'        => $request->name,
                'email'       => $request->email,
                'password'    => $request->password ? Hash::make($request->password) : $user->password,
                'business_id' => $request->business_id,
                'location_id' => $request->location_id
            ];
            $user->update($obj);
            if ($request->filled('role_id')) {
                $role = Role::findOrFail($request->role_id);
                $user->syncRoles([$role->name]);
            }
            DB::commit();

            return $this->success(
                ResponseMessage::UPDATE,
                $user,
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
            'id' => 'required|exists:users,id',
        ], $this->validationMessage());
        if ($validator->fails()) {
            return $this->validationResponse($validator->errors());
        }
        $user = User::find($request->id);
        $user->delete();

        return $this->success(ResponseMessage::DELETE, [], 200);
    }

    public function makeSuperAdmin(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required_without:user_id|email|exists:users,email',
                'user_id' => 'required_without:email|exists:users,id',
            ]);

            if ($validator->fails()) {
                return $this->error('Validation failed', 422, $validator->errors());
            }

            // Find user by email or user_id
            $user = $request->email
                ? User::where('email', $request->email)->first()
                : User::find($request->user_id);

            if (!$user) {
                return $this->error('User not found', 404);
            }

            // Get or create Super Admin role
            $superAdminRole = Role::firstOrCreate(['name' => 'Super Admin']);

            // Remove all existing roles and assign Super Admin
            $user->syncRoles([$superAdminRole->name]);

            // Remove business_id for Super Admin
            $user->business_id = null;
            $user->save();

            return $this->success('User has been assigned Super Admin role successfully', [
                'user_id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => 'Super Admin'
            ]);
        } catch (Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }
}
