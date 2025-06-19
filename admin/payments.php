<?php
require_once '../config.php';
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// Handle filters
$status_filter = $_GET['status'] ?? 'all';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$campaign_id = $_GET['campaign_id'] ?? '';

// Build query conditions
$conditions = [];
$params = [];
$types = '';

if ($status_filter !== 'all') {
    $conditions[] = 'p.payment_status = ?';
    $params[] = $status_filter;
    $types .= 's';
}

if ($date_from) {
    $conditions[] = 'DATE(p.created_at) >= ?';
    $params[] = $date_from;
    $types .= 's';
}

if ($date_to) {
    $conditions[] = 'DATE(p.created_at) <= ?';
    $params[] = $date_to;
    $types .= 's';
}

if ($campaign_id) {
    $conditions[] = 'p.campaign_id = ?';
    $params[] = $campaign_id;
    $types .= 'i';
}

// Fetch active campaigns for filter dropdown
$campaigns = $mysqli->query("SELECT id, title FROM campaigns ORDER BY title")->fetch_all(MYSQLI_ASSOC);

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Build the query
$query = "
    SELECT 
        p.*,
        b.name as buyer_name,
        b.cpf,
        b.email,
        c.title as campaign_title
    FROM payments p
    JOIN buyers b ON p.buyer_id = b.id
    JOIN campaigns c ON p.campaign_id = c.id
";

if (!empty($conditions)) {
    $query .= " WHERE " . implode(' AND ', $conditions);
}

