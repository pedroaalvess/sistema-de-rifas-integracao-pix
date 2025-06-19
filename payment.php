<?php
require_once 'config.php';
require_once 'api/blackcat.php';

session_start();

// Verify if payment_id exists in session
if (!isset($_SESSION['payment_id'])) {
    header('Location: index.php');
    exit;
}

$payment_id = $_SESSION['payment_id'];

// Fetch payment details
$stmt = $mysqli->prepare("
    SELECT p.*, b.name, b.cpf, b.cellphone, c.title as campaign_title
    FROM payments p
    JOIN buyers b ON p.buyer_id = b.id
    JOIN campaigns c ON p.campaign_id = c.id
    WHERE p.id = ?
");
$stmt->bind_param('i', $payment_id);
$stmt->execute();
$payment = $stmt->get_result()->fetch_assoc();

if (!$payment) {
    header('Location: index.php');
    exit;
}

// Handle AJAX payment status check
if (isset($_POST['check_status'])) {
    $blackcat = new BlackCatAPI();
    $status = $blackcat->checkPaymentStatus($payment['transaction_id']);
    
    if ($status === 'paid') {
        // Update payment status
        $stmt = $mysqli->prepare("UPDATE payments SET payment_status = 'paid' WHERE id = ?");
        $stmt->bind_param('i', $payment_id);
        $stmt->execute();

        // Update raffle numbers status
        $stmt = $mysqli->prepare("UPDATE raffle_numbers SET status = 'paid' WHERE payment_id = ?");
        $stmt->bind_param('i', $payment_id);
        $stmt->execute();

        echo json_encode(['status' => 'paid']);
        exit;
    }
    
    echo json_encode(['status' => $status]);
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pagamento PIX - <?= htmlspecialchars($payment['campaign_title']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body class="bg-gray-100 min-h-screen">
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-2xl mx-auto bg-white rounded-lg shadow-md p-6">
            <div class="text-center mb-6">
                <h1 class="text-2xl font-bold">Pagamento PIX</h1>
                <p class="text-gray-600">Finalize seu pagamento usando o código PIX abaixo</p>
            </div>

            <!-- Payment Status -->
            <div id="paymentStatus" class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-md">
                <h2 class="font-semibold mb-2">Status do Pagamento</h2>
                <p>Status atual: <span id="currentStatus" class="font-medium">Aguardando pagamento</span></p>
                <div id="countdown" class="mt-2">
                    Expira em: <span id="timer" class="font-medium">10:00</span>
                </div>
            </div>

            <!-- Purchase Summary -->
            <div class="mb-6 p-4 bg-gray-50 rounded-md">
                <h2 class="font-semibold mb-2">Resumo da Compra</h2>
                <p>Campanha: <?= htmlspecialchars($payment['campaign_title']) ?></p>
                <p>Quantidade: <?= htmlspecialchars($payment['quantity']) ?></p>
                <p>Tipo: <?= htmlspecialchars($payment['combo_type']) ?></p>
                <p class="font-bold mt-2">Total: R$ <?= number_format($payment['amount'], 2, ',', '.') ?></p>
            </div>

            <!-- Buyer Info -->
            <div class="mb-6 p-4 bg-gray-50 rounded-md">
                <h2 class="font-semibold mb-2">Dados do Comprador</h2>
                <p>Nome: <?= htmlspecialchars($payment['name']) ?></p>
                <p>CPF: <?= substr($payment['cpf'], 0, 3) . '.XXX.XXX-' . substr($payment['cpf'], -2) ?></p>
                <p>Celular: (XX) XXXXX-<?= substr($payment['cellphone'], -4) ?></p>
            </div>

            <!-- PIX Code Section -->
            <div class="mb-6">
                <h2 class="font-semibold mb-4">Código PIX Copia e Cola</h2>
                <div class="relative">
                    <input type="text" id="pixCode" 
                           value="<?= htmlspecialchars($payment['pix_code']) ?>" 
                           class="w-full p-3 pr-24 border rounded-md bg-gray-50" 
                           readonly>
                    <button onclick="copyPixCode()" 
                            class="absolute right-2 top-1/2 transform -translate-y-1/2 bg-blue-600 text-white px-4 py-1 rounded-md hover:bg-blue-700">
                        Copiar
                    </button>
                </div>
            </div>

            <!-- QR Code Section -->
            <div class="mb-6 text-center">
                <h2 class="font-semibold mb-4">QR Code PIX</h2>
                <img src="<?= htmlspecialchars($payment['qr_code_url']) ?>" 
                     alt="QR Code PIX" 
                     class="mx-auto max-w-[200px]">
            </div>

            <!-- Payment Confirmation Button -->
            <button id="checkPaymentBtn" 
                    onclick="checkPayment()" 
                    class="w-full bg-green-600 text-white py-3 rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2">
                Já fiz o pagamento
            </button>

            <p class="mt-4 text-sm text-gray-600 text-center">
                Se o pagamento não for concluído no tempo, os números voltam a ficar disponíveis.
            </p>
        </div>
    </div>

    <script>
        // Copy PIX code
        function copyPixCode() {
            const pixCode = document.getElementById('pixCode');
            pixCode.select();
            document.execCommand('copy');
            
            // Show feedback
            const button = document.querySelector('button');
            const originalText = button.textContent;
            button.textContent = 'Copiado!';
            setTimeout(() => button.textContent = originalText, 2000);
        }

        // Countdown timer
        function startCountdown() {
            const timerDisplay = document.getElementById('timer');
            const expiresAt = new Date('<?= $payment['expires_at'] ?>').getTime();
            
            const timer = setInterval(() => {
                const now = new Date().getTime();
                const distance = expiresAt - now;
                
                if (distance < 0) {
                    clearInterval(timer);
                    timerDisplay.textContent = 'EXPIRADO';
                    document.getElementById('checkPaymentBtn').disabled = true;
                    return;
                }
                
                const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                const seconds = Math.floor((distance % (1000 * 60)) / 1000);
                
                timerDisplay.textContent = `${minutes}:${seconds.toString().padStart(2, '0')}`;
            }, 1000);
        }

        // Check payment status
        function checkPayment() {
            const statusElement = document.getElementById('currentStatus');
            const button = document.getElementById('checkPaymentBtn');
            
            statusElement.textContent = 'Verificando pagamento...';
            button.disabled = true;
            
            fetch('payment.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'check_status=1'
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'paid') {
                    statusElement.textContent = 'Pagamento confirmado!';
                    statusElement.classList.add('text-green-600');
                    button.style.display = 'none';
                    
                    // Redirect to success page after 2 seconds
                    setTimeout(() => {
                        window.location.href = 'success.php';
                    }, 2000);
                } else {
                    statusElement.textContent = 'Aguardando confirmação...';
                    button.disabled = false;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                statusElement.textContent = 'Erro ao verificar pagamento';
                button.disabled = false;
            });
        }

        // Start countdown when page loads
        document.addEventListener('DOMContentLoaded', startCountdown);
    </script>
</body>
</html>
