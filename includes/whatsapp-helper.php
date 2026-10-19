<?php
/**
 * WhatsApp Helper Functions
 * Provides functionality to send automated WhatsApp messages to customers
 */

// Load configuration
require_once __DIR__ . '/../config/whatsapp-config.php';

/**
 * Format phone number for WhatsApp
 * Removes spaces, dashes, and adds country code if missing
 * 
 * @param string $phone The phone number to format
 * @param string $defaultCountryCode Default country code (e.g., '964' for Iraq)
 * @return string Formatted phone number
 */
function formatWhatsAppNumber($phone, $defaultCountryCode = '964') {
    // Remove all non-numeric characters
    $phone = preg_replace('/[^0-9]/', '', $phone);
    
    // If phone doesn't start with country code, add default
    if (!preg_match('/^964/', $phone) && !preg_match('/^\+/', $phone)) {
        // Remove leading zero if present
        $phone = ltrim($phone, '0');
        $phone = $defaultCountryCode . $phone;
    }
    
    return $phone;
}

/**
 * Generate WhatsApp message for order confirmation
 * 
 * @param array $orderData Order details
 * @return string Formatted message
 */
function generateOrderConfirmationMessage($orderData) {
    $message = "مرحباً " . $orderData['customer_name'] . "! 👋\n\n";
    $message .= "شكراً لطلبك من *GOALCHII Sports* ⚽\n\n";
    $message .= "📦 *تفاصيل الطلب:*\n";
    $message .= "رقم الطلب: *" . $orderData['order_number'] . "*\n";
    $message .= "التاريخ: " . date('Y-m-d H:i', strtotime($orderData['created_at'])) . "\n\n";
    
    $message .= "🛍️ *المنتجات:*\n";
    foreach ($orderData['items'] as $index => $item) {
        $message .= ($index + 1) . ". " . $item['product_name'] . "\n";
        $message .= "   المقاس: " . $item['size'] . " | الكمية: " . $item['quantity'] . "\n";
        $message .= "   السعر: " . number_format($item['total']) . " د.ع\n\n";
    }
    
    $message .= "💰 *الإجمالي:*\n";
    $message .= "المجموع الفرعي: " . number_format($orderData['subtotal']) . " د.ع\n";
    $message .= "تكلفة الشحن: " . number_format($orderData['shipping_cost']) . " د.ع\n";
    $message .= "المجموع الكلي: *" . number_format($orderData['total_amount']) . " د.ع*\n\n";
    
    $message .= "📍 *عنوان الشحن:*\n";
    $message .= $orderData['shipping_address'] . "\n";
    $message .= $orderData['city'] . ", " . $orderData['country'] . "\n\n";
    
    if (!empty($orderData['customer_notes'])) {
        $message .= "📝 *ملاحظاتك:*\n";
        $message .= $orderData['customer_notes'] . "\n\n";
    }
    
    $message .= "✅ *طريقة الدفع:* الدفع عند الاستلام\n\n";
    $message .= "سنتواصل معك قريباً لتأكيد الطلب والتسليم.\n\n";
    $message .= "للاستفسارات، يمكنك الرد على هذه الرسالة.\n\n";
    $message .= "شكراً لثقتك بنا! 🙏";
    
    return $message;
}

/**
 * Send WhatsApp message using WhatsApp Business API (Cloud API)
 * Requires WhatsApp Business API credentials
 * 
 * @param string $phone Recipient phone number
 * @param string $message Message to send
 * @return array Response with success status and details
 */
function sendWhatsAppMessageAPI($phone, $message) {
    // WhatsApp Cloud API credentials (set these in your configuration)
    $accessToken = getenv('WHATSAPP_ACCESS_TOKEN') ?: '';
    $phoneNumberId = getenv('WHATSAPP_PHONE_NUMBER_ID') ?: '';
    
    if (empty($accessToken) || empty($phoneNumberId)) {
        return [
            'success' => false,
            'error' => 'WhatsApp API credentials not configured',
            'method' => 'api'
        ];
    }
    
    $url = "https://graph.facebook.com/v17.0/{$phoneNumberId}/messages";
    
    $data = [
        'messaging_product' => 'whatsapp',
        'to' => formatWhatsAppNumber($phone),
        'type' => 'text',
        'text' => [
            'preview_url' => false,
            'body' => $message
        ]
    ];
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $accessToken,
        'Content-Type: application/json'
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    $responseData = json_decode($response, true);
    
    if ($httpCode === 200 && isset($responseData['messages'])) {
        return [
            'success' => true,
            'message_id' => $responseData['messages'][0]['id'] ?? null,
            'method' => 'api'
        ];
    }
    
    return [
        'success' => false,
        'error' => $responseData['error']['message'] ?? 'Unknown error',
        'method' => 'api'
    ];
}

/**
 * Send WhatsApp message using Twilio API
 * 
 * @param string $phone Recipient phone number
 * @param string $message Message to send
 * @return array Response with success status and details
 */
