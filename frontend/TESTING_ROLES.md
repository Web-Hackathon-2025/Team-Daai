# Testing Different User Roles

## Quick Role Testing Guide

To test different user roles, use these email patterns when logging in:

### Customer Role (Default)
- Email: `customer@test.com` or any email
- Password: any password
- Access: Browse services, submit requests, track requests

### Service Provider Role
- Email: `provider@test.com` or `service@test.com`
- Password: any password
- Access: Provider dashboard, manage profile, manage requests

### Admin Role
- Email: `admin@test.com`
- Password: any password
- Access: 
  - `/admin` - Admin Dashboard (view platform statistics)
  - `/admin/users` - Manage users (approve providers, suspend/delete users)
  - `/admin/services` - Monitor service listings
  - `/admin/reviews` - Moderate reviews and ratings

## Examples

**To test as Customer:**
- Email: `customer@test.com`
- Password: `123456`

**To test as Service Provider:**
- Email: `provider@test.com`
- Password: `123456`

**To test as Admin:**
- Email: `admin@test.com`
- Password: `123456`

## Register New Account

You can also register a new account and select the role:
- Choose "Find Services (Customer)" for customer role
- Choose "Offer Services (Service Provider)" for provider role

---

**Note:** These are mock logins for UI testing. When backend is ready, change `USE_MOCK_DATA` to `false` in `src/services/api.ts`

