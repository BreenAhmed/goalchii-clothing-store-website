# WhatsApp Auto-Message System Flow

## 📊 System Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                         CUSTOMER JOURNEY                         │
└─────────────────────────────────────────────────────────────────┘

    Customer                Store                  Database
       ┃                     ┃                        ┃
       ┃  Browse Products    ┃                        ┃
       ├────────────────────►┃                        ┃
       ┃                     ┃                        ┃
       ┃  Add to Cart        ┃                        ┃
       ├────────────────────►┃  Store in Session     ┃
       ┃                     ┃                        ┃
       ┃  Proceed Checkout   ┃                        ┃
       ├────────────────────►┃                        ┃
       ┃                     ┃                        ┃
       ┃  Fill Details       ┃                        ┃
       ┃  (Name, Phone, etc) ┃                        ┃
       ├────────────────────►┃                        ┃
       ┃                     ┃                        ┃
       ┃  Submit Order       ┃                        ┃
       ├────────────────────►┃  Save Order           ┃
       ┃                     ├───────────────────────►┃
       ┃                     ┃                        ┃
       ┃                     ┃  ┌──────────────────┐ ┃
       ┃                     ┃  │ WhatsApp Helper  │ ┃
       ┃                     ┃  │ - Format Phone   │ ┃
       ┃                     ┃  │ - Generate Msg   │ ┃
       ┃                     ┃  │ - Send/Link      │ ┃
       ┃                     ┃  └──────────────────┘ ┃
       ┃                     ┃                        ┃
       ┃                     ┃  Log Message          ┃
       ┃                     ├───────────────────────►┃
       ┃                     ┃                        ┃
       ┃  Order Success      ┃                        ┃
       ◄────────────────────┤                        ┃
       ┃                     ┃                        ┃
   📱WhatsApp Message        ┃                        ┃
   (if API configured)       ┃                        ┃
```

## 🔄 Message Flow Details

### 1. Order Placement
```
checkout.php
    ├── Validate form data
    ├── Calculate totals
    ├── Generate order number
    └── Insert into order_requests table
```

### 2. WhatsApp Integration Trigger
```
checkout.php (line 148-162)
    ├── Prepare order data
    ├── Call: sendOrderWhatsAppMessage()
    │   ├── Load config
    │   ├── Generate message
    │   └── Choose method
    │       ├── Method: Link
    │       │   └── Generate wa.me link
    │       ├── Method: API
    │       │   └── Call WhatsApp Business API
    │       └── Method: Twilio
    │           └── Call Twilio API
    └── Log result
```

### 3. Message Generation
```
generateOrderConfirmationMessage()
    ├── Greeting with customer name
    ├── Order details
    │   ├── Order number
    │   └── Date/time
    ├── Product list
    │   ├── For each item:
    │   │   ├── Product name
    │   │   ├── Size
    │   │   ├── Quantity
    │   │   └── Price
    ├── Total breakdown
    │   ├── Subtotal
    │   ├── Shipping cost
    │   └── Grand total
    ├── Shipping address
    ├── Customer notes (if any)
    ├── Payment method
    └── Closing message
```

## 🎛️ Configuration Methods

### Link Method (Default)
```
┌──────────────┐
│   Customer   │
│  Places      │
│  Order       │
└──────┬───────┘
       │
       ▼
┌──────────────┐     ┌──────────────┐
│  Generate    │────►│  Store in    │
│  WhatsApp    │     │  Database    │
│  Link        │     │  (Logs)      │
└──────────────┘     └──────────────┘
       │
       ▼
┌──────────────┐
│  Admin can   │
│  view/click  │
│  link        │
└──────────────┘
```

### API Method (WhatsApp Business)
```
┌──────────────┐
│   Customer   │
│  Places      │
│  Order       │
└──────┬───────┘
       │
       ▼
┌──────────────┐     ┌──────────────┐     ┌──────────────┐
│  Format      │────►│  Call FB     │────►│  WhatsApp    │
│  Message     │     │  Graph API   │     │  Delivers    │
└──────────────┘     └──────────────┘     └──────────────┘
       │
       ▼
┌──────────────┐
│  Log Result  │
│  in Database │
└──────────────┘
```

### Twilio Method
```
┌──────────────┐
│   Customer   │
│  Places      │
│  Order       │
└──────┬───────┘
       │
       ▼
┌──────────────┐     ┌──────────────┐     ┌──────────────┐
│  Format      │────►│  Call Twilio │────►│  WhatsApp    │
│  Message     │     │  API         │     │  Delivers    │
└──────────────┘     └──────────────┘     └──────────────┘
       │
       ▼
┌──────────────┐
│  Log Result  │
│  in Database │
└──────────────┘
```

## 🗄️ Database Structure

```
┌─────────────────────────────────────────┐
│         order_requests                  │
├─────────────────────────────────────────┤
│ - id                                    │
│ - order_number (REQ-20241113-ABC123)   │
│ - customer_name                         │
│ - customer_email                        │
│ - customer_phone ◄───────────┐         │
│ - shipping_address            │         │
│ - city                        │         │
│ - country                     │         │
│ - subtotal                    │         │
│ - shipping_cost               │         │
│ - total_amount                │         │
│ - payment_method              │         │
│ - customer_notes              │         │
│ - items_json                  │         │
│ - status                      │         │
│ - created_at                  │         │
└───────────────────────────────┘         │
                                          │
                                          │ Used to
                                          │ send message
                                          │
