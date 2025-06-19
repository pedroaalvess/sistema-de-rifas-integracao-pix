<?php
require_once '../config.php';
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// Handle campaign status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['campaign_id'])) {
    $campaign_id = (int)$_POST['campaign_id'];
    $action = $_POST['action'];
    
    switch ($action) {
        case 'activate':
            $status = 'active';
            break;
        case 'deactivate':
            $status = 'inactive';
            break;
        case 'mark_drawn':
            $status = 'drawn';
            break;
        default:
            $status = null;
    }
    
    if ($status) {
        $stmt = $mysqli->prepare("UPDATE campaigns SET status = ? WHERE id = ?");
        $stmt->bind_param('si', $status, $campaign_id);
        $stmt->execute();
    }
}

// Fetch campaigns with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Get total campaigns count
$total_result = $mysqli->query("SELECT COUNT(*) as count FROM campaigns");
$total_campaigns = $total_result->fetch_assoc()['count'];
$total_pages = ceil($total_campaigns / $per_page);

// Fetch campaigns for current page
$stmt = $mysqli->prepare("
    SELECT 
        c.*,
        (SELECT COUNT(*) FROM payments p WHERE p.campaign_id = c.id AND p.payment_status = 'paid') as total_sales,
        (SELECT SUM(amount) FROM payments p WHERE p.campaign_id = c.id AND p.payment_status = 'paid') as total_revenue
    FROM campaigns c
    ORDER BY c.created_at DESC
    LIMIT ? OFFSET ?
");
$stmt->bind_param('ii', $per_page, $offset);
$stmt->execute();
$campaigns = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Campanhas - Sistema de Rifas</title>
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
                <div class="flex items-center">
                    <a href="create_campaign.php" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                        <i class="fas fa-plus mr-2"></i> Nova Campanha
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <!-- Campaigns List -->
        <div class="bg-white shadow-sm rounded-lg overflow-hidden">
            <div class="p-6">
                <h1 class="text-2xl font-bold text-gray-900 mb-6">Gerenciar Campanhas</h1>

                <?php if (empty($campaigns)): ?>
                    <div class="text-center py-8">
                        <p class="text-gray-500">Nenhuma campanha encontrada.</p>
                    </div>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead>
                                <tr>
                                    <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Campanha
                                    </th>
                                    <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Status
                                    </th>
                                    <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Data do Sorteio
                                    </th>
                                    <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Vendas
                                    </th>
                                    <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Receita
                                    </th>
                                    <th class="px-6 py-3 bg-gray-50 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Ações
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($campaigns as $campaign): ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <div class="flex-shrink-0 h-10 w-10">
                                                    <img class="h-10 w-10 rounded-full object-cover" 
                                                         src="<?= htmlspecialchars($campaign['image_url']) ?>" 
                                                         alt="<?= htmlspecialchars($campaign['title']) ?>">
                                                </div>
                                                <div class="ml-4">
                                                    <div class="text-sm font-medium text-gray-900">
                                                        <?= htmlspecialchars($campaign['title']) ?>
                                                    </div>
                                                    <div class="text-sm text-gray-500">
                                                        Criada em <?= date('d/m/Y', strtotime($campaign['created_at'])) ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <?php
                                            $status_colors = [
                                                'active' => 'green',
                                                'inactive' => 'yellow',
                                                'drawn' => 'blue'
                                            ];
                                            $status_text = [
                                                'active' => 'Ativa',
                                                'inactive' => 'Inativa',
                                                'drawn' => 'Sorteada'
                                            ];
                                            $color = $status_colors[$campaign['status']] ?? 'gray';
                                            $text = $status_text[$campaign['status']] ?? 'Desconhecido';
                                            ?>
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-<?= $color ?>-100 text-<?= $color ?>-800">
                                                <?= $text ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?= date('d/m/Y H:i', strtotime($campaign['draw_date'])) ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?= $campaign['total_sales'] ?? 0 ?> vendas
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            R$ <?= number_format($campaign['total_revenue'] ?? 0, 2, ',', '.') ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <div class="flex justify-end space-x-2">
                                                <a href="edit_campaign.php?id=<?= $campaign['id'] ?>" 
                                                   class="text-blue-600 hover:text-blue-900">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                
                                                <form method="POST" class="inline-block">
                                                    <input type="hidden" name="campaign_id" value="<?= $campaign['id'] ?>">
                                                    <?php if ($campaign['status'] === 'active'): ?>
                                                        <button type="submit" name="action" value="deactivate"
                                                                class="text-yellow-600 hover:text-yellow-900">
                                                            <i class="fas fa-pause"></i>
                                                        </button>
                                                    <?php elseif ($campaign['status'] === 'inactive'): ?>
                                                        <button type="submit" name="action" value="activate"
                                                                class="text-green-600 hover:text-green-900">
                                                            <i class="fas fa-play"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                    
                                                    <?php if ($campaign['status'] !== 'drawn'): ?>
                                                        <button type="submit" name="action" value="mark_drawn"
                                                                class="text-purple-600 hover:text-purple-900 ml-2"
                                                                onclick="return confirm('Tem certeza que deseja marcar esta campanha como sorteada?')">
                                                            <i class="fas fa-trophy"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                </form>
                                            </div>
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
                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <a href="?page=<?= $i ?>" 
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
</body>
</html>
