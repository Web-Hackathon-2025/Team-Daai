# Karigar Backend Setup Guide

## What We've Built

A complete Laravel backend API for the **Karigar** hyperlocal services marketplace that connects customers with nearby service providers.

---

## Files Created/Modified

### ✅ Routes
- `routes/api.php` - Clean API routes for Karigar

### ✅ Controllers
- `ProviderController.php` - Service provider management
- `RequestController.php` - Service request/booking workflow
- `ReviewController.php` - Reviews & ratings system
- `AuthController.php` - Updated with register() method

### ✅ Models
- `ProviderProfile.php` - Provider details
- `Service.php` - Services offered
- `ServiceRequest.php` - Booking management
- `Review.php` - Customer reviews
- `User.php` - Updated with Karigar relationships

### ✅ Migrations
- `2025_12_19_000001_create_provider_profiles_table.php`
- `2025_12_19_000002_create_services_table.php`
- `2025_12_19_000003_create_service_requests_table.php`
- `2025_12_19_000004_create_reviews_table.php`

### ✅ Seeders
- `RoleSeeder.php` - Seeds customer, service_provider, admin roles

### ✅ Middleware
- `CheckRole.php` - Role-based access control

---

## Setup Instructions

### 1. Run Migrations
```bash
php artisan migrate
```

### 2. Seed Roles & Permissions
```bash
php artisan db:seed --class=RoleSeeder
```

Or run all seeders:
```bash
php artisan db:seed
```

### 3. Test the API

#### Register a Customer
```bash
POST http://localhost:8000/api/auth/register
Content-Type: application/json

{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "password123",
  "role": "customer"
}
```

#### Register a Service Provider
```bash
POST http://localhost:8000/api/auth/register
Content-Type: application/json

{
  "name": "Ahmed Plumber",
  "email": "ahmed@example.com",
  "password": "password123",
  "role": "service_provider"
}
```

#### Login
```bash
POST http://localhost:8000/api/auth/login
Content-Type: application/json

{
  "email": "john@example.com",
  "password": "password123"
}
```

---

## API Endpoints Summary

### Authentication
- `POST /api/auth/register` - Register user
- `POST /api/auth/login` - Login
- `POST /api/auth/logout` - Logout (protected)
- `POST /api/auth/refresh` - Refresh token (protected)

### Service Providers
- `GET /api/providers` - Browse providers (with filters)
- `GET /api/providers/{id}` - View provider details
- `GET /api/providers/me` - Get own profile (provider only)
- `PUT /api/providers/me` - Update profile (provider only)

### Service Requests
- `POST /api/requests` - Create request (customer)
- `GET /api/requests` - List requests (role-based)
- `GET /api/requests/{id}` - View request details
- `PUT /api/requests/{id}/accept` - Accept (provider)
- `PUT /api/requests/{id}/reject` - Reject (provider)
- `PUT /api/requests/{id}/reschedule` - Reschedule (provider)
- `PUT /api/requests/{id}/complete` - Mark complete (provider)
- `PUT /api/requests/{id}/cancel` - Cancel (both)

### Reviews
- `POST /api/reviews` - Submit review (customer)
- `GET /api/reviews/provider/{id}` - View provider reviews

### Admin (Bonus)
- `GET /api/admin/users` - View all users
- `PUT /api/admin/users/{id}/approve` - Approve provider
- `PUT /api/admin/users/{id}/suspend` - Suspend user
- `DELETE /api/admin/users/{id}` - Delete user
- `GET /api/admin/stats` - Platform statistics

---

## Roles & Permissions

### Customer
- Create service requests
- View own requests
- Cancel requests
- Submit reviews
- View providers

### Service Provider
- Manage profile & services
- View incoming requests
- Accept/reject/reschedule requests
- Mark requests complete
- Cancel assigned requests

### Admin
- All permissions
- Manage users
- Approve/suspend providers
- View platform statistics
- Moderate reviews

---

## Database Schema

### users
- Standard Laravel users table
- Roles via Spatie Laravel Permission

### provider_profiles
- user_id, category, location, availability, phone, description, is_approved

### services
- provider_id, name, description, price, duration, is_active

### service_requests
- customer_id, provider_id, service_id, status, dates/times, address, description

### reviews
- service_request_id (unique), customer_id, provider_id, rating (1-5), comment

---

## Status Workflow

Service requests follow this workflow:
```
requested → confirmed → completed
     ↓           ↓
  cancelled   cancelled
```

---

## Next Steps for Frontend Integration

1. **Authentication Flow**
   - Implement register/login forms
   - Store JWT token in localStorage/cookies
   - Add token to Authorization header

2. **Browse Providers**
   - List all providers with filters
   - Search by category/location
   - View provider details & ratings

3. **Service Requests**
   - Create request form
   - Track request status
   - Display request history

4. **Reviews System**
   - Submit reviews after completion
   - Display rating breakdown
   - Show customer testimonials

5. **Dashboard**
   - Customer: View requests & reviews
   - Provider: Manage incoming requests
   - Admin: Platform statistics

---

## Environment Setup

Ensure your `.env` has:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=karigar
DB_USERNAME=root
DB_PASSWORD=

JWT_SECRET=your_jwt_secret_here
```

---

## CORS Configuration

For frontend integration, update `config/cors.php`:
```php
'paths' => ['api/*'],
'allowed_origins' => ['http://localhost:3000'], // Your React app URL
'allowed_methods' => ['*'],
'allowed_headers' => ['*'],
'exposed_headers' => [],
'max_age' => 0,
'supports_credentials' => false,
```

---

## Testing Checklist

- [ ] Register customer & service provider
- [ ] Login with both roles
- [ ] Provider updates profile & adds services
- [ ] Customer creates service request
- [ ] Provider accepts/rejects request
- [ ] Provider marks request complete
- [ ] Customer submits review
- [ ] View provider with ratings

---

**Ready to build the frontend! 🚀**
