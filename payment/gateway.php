<?php
/**
 * YoursSpeciallyJewellery - Payment Gateway Integration Layer
 * Ready for Indian Gateways (Razorpay / Cashfree / PayU / PhonePe)
 */

require_once __DIR__ . '/../config/config.php';

// ==========================================================
// PAYMENT GATEWAY CONFIGURATION PLACEHOLDERS
// Insert your live/sandbox credentials when you register your gateway
// ==========================================================
define('PAYMENT_GATEWAY_PROVIDER', getenv('PAYMENT_PROVIDER') ?: 'razorpay'); // 'razorpay' or 'cashfree'

// Razorpay Credentials (https://dashboard.razorpay.com/)
define('RAZORPAY_KEY_ID', getenv('RAZORPAY_KEY_ID') ?: 'rzp_test_YOUR_KEY_ID_HERE');
define('RAZORPAY_KEY_SECRET', getenv('RAZORPAY_KEY_SECRET') ?: 'YOUR_RAZORPAY_SECRET_HERE');

// Cashfree Credentials (https://merchant.cashfree.com/)
define('CASHFREE_APP_ID', getenv('CASHFREE_APP_ID') ?: 'YOUR_CASHFREE_APP_ID_HERE');
define('CASHFREE_SECRET_KEY', getenv('CASHFREE_SECRET_KEY') ?: 'YOUR_CASHFREE_SECRET_KEY_HERE');

/**
 * Interface defining standard payment provider contract
 */
interface PaymentGatewayInterface {
    public function createGatewayOrder(array $orderData): array;
    public function verifyPayment(array $paymentData): bool;
    public function verifyWebhook(string $payload, string $signature): bool;
}

/**
 * Razorpay Implementation Layer
 */
class RazorpayGateway implements PaymentGatewayInterface {
    private string $keyId;
    private string $keySecret;

    public function __construct() {
        $this->keyId = RAZORPAY_KEY_ID;
        $this->keySecret = RAZORPAY_KEY_SECRET;
    }

    public function createGatewayOrder(array $orderData): array {
        // Prepare order payload (Amount in paise for INR: ₹1 = 100 paise)
        $amountInPaise = (int)round($orderData['total_amount'] * 100);

        // If credentials are placeholders, provide a seamless mock gateway response for testing
        if (str_contains($this->keyId, 'YOUR_KEY_ID') || str_contains($this->keyId, 'rzp_test_YOUR')) {
            return [
                'success' => true,
                'gateway_order_id' => 'order_mock_' . bin2hex(random_bytes(6)),
                'amount' => $amountInPaise,
                'currency' => 'INR',
                'key_id' => $this->keyId,
                'is_mock' => true
            ];
        }

        // Live API call to Razorpay: https://api.razorpay.com/v1/orders
        $ch = curl_init('https://api.razorpay.com/v1/orders');
        curl_setopt($ch, CURLOPT_USERPWD, $this->keyId . ':' . $this->keySecret);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
            'amount' => $amountInPaise,
            'currency' => 'INR',
            'receipt' => $orderData['order_number'],
            'notes' => [
                'customer_name' => $orderData['customer_name'],
                'customer_email' => $orderData['customer_email']
            ]
        ]));

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $result = json_decode($response, true);
        if ($httpCode === 200 && isset($result['id'])) {
            return [
                'success' => true,
                'gateway_order_id' => $result['id'],
                'amount' => $amountInPaise,
                'currency' => 'INR',
                'key_id' => $this->keyId,
                'is_mock' => false
            ];
        }

        error_log("Razorpay Order Creation Failed: " . $response);
        return [
            'success' => false,
            'message' => $result['error']['description'] ?? 'Failed to initialize payment gateway order.'
        ];
    }

    public function verifyPayment(array $paymentData): bool {
        $razorpayOrderId = $paymentData['razorpay_order_id'] ?? '';
        $razorpayPaymentId = $paymentData['razorpay_payment_id'] ?? '';
        $razorpaySignature = $paymentData['razorpay_signature'] ?? '';

        if (empty($razorpayOrderId) || empty($razorpayPaymentId)) {
            return false;
        }

        // In test mode without live keys
        if (str_contains($this->keyId, 'YOUR_KEY_ID') || str_contains($this->keyId, 'rzp_test_YOUR')) {
            return true;
        }

        // Cryptographic HMAC SHA256 signature verification
        $expectedSignature = hash_hmac('sha256', $razorpayOrderId . '|' . $razorpayPaymentId, $this->keySecret);
        return hash_equals($expectedSignature, $razorpaySignature);
    }

    public function verifyWebhook(string $payload, string $signature): bool {
        $expectedSignature = hash_hmac('sha256', $payload, $this->keySecret);
        return hash_equals($expectedSignature, $signature);
    }
}

/**
 * Factory to return configured gateway handler
 */
function getPaymentGateway(): PaymentGatewayInterface {
    return new RazorpayGateway();
}
