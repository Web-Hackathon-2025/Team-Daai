<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Traits\ResponseAPI;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Exception;

class AdminSettingsController extends Controller
{
    use ResponseAPI;

    public function index()
    {
        try {
            $user = Auth::user();

            // If user has no business and is super admin, create one
            if (!$user->business_id && $user->hasRole('Super Admin')) {
                $business = Business::create([
                    'name' => 'Super Admin Business',
                    'email' => $user->email,
                    'is_active' => true,
                    'createdby_id' => $user->id,
                ]);

                $user->business_id = $business->id;
                $user->save();
            } else {
                $business = Business::find($user->business_id);
            }

            if (!$business) {
                return $this->error('Business not found', 404);
            }

            return $this->success('Settings loaded successfully', $business);
        } catch (Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function update(Request $request)
    {

        try {
            $user = Auth::user();

            // Super Admin doesn't need a business
            if ($user->hasRole('Super Admin')) {
                return $this->success('Settings updated successfully for Super Admin');
            }

            if (!$user->business_id) {
                return $this->error('No business associated with this user', 404);
            }

            $business = Business::findOrFail($user->business_id);
            $business->update($request->all());

            return $this->success('Settings updated successfully', $business);
        } catch (Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function getEmailSettings()
    {
        try {
            $user = Auth::user();

            // If user has no business and is super admin, create one
            if (!$user->business_id && $user->hasRole('Super Admin')) {
                $business = Business::create([
                    'name' => 'Super Admin Business',
                    'email' => $user->email,
                    'is_active' => true,
                    'createdby_id' => $user->id,
                ]);

                $user->business_id = $business->id;
                $user->save();
            } else {
                $business = Business::findOrFail($user->business_id);
            }

            // Return email settings from business or default values
            $emailSettings = [
                'mail_driver' => $business->mail_driver ?? 'smtp',
                'mail_host' => $business->mail_host ?? '',
                'mail_port' => $business->mail_port ?? '587',
                'mail_username' => $business->mail_username ?? '',
                'mail_password' => $business->mail_password ?? '',
                'mail_encryption' => $business->mail_encryption ?? 'tls',
                'mail_from_address' => $business->mail_from_address ?? '',
                'mail_from_name' => $business->mail_from_name ?? '',
                'enable_welcome_email_to_new_business' => $business->enable_welcome_email_to_new_business ?? false,
                'enable_new_business_registration_email' => $business->enable_new_business_registration_email ?? false,
                'enable_new_subscription_email' => $business->enable_new_subscription_email ?? false,
                'welcome_email_subject' => $business->welcome_email_subject ?? 'Welcome!',
            ];

            return $this->success('Email settings loaded successfully', $emailSettings);
        } catch (Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function updateEmailSettings(Request $request)
    {
        try {
            $user = Auth::user();

            // Super Admin doesn't need a business for email settings
            if ($user->hasRole('Super Admin')) {
                return $this->success('Email settings updated successfully for Super Admin');
            }

            if (!$user->business_id) {
                return $this->error('No business associated with this user', 404);
            }

            $business = Business::findOrFail($user->business_id);

            $validator = Validator::make($request->all(), [
                'mail_driver' => 'nullable|string',
                'mail_host' => 'nullable|string',
                'mail_port' => 'nullable|string',
                'mail_username' => 'nullable|string',
                'mail_password' => 'nullable|string',
                'mail_encryption' => 'nullable|string',
                'mail_from_address' => 'nullable|email',
                'mail_from_name' => 'nullable|string',
                'enable_welcome_email_to_new_business' => 'nullable|in:0,1,true,false',
                'enable_new_business_registration_email' => 'nullable|in:0,1,true,false',
                'enable_new_subscription_email' => 'nullable|in:0,1,true,false',
                'welcome_email_subject' => 'nullable|string',
            ]);

            // if ($validator->fails()) {
            //     return $this->error('Validation failed', 422, $validator->errors());
            // }

            // Convert boolean strings to actual booleans
            $data = $request->all();
            if (isset($data['enable_welcome_email_to_new_business'])) {
                $data['enable_welcome_email_to_new_business'] = filter_var($data['enable_welcome_email_to_new_business'], FILTER_VALIDATE_BOOLEAN);
            }
            if (isset($data['enable_new_business_registration_email'])) {
                $data['enable_new_business_registration_email'] = filter_var($data['enable_new_business_registration_email'], FILTER_VALIDATE_BOOLEAN);
            }
            if (isset($data['enable_new_subscription_email'])) {
                $data['enable_new_subscription_email'] = filter_var($data['enable_new_subscription_email'], FILTER_VALIDATE_BOOLEAN);
            }

            $business->update($data);

            return $this->success('Email settings updated successfully', $business);
        } catch (Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }
}
