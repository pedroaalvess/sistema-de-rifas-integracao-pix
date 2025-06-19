<?php
require_once 'config.php';
session_start();

// Verify if payment_id exists in session
if (!isset($_SESSION['payment_id'])) {
    header('Location: index.php');
    exit;
}

$payment_id = $_SESSION['payment_id'];

// Fetch payment and related details
$stmt = $mysqli->prepare("
    SELECT 
        p.*, 
        b.name, b.cpf, b.email,
        c.title as campaign_title,
        c.draw_date
    FROM payments p
    JOIN buyers b ON p.buyer_id = b.id
    JOIN campaigns c ON p.campaign_id = c.id
    WHERE p.id = ? AND p.payment_status = 'paid'
");
$stmt->bind_param('i', $payment_id);
$stmt->execute();
$payment = $stmt->get_result()->fetch_assoc();

if (!$payment) {
    header('Location: index.php');
    exit;
}

// Fetch raffle numbers
$stmt = $mysqli->prepare("
    SELECT number 
    FROM raffle_numbers 
    WHERE payment_id = ? AND status = 'paid'
    ORDER BY number ASC
");
$stmt->bind_param('i', $payment_id);
$stmt->execute();
$result = $stmt->get_result();
$raffle_numbers = [];
while ($row = $result->fetch_assoc()) {
    $raffle_numbers[] = $row['number'];
}

// Clear the session payment_id
unset($_SESSION['payment_id']);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pagamento Confirmado - Sistema de Rifas</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body class="bg-gray-100 min-h-screen">
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-2xl mx-auto">
            <!-- Success Message -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <div class="text-center mb-6">
                    <i class="fas fa-check-circle text-green-500 text-5xl mb-4"></i>
                    <h1 class="text-2xl font-bold text-gray-800">Pagamento Confirmado!</h1>
                    <p class="text-gray-600">Seus números da sorte foram reservados com sucesso.</p>
                </div>

                <!-- Campaign Details -->
                <div class="mb-6 p-4 bg-gray-50 rounded-md">
                    <h2 class="font-semibold mb-2">Detalhes da Campanha</h2>
                    <p class="text-gray-700">Campanha: <?= htmlspecialchars($payment['campaign_title']) ?></p>
                    <p class="text-gray-700">Data do Sorteio: <?= date('d/m/Y', strtotime($payment['draw_date'])) ?></p>
                </div>

                <!-- Buyer Details -->
                <div class="mb-6 p-4 bg-gray-50 rounded-md">
                    <h2 class="font-semibold mb-2">Seus Dados</h2>
                    <p class="text-gray-700">Nome: <?= htmlspecialchars($payment['name']) ?></p>
                    <p class="text-gray-700">CPF: <?= substr($payment['cpf'], 0, 3) . '.XXX.XXX-' . substr($payment['cpf'], -2) ?></p>
                    <p class="text-gray-700">Email: <?= htmlspecialchars($payment['email']) ?></p>
                </div>

                <!-- Purchase Details -->
                <div class="mb-6 p-4 bg-gray-50 rounded-md">
                    <h2 class="font-semibold mb-2">Detalhes da Compra</h2>
                    <p class="text-gray-700">Quantidade: <?= $payment['quantity'] ?> números</p>
                    <p class="text-gray-700">Tipo: <?= $payment['combo_type'] === 'unit' ? 'Individual' : 'Combo ' . $payment['combo_type'] ?></p>
                    <p class="text-gray-700 font-semibold">Valor Total: R$ <?= number_format($payment['amount'], 2, ',', '.') ?></p>
                </div>

                <!-- Raffle Numbers -->
                <div class="mb-6">
                    <h2 class="font-semibold mb-2">Seus Números da Sorte</h2>
                    <div class="bg-gray-50 p-4 rounded-md">
                        <div class="flex flex-wrap gap-2">
                            <?php foreach ($raffle_numbers as $number): ?>
                                <span class="inline-block bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-sm">
                                    <?= str_pad($number, 4, '0', STR_PAD_LEFT) ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Next Steps -->
                <div class="bg-blue-50 border border-blue-200 rounded-md p-4 mb-6">
                    <h2 class="font-semibold text-blue-800 mb-2">Próximos Passos</h2>
                    <ul class="text-blue-700 space-y-2">
                        <li><i class="fas fa-envelope mr-2"></i> Um email com seus números foi enviado para seu endereço cadastrado</li>
                        <li><i class="fas fa-bell mr-2"></i> Você receberá uma notificação no dia do sorteio</li>
                        <li><i class="fas fa-trophy mr-2"></i> Acompanhe o resultado em nossa plataforma</li>
                    </ul>
                </div>

                <!-- Action Buttons -->
                <div class="flex flex-col sm:flex-row gap-4">
                    <a href="index.php" 
                       class="flex-1 bg-blue-600 text-white text-center py-3 px-6 rounded-md hover:bg-blue-700 transition duration-200">
                        Ver Outras Campanhas
                    </a>
                    <button onclick="window.print()" 
                            class="flex-1 bg-gray-600 text-white py-3 px-6 rounded-md hover:bg-gray-700 transition duration-200">
                        Imprimir Comprovante
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Print Styles -->
    <style media="print">
        @page {
            margin: 2cm;
        }
        body {
            print-color-adjust: exact;
            -webkit-print-color-adjust: exact;
        }
        .container {
            width: 100% !important;
            max-width: none !important;
        }
    </style>
</body>
</html>