function sendWhatsAppMessageTwilio($phone, $message) {
    $accountSid = getenv('TWILIO_ACCOUNT_SID') ?: '';
    $authToken = getenv('TWILIO_AUTH_TOKEN') ?: '';
    $twilioWhatsAppNumber = getenv('TWILIO_WHATSAPP_NUMBER') ?: '';
    
    if (empty($accountSid) || empty($authToken) || empty($twilioWhatsAppNumber)) {
        return [
            'success' => false,
            'error' => 'Twilio credentials not configured',
            'method' => 'twilio'
        ];
    }
    
    $url = "https://api.twilio.com/2010-04-01/Accounts/{$accountSid}/Messages.json";
    
    $formattedPhone = formatWhatsAppNumber($phone);
    
    $data = [
        'From' => 'whatsapp:' . $twilioWhatsAppNumber,
        'To' => 'whatsapp:+' . $formattedPhone,
        'Body' => $message
    ];
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_USERPWD, $accountSid . ':' . $authToken);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    $responseData = json_decode($response, true);
    
    if ($httpCode === 201 && isset($responseData['sid'])) {
        return [
            'success' => true,
            'message_id' => $responseData['sid'],
            'method' => 'twilio'
        ];
    }
    
    return [
        'success' => false,
        'error' => $responseData['message'] ?? 'Unknown error',
        'method' => 'twilio'
    ];
}

/**
 * Generate WhatsApp Web link with pre-filled message
 * This opens WhatsApp Web/App with the message ready to send
 * User needs to manually click send
 * 
 * @param string $phone Recipient phone number
 * @param string $message Pre-filled message
 * @return string WhatsApp link
 */
function generateWhatsAppLink($phone, $message) {
    $formattedPhone = formatWhatsAppNumber($phone);
    $encodedMessage = urlencode($message);
    
    return "https://wa.me/{$formattedPhone}?text={$encodedMessage}";
}

/**
 * Log WhatsApp message attempt to database
 * 
 * @param string $orderNumber Order reference
 * @param string $phone Recipient phone
 * @param string $message Message sent
 * @param array $result Send result
 * @return bool Success status
 */
function logWhatsAppMessage($orderNumber, $phone, $message, $result) {
    try {
        $pdo = getDBConnection();
        
        // Create table if not exists
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS whatsapp_logs (
                id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
                order_number VARCHAR(50) NOT NULL,
                phone_number VARCHAR(30) NOT NULL,
                message TEXT NOT NULL,
                method VARCHAR(20),
                success BOOLEAN DEFAULT FALSE,
                message_id VARCHAR(100),
                error_message TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_order (order_number),
                INDEX idx_phone (phone_number),
                INDEX idx_created (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");
        
        $stmt = $pdo->prepare("
            INSERT INTO whatsapp_logs (
                order_number, phone_number, message, method, 
                success, message_id, error_message
            ) VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        
        return $stmt->execute([
            $orderNumber,
            $phone,
            $message,
            $result['method'] ?? 'link',
            $result['success'] ? 1 : 0,
            $result['message_id'] ?? null,
            $result['error'] ?? null
        ]);
        
    } catch (Exception $e) {
        error_log('[WhatsApp] Failed to log message: ' . $e->getMessage());
        return false;
    }
}

/**
 * Send WhatsApp message to customer after order
 * Tries different methods based on configuration
 * 
 * @param string $phone Customer phone number
 * @param array $orderData Order details
 * @return array Result with success status and method used
 */
function sendOrderWhatsAppMessage($phone, $orderData) {
    $message = generateOrderConfirmationMessage($orderData);
    $result = ['success' => false];
    
    // Try WhatsApp Business API first
    $whatsappMethod = getenv('WHATSAPP_METHOD') ?: 'link'; // 'api', 'twilio', or 'link'
    
    switch ($whatsappMethod) {
        case 'api':
            $result = sendWhatsAppMessageAPI($phone, $message);
            break;
            
        case 'twilio':
            $result = sendWhatsAppMessageTwilio($phone, $message);
            break;
            
        case 'link':
        default:
            // Generate link for manual sending or admin notification
            $link = generateWhatsAppLink($phone, $message);
            $result = [
                'success' => true,
                'method' => 'link',
                'link' => $link,
                'message' => 'WhatsApp link generated'
            ];
            break;
    }
    
    // Log the attempt
    logWhatsAppMessage(
        $orderData['order_number'],
        $phone,
        $message,
        $result
    );
    
    return $result;
}

/**
 * Get WhatsApp configuration status
 * 
 * @return array Configuration status
 */
function getWhatsAppConfig() {
    return [
        'method' => getenv('WHATSAPP_METHOD') ?: 'link',
        'api_configured' => !empty(getenv('WHATSAPP_ACCESS_TOKEN')) && !empty(getenv('WHATSAPP_PHONE_NUMBER_ID')),
        'twilio_configured' => !empty(getenv('TWILIO_ACCOUNT_SID')) && !empty(getenv('TWILIO_AUTH_TOKEN')),
    ];
}

