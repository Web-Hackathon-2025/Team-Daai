<?php

namespace App\Http\Controllers;

use App\Enums\ResponseMessage;
use App\Models\Business;
use App\Models\SubscriptionPackage;
use App\Models\User;
use App\Traits\ResponseAPI;
use App\Traits\LogsActivity;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class BusinessController extends Controller
{
    use ResponseAPI, LogsActivity;
    public function index()
    {
        $business = Business::with('subscription_package')->get();

        return $this->success(ResponseMessage::FETCHED, $business, 200);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            // Business fields
            'name' => 'required|string|max:255',
            'domain' => 'required|string|max:255',
            'owner' => 'nullable|string|max:255',
            'email' => 'required|string|email|max:255',
            'phone' => 'required|string|max:255',
            'address' => 'nullable|string|max:255',
            'website' => 'nullable|string|max:255',
            'logo' => 'nullable|image|mimes:png,jpg,jpeg|max:2048',
            'subscription_package_id' => 'required|exists:subscription_packages,id',
            'start_date' => 'nullable|date',
            'alternate_phone' => 'nullable|string|max:255',
            // Address
            'country' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'zip_code' => 'nullable|string|max:20',
            'landmark' => 'nullable|string|max:255',
            // Settings
            'timezone' => 'nullable|string|max:255',
            'currency' => 'nullable|string|max:10',
            // Payment
            'paid_via' => 'nullable|string|max:255',
            'payment_transaction_id' => 'nullable|string|max:255',

            // Owner user fields
            'email' => 'required|string|email|max:255|unique:users,email',
            'username' => 'nullable|string|max:255|unique:users,username',
            'password' => 'required|string|min:6|confirmed',
            'password_confirmation' => 'required',
            'prefix' => 'nullable|string|max:10',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:255',
        ],$this->validationMessage());

        if ($validator->fails()) {
            return $this->validationResponse($validator->errors());
        }

        DB::beginTransaction();

        try {
            $package = SubscriptionPackage::find($request->subscription_package_id);
            $expiryDate = Carbon::now()->addDays($package->duration)->format('Y-m-d');

            // Create Business
            $businessData = [
                'name' => $request->name,
                'domain' => $request->domain,
                'owner' => $request->owner,
                'email' => $request->email,
                'phone' => $request->phone,
                'address' => $request->address,
                'website' => $request->website,
                'subscription_package_id' => $request->subscription_package_id,
                'subscription_status' => 'active',
                'subscription_end_date' => $expiryDate,
                'is_active' => 1,
                'createdby_id' => auth()->user()->id,
                'start_date' => $request->start_date,
                'alternate_phone' => $request->alternate_phone,
                'country' => $request->country,
                'state' => $request->state,
                'city' => $request->city,
                'zip_code' => $request->zip_code,
                'landmark' => $request->landmark,
                'timezone' => $request->timezone ?? 'UTC',
                'currency' => $request->currency ?? 'USD',
                'paid_via' => $request->paid_via,
                'payment_transaction_id' => $request->payment_transaction_id,
            ];

            if ($request->hasFile('logo')) {
                $businessData['logo'] = $request->file('logo')->store('business', 'public');
            }

            $business = Business::create($businessData);

            // Create Business Owner User
            $ownerName = trim($request->first_name . ' ' . $request->last_name);
            $owner = User::create([
                'business_id' => $business->id,
                'name' => $ownerName,
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'prefix' => $request->prefix,
                'email' => $request->email,
                'username' => $request->username,
                'password' => Hash::make($request->password),
                'alternate_phone' => $request->phone,
            ]);

            // Assign business-admin role
            $owner->assignRole('Business Admin');

            $data = Business::with('subscription_package', 'users')->find($business->id);

            // Log activity
            $this->logBusinessActivity(
                'Created',
                "Business '{$business->name}' was created with subscription package '{$package->name}'",
                $business
            );

            DB::commit();

            return $this->success(
                ResponseMessage::SAVE,
                [
                    'business' => $data,
                    'owner' => $owner->makeVisible('email'),
                    'message' => 'Business created successfully. Owner can now login with provided credentials.'
                ],
                200
            );
        } catch (Exception $e) {
            DB::rollBack();
            return $this->error($e->getMessage(), 500);
        }
    }
    public function getById($business_id)
    {
        $business = Business::with('subscription_package','adminUser')->find($business_id);

        return $this->success(ResponseMessage::FETCHED_DETAIL, $business, 200);
    }


    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:businesses,id',
            'name' => 'required|string|max:255',
            'owner' => 'nullable|string|max:255',
            'email' => 'required|string|email|max:255',
            'phone' => 'required|string|max:255',
            'address' => 'nullable|string|max:255',
            'website' => 'nullable|string|max:255',
            'logo' => 'nullable|image|mimes:png,jpg,jpeg|max:2048',
            'subscription_package_id' => 'required|exists:subscription_packages,id',
            'start_date' => 'nullable|date',
            'alternate_phone' => 'nullable|string|max:255',
            // Address
            'country' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'zip_code' => 'nullable|string|max:20',
            'landmark' => 'nullable|string|max:255',
            // Settings
            'timezone' => 'nullable|string|max:255',
            'currency' => 'nullable|string|max:10',
            // Payment
            'paid_via' => 'nullable|string|max:255',
            'payment_transaction_id' => 'nullable|string|max:255',
        ],$this->validationMessage());

        if ($validator->fails()) {
            return $this->validationResponse($validator->errors());
        }

        DB::beginTransaction();

        try {
            $package = SubscriptionPackage::find($request->subscription_package_id);
            $expiryDate = Carbon::now()->addDays($package->duration)->format('Y-m-d');

            $businessData = [
                'name' => $request->name,
                'owner' => $request->owner,
                'email' => $request->email,
                'phone' => $request->phone,
                'address' => $request->address,
                'website' => $request->website,
                'subscription_package_id' => $request->subscription_package_id,
                'subscription_status' => 'active',
                'subscription_end_date' => $expiryDate,
                'is_active' => 1,
                'updatedby_id' => auth()->user()->id,
                'start_date' => $request->start_date,
                'alternate_phone' => $request->alternate_phone,
                'country' => $request->country,
                'state' => $request->state,
                'city' => $request->city,
                'zip_code' => $request->zip_code,
                'landmark' => $request->landmark,
                'timezone' => $request->timezone,
                'currency' => $request->currency,
                'paid_via' => $request->paid_via,
                'payment_transaction_id' => $request->payment_transaction_id,
            ];

            if ($request->hasFile('logo')) {
                $businessData['logo'] = $request->file('logo')->store('business', 'public');
            }

            $business = Business::where('id', $request->id)->update($businessData);
            $data = Business::with('subscription_package')->find($request->id);

            // Log activity
            $this->logBusinessActivity(
                'Updated',
                "Business '{$data->name}' was updated",
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
            'id' => 'required|exists:businesses,id',
        ],$this->validationMessage());
        if ($validator->fails()) {
            return $this->validationResponse($validator->errors());
        }
        $business = Business::find($request->id);
        $business->is_active = $business->is_active == 1 ? 0 : 1;
        $business->updatedby_id = auth()->user()->id;
        $business->update();

        // Log activity
        $status = $business->is_active ? 'activated' : 'deactivated';
        $this->logBusinessActivity(
            'Status Changed',
            "Business '{$business->name}' was {$status}",
            $business
        );

        return $this->success(ResponseMessage::UPDATE_STATUS, $business, 200);
    }
    public function destroy(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:businesses,id',
        ],$this->validationMessage());
        if ($validator->fails()) {
            return $this->validationResponse($validator->errors());
        }
        $business = Business::find($request->id);
        $businessName = $business->name;
        $business->deletedby_id = auth()->user()->id;
        $business->update();
        $business->delete();

        // Log activity
        $this->logBusinessActivity(
            'Deleted',
            "Business '{$businessName}' was deleted",
            null
        );

        return $this->success(ResponseMessage::DELETE, [], 200);
    }
}
