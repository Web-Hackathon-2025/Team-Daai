<?php

namespace App\Http\Controllers;

use App\Enums\ResponseMessage;
use App\Models\SubscriptionPackageFeature;
use App\Traits\ResponseAPI;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class SubscriptionPackageFeatureController extends Controller
{
    use ResponseAPI;
    public function index()
    {
        $subscription_package_features = SubscriptionPackageFeature::get();
        return $this->success(
            ResponseMessage::FETCHED,
            $subscription_package_features,
            200
        );
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'description' => 'required|string'
        ],$this->validationMessage());
        if ($validator->fails()) {
            return $this->validationResponse($validator->errors());
        }
        DB::beginTransaction();

        try {
            $obj = [
                'name' => $request->name,
                'description' => $request->description,
                'is_active' => 1,
                'createdby_id' => auth()->user()->id
            ];
            $subscription_package_feature = SubscriptionPackageFeature::create($obj);
            $data = SubscriptionPackageFeature::find($subscription_package_feature->id);
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
    public function getById($subscription_package_feature_id)
    {
        $subscription_package_feature = SubscriptionPackageFeature::find($subscription_package_feature_id);
        return $this->success(ResponseMessage::FETCHED_DETAIL, $subscription_package_feature, 200);
    }


    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:subscription_package_features,id',
            'name' => 'required|string',
            'description' => 'required|string'
        ],$this->validationMessage());
        if ($validator->fails()) {
            return $this->validationResponse($validator->errors());
        }
        DB::beginTransaction();

        try {
            $obj = [
                'name' => $request->name,
                'description' => $request->description,
                'is_active' => 1,
                'updatedby_id' => auth()->user()->id
            ];
            $subscription_package_feature = SubscriptionPackageFeature::where('id', $request->id)->update($obj);
            $subscription_package_feature->features()->sync($request->features);
            $data = SubscriptionPackageFeature::find($request->id);
            DB::commit();
            return $this->success(
                ResponseMessage::UPDATE,
                $data,
                200
            );
        } catch (Exception $e) {
            DB::rollBack();
            return $this->error($e->getMessage(), 500);
        }
    }
    public function status(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:subscription_package_features,id',
        ],$this->validationMessage());
        if ($validator->fails()) {
            return $this->validationResponse($validator->errors());
        }
        $subscription_package_feature = SubscriptionPackageFeature::find($request->id);
        $subscription_package_feature->is_active = $subscription_package_feature->is_active == 1 ? 0 : 1;
        $subscription_package_feature->updatedby_id = auth()->user()->id;
        $subscription_package_feature->update();
        return $this->success(ResponseMessage::UPDATE_STATUS, $subscription_package_feature, 200);
    }
    public function destroy(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:subscription_package_features,id',
        ],$this->validationMessage());
        if ($validator->fails()) {
            return $this->validationResponse($validator->errors());
        }
        $subscription_package_feature = SubscriptionPackageFeature::find($request->id);
        $subscription_package_feature->delete();
        return $this->success(ResponseMessage::DELETE, [], 200);
    }
}
