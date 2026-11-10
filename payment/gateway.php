<?php
/**
 * YoursSpeciallyJewellery - Payment Gateway Integration Layer
 * Razorpay Standard Web Checkout Integration (API v1)
 */

require_once __DIR__ . '/../config/config.php';

// ==========================================================
// RAZORPAY ENVIRONMENT CREDENTIALS
// Loaded securely from .env or system environment variables
// ==========================================================
$envKeyId = getenv('RAZORPAY_KEY_ID') ?: ($_ENV['RAZORPAY_KEY_ID'] ?? ($_SERVER['RAZORPAY_KEY_ID'] ?? ''));
$envKeySecret = getenv('RAZORPAY_KEY_SECRET') ?: ($_ENV['RAZORPAY_KEY_SECRET'] ?? ($_SERVER['RAZORPAY_KEY_SECRET'] ?? ''));

if (!defined('RAZORPAY_KEY_ID')) {
    define('RAZORPAY_KEY_ID', $envKeyId);
}
if (!defined('RAZORPAY_KEY_SECRET')) {
    define('RAZORPAY_KEY_SECRET', $envKeySecret);
}

/**
 * Interface defining standard payment provider contract
 */
interface PaymentGatewayInterface {
    public function createGatewayOrder(array $orderData): array;
    public function verifyPayment(array $paymentData): bool;
    public function verifyWebhook(string $payload, string $signature): bool;
}

/**
 * Razorpay Standard Checkout Implementation
 */
class RazorpayGateway implements PaymentGatewayInterface {
    private string $keyId;
    private string $keySecret;
    private string $apiBaseUrl = 'https://api.razorpay.com/v1';

    public function __construct(?string $keyId = null, ?string $keySecret = null) {
        $this->keyId = $keyId ?: RAZORPAY_KEY_ID;
        $this->keySecret = $keySecret ?: RAZORPAY_KEY_SECRET;
    }

    public function getKeyId(): string {
        return $this->keyId;
    }

