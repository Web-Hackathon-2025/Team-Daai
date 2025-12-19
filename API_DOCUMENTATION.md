# Karigar API Documentation

**Version:** 1.0.0  
**Base URL:** `http://127.0.0.1:8000/api`  
**Last Updated:** December 19, 2025

---

## Table of Contents

1. [Overview](#overview)
2. [Authentication](#authentication)
3. [Service Providers](#service-providers)
4. [Service Requests](#service-requests)
5. [Reviews & Ratings](#reviews--ratings)
6. [Notifications](#notifications)
7. [Profile Management](#profile-management)
8. [Dashboard](#dashboard)
9. [Admin Panel](#admin-panel)
10. [Error Handling](#error-handling)
11. [Frontend Integration Guide](#frontend-integration-guide)

---

## Overview

Karigar is a hyperlocal services marketplace that connects customers with nearby service providers (Karigar). This API provides endpoints for user management, service bookings, reviews, and real-time notifications.

### Key Features
- JWT-based authentication
- Role-based access control (Customer, Service Provider, Admin)
- Service provider discovery and filtering
- Service request/booking management
- Review and rating system
- Real-time notifications
- Admin dashboard

### Base Configuration

```javascript
const API_BASE_URL = 'http://127.0.0.1:8000/api';
const headers = {
  'Content-Type': 'application/json',
  'Accept': 'application/json',
  'Authorization': 'Bearer {token}' // For protected routes
};
```

---

## Authentication

### Register User

Create a new user account as customer, service provider, or admin.

**Endpoint:** `POST /auth/register`

**Request Headers:**
```
Content-Type: application/json
Accept: application/json
```

**Request Body:**
```json
{
  "first_name": "John",
  "last_name": "Doe",
  "prefix": "Mr",
  "email": "john@example.com",
  "username": "johndoe",
  "phone": "+923001234567",
  "alternate_phone": "+923009876543",
  "password": "Password123",
  "password_confirmation": "Password123",
  "role": "customer"
}
```

**Field Descriptions:**
| Field | Type | Required | Description |
|-------|------|----------|-------------|
| first_name | string | Yes | User's first name |
| last_name | string | Yes | User's last name |
| prefix | string | No | Title (Mr, Mrs, Dr, etc.) |
| email | string | Yes | Valid email address |
| username | string | No | Unique username |
| phone | string | Yes | Primary phone number |
| alternate_phone | string | No | Secondary phone number |
| password | string | Yes | Minimum 8 characters |
| password_confirmation | string | Yes | Must match password |
| role | string | Yes | `customer`, `service_provider`, or `admin` |

**Success Response (201):**
```json
{
  "status": "success",
  "message": "User registered successfully",
  "data": {
    "user": {
      "id": 1,
      "first_name": "John",
      "last_name": "Doe",
      "prefix": "Mr",
      "name": "John Doe",
      "email": "john@example.com",
      "username": "johndoe",
      "phone": "+923001234567",
      "alternate_phone": "+923009876543",
      "role": "customer",
      "created_at": "2025-12-19T10:30:00.000000Z"
    },
    "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
    "token_type": "bearer",
    "expires_in": 3600
  }
}
```

**Error Response (422):**
```json
{
  "status": "error",
  "message": "Validation failed",
  "errors": {
    "email": ["The email has already been taken."],
    "password": ["The password must be at least 8 characters."]
  }
}
```

---

### Login

Authenticate user and receive JWT token.

**Endpoint:** `POST /auth/login`

**Request Body:**
```json
{
  "login": "john@example.com",
  "password": "Password123"
}
```

**Field Descriptions:**
| Field | Type | Required | Description |
|-------|------|----------|-------------|
| login | string | Yes | Email, username, or phone number |
| password | string | Yes | User's password |

**Success Response (200):**
```json
{
  "status": "success",
  "message": "Login successful",
  "data": {
    "user": {
      "id": 1,
      "first_name": "John",
      "last_name": "Doe",
      "name": "John Doe",
      "email": "john@example.com",
      "role": "customer"
    },
    "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
    "token_type": "bearer",
    "expires_in": 3600
  }
}
```

**Error Response (401):**
```json
{
  "status": "error",
  "message": "Invalid credentials"
}
```

---

### Refresh Token

Get a new access token using current valid token.

**Endpoint:** `POST /auth/refresh`

**Request Headers:**
```
Authorization: Bearer {access_token}
```

**Success Response (200):**
```json
{
  "status": "success",
  "data": {
    "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
    "token_type": "bearer",
    "expires_in": 3600
  }
}
```

---

### Logout

Invalidate current token and logout user.

**Endpoint:** `POST /auth/logout`

**Request Headers:**
```
Authorization: Bearer {access_token}
```

**Success Response (200):**
```json
{
  "status": "success",
  "message": "Successfully logged out"
}
```

---

## Service Providers

### List All Providers

Get paginated list of service providers with filtering options.

**Endpoint:** `GET /providers`

**Request Headers:**
```
Authorization: Bearer {access_token}
```

**Query Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| search | string | No | Search by name or description |
| category | string | No | Filter by service category |
| location | string | No | Filter by location/city |
| availability | string | No | `available`, `busy`, `offline` |
| is_approved | boolean | No | Filter approved providers |
| per_page | integer | No | Items per page (default: 15) |
| page | integer | No | Page number (default: 1) |

**Example Request:**
```
GET /providers?search=plumber&location=Lahore&availability=available&per_page=10&page=1
```

**Success Response (200):**
```json
{
  "status": "success",
  "message": "Providers retrieved successfully",
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 5,
        "user": {
          "id": 10,
          "first_name": "Ahmed",
          "last_name": "Khan",
          "name": "Ahmed Khan",
          "phone": "+923001234567",
          "profile_picture": "https://example.com/profiles/ahmed.jpg"
        },
        "category": "Plumber",
        "location": "Lahore, Pakistan",
        "availability": "available",
        "phone": "+923001234567",
        "description": "Experienced plumber with 5+ years in residential and commercial plumbing",
        "is_approved": true,
        "average_rating": 4.5,
        "total_reviews": 25,
        "created_at": "2025-01-10T08:00:00.000000Z"
      }
    ],
    "first_page_url": "http://127.0.0.1:8000/api/providers?page=1",
    "from": 1,
    "last_page": 5,
    "last_page_url": "http://127.0.0.1:8000/api/providers?page=5",
    "next_page_url": "http://127.0.0.1:8000/api/providers?page=2",
    "path": "http://127.0.0.1:8000/api/providers",
    "per_page": 10,
    "prev_page_url": null,
    "to": 10,
    "total": 50
  }
}
```

---

### Get Provider Details

Get detailed information about a specific service provider.

**Endpoint:** `GET /providers/{id}`

**Request Headers:**
```
Authorization: Bearer {access_token}
```

**Success Response (200):**
```json
{
  "status": "success",
  "message": "Provider details retrieved successfully",
  "data": {
    "id": 5,
    "user_id": 10,
    "user": {
      "id": 10,
      "first_name": "Ahmed",
      "last_name": "Khan",
      "name": "Ahmed Khan",
      "email": "ahmed@example.com",
      "phone": "+923001234567",
      "profile_picture": "https://example.com/profiles/ahmed.jpg"
    },
    "category": "Plumber",
    "location": "Lahore, Pakistan",
    "availability": "available",
    "phone": "+923001234567",
    "description": "Experienced plumber with 5+ years in residential and commercial plumbing. Specializing in water heater installation, pipe repairs, and bathroom renovations.",
    "is_approved": true,
    "average_rating": 4.5,
    "total_reviews": 25,
    "services": [
      {
        "id": 1,
        "name": "Pipe Repair",
        "description": "All types of pipe repairs",
        "price": 1500.00,
        "duration": 120,
        "is_active": true
      },
      {
        "id": 2,
        "name": "Water Heater Installation",
        "description": "Professional water heater installation",
        "price": 5000.00,
        "duration": 180,
        "is_active": true
      }
    ],
    "reviews": [
      {
        "id": 1,
        "customer": {
          "id": 1,
          "name": "John Doe"
        },
        "rating": 5,
        "comment": "Excellent work! Very professional and punctual.",
        "created_at": "2025-01-15T10:30:00.000000Z"
      }
    ],
    "created_at": "2025-01-10T08:00:00.000000Z",
    "updated_at": "2025-12-19T10:00:00.000000Z"
  }
}
```

**Error Response (404):**
```json
{
  "status": "error",
  "message": "Provider not found"
}
```

---

### Get Own Provider Profile

Get logged-in provider's profile (Provider only).

**Endpoint:** `GET /providers/me`

**Request Headers:**
```
Authorization: Bearer {access_token}
```

**Role Required:** `service_provider`

**Success Response (200):**
```json
{
  "status": "success",
  "data": {
    "id": 5,
    "user_id": 10,
    "category": "Plumber",
    "location": "Lahore, Pakistan",
    "availability": "available",
    "phone": "+923001234567",
    "description": "Experienced plumber...",
    "is_approved": true,
    "services": [...],
    "statistics": {
      "total_requests": 150,
      "completed_requests": 140,
      "cancelled_requests": 5,
      "average_rating": 4.5,
      "total_reviews": 25
    }
  }
}
```

---

### Update Own Provider Profile

Update logged-in provider's profile (Provider only).

**Endpoint:** `PUT /providers/me`

**Request Headers:**
```
Authorization: Bearer {access_token}
```

**Role Required:** `service_provider`

**Request Body:**
```json
{
  "category": "Plumber",
  "location": "Lahore, Pakistan",
  "availability": "available",
  "phone": "+923001234567",
  "description": "Updated description of services offered..."
}
```

**Success Response (200):**
```json
{
  "status": "success",
  "message": "Profile updated successfully",
  "data": {
    "id": 5,
    "category": "Plumber",
    "location": "Lahore, Pakistan",
    "availability": "available",
    "updated_at": "2025-12-19T10:30:00.000000Z"
  }
}
```

---

## Service Requests

### Create Service Request

Create a new service request/booking (Customer only).

**Endpoint:** `POST /requests`

**Request Headers:**
```
Authorization: Bearer {access_token}
```

**Role Required:** `customer`

**Request Body:**
```json
{
  "provider_id": 5,
  "service_id": 2,
  "request_date": "2025-12-25",
  "request_time": "10:00:00",
  "location": "DHA Phase 5, Lahore",
  "description": "Need water heater installation for 50 gallon tank",
  "estimated_hours": 3
}
```

**Field Descriptions:**
| Field | Type | Required | Description |
|-------|------|----------|-------------|
| provider_id | integer | Yes | ID of service provider |
| service_id | integer | Yes | ID of specific service |
| request_date | date | Yes | Preferred service date (YYYY-MM-DD) |
| request_time | time | Yes | Preferred service time (HH:MM:SS) |
| location | string | Yes | Service location address |
| description | text | No | Additional details/requirements |
| estimated_hours | integer | No | Estimated duration in hours |

**Success Response (201):**
```json
{
  "status": "success",
  "message": "Service request created successfully",
  "data": {
    "id": 15,
    "customer_id": 1,
    "provider_id": 5,
    "service_id": 2,
    "status": "requested",
    "request_date": "2025-12-25",
    "request_time": "10:00:00",
    "location": "DHA Phase 5, Lahore",
    "description": "Need water heater installation for 50 gallon tank",
    "estimated_hours": 3,
    "created_at": "2025-12-19T10:30:00.000000Z"
  }
}
```

**Status Flow:**
```
requested → confirmed → completed
         ↘ cancelled
         ↘ rejected
```

---

### List Service Requests

Get paginated list of service requests.

**Endpoint:** `GET /requests`

**Request Headers:**
```
Authorization: Bearer {access_token}
```

**Query Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| status | string | No | `requested`, `confirmed`, `completed`, `cancelled`, `rejected` |
| provider_id | integer | No | Filter by provider |
| customer_id | integer | No | Filter by customer |
| per_page | integer | No | Items per page (default: 15) |
| page | integer | No | Page number |

**Example Request:**
```
GET /requests?status=requested&per_page=10
```

**Success Response (200):**
```json
{
  "status": "success",
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 15,
        "customer": {
          "id": 1,
          "name": "John Doe",
          "phone": "+923111111111"
        },
        "provider": {
          "id": 5,
          "name": "Ahmed Khan",
          "phone": "+923001234567"
        },
        "service": {
          "id": 2,
          "name": "Water Heater Installation",
          "price": 5000.00
        },
        "status": "requested",
        "request_date": "2025-12-25",
        "request_time": "10:00:00",
        "location": "DHA Phase 5, Lahore",
        "created_at": "2025-12-19T10:30:00.000000Z"
      }
    ],
    "per_page": 10,
    "total": 25
  }
}
```

---

### Get Request Details

Get detailed information about a specific service request.

**Endpoint:** `GET /requests/{id}`

**Request Headers:**
```
Authorization: Bearer {access_token}
```

**Success Response (200):**
```json
{
  "status": "success",
  "data": {
    "id": 15,
    "customer": {
      "id": 1,
      "first_name": "John",
      "last_name": "Doe",
      "name": "John Doe",
      "phone": "+923111111111",
      "email": "john@example.com"
    },
    "provider": {
      "id": 5,
      "name": "Ahmed Khan",
      "category": "Plumber",
      "phone": "+923001234567"
    },
    "service": {
      "id": 2,
      "name": "Water Heater Installation",
      "description": "Professional water heater installation",
      "price": 5000.00,
      "duration": 180
    },
    "status": "requested",
    "request_date": "2025-12-25",
    "request_time": "10:00:00",
    "confirmed_date": null,
    "confirmed_time": null,
    "completed_date": null,
    "location": "DHA Phase 5, Lahore",
    "description": "Need water heater installation for 50 gallon tank",
    "estimated_hours": 3,
    "actual_hours": null,
    "price": 5000.00,
    "created_at": "2025-12-19T10:30:00.000000Z",
    "updated_at": "2025-12-19T10:30:00.000000Z"
  }
}
```

---

### Accept Service Request

Accept a service request (Provider only).

**Endpoint:** `PUT /requests/{id}/accept`

**Request Headers:**
```
Authorization: Bearer {access_token}
```

**Role Required:** `service_provider`

**Request Body:**
```json
{
  "confirmed_date": "2025-12-25",
  "confirmed_time": "10:00:00",
  "notes": "Will bring all necessary equipment"
}
```

**Success Response (200):**
```json
{
  "status": "success",
  "message": "Request accepted successfully",
  "data": {
    "id": 15,
    "status": "confirmed",
    "confirmed_date": "2025-12-25",
    "confirmed_time": "10:00:00"
  }
}
```

---

### Reject Service Request

Reject a service request (Provider only).

**Endpoint:** `PUT /requests/{id}/reject`

**Request Headers:**
```
Authorization: Bearer {access_token}
```

**Role Required:** `service_provider`

**Request Body:**
```json
{
  "rejection_reason": "Not available on requested date"
}
```

**Success Response (200):**
```json
{
  "status": "success",
  "message": "Request rejected",
  "data": {
    "id": 15,
    "status": "rejected"
  }
}
```

---

### Reschedule Service Request

Propose new date/time for service request (Provider only).

**Endpoint:** `PUT /requests/{id}/reschedule`

**Request Headers:**
```
Authorization: Bearer {access_token}
```

**Role Required:** `service_provider`

**Request Body:**
```json
{
  "new_date": "2025-12-26",
  "new_time": "14:00:00",
  "reason": "Previous booking conflict"
}
```

**Success Response (200):**
```json
{
  "status": "success",
  "message": "Reschedule request sent to customer",
  "data": {
    "id": 15,
    "proposed_date": "2025-12-26",
    "proposed_time": "14:00:00"
  }
}
```

---

### Complete Service Request

Mark service request as completed (Provider only).

**Endpoint:** `PUT /requests/{id}/complete`

**Request Headers:**
```
Authorization: Bearer {access_token}
```

**Role Required:** `service_provider`

**Request Body:**
```json
{
  "actual_hours": 3,
  "completion_notes": "Water heater installed successfully. Tested and working properly.",
  "final_price": 5000.00
}
```

**Success Response (200):**
```json
{
  "status": "success",
  "message": "Service request marked as completed",
  "data": {
    "id": 15,
    "status": "completed",
    "completed_date": "2025-12-25",
    "actual_hours": 3,
    "final_price": 5000.00
  }
}
```

---

### Cancel Service Request

Cancel a service request (Both customer and provider).

**Endpoint:** `PUT /requests/{id}/cancel`

**Request Headers:**
```
Authorization: Bearer {access_token}
```

**Request Body:**
```json
{
  "cancellation_reason": "Customer changed mind"
}
```

**Success Response (200):**
```json
{
  "status": "success",
  "message": "Service request cancelled",
  "data": {
    "id": 15,
    "status": "cancelled"
  }
}
```

---

## Reviews & Ratings

### Submit Review

Submit a review for completed service (Customer only).

**Endpoint:** `POST /reviews`

**Request Headers:**
```
Authorization: Bearer {access_token}
```

**Role Required:** `customer`

**Request Body:**
```json
{
  "service_request_id": 15,
  "provider_id": 5,
  "rating": 5,
  "comment": "Excellent service! Ahmed was very professional and completed the work on time. Highly recommended!"
}
```

**Field Descriptions:**
| Field | Type | Required | Description |
|-------|------|----------|-------------|
| service_request_id | integer | Yes | ID of completed service request |
| provider_id | integer | Yes | ID of service provider |
| rating | integer | Yes | Rating 1-5 stars |
| comment | text | No | Review text |

**Success Response (201):**
```json
{
  "status": "success",
  "message": "Review submitted successfully",
  "data": {
    "id": 25,
    "service_request_id": 15,
    "customer_id": 1,
    "provider_id": 5,
    "rating": 5,
    "comment": "Excellent service!",
    "created_at": "2025-12-19T15:30:00.000000Z"
  }
}
```

**Error Response (422):**
```json
{
  "status": "error",
  "message": "You have already reviewed this service request"
}
```

---

### Get Provider Reviews

Get all reviews for a specific provider.

**Endpoint:** `GET /reviews/provider/{provider_id}`

**Request Headers:**
```
Authorization: Bearer {access_token}
```

**Query Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| per_page | integer | No | Items per page (default: 15) |
| page | integer | No | Page number |

**Success Response (200):**
```json
{
  "status": "success",
  "data": {
    "provider": {
      "id": 5,
      "name": "Ahmed Khan",
      "average_rating": 4.5,
      "total_reviews": 25
    },
    "rating_breakdown": {
      "5_stars": 15,
      "4_stars": 7,
      "3_stars": 2,
      "2_stars": 1,
      "1_star": 0
    },
    "reviews": {
      "current_page": 1,
      "data": [
        {
          "id": 25,
          "customer": {
            "id": 1,
            "name": "John Doe"
          },
          "service_request": {
            "id": 15,
            "service_name": "Water Heater Installation"
          },
          "rating": 5,
          "comment": "Excellent service!",
          "created_at": "2025-12-19T15:30:00.000000Z"
        }
      ],
      "per_page": 15,
      "total": 25
    }
  }
}
```

---

## Notifications

### Get Notifications

Get paginated list of user notifications.

**Endpoint:** `GET /notifications`

**Request Headers:**
```
Authorization: Bearer {access_token}
```

**Query Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| status | string | No | `read`, `unread`, or `all` (default: all) |
| per_page | integer | No | Items per page (default: 15) |
| page | integer | No | Page number |

**Success Response (200):**
```json
{
  "status": "success",
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 1,
        "user_id": 1,
        "type": "request_accepted",
        "title": "Request Accepted",
        "message": "Ahmed Khan accepted your service request for Water Heater Installation",
        "data": {
          "request_id": 15,
          "provider_id": 5,
          "provider_name": "Ahmed Khan"
        },
        "status": "unread",
        "created_at": "2025-12-19T10:30:00.000000Z"
      },
      {
        "id": 2,
        "user_id": 1,
        "type": "request_completed",
        "title": "Service Completed",
        "message": "Your service request has been completed. Please leave a review.",
        "data": {
          "request_id": 14,
          "provider_id": 5
        },
        "status": "read",
        "created_at": "2025-12-18T16:00:00.000000Z"
      }
    ],
    "per_page": 15,
    "total": 50,
    "unread_count": 5
  }
}
```

**Notification Types:**
- `request_created` - New request (Provider)
- `request_accepted` - Request accepted (Customer)
- `request_rejected` - Request rejected (Customer)
- `request_completed` - Service completed (Customer)
- `request_cancelled` - Request cancelled (Both)
- `review_received` - New review (Provider)

---

### Get Unread Count

Get count of unread notifications.

**Endpoint:** `GET /notifications/unread-count`

**Request Headers:**
```
Authorization: Bearer {access_token}
```

**Success Response (200):**
```json
{
  "status": "success",
  "data": {
    "unread_count": 5
  }
}
```

---

### Get Single Notification

Get details of a specific notification.

**Endpoint:** `GET /notifications/{id}`

**Request Headers:**
```
Authorization: Bearer {access_token}
```

**Success Response (200):**
```json
{
  "status": "success",
  "data": {
    "id": 1,
    "type": "request_accepted",
    "title": "Request Accepted",
    "message": "Ahmed Khan accepted your service request",
    "data": {
      "request_id": 15,
      "provider_id": 5
    },
    "status": "read",
    "created_at": "2025-12-19T10:30:00.000000Z"
  }
}
```

---

### Mark Notifications as Read

Mark specific notifications as read.

**Endpoint:** `POST /notifications/mark-read`

**Request Headers:**
```
Authorization: Bearer {access_token}
```

**Request Body:**
```json
{
  "notification_ids": [1, 2, 3]
}
```

**Success Response (200):**
```json
{
  "status": "success",
  "message": "3 notifications marked as read"
}
```

---

### Mark All Notifications as Read

Mark all user notifications as read.

**Endpoint:** `POST /notifications/mark-all-read`

**Request Headers:**
```
Authorization: Bearer {access_token}
```

**Success Response (200):**
```json
{
  "status": "success",
  "message": "All notifications marked as read"
}
```

---

### Delete Notification

Delete a specific notification.

**Endpoint:** `DELETE /notifications/{id}`

**Request Headers:**
```
Authorization: Bearer {access_token}
```

**Success Response (200):**
```json
{
  "status": "success",
  "message": "Notification deleted successfully"
}
```

---

## Profile Management

### Get Current User Profile

Get authenticated user's profile information.

**Endpoint:** `GET /profile`

**Request Headers:**
```
Authorization: Bearer {access_token}
```

**Success Response (200):**
```json
{
  "status": "success",
  "message": "Profile retrieved successfully",
  "data": {
    "id": 1,
    "first_name": "John",
    "last_name": "Doe",
    "prefix": "Mr",
    "name": "John Doe",
    "email": "john@example.com",
    "username": "johndoe",
    "phone": "+923001234567",
    "alternate_phone": "+923009876543",
    "profile_picture": "https://example.com/storage/profiles/john.jpg",
    "role": "customer",
    "provider_profile": null,
    "created_at": "2025-01-01T00:00:00.000000Z",
    "updated_at": "2025-12-19T10:00:00.000000Z"
  }
}
```

---

### Update Profile

Update user profile information.

**Endpoint:** `PUT /profile`

**Request Headers:**
```
Authorization: Bearer {access_token}
```

**Request Body:**
```json
{
  "first_name": "John",
  "last_name": "Doe",
  "prefix": "Mr",
  "username": "johndoe_updated",
  "phone": "+923001234567",
  "alternate_phone": "+923009876543"
}
```

**Success Response (200):**
```json
{
  "status": "success",
  "message": "Profile updated successfully",
  "data": {
    "id": 1,
    "first_name": "John",
    "last_name": "Doe",
    "name": "John Doe",
    "updated_at": "2025-12-19T10:30:00.000000Z"
  }
}
```

---

### Change Password

Change user password.

**Endpoint:** `POST /profile/change-password`

**Request Headers:**
```
Authorization: Bearer {access_token}
```

**Request Body:**
```json
{
  "current_password": "OldPassword123",
  "new_password": "NewPassword123",
  "new_password_confirmation": "NewPassword123"
}
```

**Field Requirements:**
- `new_password` must be at least 8 characters
- Must contain at least one uppercase letter
- Must contain at least one lowercase letter
- Must contain at least one number
- Must be different from current password

**Success Response (200):**
```json
{
  "status": "success",
  "message": "Password changed successfully",
  "data": {
    "message": "Please login again with your new password"
  }
}
```

**Error Response (400):**
```json
{
  "status": "error",
  "message": "Current password is incorrect"
}
```

---

## Dashboard

### Get User Dashboard Stats

Get statistics for authenticated user's dashboard.

**Endpoint:** `GET /dashboard/stats`

**Request Headers:**
```
Authorization: Bearer {access_token}
```

**Customer Response (200):**
```json
{
  "status": "success",
  "message": "Dashboard stats retrieved successfully",
  "data": {
    "total_requests": 15,
    "pending_requests": 3,
    "confirmed_requests": 5,
    "completed_requests": 6,
    "total_spent": 45000.00,
    "unread_notifications": 2
  }
}
```

**Provider Response (200):**
```json
{
  "status": "success",
  "message": "Dashboard stats retrieved successfully",
  "data": {
    "total_requests": 150,
    "pending_requests": 5,
    "confirmed_requests": 10,
    "completed_requests": 120,
    "total_earnings": 500000.00,
    "average_rating": 4.5,
    "total_reviews": 85,
    "unread_notifications": 8
  }
}
```

**Admin Response (200):**
```json
{
  "status": "success",
  "message": "Dashboard stats retrieved successfully",
  "data": {
    "total_customers": 1500,
    "total_providers": 250,
    "pending_approvals": 15,
    "total_requests": 5000,
    "completed_requests": 4500,
    "total_revenue": 2500000.00,
    "average_rating": 4.3,
    "unread_notifications": 10
  }
}
```

---

## Admin Panel

### List All Users

Get paginated list of all users (Admin only).

**Endpoint:** `GET /admin/users`

**Request Headers:**
```
Authorization: Bearer {access_token}
```

**Role Required:** `admin`

**Query Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| role | string | No | Filter by role (`customer`, `service_provider`, `admin`) |
| search | string | No | Search by name, email, phone |
| per_page | integer | No | Items per page (default: 15) |
| page | integer | No | Page number |

**Success Response (200):**
```json
{
  "status": "success",
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 1,
        "first_name": "John",
        "last_name": "Doe",
        "name": "John Doe",
        "email": "john@example.com",
        "phone": "+923001234567",
        "role": "customer",
        "created_at": "2025-01-01T00:00:00.000000Z"
      }
    ],
    "per_page": 15,
    "total": 1500
  }
}
```

---

### Get User Details

Get detailed information about a specific user (Admin only).

**Endpoint:** `GET /admin/users/{id}`

**Request Headers:**
```
Authorization: Bearer {access_token}
```

**Role Required:** `admin`

**Success Response (200):**
```json
{
  "status": "success",
  "data": {
    "id": 10,
    "first_name": "Ahmed",
    "last_name": "Khan",
    "name": "Ahmed Khan",
    "email": "ahmed@example.com",
    "phone": "+923001234567",
    "role": "service_provider",
    "provider_profile": {
      "id": 5,
      "category": "Plumber",
      "is_approved": true,
      "average_rating": 4.5
    },
    "created_at": "2025-01-10T08:00:00.000000Z"
  }
}
```

---

### Approve Service Provider

Approve a service provider account (Admin only).

**Endpoint:** `PUT /admin/users/{id}/approve`

**Request Headers:**
```
Authorization: Bearer {access_token}
```

**Role Required:** `admin`

**Success Response (200):**
```json
{
  "status": "success",
  "message": "Service provider approved successfully"
}
```

---

### Suspend User

Suspend a user account (Admin only).

**Endpoint:** `PUT /admin/users/{id}/suspend`

**Request Headers:**
```
Authorization: Bearer {access_token}
```

**Role Required:** `admin`

**Request Body:**
```json
{
  "reason": "Violation of terms and conditions"
}
```

**Success Response (200):**
```json
{
  "status": "success",
  "message": "User suspended successfully"
}
```

---

### Delete User

Permanently delete a user account (Admin only).

**Endpoint:** `DELETE /admin/users/{id}`

**Request Headers:**
```
Authorization: Bearer {access_token}
```

**Role Required:** `admin`

**Success Response (200):**
```json
{
  "status": "success",
  "message": "User deleted successfully"
}
```

---

### Get Admin Statistics

Get platform-wide statistics (Admin only).

**Endpoint:** `GET /admin/stats`

**Request Headers:**
```
Authorization: Bearer {access_token}
```

**Role Required:** `admin`

**Success Response (200):**
```json
{
  "status": "success",
  "data": {
    "users": {
      "total": 1750,
      "customers": 1500,
      "providers": 250,
      "admins": 5
    },
    "providers": {
      "total": 250,
      "approved": 230,
      "pending": 20
    },
    "requests": {
      "total": 5000,
      "requested": 50,
      "confirmed": 200,
      "completed": 4500,
      "cancelled": 200,
      "rejected": 50
    },
    "revenue": {
      "total": 2500000.00,
      "this_month": 250000.00,
      "last_month": 220000.00
    },
    "ratings": {
      "average": 4.3,
      "total_reviews": 3500
    }
  }
}
```

---

### List Roles

Get all available roles (Admin only).

**Endpoint:** `GET /admin/roles`

**Request Headers:**
```
Authorization: Bearer {access_token}
```

**Role Required:** `admin`

**Success Response (200):**
```json
{
  "status": "success",
  "data": [
    {
      "id": 1,
      "name": "customer",
      "permissions": [
        "view_providers",
        "create_request",
        "submit_review"
      ]
    },
    {
      "id": 2,
      "name": "service_provider",
      "permissions": [
        "manage_profile",
        "accept_request",
        "complete_request"
      ]
    },
    {
      "id": 3,
      "name": "admin",
      "permissions": ["*"]
    }
  ]
}
```

---

### Create Role

Create a new role (Admin only).

**Endpoint:** `POST /admin/roles`

**Request Headers:**
```
Authorization: Bearer {access_token}
```

**Role Required:** `admin`

**Request Body:**
```json
{
  "name": "moderator",
  "permissions": [
    "view_users",
    "approve_providers",
    "view_requests"
  ]
}
```

---

### List Permissions

Get all available permissions (Admin only).

**Endpoint:** `GET /admin/permissions`

**Request Headers:**
```
Authorization: Bearer {access_token}
```

**Role Required:** `admin`

**Success Response (200):**
```json
{
  "status": "success",
  "data": [
    {
      "id": 1,
      "name": "view_providers",
      "description": "View service providers list"
    },
    {
      "id": 2,
      "name": "create_request",
      "description": "Create service requests"
    },
    {
      "id": 3,
      "name": "manage_users",
      "description": "Manage all users"
    }
  ]
}
```

---

## Error Handling

### Standard Error Response Format

All error responses follow this structure:

```json
{
  "status": "error",
  "message": "Error description",
  "errors": {
    "field_name": ["Error message for field"]
  }
}
```

### HTTP Status Codes

| Code | Description | When Used |
|------|-------------|-----------|
| 200 | OK | Successful GET, PUT, DELETE |
| 201 | Created | Successful POST (resource created) |
| 400 | Bad Request | Invalid request format |
| 401 | Unauthorized | Missing or invalid token |
| 403 | Forbidden | Insufficient permissions |
| 404 | Not Found | Resource doesn't exist |
| 422 | Unprocessable Entity | Validation failed |
| 500 | Internal Server Error | Server-side error |

### Common Error Examples

#### Validation Error (422)
```json
{
  "status": "error",
  "message": "Validation failed",
  "errors": {
    "email": [
      "The email field is required.",
      "The email must be a valid email address."
    ],
    "password": [
      "The password must be at least 8 characters."
    ]
  }
}
```

#### Authentication Error (401)
```json
{
  "status": "error",
  "message": "Unauthenticated",
  "code": 401
}
```

#### Authorization Error (403)
```json
{
  "status": "error",
  "message": "You do not have permission to perform this action",
  "code": 403
}
```

#### Not Found Error (404)
```json
{
  "status": "error",
  "message": "Provider not found",
  "code": 404
}
```

#### Server Error (500)
```json
{
  "status": "error",
  "message": "Something went wrong. Please try again later.",
  "code": 500
}
```

---

## Frontend Integration Guide

### 1. Axios Setup

```javascript
// api/client.js
import axios from 'axios';

const apiClient = axios.create({
  baseURL: 'http://127.0.0.1:8000/api',
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  }
});

// Request interceptor - Add token
apiClient.interceptors.request.use(
  config => {
    const token = localStorage.getItem('access_token');
    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
  },
  error => Promise.reject(error)
);

// Response interceptor - Handle errors
apiClient.interceptors.response.use(
  response => response,
  async error => {
    const originalRequest = error.config;

    // Handle 401 Unauthorized
    if (error.response?.status === 401 && !originalRequest._retry) {
      originalRequest._retry = true;
      
      try {
        // Try to refresh token
        const response = await apiClient.post('/auth/refresh');
        const { access_token } = response.data.data;
        
        localStorage.setItem('access_token', access_token);
        originalRequest.headers.Authorization = `Bearer ${access_token}`;
        
        return apiClient(originalRequest);
      } catch (refreshError) {
        // Refresh failed, logout user
        localStorage.removeItem('access_token');
        localStorage.removeItem('user');
        window.location.href = '/login';
        return Promise.reject(refreshError);
      }
    }

    return Promise.reject(error);
  }
);

export default apiClient;
```

### 2. Authentication Service

```javascript
// services/authService.js
import apiClient from '../api/client';

export const authService = {
  // Register
  async register(userData) {
    const response = await apiClient.post('/auth/register', userData);
    if (response.data.status === 'success') {
      const { access_token, user } = response.data.data;
      localStorage.setItem('access_token', access_token);
      localStorage.setItem('user', JSON.stringify(user));
    }
    return response.data;
  },

  // Login
  async login(credentials) {
    const response = await apiClient.post('/auth/login', credentials);
    if (response.data.status === 'success') {
      const { access_token, user } = response.data.data;
      localStorage.setItem('access_token', access_token);
      localStorage.setItem('user', JSON.stringify(user));
    }
    return response.data;
  },

  // Logout
  async logout() {
    try {
      await apiClient.post('/auth/logout');
    } finally {
      localStorage.removeItem('access_token');
      localStorage.removeItem('user');
    }
  },

  // Get current user
  getCurrentUser() {
    const user = localStorage.getItem('user');
    return user ? JSON.parse(user) : null;
  },

  // Check if authenticated
  isAuthenticated() {
    return !!localStorage.getItem('access_token');
  },

  // Check user role
  hasRole(role) {
    const user = this.getCurrentUser();
    return user?.role === role;
  }
};
```

### 3. Provider Service

```javascript
// services/providerService.js
import apiClient from '../api/client';

export const providerService = {
  // Get all providers
  async getProviders(params = {}) {
    const response = await apiClient.get('/providers', { params });
    return response.data;
  },

  // Get provider details
  async getProvider(id) {
    const response = await apiClient.get(`/providers/${id}`);
    return response.data;
  },

  // Get own profile (provider only)
  async getOwnProfile() {
    const response = await apiClient.get('/providers/me');
    return response.data;
  },

  // Update own profile (provider only)
  async updateOwnProfile(data) {
    const response = await apiClient.put('/providers/me', data);
    return response.data;
  }
};
```

### 4. Request Service

```javascript
// services/requestService.js
import apiClient from '../api/client';

export const requestService = {
  // Create request
  async createRequest(data) {
    const response = await apiClient.post('/requests', data);
    return response.data;
  },

  // Get all requests
  async getRequests(params = {}) {
    const response = await apiClient.get('/requests', { params });
    return response.data;
  },

  // Get request details
  async getRequest(id) {
    const response = await apiClient.get(`/requests/${id}`);
    return response.data;
  },

  // Accept request (provider)
  async acceptRequest(id, data) {
    const response = await apiClient.put(`/requests/${id}/accept`, data);
    return response.data;
  },

  // Reject request (provider)
  async rejectRequest(id, data) {
    const response = await apiClient.put(`/requests/${id}/reject`, data);
    return response.data;
  },

  // Complete request (provider)
  async completeRequest(id, data) {
    const response = await apiClient.put(`/requests/${id}/complete`, data);
    return response.data;
  },

  // Cancel request
  async cancelRequest(id, data) {
    const response = await apiClient.put(`/requests/${id}/cancel`, data);
    return response.data;
  }
};
```

### 5. React Component Example

```jsx
// components/ProvidersList.jsx
import React, { useEffect, useState } from 'react';
import { providerService } from '../services/providerService';

function ProvidersList() {
  const [providers, setProviders] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [filters, setFilters] = useState({
    search: '',
    category: '',
    location: '',
    page: 1
  });

  useEffect(() => {
    loadProviders();
  }, [filters]);

  const loadProviders = async () => {
    try {
      setLoading(true);
      const response = await providerService.getProviders(filters);
      setProviders(response.data.data);
      setError(null);
    } catch (err) {
      setError(err.response?.data?.message || 'Failed to load providers');
    } finally {
      setLoading(false);
    }
  };

  if (loading) return <div>Loading...</div>;
  if (error) return <div>Error: {error}</div>;

  return (
    <div>
      <h1>Service Providers</h1>
      
      {/* Filters */}
      <div className="filters">
        <input
          type="text"
          placeholder="Search..."
          value={filters.search}
          onChange={e => setFilters({...filters, search: e.target.value})}
        />
      </div>

      {/* Provider List */}
      <div className="provider-grid">
        {providers.map(provider => (
          <div key={provider.id} className="provider-card">
            <h3>{provider.user.name}</h3>
            <p>{provider.category}</p>
            <p>{provider.location}</p>
            <p>Rating: {provider.average_rating} ⭐</p>
            <p>Reviews: {provider.total_reviews}</p>
          </div>
        ))}
      </div>
    </div>
  );
}

export default ProvidersList;
```

### 6. Protected Route Component

```jsx
// components/ProtectedRoute.jsx
import React from 'react';
import { Navigate } from 'react-router-dom';
import { authService } from '../services/authService';

function ProtectedRoute({ children, requiredRole }) {
  if (!authService.isAuthenticated()) {
    return <Navigate to="/login" />;
  }

  if (requiredRole && !authService.hasRole(requiredRole)) {
    return <Navigate to="/unauthorized" />;
  }

  return children;
}

export default ProtectedRoute;
```

### 7. Usage in Routes

```jsx
// App.jsx
import { BrowserRouter, Routes, Route } from 'react-router-dom';
import ProtectedRoute from './components/ProtectedRoute';
import Login from './pages/Login';
import Register from './pages/Register';
import Dashboard from './pages/Dashboard';
import ProvidersList from './pages/ProvidersList';
import AdminPanel from './pages/AdminPanel';

function App() {
  return (
    <BrowserRouter>
      <Routes>
        <Route path="/login" element={<Login />} />
        <Route path="/register" element={<Register />} />
        
        <Route 
          path="/dashboard" 
          element={
            <ProtectedRoute>
              <Dashboard />
            </ProtectedRoute>
          } 
        />
        
        <Route 
          path="/providers" 
          element={
            <ProtectedRoute>
              <ProvidersList />
            </ProtectedRoute>
          } 
        />
        
        <Route 
          path="/admin" 
          element={
            <ProtectedRoute requiredRole="admin">
              <AdminPanel />
            </ProtectedRoute>
          } 
        />
      </Routes>
    </BrowserRouter>
  );
}

export default App;
```

---

## Best Practices

### Security
1. **Always use HTTPS in production**
2. **Store tokens securely** (httpOnly cookies for production)
3. **Implement CSRF protection** for state-changing operations
4. **Validate all user inputs** on frontend before API calls
5. **Never expose sensitive data** in error messages

### Performance
1. **Implement pagination** for all list endpoints
2. **Cache static data** (categories, locations)
3. **Debounce search inputs** to reduce API calls
4. **Use loading states** for better UX
5. **Implement infinite scroll** for mobile views

### Error Handling
1. **Display user-friendly error messages**
2. **Log errors** for debugging
3. **Implement retry logic** for failed requests
4. **Handle network errors** gracefully
5. **Show validation errors** near form fields

### State Management
1. **Use React Context or Redux** for global state
2. **Cache user data** to reduce API calls
3. **Implement optimistic updates** for better UX
4. **Clear sensitive data** on logout
5. **Sync state** with API responses

---

## Testing with Postman

### Import Collection

1. Create new Postman collection named "Karigar API"
2. Add environment variable `base_url` = `http://127.0.0.1:8000/api`
3. Add environment variable `token` for authentication

### Example Request

**Register User:**
```
POST {{base_url}}/auth/register
Content-Type: application/json

{
  "first_name": "Test",
  "last_name": "User",
  "email": "test@karigar.com",
  "phone": "+923001234567",
  "password": "Password123",
  "password_confirmation": "Password123",
  "role": "customer"
}
```

**Get Providers (with auth):**
```
GET {{base_url}}/providers?search=plumber&location=Lahore
Authorization: Bearer {{token}}
```

---

## Support

For API issues or questions:
- **Email:** support@karigar.com
- **Documentation:** http://127.0.0.1:8000/api/documentation
- **Repository:** Contact your development team

---

**Last Updated:** December 19, 2025  
**API Version:** 1.0.0
