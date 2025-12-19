<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Traits\ResponseAPI;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class ProviderController extends Controller
{
    use ResponseAPI;

    /**
     * Get all service providers with optional filters
     * GET /api/providers
     */
    public function index(Request $request)
    {
        try {
            $query = User::role('service_provider')
                ->with(['providerProfile.services', 'reviews']);

            // Filter by category
            if ($request->has('category')) {
                $query->whereHas('providerProfile', function ($q) use ($request) {
                    $q->where('category', $request->category);
                });
            }

            // Filter by location
            if ($request->has('location')) {
                $query->whereHas('providerProfile', function ($q) use ($request) {
                    $q->where('location', 'LIKE', '%' . $request->location . '%');
                });
            }

            // Search by name or service
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'LIKE', '%' . $search . '%')
                        ->orWhereHas('providerProfile.services', function ($sq) use ($search) {
                            $sq->where('name', 'LIKE', '%' . $search . '%');
                        });
                });
            }

            // Pagination
            $limit = $request->get('limit', 10);
            $providers = $query->paginate($limit);

            // Transform data
            $data = $providers->map(function ($provider) {
                return [
                    'id' => $provider->id,
                    'name' => $provider->name,
                    'email' => $provider->email,
                    'phone' => $provider->phone ?? $provider->providerProfile->phone ?? null,
                    'category' => $provider->providerProfile->category ?? null,
                    'location' => $provider->providerProfile->location ?? null,
                    'rating' => round($provider->reviews->avg('rating') ?? 0, 1),
                    'total_reviews' => $provider->reviews->count(),
                    'services' => $provider->providerProfile->services->map(function ($service) {
                        return [
                            'id' => $service->id,
                            'name' => $service->name,
                            'price' => $service->price,
                            'description' => $service->description,
                        ];
                    }) ?? [],
                ];
            });

            return $this->success('Providers retrieved successfully', [
                'providers' => $data,
                'total' => $providers->total(),
                'page' => $providers->currentPage(),
                'limit' => $providers->perPage(),
            ]);
        } catch (\Exception $e) {
            return $this->error('Failed to retrieve providers: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get single service provider details
     * GET /api/providers/{id}
     */
    public function show($id)
    {
        try {
            $provider = User::role('service_provider')
                ->with(['providerProfile.services', 'reviews.customer'])
                ->findOrFail($id);

            $data = [
                'id' => $provider->id,
                'name' => $provider->name,
                'email' => $provider->email,
                'phone' => $provider->phone ?? $provider->providerProfile->phone ?? null,
                'category' => $provider->providerProfile->category ?? null,
                'location' => $provider->providerProfile->location ?? null,
                'rating' => round($provider->reviews->avg('rating') ?? 0, 1),
                'total_reviews' => $provider->reviews->count(),
                'availability' => $provider->providerProfile->availability ?? null,
                'services' => $provider->providerProfile->services->map(function ($service) {
                    return [
                        'id' => $service->id,
                        'name' => $service->name,
                        'price' => $service->price,
                        'description' => $service->description,
                    ];
                }) ?? [],
                'reviews' => $provider->reviews->take(10)->map(function ($review) {
                    return [
                        'id' => $review->id,
                        'customer_name' => $review->customer->name ?? 'Anonymous',
                        'rating' => $review->rating,
                        'comment' => $review->comment,
                        'created_at' => $review->created_at->format('Y-m-d'),
                    ];
                }),
            ];

            return $this->success('Provider details retrieved successfully', ['provider' => $data]);
        } catch (\Exception $e) {
            return $this->error('Provider not found', 404);
        }
    }

    /**
     * Get current service provider's own profile
     * GET /api/providers/me
     */
    public function getOwnProfile()
    {
        try {
            $provider = Auth::user()->load(['providerProfile.services', 'reviews']);

            $data = [
                'id' => $provider->id,
                'name' => $provider->name,
                'email' => $provider->email,
                'phone' => $provider->phone ?? $provider->providerProfile->phone ?? null,
                'category' => $provider->providerProfile->category ?? null,
                'location' => $provider->providerProfile->location ?? null,
                'availability' => $provider->providerProfile->availability ?? null,
                'rating' => round($provider->reviews->avg('rating') ?? 0, 1),
                'total_reviews' => $provider->reviews->count(),
                'services' => $provider->providerProfile->services ?? [],
            ];

            return $this->success('Profile retrieved successfully', ['provider' => $data]);
        } catch (\Exception $e) {
            return $this->error('Failed to retrieve profile: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Update service provider profile
     * PUT /api/providers/me
     */
    public function updateOwnProfile(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'phone' => 'sometimes|string|max:20',
            'category' => 'sometimes|string|max:100',
            'location' => 'sometimes|string|max:255',
            'availability' => 'sometimes|string|max:255',
            'services' => 'sometimes|array',
            'services.*.name' => 'required_with:services|string|max:255',
            'services.*.price' => 'required_with:services|numeric|min:0',
            'services.*.description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->validationResponse($validator->errors());
        }

        try {
            DB::beginTransaction();

            $user = Auth::user();

            // Update user details
            if ($request->has('name')) {
                $user->name = $request->name;
            }
            if ($request->has('phone')) {
                $user->phone = $request->phone;
            }
            $user->save();

            // Update or create provider profile
            $profileData = [];
            if ($request->has('category')) $profileData['category'] = $request->category;
            if ($request->has('location')) $profileData['location'] = $request->location;
            if ($request->has('availability')) $profileData['availability'] = $request->availability;

            if (!empty($profileData)) {
                $user->providerProfile()->updateOrCreate(
                    ['user_id' => $user->id],
                    $profileData
                );
            }

            // Update services
            if ($request->has('services')) {
                // Delete existing services
                $user->providerProfile->services()->delete();

                // Create new services
                foreach ($request->services as $service) {
                    $user->providerProfile->services()->create($service);
                }
            }

            DB::commit();

            return $this->success('Profile updated successfully', [
                'provider' => $user->fresh(['providerProfile.services'])
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error('Failed to update profile: ' . $e->getMessage(), 500);
        }
    }
}