    /**
     * Creates an order with Razorpay Orders API: POST https://api.razorpay.com/v1/orders
     *
     * @param array $orderData Must contain amount (in paise or total_amount) and receipt
     * @return array Array with success, order_id, amount, currency, or error details
     */
    public function createGatewayOrder(array $orderData): array {
        // Calculate amount in paise (1 INR = 100 paise)
        if (isset($orderData['amount']) && is_numeric($orderData['amount'])) {
            $amountInPaise = (int)$orderData['amount'];
        } elseif (isset($orderData['total_amount']) && is_numeric($orderData['total_amount'])) {
            $amountInPaise = (int)round((float)$orderData['total_amount'] * 100);
        } else {
            return [
                'success' => false,
                'status_code' => 400,
                'message' => 'Missing or invalid amount.'
            ];
        }

        // Validate minimum amount (Minimum 100 paise = ₹1.00)
        if ($amountInPaise < 100) {
            return [
                'success' => false,
                'status_code' => 400,
                'message' => 'Amount must be at least 100 paise (₹1.00).'
            ];
        }

        $currency = strtoupper($orderData['currency'] ?? 'INR');
        $receipt = substr((string)($orderData['receipt'] ?? $orderData['order_number'] ?? ('YSJ-' . time())), 0, 40);

        // Check if API credentials are configured
        if (empty($this->keyId) || empty($this->keySecret) || str_contains($this->keyId, 'YOUR_KEY_ID')) {
            return [
                'success' => false,
                'status_code' => 401,
                'message' => 'Razorpay API credentials not configured in .env'
            ];
        }

        // Prepare Razorpay API request payload
        $payload = [
            'amount' => $amountInPaise,
            'currency' => $currency,
            'receipt' => $receipt,
            'notes' => [
                'platform' => 'YoursSpecially Jewellery',
                'customer_name' => $orderData['customer_name'] ?? 'Boutique Customer',
                'customer_email' => $orderData['customer_email'] ?? ''
            ]
        ];

        // Call Razorpay API: POST https://api.razorpay.com/v1/orders
        $ch = curl_init($this->apiBaseUrl . '/orders');
        curl_setopt($ch, CURLOPT_USERPWD, $this->keyId . ':' . $this->keySecret);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Accept: application/json'
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        // SSL compatibility for local development environments without CA bundle
        $isLocal = (
            in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1']) ||
            strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false ||
            php_sapi_name() === 'cli'
        );
        if ($isLocal) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        } else {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($response === false || !empty($curlError)) {
            error_log("Razorpay cURL Connection Error: " . $curlError);
            return [
                'success' => false,
                'status_code' => 500,
                'message' => 'Unable to connect to Razorpay payment gateway: ' . $curlError
            ];
        }

        $result = json_decode($response, true);

        // Handle HTTP 401 Authentication Failure
        if ($httpCode === 401) {
            error_log("Razorpay Authentication Error (401): " . $response);
            return [
                'success' => false,
                'status_code' => 401,
                'message' => 'Razorpay authentication failed. Please verify RAZORPAY_KEY_ID and RAZORPAY_KEY_SECRET in .env.'
            ];
        }

        // Success: Razorpay order created
        if ($httpCode === 200 && isset($result['id'])) {
            return [
                'success' => true,
                'order_id' => $result['id'],
                'amount' => (int)$result['amount'],
                'currency' => $result['currency'],
                'receipt' => $result['receipt'] ?? $receipt,
                'key_id' => $this->keyId,
                'status_code' => 200
            ];
        }

        // Razorpay API returned error
        $errorMsg = $result['error']['description'] ?? ($result['error']['code'] ?? 'Failed to create Razorpay order.');
        error_log("Razorpay Order API Error ($httpCode): " . $response);
        return [
            'success' => false,
            'status_code' => $httpCode ?: 500,
            'message' => $errorMsg,
            'raw_error' => $result['error'] ?? null
        ];
    }

    /**
     * Verifies payment signature using HMAC SHA-256
     * Expected signature = HMAC-SHA256(order_id + "|" + payment_id, KEY_SECRET)
     *
     * @param array $paymentData Must contain razorpay_order_id, razorpay_payment_id, razorpay_signature
     * @return bool True if signature matches, false otherwise
     */
    public function verifyPayment(array $paymentData): bool {
        $razorpayOrderId = trim($paymentData['razorpay_order_id'] ?? '');
        $razorpayPaymentId = trim($paymentData['razorpay_payment_id'] ?? '');
        $razorpaySignature = trim($paymentData['razorpay_signature'] ?? '');

        if (empty($razorpayOrderId) || empty($razorpayPaymentId) || empty($razorpaySignature)) {
            return false;
        }

        if (empty($this->keySecret)) {
            error_log("Razorpay verifyPayment error: Key secret is not configured.");
            return false;
        }

        // Compute HMAC-SHA256(order_id + "|" + payment_id, KEY_SECRET)
        $expectedSignature = hash_hmac('sha256', $razorpayOrderId . '|' . $razorpayPaymentId, $this->keySecret);

        // Constant-time string comparison to prevent timing attacks
        return hash_equals($expectedSignature, $razorpaySignature);
    }

    /**
     * Verifies webhook signature
     */
    public function verifyWebhook(string $payload, string $signature): bool {
        if (empty($this->keySecret) || empty($signature)) {
            return false;
        }
        $expectedSignature = hash_hmac('sha256', $payload, $this->keySecret);
        return hash_equals($expectedSignature, $signature);
    }
}

/**
 * Returns singleton or instance of configured payment gateway
 */
function getPaymentGateway(): PaymentGatewayInterface {
    static $gateway = null;
    if ($gateway === null) {
        $gateway = new RazorpayGateway();
    }
    return $gateway;
}
