# WhatsApp Auto-Message Setup Guide

This guide will help you set up automatic WhatsApp messages that are sent to customers after they complete their checkout.

## 📱 Features

- ✅ Automatic WhatsApp messages sent after order completion
- ✅ Order confirmation with full details (products, prices, shipping info)
- ✅ Multiple integration methods (Link, WhatsApp Business API, Twilio)
- ✅ Message logging and tracking
- ✅ Admin panel to view message history
- ✅ Ability to resend messages manually
- ✅ Arabic language support

## 🚀 Quick Start (Recommended)

The easiest method is the **WhatsApp Link Method** which requires NO API setup or paid services.

### Default Configuration (No Setup Required)

By default, the system is configured to use the "link" method which:
- Generates WhatsApp messages for each order
- Logs all messages in the database
- Works immediately without any API configuration

**How it works:**
1. When a customer places an order, the system generates a WhatsApp message
2. The message is logged in the database
3. You can view all messages in the admin panel at `whatsapp-logs.php`
4. You can manually resend messages to customers

## 📋 Installation

The following files have been created:

1. **includes/whatsapp-helper.php** - Core WhatsApp functionality
2. **config/whatsapp-config.php** - Configuration file
3. **whatsapp-logs.php** - Admin panel to view message logs
4. **ajax/get-whatsapp-message.php** - AJAX endpoint for message details

The integration has already been added to `store/checkout.php`.

## ⚙️ Configuration Methods

You have three options for sending WhatsApp messages:

### Method 1: WhatsApp Link (Default - No Setup)

This is already configured and working! No additional setup needed.

**Pros:**
- ✅ No API required
- ✅ Free to use
- ✅ Works immediately
- ✅ No external dependencies

**Cons:**
- ❌ Messages are not sent automatically (generated and logged only)
- ❌ Requires manual action to send from admin panel

### Method 2: WhatsApp Business Cloud API (Advanced)

For fully automated message sending using official WhatsApp Business API.

**Setup Steps:**

1. **Create Facebook Business Account**
   - Go to https://business.facebook.com/
   - Create or log in to your business account

2. **Set up WhatsApp Business API**
   - Go to https://developers.facebook.com/
   - Create a new app
   - Add WhatsApp product
   - Complete the setup wizard

3. **Get Your Credentials**
   - Access Token
   - Phone Number ID

4. **Configure in `config/whatsapp-config.php`:**
   ```php
   // Change this line
   putenv('WHATSAPP_METHOD=api');
   
   // Uncomment and fill these lines
   putenv('WHATSAPP_ACCESS_TOKEN=your_access_token_here');
   putenv('WHATSAPP_PHONE_NUMBER_ID=your_phone_number_id_here');
   ```

**Pros:**
- ✅ Fully automated
- ✅ Official WhatsApp API
- ✅ Reliable delivery
- ✅ Message status tracking

**Cons:**
- ❌ Requires Facebook Business verification
- ❌ Complex setup process
- ❌ May have costs for high volume

### Method 3: Twilio WhatsApp API (Easy Setup)

For automated messages using Twilio's WhatsApp integration.

**Setup Steps:**

1. **Create Twilio Account**
   - Go to https://www.twilio.com/
   - Sign up for a free account
   - Get free trial credit

2. **Set up WhatsApp Sandbox** (for testing)
   - Go to Twilio Console > Messaging > Try it out > Send a WhatsApp message
   - Follow instructions to connect your WhatsApp to the sandbox

3. **Get Your Credentials**
   - Account SID
   - Auth Token
   - WhatsApp Number (sandbox number for testing)

4. **Configure in `config/whatsapp-config.php`:**
   ```php
   // Change this line
   putenv('WHATSAPP_METHOD=twilio');
   
   // Uncomment and fill these lines
   putenv('TWILIO_ACCOUNT_SID=your_account_sid_here');
   putenv('TWILIO_AUTH_TOKEN=your_auth_token_here');
   putenv('TWILIO_WHATSAPP_NUMBER=+14155238886'); // Sandbox number
   ```

**Pros:**
- ✅ Easy setup
- ✅ Free trial available
- ✅ Good documentation
- ✅ Reliable delivery

**Cons:**
- ❌ Costs money after trial
- ❌ Sandbox mode requires users to opt-in first
- ❌ Requires Twilio account

