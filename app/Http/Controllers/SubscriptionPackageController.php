<?php

namespace App\Http\Controllers;

use App\Enums\ResponseMessage;
use App\Models\SubscriptionPackage;
use App\Traits\ResponseAPI;
use App\Traits\LogsActivity;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class SubscriptionPackageController extends Controller
{
    use ResponseAPI, LogsActivity;
    public function index()
    {
        $subscription_package = SubscriptionPackage::all();

        return $this->success(ResponseMessage::FETCHED, $subscription_package, 200);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'description' => 'required|string',
            'price' => 'required|numeric',
            'price_interval' => 'required|in:monthly,yearly',
            'duration' => 'nullable|integer',
            'trial_days' => 'nullable|integer',
            'max_cameras' => 'nullable|integer',
            'max_locations' => 'nullable|integer',
            'max_users' => 'nullable|integer',
            'analytics_enabled' => 'nullable|boolean',
            'api_access_enabled' => 'nullable|boolean',
            'recording_enabled' => 'nullable|boolean',
            'motion_detection_enabled' => 'nullable|boolean',
            'payment_gateway_id' => 'nullable|exists:payment_gateways,id',
        ],$this->validationMessage());
        if ($validator->fails()) {
            return $this->validationResponse($validator->errors());
        }
        DB::beginTransaction();

        try {
            $obj = [
                'name' => $request->name,
                'description' => $request->description,
                'price' => $request->price,
                'price_interval' => $request->price_interval,
                'duration' => $request->duration ?? 0,
                'trial_days' => $request->trial_days ?? 0,
                'max_cameras' => $request->max_cameras ?? 0,
                'max_locations' => $request->max_locations ?? 0,
                'max_users' => $request->max_users ?? 0,
                'analytics_enabled' => $request->analytics_enabled ?? false,
                'api_access_enabled' => $request->api_access_enabled ?? false,
                'recording_enabled' => $request->recording_enabled ?? false,
                'motion_detection_enabled' => $request->motion_detection_enabled ?? false,
                'is_active' => 1,
                'createdby_id' => auth()->user()->id
            ];
            $subscription_package = SubscriptionPackage::create($obj);
            // $features = $request->input('features', []);
            // if (is_array($features) && count($features) > 0) {
            //     $subscription_package->features()->sync($features);
            // }
            $subscription_package_data = SubscriptionPackage::find($subscription_package->id);

            // Log activity
            $this->logPackageActivity(
                'Created',
                "Subscription package '{$subscription_package->name}' was created with price {$subscription_package->price} ({$subscription_package->price_interval})",
                $subscription_package
            );

            DB::commit();
            return $this->success(
                ResponseMessage::SAVE,
                $subscription_package_data,
                200
            );
        } catch (Exception $e) {
            DB::rollBack();
            return $this->error($e->getMessage(), 500);
        }
    }
    public function getById($subscription_package_id)
    {
        $subscription_package = SubscriptionPackage::find($subscription_package_id);
        return $this->success(ResponseMessage::FETCHED_DETAIL, $subscription_package, 200);
    }


    public function update(Request $request)
    {
        // dd($request->all());
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:subscription_packages,id',
            'name' => 'required|string',
            'description' => 'required|string',
            'price' => 'required|numeric',
            'price_interval' => 'required|in:monthly,yearly',
            'duration' => 'nullable|integer',
            'trial_days' => 'nullable|integer',
            'max_cameras' => 'nullable|integer',
            'max_locations' => 'nullable|integer',
            'max_users' => 'nullable|integer',
            'analytics_enabled' => 'nullable|boolean',
            'api_access_enabled' => 'nullable|boolean',
            'recording_enabled' => 'nullable|boolean',
            'motion_detection_enabled' => 'nullable|boolean',
            'payment_gateway_id' => 'nullable|exists:payment_gateways,id',
        ],$this->validationMessage());
        if ($validator->fails()) {
            return $this->validationResponse($validator->errors());
        }
        DB::beginTransaction();

        try {
            $obj = [
                'name' => $request->name,
                'description' => $request->description,
                'price' => $request->price,
                'price_interval' => $request->price_interval,
                'duration' => $request->duration ?? 1,
                'trial_days' => $request->trial_days ?? 0,
                'max_cameras' => $request->max_cameras ?? 0,
                'max_locations' => $request->max_locations ?? 0,
                'max_users' => $request->max_users ?? 0,
                'analytics_enabled' => $request->analytics_enabled ?? false,
                'api_access_enabled' => $request->api_access_enabled ?? false,
                'recording_enabled' => $request->recording_enabled ?? false,
                'motion_detection_enabled' => $request->motion_detection_enabled ?? false,
                'updatedby_id' => auth()->user()->id
            ];
            $subscription_package = SubscriptionPackage::where('id', $request->id)->update($obj);
            // $features = $request->input('features', []);
            // if (is_array($features) && count($features) > 0) {
            //     $subscription_package->features()->sync($features);
            // }
            $data = SubscriptionPackage::find($request->id);

            // Log activity
            $this->logPackageActivity(
                'Updated',
                "Subscription package '{$data->name}' was updated",
                $data
            );

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
            'id' => 'required|exists:subscription_packages,id',
        ],$this->validationMessage());
        if ($validator->fails()) {
            return $this->validationResponse($validator->errors());
        }
        $subscription_package = SubscriptionPackage::find($request->id);
        $subscription_package->is_active = $subscription_package->is_active == 1 ? 0 : 1;
        $subscription_package->updatedby_id = auth()->user()->id;
        $subscription_package->update();

        // Log activity
        $status = $subscription_package->is_active ? 'activated' : 'deactivated';
        $this->logPackageActivity(
            'Status Changed',
            "Subscription package '{$subscription_package->name}' was {$status}",
            $subscription_package
        );

        return $this->success(ResponseMessage::UPDATE_STATUS, $subscription_package, 200);
    }
    public function destroy(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:subscription_packages,id',
        ],$this->validationMessage());
        if ($validator->fails()) {
            return $this->validationResponse($validator->errors());
        }

        $subscription_package = SubscriptionPackage::find($request->id);

        // Check if package is associated with any business
        $businessCount = \App\Models\Business::where('subscription_package_id', $request->id)->count();

        if ($businessCount > 0) {
            return $this->error('Cannot delete this package. It is currently assigned to ' . $businessCount . ' business(es).', 400);
        }

        $packageName = $subscription_package->name;
        $subscription_package->deletedby_id = auth()->user()->id;
        $subscription_package->update();
        $subscription_package->delete();

        // Log activity
        $this->logPackageActivity(
            'Deleted',
            "Subscription package '{$packageName}' was deleted",
            null
        );

        return $this->success(ResponseMessage::DELETE, [], 200);
    }
}
