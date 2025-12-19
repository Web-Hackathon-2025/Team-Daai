<?php

namespace App\Http\Controllers;

use App\Enums\ResponseMessage;
use App\Models\Location;
use App\Models\Business;
use Illuminate\Http\Request;
use App\Traits\ResponseAPI;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class LocationController extends Controller
{
    use ResponseAPI;
    public function index()
    {
        $location = Location::with('subscription_package')->get();

        return $this->success(ResponseMessage::FETCHED, $location, 200);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255',
            'phone' => 'required|string|max:255',
            'address' => 'required|string|max:255',
            'logo' => 'required|png|jpg|jpeg|max:2048',
            'business_id' => 'nullable|exists:businesses,id',
        ],$this->validationMessage());
        $validator->sometimes('business_id', 'required', function ($input) {
            return auth()->user() && auth()->user()->roles[0]->name === 'Super Admin';
        });
        if ($validator->fails()) {
            return $this->validationResponse($validator->errors());
        }

        // Determine business_id
        $business_id = !empty($request->business_id) ? $request->business_id : auth()->user()->business_id;

        // Check package limits for locations
        if ($business_id) {
            $business = Business::with('subscription_package')->find($business_id);
            if (!$business || !$business->subscription_package) {
                return $this->error('Business or subscription package not found', 404);
            }

            $currentLocationCount = Location::where('business_id', $business_id)->count();
            if ($currentLocationCount >= $business->subscription_package->max_locations) {
                return $this->error(
                    'Location limit exceeded. Your package allows maximum ' . $business->subscription_package->max_locations . ' locations.',
                    403
                );
            }
        }

        DB::beginTransaction();

        try {
            if ($request->hasFile('logo')) {
                $obj['logo'] = $request->file('logo')->store('location', 'public');
            }
            $obj = [
                'name' => $request->name,
                'owner' => $request->owner,
                'email' => $request->email,
                'phone' => $request->phone,
                'address' => $request->address,
                'logo' => $request->logo,
                'business_id' => !empty($request->business_id)?$request->business_id:auth()->user()->business_id,
                'is_active' => 1,
                'createdby_id' => auth()->user()->id
            ];
            $location = Location::create($obj);
            $data = Location::with('business')->find($location->id);
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
    public function getById($location_id)
    {
        $location = Location::with('business')->find($location_id);

        return $this->success(ResponseMessage::FETCHED_DETAIL, $location, 200);
    }


    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255',
            'phone' => 'required|string|max:255',
            'address' => 'required|string|max:255',
            'logo' => 'required|png|jpg|jpeg|max:2048',
            'business_id' => 'nullable|exists:businesses,id',
        ],$this->validationMessage());
        $validator->sometimes('business_id', 'required', function ($input) {
            return auth()->user() && auth()->user()->roles[0]->name === 'Super Admin';
        });
        if ($validator->fails()) {
            return $this->validationResponse($validator->errors());
        }

        // Authorization check - ensure user can only update locations from their business
        $location = Location::find($request->id);
        if (!$location) {
            return $this->error('Location not found', 404);
        }

        if (!auth()->user()->hasRole('Super Admin') && $location->business_id != auth()->user()->business_id) {
            return $this->error('You are not authorized to update this location', 403);
        }

        DB::beginTransaction();

        try {
            $obj = [
                'name' => $request->name,
                'owner' => $request->owner,
                'email' => $request->email,
                'phone' => $request->phone,
                'address' => $request->address,
                'logo' => $request->logo,
                'business_id' => !empty($request->business_id)?$request->business_id:auth()->user()->business_id,
                'is_active' => 1,
                'updatedby_id' => auth()->user()->id
            ];
            $location = Location::where('id', $request->id)->update($obj);
            $data = Location::with('subscription_package')->find($request->id);
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
            'id' => 'required|exists:locations,id',
        ],$this->validationMessage());
        if ($validator->fails()) {
            return $this->validationResponse($validator->errors());
        }
        $location = Location::find($request->id);

        // Authorization check
        if (!auth()->user()->hasRole('Super Admin') && $location->business_id != auth()->user()->business_id) {
            return $this->error('You are not authorized to update this location', 403);
        }

        $location->is_active = $location->is_active == 1 ? 0 : 1;
        $location->updatedby_id = auth()->user()->id;
        $location->update();

        return $this->success(ResponseMessage::UPDATE_STATUS, $location, 200);
    }
    public function destroy(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:locations,id',
        ],$this->validationMessage());
        if ($validator->fails()) {
            return $this->validationResponse($validator->errors());
        }
        $location = Location::find($request->id);

        // Authorization check
        if (!auth()->user()->hasRole('Super Admin') && $location->business_id != auth()->user()->business_id) {
            return $this->error('You are not authorized to delete this location', 403);
        }

        $location->deletedby_id = auth()->user()->id;
        $location->update();
        $location->delete();

        return $this->success(ResponseMessage::DELETE, [], 200);
    }
}