## 🎯 Usage

### For Customers

1. Customer completes checkout
2. System automatically generates/sends WhatsApp message
3. Customer receives order confirmation on WhatsApp

### For Admins

1. **View Message Logs**
   - Go to `whatsapp-logs.php` in your admin panel
   - See all sent messages, their status, and details

2. **Resend Messages**
   - Click "Resend" button next to any message
   - System will attempt to send the message again

3. **View Message Details**
   - Click "View" button to see the full message content
   - See delivery status and any error messages

## 📊 Admin Panel Features

Access the WhatsApp admin panel at: `http://yoursite.com/whatsapp-logs.php`

**Features:**
- 📈 Statistics dashboard (total, successful, failed messages)
- 📋 Message history with filtering
- 🔍 View full message content
- 🔄 Resend failed messages
- ⚙️ Configuration status display

## 🔧 Customization

### Customize Message Template

Edit the `generateOrderConfirmationMessage()` function in `includes/whatsapp-helper.php`:

```php
function generateOrderConfirmationMessage($orderData) {
    $message = "مرحباً " . $orderData['customer_name'] . "! 👋\n\n";
    // Customize your message here
    return $message;
}
```

### Change Default Country Code

Edit `config/whatsapp-config.php`:

```php
define('WHATSAPP_DEFAULT_COUNTRY_CODE', '964'); // Change to your country code
```

### Disable WhatsApp Messages

Edit `config/whatsapp-config.php`:

```php
define('WHATSAPP_ENABLED', false);
```

## 🗄️ Database

The system automatically creates these tables:

### `whatsapp_logs`
Stores all WhatsApp message attempts with:
- Order number
- Phone number
- Message content
- Send method (link/api/twilio)
- Success status
- Error messages (if failed)
- Timestamp

## 🐛 Troubleshooting

### Messages Not Being Generated

1. Check if WhatsApp is enabled:
   ```php
   // In config/whatsapp-config.php
   define('WHATSAPP_ENABLED', true);
   ```

2. Check error logs:
   - Check `store/checkout-error.log`
   - Check PHP error logs

### API Messages Not Sending

1. **WhatsApp Business API:**
   - Verify Access Token is correct
   - Verify Phone Number ID is correct
   - Check that your WhatsApp Business number is verified
   - Check rate limits

2. **Twilio:**
   - Verify Account SID is correct
   - Verify Auth Token is correct
   - Check that customer has opted into WhatsApp sandbox
   - Check Twilio console for error messages

### Phone Number Format Issues

The system automatically formats phone numbers, but ensure:
- Numbers include country code (or default is set correctly)
- Remove any special characters (the system does this automatically)

## 📞 Support

For issues or questions:
1. Check the error logs
2. View the WhatsApp logs page for detailed error messages
3. Verify your configuration in `config/whatsapp-config.php`

## 🔐 Security Notes

- **Never commit API credentials to version control**
- Keep `config/whatsapp-config.php` secure
- Use environment variables for production
- Regularly rotate API tokens
- Monitor usage to prevent abuse

## 📝 Example Message

Here's what customers will receive:

```
مرحباً Ahmed Ali! 👋

شكراً لطلبك من *GOALCHII Sports* ⚽

📦 *تفاصيل الطلب:*
رقم الطلب: *REQ-20241113-ABC123*
التاريخ: 2024-11-13 15:30

🛍️ *المنتجات:*
1. قميص رياضي أحمر
   المقاس: L | الكمية: 2
   السعر: 50,000 د.ع

💰 *الإجمالي:*
المجموع الفرعي: 50,000 د.ع
تكلفة الشحن: 5,000 د.ع
المجموع الكلي: *55,000 د.ع*

📍 *عنوان الشحن:*
شارع فلسطين، بناية 10، شقة 3
بغداد, العراق

✅ *طريقة الدفع:* الدفع عند الاستلام

سنتواصل معك قريباً لتأكيد الطلب والتسليم.

للاستفسارات، يمكنك الرد على هذه الرسالة.

شكراً لثقتك بنا! 🙏
```

## 🎉 You're All Set!

The WhatsApp auto-message feature is now installed and ready to use with the default "link" method. For automated sending, configure either WhatsApp Business API or Twilio as described above.

Visit `whatsapp-logs.php` to see all messages!



