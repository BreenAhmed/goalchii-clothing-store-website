<?php
/**
 * WhatsApp Configuration File
 * 
 * Configure your WhatsApp messaging method here
 * 
 * Available Methods:
 * 1. 'link' - Generates WhatsApp links (default, no API required)
 * 2. 'api' - WhatsApp Business Cloud API (requires Facebook Business account)
 * 3. 'twilio' - Twilio WhatsApp API (requires Twilio account)
 */

// Set your preferred method
// Options: 'link', 'api', 'twilio'
putenv('WHATSAPP_METHOD=link');

// ============================================================================
// METHOD 1: WhatsApp Business Cloud API Configuration
// ============================================================================
// To use WhatsApp Business API:
// 1. Create a Facebook Business account
// 2. Set up WhatsApp Business API
// 3. Get your Access Token and Phone Number ID
// 4. Uncomment and fill in the credentials below
// 5. Change WHATSAPP_METHOD to 'api'

// putenv('WHATSAPP_ACCESS_TOKEN=your_access_token_here');
// putenv('WHATSAPP_PHONE_NUMBER_ID=your_phone_number_id_here');

// ============================================================================
// METHOD 2: Twilio WhatsApp API Configuration
// ============================================================================
// To use Twilio:
// 1. Create a Twilio account at https://www.twilio.com
// 2. Set up WhatsApp messaging
// 3. Get your Account SID, Auth Token, and WhatsApp number
// 4. Uncomment and fill in the credentials below
// 5. Change WHATSAPP_METHOD to 'twilio'

// putenv('TWILIO_ACCOUNT_SID=your_account_sid_here');
// putenv('TWILIO_AUTH_TOKEN=your_auth_token_here');
// putenv('TWILIO_WHATSAPP_NUMBER=+14155238886'); // Your Twilio WhatsApp number

// ============================================================================
// Default Country Code for Phone Numbers
// ============================================================================
// This will be prepended to phone numbers that don't have a country code
// Default is 964 (Iraq)
define('WHATSAPP_DEFAULT_COUNTRY_CODE', '964');

// ============================================================================
// Enable/Disable WhatsApp Notifications
// ============================================================================
define('WHATSAPP_ENABLED', true);

// ============================================================================
// Admin WhatsApp Number (for notifications)
// ============================================================================
// Set your business WhatsApp number to receive order notifications
define('ADMIN_WHATSAPP_NUMBER', '9647xxxxxxxxx'); // Replace with your number



