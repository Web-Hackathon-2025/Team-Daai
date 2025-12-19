<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Traits\ResponseAPI;
use App\Traits\LogsActivity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class ProfileController extends Controller
{
    use ResponseAPI, LogsActivity;

    /**
     * Get current user profile
     *
     * @OA\Get(
     *     path="/api/profile",
     *     tags={"Profile"},
     *     summary="Get user profile",
     *     description="Retrieve current authenticated user's profile information",
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Profile retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Profile retrieved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="email", type="string", example="user@example.com"),
     *                 @OA\Property(property="first_name", type="string", example="John"),
     *                 @OA\Property(property="last_name", type="string", example="Doe"),
     *                 @OA\Property(property="prefix", type="string", example="Mr."),
     *                 @OA\Property(property="username", type="string", example="johndoe"),
     *                 @OA\Property(property="phone", type="string", example="+1234567890"),
     *                 @OA\Property(property="alternate_phone", type="string", example="+0987654321"),
     *                 @OA\Property(property="profile_picture", type="string", example="https://api.example.com/storage/profiles/user-1.jpg"),
     *                 @OA\Property(
     *                     property="provider_profile",
     *                     type="object",
     *                     @OA\Property(property="id", type="integer"),
     *                     @OA\Property(property="category", type="string", example="Plumber"),
     *                     @OA\Property(property="location", type="string"),
     *                     @OA\Property(property="is_approved", type="boolean")
     *                 ),
     *                 @OA\Property(
     *                     property="roles",
     *                     type="array",
     *                     @OA\Items(
     *                         @OA\Property(property="id", type="integer"),
     *                         @OA\Property(property="name", type="string", example="viewer")
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function show()
    {
        $user = Auth::user()->load([
            "providerProfile",
            "roles:id,name",
        ]);

        // Add full profile picture URL
        if ($user->profile_picture) {
            $user->profile_picture = Storage::disk("public")->url(
                $user->profile_picture,
            );
        } else {
            $user->profile_picture = null;
        }

        return $this->success("Profile retrieved successfully", $user);
    }

    /**
     * Update profile information
     *
     * @OA\Post(
     *     path="/api/profile/update",
     *     tags={"Profile"},
     *     summary="Update profile information",
     *     description="Update user's personal profile details (name, email, phone, etc.)",
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="first_name", type="string", example="John", description="First name (max 255 chars)"),
     *             @OA\Property(property="last_name", type="string", example="Doe", description="Last name (max 255 chars)"),
     *             @OA\Property(property="email", type="string", format="email", example="newemail@example.com", description="Email address (must be unique)"),
     *             @OA\Property(property="prefix", type="string", example="Mr.", description="Name prefix (Mr., Mrs., Dr., etc.)"),
     *             @OA\Property(property="username", type="string", example="johndoe", description="Username (max 255 chars)"),
     *             @OA\Property(property="phone", type="string", example="+1234567890", description="Primary phone number"),
     *             @OA\Property(property="alternate_phone", type="string", example="+0987654321", description="Alternate phone number")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Profile updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Profile updated successfully"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function update(Request $request)
    {
        $user = Auth::user();
        $validated = $request->validate([
            "first_name" => "nullable|string|max:255",
            "last_name" => "nullable|string|max:255",
            "email" => "nullable|email|max:255|unique:users,email," . $user->id,
            "prefix" => "nullable|string|max:50",
            "username" =>
                "nullable|string|max:255|unique:users,username," . $user->id,
            "phone" => "nullable|string|max:20",
            "alternate_phone" => "nullable|string|max:20",
        ]);

        try {
            // Track changes for activity log
            $changes = [];

            foreach ($validated as $key => $value) {
                if ($value !== null && $user->$key !== $value) {
                    $changes[$key] = [
                        "from" => $user->$key,
                        "to" => $value,
                    ];
                }
            }

            if (empty($changes)) {
                return $this->success(
                    "No changes made to profile update",
                    $user,
                );
            }

            $user->update($validated);

            $this->logActivity(
                category: "profile",
                action: "update_profile",
                description: "Updated profile information",
                changes: $changes,
            );

            $user->load([
                "providerProfile",
                "roles:id,name",
            ]);

            return $this->success("Profile updated successfully", $user);
        } catch (\Exception $e) {
            Log::error("Failed to update profile: " . $e->getMessage());
            return $this->error(
                "Failed to update profile. Please try again.",
                500,
            );
        }
    }
    public function vmsUpdate(Request $request)
    {
        $user = Auth::user();
        $validator = \Illuminate\Support\Facades\Validator::make(
            $request->all(),
            [
                "profile_picture" =>
                    "nullable|image|mimes:jpeg,png,gif,webp|max:5120",
                "current_password" => "nullable|string|min:6",
                "new_password" => [
                    "nullable",
                    "string",
                    "min:8",
                    "confirmed",
                    "different:current_password",
                    "regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/",
                ],
                "new_password_confirmation" => "nullable|string|min:8",
            ],
            [
                "new_password.different" =>
                    "The new password must be different from the current password.",
                "new_password.regex" =>
                    "The new password must contain at least one uppercase letter, one lowercase letter, and one number.",
                "new_password.confirmed" =>
                    "The new password confirmation does not match.",
            ],
        );

        if ($validator->fails()) {
            return $this->error($validator->errors()->first(), 422);
        }

        $validated = $validator->validated();

        try {
            $updates = [];

            // Handle profile picture upload if present
            if (!empty($validated["profile_picture"])) {
                // Delete old profile picture if exists
                if (
                    $user->profile_picture &&
                    Storage::disk("public")->exists($user->profile_picture)
                ) {
                    Storage::disk("public")->delete($user->profile_picture);
                }

                // Store new picture (only store the path, not the full URL)
                $path = $validated["profile_picture"]->store("profiles", "public");
                $updates["profile_picture"] = $path; // Store only the relative path

                $this->logActivity(
                    category: "profile",
                    action: "update_picture",
                    description: "Updated profile picture",
                );
            }

            // Handle password change if requested
            if (!empty($validated["new_password"])) {
                if (
                    empty($validated["current_password"]) ||
                    !Hash::check(
                        $validated["current_password"],
                        $user->password,
                    )
                ) {
                    return $this->error("Current password is incorrect", 400);
                }

                $updates["password"] = Hash::make($validated["new_password"]);

                $this->logActivity(
                    category: "profile",
                    action: "change_password",
                    description: "Changed password",
                );
            }

            if (empty($updates)) {
                return $this->success("No changes made to profile here", $user);
            }

            // Apply updates
            $user->update($updates);

            // Reload relations
            $user->load([
                "providerProfile",
                "roles:id,name",
            ]);

            return $this->success("Profile updated successfully", $user);
        } catch (\Exception $e) {
            Log::error("Failed to upload profile: " . $e->getMessage());
            return $this->error(
                "Failed to upload profile. Please try again.",
                500,
            );
        }
    }

    /**
     * Upload profile picture
     *
     * @OA\Post(
     *     path="/api/profile/update-picture",
     *     tags={"Profile"},
     *     summary="Upload profile picture",
     *     description="Upload or update user's profile picture (JPEG, PNG, GIF - max 5MB)",
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         content={
     *             @OA\MediaType(
     *                 mediaType="multipart/form-data",
     *                 @OA\Schema(
     *                     required={"picture"},
     *                     @OA\Property(
     *                         property="picture",
     *                         type="string",
     *                         format="binary",
     *                         description="Profile picture file (JPEG, PNG, GIF - max 5MB)"
     *                     )
     *                 )
     *             )
     *         }
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Profile picture uploaded successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Profile picture updated successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="profile_picture", type="string", example="profiles/user-1-abc123.jpg"),
     *                 @OA\Property(property="profile_picture_url", type="string", example="https://api.example.com/storage/profiles/user-1-abc123.jpg")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=422, description="Invalid file or file too large"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function updatePicture(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            "profile_picture" =>
                "required|image|mimes:jpeg,png,gif,webp|max:5120", // 5MB
        ]);

        try {
            // Delete old profile picture if exists
            if (
                $user->profile_picture &&
                Storage::disk("public")->exists($user->profile_picture)
            ) {
                Storage::disk("public")->delete($user->profile_picture);
            }

            // Upload new picture
            $path = $validated["profile_picture"]->store("profiles", "public");

            // Update user profile picture path
            $user->update(["profile_picture" => $path]);

            $this->logActivity(
                category: "profile",
                action: "update_picture",
                description: "Updated profile picture",
            );

            return $this->success("Profile picture updated successfully", [
                "profile_picture" => $path,
                "profile_picture_url" => Storage::disk("public")->url($path),
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to upload profile picture: " . $e->getMessage());
            return $this->error(
                "Failed to upload profile picture. Please try again.",
                500,
            );
        }
    }

    /**
     * Change password
     *
     * @OA\Post(
     *     path="/api/profile/change-password",
     *     tags={"Profile"},
     *     summary="Change user password",
     *     description="Change the current user's password with verification of existing password",
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"current_password", "new_password", "new_password_confirmation"},
     *             @OA\Property(property="current_password", type="string", format="password", example="currentPassword123", description="Current password for verification"),
     *             @OA\Property(property="new_password", type="string", format="password", example="newPassword123", description="New password (min 8 chars, must differ from current)"),
     *             @OA\Property(property="new_password_confirmation", type="string", format="password", example="newPassword123", description="Confirm new password")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Password changed successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Password changed successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="message", type="string", example="Please login again with your new password")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=400, description="Current password is incorrect"),
     *     @OA\Response(response=422, description="Validation error - password too weak or does not match confirmation"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function changePassword(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate(
            [
                "current_password" => "required|string|min:6",
                "new_password" => [
                    "required",
                    "string",
                    "min:8",
                    "confirmed",
                    "different:current_password",
                    "regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/", // At least one lowercase, one uppercase, one digit
                ],
                "new_password_confirmation" => "required|string|min:8",
            ],
            [
                "new_password.different" =>
                    "The new password must be different from the current password.",
                "new_password.regex" =>
                    "The new password must contain at least one uppercase letter, one lowercase letter, and one number.",
                "new_password.confirmed" =>
                    "The new password confirmation does not match.",
            ],
        );

        try {
            // Verify current password
            if (!Hash::check($validated["current_password"], $user->password)) {
                return $this->error("Current password is incorrect", 400);
            }

            // Update password
            $user->update([
                "password" => Hash::make($validated["new_password"]),
            ]);

            $this->logActivity(
                category: "profile",
                action: "change_password",
                description: "Changed password",
            );

            return $this->success("Password changed successfully", [
                "message" => "Please login again with your new password",
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to change password: " . $e->getMessage());
            return $this->error(
                "Failed to change password. Please try again.",
                500,
            );
        }
    }
}