┌─────────────────────────────────────────┘
│
▼
┌─────────────────────────────────────────┐
│         whatsapp_logs                   │
├─────────────────────────────────────────┤
│ - id                                    │
│ - order_number (FK reference)           │
│ - phone_number                          │
│ - message (full text)                   │
│ - method (link/api/twilio)              │
│ - success (true/false)                  │
│ - message_id (from API)                 │
│ - error_message (if failed)             │
│ - created_at                            │
└─────────────────────────────────────────┘
```

## 👨‍💼 Admin Panel Flow

```
┌─────────────────────────────────────────┐
│          Admin Dashboard                │
└────────────────┬────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────┐
│     E-Commerce Dropdown Menu            │
│  ┌───────────────────────────────────┐  │
│  │ - Website Dashboard               │  │
│  │ - Products                        │  │
│  │ - Blog                            │  │
│  │ - Orders                          │  │
│  │ - Clubs                           │  │
│  │ - Messages                        │  │
│  │ - WhatsApp Logs ◄─────────────┐  │  │
│  │ - View Website                 │  │  │
│  └────────────────────────────────┘  │  │
└─────────────────────────────────────────┘
                                       │
                                       ▼
┌──────────────────────────────────────────────┐
│        WhatsApp Logs Page                    │
├──────────────────────────────────────────────┤
│  ┌────────────────────────────────────┐      │
│  │  Configuration Status              │      │
│  │  - Current Method                  │      │
│  │  - API Status                      │      │
│  │  - Twilio Status                   │      │
│  └────────────────────────────────────┘      │
│                                              │
│  ┌────────────────────────────────────┐      │
│  │  Statistics                        │      │
│  │  - Total Messages: 150             │      │
│  │  - Successful: 145                 │      │
│  │  - Failed: 5                       │      │
│  └────────────────────────────────────┘      │
│                                              │
│  ┌────────────────────────────────────┐      │
│  │  Message List                      │      │
│  │  ┌──────────────────────────────┐  │      │
│  │  │ Order # | Customer | Phone  │  │      │
│  │  │ Status  | [View] | [Resend]│  │      │
│  │  └──────────────────────────────┘  │      │
│  └────────────────────────────────────┘      │
└──────────────────────────────────────────────┘
```

## 🧪 Testing Flow

```
┌─────────────────────────────────────────┐
│      test-whatsapp.php                  │
└────────────────┬────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────┐
│  1. Display Configuration Status        │
│     - Method being used                 │
│     - API configuration status          │
└────────────────┬────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────┐
│  2. Test Form                           │
│     - Enter phone number                │
│     - Enter customer name               │
│     - Submit                            │
└────────────────┬────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────┐
│  3. Generate Test Order Data            │
│     - Create dummy order                │
│     - Use provided phone/name           │
└────────────────┬────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────┐
│  4. Call sendOrderWhatsAppMessage()     │
└────────────────┬────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────┐
│  5. Display Result                      │
│     - Success: Show link or message ID  │
│     - Failed: Show error details        │
└─────────────────────────────────────────┘
```

## 📱 Phone Number Processing

```
Input: "0750 123 4567"
   │
   ▼
Remove non-numeric: "07501234567"
   │
   ▼
Remove leading zero: "7501234567"
   │
   ▼
Add country code: "9647501234567"
   │
   ▼
Output: "9647501234567"

---

Input: "9647501234567"
   │
   ▼
Already has country code
   │
   ▼
Output: "9647501234567"

---

Input: "+964 750 123 4567"
   │
   ▼
Remove non-numeric: "9647501234567"
   │
   ▼
Output: "9647501234567"
```

## 🔄 Message Resend Flow

```
┌─────────────────────────────────────────┐
│  Admin clicks "Resend" in Logs Page     │
└────────────────┬────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────┐
│  Fetch original order from database     │
│  - Get order_number from log            │
│  - Query order_requests table           │
└────────────────┬────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────┐
│  Reconstruct order data                 │
│  - Parse items_json                     │
│  - Build order array                    │
└────────────────┬────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────┐
│  Call sendOrderWhatsAppMessage()        │
│  - Same process as initial send         │
└────────────────┬────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────┐
│  Log new attempt                        │
│  - Create new entry in whatsapp_logs    │
└────────────────┬────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────┐
│  Show result to admin                   │
│  - Success or error message             │
└─────────────────────────────────────────┘
```

## 🎯 Key Integration Points

1. **Entry Point:** `store/checkout.php` (line 148)
2. **Core Logic:** `includes/whatsapp-helper.php`
3. **Configuration:** `config/whatsapp-config.php`
4. **Logging:** `whatsapp_logs` table
5. **Admin View:** `whatsapp-logs.php`
6. **Testing:** `test-whatsapp.php`

## 📊 Success Metrics

```
┌─────────────────────────────────────────┐
│            Tracking Points              │
├─────────────────────────────────────────┤
│ ✓ Orders placed                         │
│ ✓ Messages generated                    │
│ ✓ Messages sent (if API)                │
│ ✓ Success rate                          │
│ ✓ Failed attempts                       │
│ ✓ API response times                    │
│ ✓ Error types                           │
└─────────────────────────────────────────┘
```

All metrics available in `whatsapp-logs.php`



