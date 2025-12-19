<?php

namespace App\Enums;

use Illuminate\Auth\Events\Logout;

class ResponseMessage
{
    // Success Messages
    const SAVE                  = "Record saved successfully.";
    const UPDATE                = "Record updated successfully.";
    const DELETE                = "Record deleted successfully.";
    const FETCHED               = "Records retrieved successfully.";
    const FETCHED_DETAIL        = "Record details retrieved successfully.";
    const SUCCESS               = "Operation completed successfully.";
    const UPDATE_STATUS         = "Status changed successfully.";
    const EMAIL_SENT            = "Email has been sent.";
    const FILE_UPLOADED         = "File uploaded successfully.";

    // Filter/Search/Sort
    const NO_MATCHING_RECORDS   = "No matching records found.";
    const FILTER_APPLIED        = "Filter applied successfully.";
    const SORT_APPLIED          = "Sort order applied successfully.";

    // Import/Export
    const IMPORTED              = "Records imported successfully.";
    const EXPORT_STARTED        = "Export process started successfully.";
    const NO_TO_EXPORT          = "No records available for export.";

    // Operation Failures / No Operation
    const NOT_FOUND             = "Record not found. Please try again later.";
    const NOT_SAVE              = "Record could not be saved. Please try again later.";
    const NOT_UPDATE            = "Record could not be updated. Please try again later.";
    const NOT_DELETE            = "Record could not be deleted. Please try again later.";
    const NOT_UPDATE_STATUS     = "Record status could not be updated. Please try again later.";
    const NOT_SEND_EMAIL        = "Email could not be sent. Please try again later.";
    const ACTION_NOT_ALLOWED    = "This action is not allowed.";
    const EMAIL_NOT_FOUND       = "This email is not registered with our system. Please check and try again.";

    // Server & Unexpected Errors
    const ERROR                 = "Something went wrong here. Please try again later.";
    const SERVER_ERROR          = "A server error occurred.";
    const SERVICE_UNAVAILABLE   = "Service is temporarily unavailable.";

    // Authentication Message
    const REGISTER              = "User registered successfully.";
    const LOGIN                 = "User logged in successfully.";
    const INVALID_LOGIN         = "Invalid login details.";
    const INVALID_CURRENT_PASSWORD      = "Invalid current password.";
    const INVALID_COMPANY_ATTACHED     = "Invalid company attached to this user.";
    const UNAUTHORIZED = "Unauthorized";
    const LOGOUT                = "User logged out successfully.";
    const PASSWORD_CHANGED      = "Password changed successfully.";
    const OTP_SENT              = "OTP sent successfully.";
    const OTP_VERIFIED          = "OTP verified successfully.";
    const OTP_EXPIRED           = "This OTP has expired.";
    const OTP_INVALID           = "The OTP you entered is invalid.";
    const ACCOUNT_DELETED       = "Account deleted successfully.";
    const EMAIL_ALREADY         = "Email already exists. Please use the linked login method.";
    const CONNECTED_ALREADY     = "You are already connected from another device.";
    const SUBSCRIPTION_EXPIRED  = "Your subscription has expired. Please renew your subscription to continue using our service.";
}
