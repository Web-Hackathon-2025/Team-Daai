<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Traits\ResponseAPI;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Enums\ResponseMessage;
use Spatie\Permission\Models\Permission;

class PermissionController extends Controller
{
    use ResponseAPI;
    public function index()
    {
        $permissions = Permission::all();

        return $this->success(ResponseMessage::FETCHED, $permissions, 200);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|unique:permissions,name'
        ],$this->validationMessage());

        if ($validator->fails()) {
            return $this->validationResponse($validator->errors());
        }
        DB::beginTransaction();

        try {
            $obj = [
                'name' => $request->name,
            ];
            $permission = Permission::create($obj);
            $data = Permission::findOrFail($permission->id);
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
    public function getById($permission_id)
    {
        $permission = Permission::find($permission_id);

        return $this->success(ResponseMessage::FETCHED_DETAIL, $permission, 200);
    }


    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|unique:permissions,name,id'
        ],$this->validationMessage());
        if ($validator->fails()) {
            return $this->validationResponse($validator->errors());
        }
        DB::beginTransaction();

        try {
            $obj = [
                'name' => $request->name
            ];
            $permission = Permission::findOrFail($request->id);
            $permission->update($obj);
            DB::commit();

            return $this->success(
                ResponseMessage::UPDATE,
                $permission,
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
            'id' => 'required|exists:permissions,id',
        ],$this->validationMessage());
        if ($validator->fails()) {
            return $this->validationResponse($validator->errors());
        }
        $permission = Permission::find($request->id);
        $permission->delete();

        return $this->success(ResponseMessage::DELETE, [], 200);
    }
}
