<?php

namespace App\Http\Controllers;

use App\Models\PaymentGateway;
use App\Traits\ResponseAPI;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Exception;

class PaymentGatewayController extends Controller
{
    use ResponseAPI;

    public function index()
    {
        try {
            $user = Auth::user();
            $gateways = PaymentGateway::where('business_id', $user->business_id)->get();

            return $this->success('Payment gateways loaded successfully', $gateways);
        } catch (Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function show($id)
    {
        try {
            $user = Auth::user();
            $gateway = PaymentGateway::where('business_id', $user->business_id)
                                    ->findOrFail($id);

            return $this->success('Payment gateway loaded successfully', $gateway);
        } catch (Exception $e) {
            return $this->error('Payment gateway not found', 404);
        }
    }

    public function store(Request $request)
    {
        try {
            $user = Auth::user();

            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'api_key' => 'required|string',
                'api_secret' => 'required|string',
                'is_active' => 'nullable|boolean',
            ]);

            if ($validator->fails()) {
                return $this->validationResponse($validator->errors());
            }

            $gateway = PaymentGateway::create([
                'business_id' => $user->business_id,
                'name' => $request->name,
                'api_key' => $request->api_key,
                'api_secret' => $request->api_secret,
                'is_active' => $request->is_active ?? true,
                'createdby_id' => $user->id,
            ]);

            return $this->success('Payment gateway created successfully', $gateway, 201);
        } catch (Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $user = Auth::user();

            $gateway = PaymentGateway::where('business_id', $user->business_id)
                                    ->findOrFail($id);

            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|string|max:255',
                'api_key' => 'sometimes|string',
                'api_secret' => 'sometimes|string',
                'is_active' => 'nullable|boolean',
            ]);

            if ($validator->fails()) {
                return $this->validationResponse($validator->errors());
            }

            $gateway->update(array_merge(
                $request->only(['name', 'api_key', 'api_secret', 'is_active']),
                ['updatedby_id' => $user->id]
            ));

            return $this->success('Payment gateway updated successfully', $gateway);
        } catch (Exception $e) {
            return $this->error('Payment gateway not found', 404);
        }
    }

    public function destroy($id)
    {
        try {
            $user = Auth::user();
            $gateway = PaymentGateway::where('business_id', $user->business_id)
                                    ->findOrFail($id);
            $gateway->delete();

            return $this->success('Payment gateway deleted successfully');
        } catch (Exception $e) {
            return $this->error('Payment gateway not found', 404);
        }
    }

    public function toggleStatus($id)
    {
        try {
            $user = Auth::user();
            $gateway = PaymentGateway::where('business_id', $user->business_id)
                                    ->findOrFail($id);

            $gateway->is_active = !$gateway->is_active;
            $gateway->updatedby_id = $user->id;
            $gateway->save();

            return $this->success('Payment gateway status updated successfully', [
                'is_active' => $gateway->is_active
            ]);
        } catch (Exception $e) {
            return $this->error('Payment gateway not found', 404);
        }
    }
}
