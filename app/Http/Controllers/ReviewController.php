<?php

namespace App\Http\Controllers;

use App\Models\Review;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Traits\ResponseAPI;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class ReviewController extends Controller
{
    use ResponseAPI;

    /**
     * @OA\Post(
     *     path="/api/reviews",
     *     tags={"Reviews & Ratings"},
     *     summary="Submit a review",
     *     description="Customer submits a review and rating for a completed service",
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"request_id", "provider_id", "rating"},
     *             @OA\Property(property="request_id", type="integer", example=1),
     *             @OA\Property(property="provider_id", type="integer", example=2),
     *             @OA\Property(property="rating", type="integer", minimum=1, maximum=5, example=5),
     *             @OA\Property(property="comment", type="string", example="Excellent service!")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Review submitted successfully"),
     *     @OA\Response(response=400, description="Validation error or review already exists")
     * )
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'request_id' => 'required|exists:service_requests,id',
            'provider_id' => 'required|exists:users,id',
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return $this->validationResponse($validator->errors());
        }

        try {
            $user = Auth::user();

            // Verify the service request exists and belongs to the customer
            $serviceRequest = ServiceRequest::findOrFail($request->request_id);

            if ($serviceRequest->customer_id !== $user->id) {
                return $this->error('Unauthorized: This is not your service request', 403);
            }

            // Verify the request is completed
            if ($serviceRequest->status !== 'completed') {
                return $this->error('Can only review completed services', 400);
            }

            // Check if review already exists
            $existingReview = Review::where('service_request_id', $request->request_id)
                ->where('customer_id', $user->id)
                ->first();

            if ($existingReview) {
                return $this->error('You have already reviewed this service', 400);
            }

            // Verify provider
            $provider = User::findOrFail($request->provider_id);
            if (!$provider->hasRole('service_provider')) {
                return $this->error('Invalid provider', 400);
            }

            // Create review
            $review = Review::create([
                'service_request_id' => $request->request_id,
                'customer_id' => $user->id,
                'provider_id' => $request->provider_id,
                'rating' => $request->rating,
                'comment' => $request->comment,
            ]);

            $review->load('customer');

            return $this->success('Review submitted successfully', [
                'review' => [
                    'id' => $review->id,
                    'customer_name' => $review->customer->name,
                    'provider_id' => $review->provider_id,
                    'rating' => $review->rating,
                    'comment' => $review->comment,
                    'created_at' => $review->created_at->format('Y-m-d H:i:s'),
                ]
            ], 201);
        } catch (\Exception $e) {
            return $this->error('Failed to submit review: ' . $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/reviews/provider/{provider_id}",
     *     tags={"Reviews & Ratings"},
     *     summary="Get provider reviews",
     *     description="Get all reviews and ratings for a specific service provider",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(name="provider_id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Reviews retrieved successfully"),
     *     @OA\Response(response=404, description="Provider not found")
     * )
     */
    public function getProviderReviews($provider_id)
    {
        try {
            // Verify provider exists
            $provider = User::findOrFail($provider_id);

            if (!$provider->hasRole('service_provider')) {
                return $this->error('Invalid provider', 400);
            }

            $reviews = Review::where('provider_id', $provider_id)
                ->with('customer')
                ->orderBy('created_at', 'desc')
                ->get();

            $data = $reviews->map(function ($review) {
                return [
                    'id' => $review->id,
                    'customer_name' => $review->customer->name ?? 'Anonymous',
                    'rating' => $review->rating,
                    'comment' => $review->comment,
                    'created_at' => $review->created_at->format('Y-m-d H:i:s'),
                ];
            });

            $averageRating = $reviews->avg('rating');
            $totalReviews = $reviews->count();

            // Calculate rating breakdown
            $ratingBreakdown = [
                '5' => $reviews->where('rating', 5)->count(),
                '4' => $reviews->where('rating', 4)->count(),
                '3' => $reviews->where('rating', 3)->count(),
                '2' => $reviews->where('rating', 2)->count(),
                '1' => $reviews->where('rating', 1)->count(),
            ];

            return $this->success('Reviews retrieved successfully', [
                'reviews' => $data,
                'average_rating' => round($averageRating, 1),
                'total_reviews' => $totalReviews,
                'rating_breakdown' => $ratingBreakdown,
            ]);
        } catch (\Exception $e) {
            return $this->error('Provider not found', 404);
        }
    }

    /**
     * Get reviews by the current customer (optional helper method)
     * GET /api/reviews/my-reviews
     */
    public function getMyReviews()
    {
        try {
            $user = Auth::user();

            if (!$user->hasRole('customer')) {
                return $this->error('Only customers can view their reviews', 403);
            }

            $reviews = Review::where('customer_id', $user->id)
                ->with(['provider', 'serviceRequest.service'])
                ->orderBy('created_at', 'desc')
                ->get();

            $data = $reviews->map(function ($review) {
                return [
                    'id' => $review->id,
                    'provider_name' => $review->provider->name ?? null,
                    'service_name' => $review->serviceRequest->service->name ?? null,
                    'rating' => $review->rating,
                    'comment' => $review->comment,
                    'created_at' => $review->created_at->format('Y-m-d H:i:s'),
                ];
            });

            return $this->success('Your reviews retrieved successfully', ['reviews' => $data]);
        } catch (\Exception $e) {
            return $this->error('Failed to retrieve reviews: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Update a review (optional feature)
     * PUT /api/reviews/{id}
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'rating' => 'sometimes|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return $this->validationResponse($validator->errors());
        }

        try {
            $user = Auth::user();
            $review = Review::findOrFail($id);

            // Check authorization
            if ($review->customer_id !== $user->id) {
                return $this->error('Unauthorized', 403);
            }

            $review->update($request->only(['rating', 'comment']));

            return $this->success('Review updated successfully', [
                'review' => [
                    'id' => $review->id,
                    'rating' => $review->rating,
                    'comment' => $review->comment,
                    'updated_at' => $review->updated_at->format('Y-m-d H:i:s'),
                ]
            ]);
        } catch (\Exception $e) {
            return $this->error('Failed to update review: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Delete a review (optional feature)
     * DELETE /api/reviews/{id}
     */
    public function destroy($id)
    {
        try {
            $user = Auth::user();
            $review = Review::findOrFail($id);

            // Check authorization (customer can delete own review, admin can delete any)
            if ($review->customer_id !== $user->id && !$user->hasRole('admin')) {
                return $this->error('Unauthorized', 403);
            }

            $review->delete();

            return $this->success('Review deleted successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to delete review: ' . $e->getMessage(), 500);
        }
    }
}