// Get total count for pagination
$count_query = str_replace("SELECT p.*,", "SELECT COUNT(*) as count", $query);
$stmt = $mysqli->prepare($count_query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$total_payments = $stmt->get_result()->fetch_assoc()['count'];
$total_pages = ceil($total_payments / $per_page);

// Add pagination to main query
$query .= " ORDER BY p.created_at DESC LIMIT ? OFFSET ?";
$stmt = $mysqli->prepare($query);

if (!empty($params)) {
    $params[] = $per_page;
    $params[] = $offset;
    $types .= 'ii';
    $stmt->bind_param($types, ...$params);
} else {
    $stmt->bind_param('ii', $per_page, $offset);
}

$stmt->execute();
$payments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Calculate totals
$total_amount = 0;
$total_confirmed = 0;
foreach ($payments as $payment) {
    if ($payment['payment_status'] === 'paid') {
        $total_confirmed += $payment['amount'];
    }
    $total_amount += $payment['amount'];
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Pagamentos - Sistema de Rifas</title>
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
                        <a href="dashboard.php" class="text-gray-600 hover:text-gray-900">
                            <i class="fas fa-arrow-left mr-2"></i> Voltar ao Dashboard
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <!-- Filters -->
        <div class="bg-white shadow-sm rounded-lg mb-6">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900 mb-4">Filtros</h2>
                <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Status</label>
                        <select name="status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="all" <?= $status_filter === 'all' ? 'selected' : '' ?>>Todos</option>
                            <option value="pending" <?= $status_filter === 'pending' ? 'selected' : '' ?>>Pendente</option>
                            <option value="awaiting_confirmation" <?= $status_filter === 'awaiting_confirmation' ? 'selected' : '' ?>>Aguardando</option>
                            <option value="paid" <?= $status_filter === 'paid' ? 'selected' : '' ?>>Pago</option>
                            <option value="cancelled" <?= $status_filter === 'cancelled' ? 'selected' : '' ?>>Cancelado</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Campanha</label>
                        <select name="campaign_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">Todas</option>
                            <?php foreach ($campaigns as $c): ?>
                                <option value="<?= $c['id'] ?>" <?= $campaign_id == $c['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($c['title']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Data Inicial</label>
                        <input type="date" name="date_from" value="<?= htmlspecialchars($date_from) ?>"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Data Final</label>
                        <input type="date" name="date_to" value="<?= htmlspecialchars($date_to) ?>"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <div class="md:col-span-4">
                        <button type="submit" class="w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700">
                            Filtrar
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Summary -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h3 class="text-lg font-medium text-gray-900">Total de Pagamentos</h3>
                <p class="mt-2 text-3xl font-bold text-gray-900">
                    <?= $total_payments ?>
                </p>
            </div>
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h3 class="text-lg font-medium text-gray-900">Valor Total</h3>
                <p class="mt-2 text-3xl font-bold text-gray-900">
                    R$ <?= number_format($total_amount, 2, ',', '.') ?>
                </p>
            </div>
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h3 class="text-lg font-medium text-gray-900">Valor Confirmado</h3>
                <p class="mt-2 text-3xl font-bold text-green-600">
                    R$ <?= number_format($total_confirmed, 2, ',', '.') ?>
                </p>
            </div>
        </div>

        <!-- Payments Table -->
        <div class="bg-white shadow-sm rounded-lg">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900 mb-4">Pagamentos</h2>
                
                <?php if (empty($payments)): ?>
                    <p class="text-gray-500 text-center py-4">Nenhum pagamento encontrado.</p>
                <?php else: ?>
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
                                    <th class="px-6 py-3 bg-gray-50 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Ações
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($payments as $payment): ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?= date('d/m/Y H:i', strtotime($payment['created_at'])) ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?= htmlspecialchars($payment['campaign_title']) ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900">
                                                <?= htmlspecialchars($payment['buyer_name']) ?>
                                            </div>
                                            <div class="text-sm text-gray-500">
                                                CPF: <?= substr($payment['cpf'], 0, 3) . '.XXX.XXX-' . substr($payment['cpf'], -2) ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            R$ <?= number_format($payment['amount'], 2, ',', '.') ?>
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
                                            $color = $status_colors[$payment['payment_status']] ?? 'gray';
                                            $text = $status_text[$payment['payment_status']] ?? 'Desconhecido';
                                            ?>
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-<?= $color ?>-100 text-<?= $color ?>-800">
                                                <?= $text ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <button onclick="showPaymentDetails(<?= htmlspecialchars(json_encode($payment)) ?>)"
                                                    class="text-blue-600 hover:text-blue-900">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <div class="mt-4 flex justify-center">
                            <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                                <?php
                                $query_params = $_GET;
                                for ($i = 1; $i <= $total_pages; $i++):
                                    $query_params['page'] = $i;
                                    $query_string = http_build_query($query_params);
                                ?>
                                    <a href="?<?= $query_string ?>" 
                                       class="<?= $i === $page ? 'bg-blue-50 border-blue-500 text-blue-600' : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50' ?> relative inline-flex items-center px-4 py-2 border text-sm font-medium">
                                        <?= $i ?>
                                    </a>
                                <?php endfor; ?>
                            </nav>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Payment Details Modal -->
    <div id="paymentModal" class="fixed inset-0 bg-gray-500 bg-opacity-75 hidden">
        <div class="flex items-center justify-center min-h-screen">
            <div class="bg-white rounded-lg max-w-2xl w-full mx-4">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-medium text-gray-900">Detalhes do Pagamento</h3>
                        <button onclick="closeModal()" class="text-gray-400 hover:text-gray-500">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div id="paymentDetails" class="space-y-4">
                        <!-- Payment details will be inserted here -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function showPaymentDetails(payment) {
            const modal = document.getElementById('paymentModal');
            const details = document.getElementById('paymentDetails');
            
            const statusText = {
                'pending': 'Pendente',
                'awaiting_confirmation': 'Aguardando Confirmação',
                'paid': 'Pago',
                'cancelled': 'Cancelado'
            };

            details.innerHTML = `
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm font-medium text-gray-500">Campanha</p>
                        <p class="mt-1">${payment.campaign_title}</p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500">Status</p>
                        <p class="mt-1">${statusText[payment.payment_status]}</p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500">Comprador</p>
                        <p class="mt-1">${payment.buyer_name}</p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500">Email</p>
                        <p class="mt-1">${payment.email}</p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500">Valor</p>
                        <p class="mt-1">R$ ${parseFloat(payment.amount).toLocaleString('pt-BR', { minimumFractionDigits: 2 })}</p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500">Data/Hora</p>
                        <p class="mt-1">${new Date(payment.created_at).toLocaleString('pt-BR')}</p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500">Quantidade</p>
                        <p class="mt-1">${payment.quantity} números</p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500">Tipo</p>
                        <p class="mt-1">${payment.combo_type === 'unit' ? 'Individual' : 'Combo ' + payment.combo_type}</p>
                    </div>
                </div>
                ${payment.pix_code ? `
                    <div class="mt-4">
                        <p class="text-sm font-medium text-gray-500">Código PIX</p>
                        <div class="mt-1 bg-gray-50 p-2 rounded">
                            <code class="text-sm">${payment.pix_code}</code>
                        </div>
                    </div>
                ` : ''}
            `;
            
            modal.classList.remove('hidden');
        }

        function closeModal() {
            document.getElementById('paymentModal').classList.add('hidden');
        }

        // Close modal when clicking outside
        document.getElementById('paymentModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });
    </script>
</body>
</html>
