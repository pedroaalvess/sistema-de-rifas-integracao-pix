<?php
require_once __DIR__ . '/../config.php';

class BlackCatAPI {
    private $baseUrl = 'https://api.blackcatpagamentos.com/v1';
    private $publicKey;
    private $secretKey;

    public function __construct() {
        $this->publicKey = BLACKCAT_PUBLIC_KEY;
        $this->secretKey = BLACKCAT_SECRET_KEY;
    }

    /**
     * Create a PIX payment
     * @param float $amount Total amount to be paid
     * @param array $metadata Additional data about the purchase
     * @return array|false Returns payment data or false on failure
     */
    public function createPixPayment($amount, $metadata = []) {
        try {
            $payload = [
                'amount' => (int)($amount * 100), // Convert to cents
                'paymentMethod' => 'pix',
                'metadata' => array_merge([
                    'campaign_id' => $metadata['campaign_id'] ?? null,
                    'buyer_id' => $metadata['buyer_id'] ?? null,
                    'quantity' => $metadata['quantity'] ?? 1,
                    'combo_type' => $metadata['combo_type'] ?? null
                ], $metadata)
            ];

            $response = $this->makeRequest('/transactions', 'POST', $payload);

            if (isset($response['error'])) {
                throw new Exception($response['error']['message'] ?? 'Unknown error occurred');
            }

            return [
                'pix_code' => $response['pix_code'] ?? null,
                'qr_code_url' => $response['qr_code_url'] ?? null,
                'transaction_id' => $response['id'] ?? null,
                'expires_at' => date('Y-m-d H:i:s', strtotime('+10 minutes'))
            ];
        } catch (Exception $e) {
            error_log("PIX Payment Creation Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Check payment status
     * @param string $transactionId The transaction ID to check
     * @return string|false Returns status or false on failure
     */
    public function checkPaymentStatus($transactionId) {
        try {
            $response = $this->makeRequest("/transactions/{$transactionId}", 'GET');
            
            if (isset($response['error'])) {
                throw new Exception($response['error']['message'] ?? 'Unknown error occurred');
            }

            return $response['status'] ?? false;
        } catch (Exception $e) {
            error_log("Payment Status Check Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Make HTTP request to BlackCat API
     * @param string $endpoint API endpoint
     * @param string $method HTTP method
     * @param array $data Request data
     * @return array Response data
     */
    private function makeRequest($endpoint, $method = 'GET', $data = null) {
        $ch = curl_init();
        
        $url = $this->baseUrl . $endpoint;
        $headers = [
            'Authorization: Basic ' . base64_encode($this->publicKey . ':' . $this->secretKey),
            'Content-Type: application/json'
        ];

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => true
        ]);

        if ($data && in_array($method, ['POST', 'PUT', 'PATCH'])) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if (curl_errno($ch)) {
            throw new Exception('Curl error: ' . curl_error($ch));
        }
        
        curl_close($ch);

        $responseData = json_decode($response, true);
        
        if ($httpCode >= 400) {
            throw new Exception('API Error: ' . ($responseData['message'] ?? 'Unknown error'));
        }

        return $responseData;
    }
}

// Example usage:
/*
$blackcat = new BlackCatAPI();

// Create a payment
$payment = $blackcat->createPixPayment(100.00, [
    'campaign_id' => 1,
    'buyer_id' => 1,
    'quantity' => 5,
    'combo_type' => '+100'
]);

if ($payment) {
    // Store payment info in database
    // Show PIX code and QR code to user
}

// Check payment status
$status = $blackcat->checkPaymentStatus('transaction_id_here');
if ($status === 'paid') {
    // Update payment status in database
    // Release raffle numbers to buyer
}
*/
?>
