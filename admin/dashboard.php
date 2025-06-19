<?php
require_once '../config.php';
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// Fetch active campaigns count
$stmt = $mysqli->query("SELECT COUNT(*) as count FROM campaigns WHERE status = 'active'");
$active_campaigns = $stmt->fetch_assoc()['count'];

// Fetch today's payments
$stmt = $mysqli->query("
    SELECT COUNT(*) as count, SUM(amount) as total 
    FROM payments 
    WHERE DATE(created_at) = CURDATE() AND payment_status = 'paid'
");
$today_payments = $stmt->fetch_assoc();

// Fetch pending payments
$stmt = $mysqli->query("
    SELECT COUNT(*) as count 
    FROM payments 
    WHERE payment_status IN ('pending', 'awaiting_confirmation')
");
$pending_payments = $stmt->fetch_assoc()['count'];

// Fetch recent transactions
$stmt = $mysqli->query("
    SELECT 
        p.id,
        p.amount,
        p.payment_status,
        p.created_at,
        b.name as buyer_name,
        c.title as campaign_title
    FROM payments p
    JOIN buyers b ON p.buyer_id = b.id
    JOIN campaigns c ON p.campaign_id = c.id
    ORDER BY p.created_at DESC
    LIMIT 10
");
$recent_transactions = $stmt->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Administrativo - Sistema de Rifas</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body class="bg-gray-100">
    <!-- Navigation -->
    <nav class="bg-white shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex">
                    <div class="flex-shrink-0 flex items-center">
                        <h1 class="text-xl font-bold text-gray-800">Admin Dashboard</h1>
                    </div>
                </div>
                <div class="flex items-center">
                    <a href="logout.php" class="text-gray-600 hover:text-gray-900">
                        <i class="fas fa-sign-out-alt mr-1"></i> Sair
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <!-- Quick Stats -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
            <!-- Active Campaigns -->
            <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                <div class="p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 bg-blue-500 rounded-md p-3">
                            <i class="fas fa-trophy text-white text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500">Campanhas Ativas</p>
                            <p class="text-2xl font-semibold text-gray-900"><?= $active_campaigns ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Today's Revenue -->
            <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                <div class="p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 bg-green-500 rounded-md p-3">
                            <i class="fas fa-dollar-sign text-white text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500">Receita Hoje</p>
                            <p class="text-2xl font-semibold text-gray-900">
                                R$ <?= number_format($today_payments['total'] ?? 0, 2, ',', '.') ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pending Payments -->
            <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                <div class="p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 bg-yellow-500 rounded-md p-3">
                            <i class="fas fa-clock text-white text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500">Pagamentos Pendentes</p>
                            <p class="text-2xl font-semibold text-gray-900"><?= $pending_payments ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="bg-white shadow-sm rounded-lg mb-6">
            <div class="p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Ações Rápidas</h2>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <a href="create_campaign.php" class="flex items-center p-4 bg-blue-50 rounded-lg hover:bg-blue-100">
                        <i class="fas fa-plus-circle text-blue-600 mr-3"></i>
                        <span class="text-blue-600 font-medium">Nova Campanha</span>
                    </a>
                    <a href="list_campaigns.php" class="flex items-center p-4 bg-green-50 rounded-lg hover:bg-green-100">
                        <i class="fas fa-list text-green-600 mr-3"></i>
                        <span class="text-green-600 font-medium">Listar Campanhas</span>
                    </a>
                    <a href="payments.php" class="flex items-center p-4 bg-yellow-50 rounded-lg hover:bg-yellow-100">
                        <i class="fas fa-money-bill-wave text-yellow-600 mr-3"></i>
                        <span class="text-yellow-600 font-medium">Ver Pagamentos</span>
                    </a>
                    <a href="winners.php" class="flex items-center p-4 bg-purple-50 rounded-lg hover:bg-purple-100">
                        <i class="fas fa-award text-purple-600 mr-3"></i>
                        <span class="text-purple-600 font-medium">Ganhadores</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- Recent Transactions -->
        <div class="bg-white shadow-sm rounded-lg">
            <div class="p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Transações Recentes</h2>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead>
                            <tr>
                                <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Data/Hora
                                </th>
                                <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Campanha
                                </th>
                                <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Comprador
                                </th>
                                <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Valor
                                </th>
                                <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Status
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($recent_transactions as $transaction): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?= date('d/m/Y H:i', strtotime($transaction['created_at'])) ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?= htmlspecialchars($transaction['campaign_title']) ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?= htmlspecialchars($transaction['buyer_name']) ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        R$ <?= number_format($transaction['amount'], 2, ',', '.') ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php
                                        $status_colors = [
                                            'pending' => 'yellow',
                                            'awaiting_confirmation' => 'blue',
                                            'paid' => 'green',
                                            'cancelled' => 'red'
                                        ];
                                        $status_text = [
                                            'pending' => 'Pendente',
                                            'awaiting_confirmation' => 'Aguardando',
                                            'paid' => 'Pago',
                                            'cancelled' => 'Cancelado'
                                        ];
                                        $color = $status_colors[$transaction['payment_status']] ?? 'gray';
                                        $text = $status_text[$transaction['payment_status']] ?? 'Desconhecido';
                                        ?>
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-<?= $color ?>-100 text-<?= $color ?>-800">
                                            <?= $text ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="mt-4 text-right">
                    <a href="payments.php" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                        Ver todas as transações <i class="fas fa-arrow-right ml-1"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
