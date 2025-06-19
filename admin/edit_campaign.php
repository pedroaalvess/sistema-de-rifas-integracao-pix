<?php
require_once '../config.php';
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// Validate campaign ID
if (!isset($_GET['id'])) {
    header('Location: list_campaigns.php');
    exit;
}

$campaign_id = (int)$_GET['id'];

// Fetch campaign details
$stmt = $mysqli->prepare("
    SELECT * FROM campaigns WHERE id = ?
");
$stmt->bind_param('i', $campaign_id);
$stmt->execute();
$campaign = $stmt->get_result()->fetch_assoc();

if (!$campaign) {
    header('Location: list_campaigns.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate and sanitize inputs
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $image_url = trim($_POST['image_url'] ?? '');
    $unit_price = filter_var($_POST['unit_price'] ?? 0, FILTER_VALIDATE_FLOAT);
    $draw_date = $_POST['draw_date'] ?? '';
    $status = $_POST['status'] ?? 'active';
    
    // Validate combo prices
    $combo_prices = [];
    $combo_types = $_POST['combo_type'] ?? [];
    $combo_values = $_POST['combo_value'] ?? [];
    
    for ($i = 0; $i < count($combo_types); $i++) {
        if (!empty($combo_types[$i]) && isset($combo_values[$i])) {
            $combo_prices[$combo_types[$i]] = (float)$combo_values[$i];
        }
    }

    // Validation
    $errors = [];
    if (empty($title)) $errors[] = "O título é obrigatório";
    if (empty($description)) $errors[] = "A descrição é obrigatória";
    if (empty($image_url)) $errors[] = "A URL da imagem é obrigatória";
    if ($unit_price <= 0) $errors[] = "O preço unitário deve ser maior que zero";
    if (empty($draw_date)) $errors[] = "A data do sorteio é obrigatória";

    if (empty($errors)) {
        try {
            // Update campaign in database
            $stmt = $mysqli->prepare("
                UPDATE campaigns 
                SET title = ?, description = ?, image_url = ?, 
                    unit_price = ?, combo_prices = ?, draw_date = ?, 
                    status = ?
                WHERE id = ?
            ");

            $combo_prices_json = json_encode($combo_prices);
            $stmt->bind_param(
                'sssdssssi',
                $title,
                $description,
                $image_url,
                $unit_price,
                $combo_prices_json,
                $draw_date,
                $status,
                $campaign_id
            );

            if ($stmt->execute()) {
                $success = "Campanha atualizada com sucesso!";
                // Refresh campaign data
                $stmt = $mysqli->prepare("SELECT * FROM campaigns WHERE id = ?");
                $stmt->bind_param('i', $campaign_id);
                $stmt->execute();
                $campaign = $stmt->get_result()->fetch_assoc();
            } else {
                $error = "Erro ao atualizar campanha: " . $mysqli->error;
            }
        } catch (Exception $e) {
            $error = "Erro ao atualizar campanha: " . $e->getMessage();
        }
    } else {
        $error = implode("<br>", $errors);
    }
}

// Decode combo prices for display
$combo_prices = json_decode($campaign['combo_prices'], true) ?? [];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Campanha - Sistema de Rifas</title>
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
                        <a href="list_campaigns.php" class="text-gray-600 hover:text-gray-900">
                            <i class="fas fa-arrow-left mr-2"></i> Voltar para Lista
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <div class="bg-white shadow-sm rounded-lg">
            <div class="p-6">
                <h1 class="text-2xl font-bold text-gray-900 mb-6">Editar Campanha</h1>

                <?php if ($error): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                        <?= $error ?>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                        <?= $success ?>
                    </div>
                <?php endif; ?>

                <form method="POST" class="space-y-6">
                    <!-- Basic Information -->
                    <div class="grid grid-cols-1 gap-6">
                        <div>
                            <label for="title" class="block text-sm font-medium text-gray-700">
                                Título da Campanha
                            </label>
                            <input type="text" id="title" name="title" required
                                   value="<?= htmlspecialchars($campaign['title']) ?>"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>

                        <div>
                            <label for="description" class="block text-sm font-medium text-gray-700">
                                Descrição
                            </label>
                            <textarea id="description" name="description" rows="4" required
                                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"><?= htmlspecialchars($campaign['description']) ?></textarea>
                        </div>

                        <div>
                            <label for="image_url" class="block text-sm font-medium text-gray-700">
                                URL da Imagem
                            </label>
                            <input type="url" id="image_url" name="image_url" required
                                   value="<?= htmlspecialchars($campaign['image_url']) ?>"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <?php if ($campaign['image_url']): ?>
                                <img src="<?= htmlspecialchars($campaign['image_url']) ?>" 
                                     alt="Preview" 
                                     class="mt-2 h-32 w-auto object-cover rounded">
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Pricing -->
                    <div class="border-t pt-6">
                        <h2 class="text-lg font-medium text-gray-900 mb-4">Preços</h2>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="unit_price" class="block text-sm font-medium text-gray-700">
                                    Preço Unitário (R$)
                                </label>
                                <input type="number" id="unit_price" name="unit_price" required
                                       min="0.01" step="0.01"
                                       value="<?= htmlspecialchars($campaign['unit_price']) ?>"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            </div>

                            <div>
                                <label for="draw_date" class="block text-sm font-medium text-gray-700">
                                    Data do Sorteio
                                </label>
                                <input type="datetime-local" id="draw_date" name="draw_date" required
                                       value="<?= date('Y-m-d\TH:i', strtotime($campaign['draw_date'])) ?>"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            </div>
                        </div>
                    </div>

                    <!-- Status -->
                    <div class="border-t pt-6">
                        <h2 class="text-lg font-medium text-gray-900 mb-4">Status da Campanha</h2>
                        
                        <div class="flex items-center space-x-4">
                            <label class="inline-flex items-center">
                                <input type="radio" name="status" value="active" 
                                       <?= $campaign['status'] === 'active' ? 'checked' : '' ?>
                                       class="form-radio text-blue-600">
                                <span class="ml-2">Ativa</span>
                            </label>
                            <label class="inline-flex items-center">
                                <input type="radio" name="status" value="inactive"
                                       <?= $campaign['status'] === 'inactive' ? 'checked' : '' ?>
                                       class="form-radio text-yellow-600">
                                <span class="ml-2">Inativa</span>
                            </label>
                            <label class="inline-flex items-center">
                                <input type="radio" name="status" value="drawn"
                                       <?= $campaign['status'] === 'drawn' ? 'checked' : '' ?>
                                       class="form-radio text-green-600">
                                <span class="ml-2">Sorteada</span>
                            </label>
                        </div>
                    </div>

                    <!-- Combo Prices -->
                    <div class="border-t pt-6">
                        <h2 class="text-lg font-medium text-gray-900 mb-4">Preços dos Combos</h2>
                        
                        <div id="comboPrices" class="space-y-4">
                            <?php foreach ($combo_prices as $type => $price): ?>
                                <div class="flex items-center gap-4">
                                    <div class="flex-1">
                                        <label class="block text-sm font-medium text-gray-700">Tipo do Combo</label>
                                        <input type="text" name="combo_type[]" value="<?= htmlspecialchars($type) ?>"
                                               placeholder="+70, +100, etc"
                                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    </div>
                                    <div class="flex-1">
                                        <label class="block text-sm font-medium text-gray-700">Valor (R$)</label>
                                        <input type="number" name="combo_value[]" value="<?= htmlspecialchars($price) ?>"
                                               min="0.01" step="0.01"
                                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    </div>
                                    <button type="button" onclick="this.parentElement.remove()"
                                            class="mt-6 text-red-600 hover:text-red-800">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <button type="button" onclick="addComboPrice()"
                                class="mt-4 inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            <i class="fas fa-plus mr-2"></i> Adicionar Combo
                        </button>
                    </div>

                    <!-- Submit Button -->
                    <div class="border-t pt-6">
                        <button type="submit"
                                class="w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                            Salvar Alterações
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function addComboPrice() {
            const container = document.getElementById('comboPrices');
            const div = document.createElement('div');
            div.className = 'flex items-center gap-4';
            div.innerHTML = `
                <div class="flex-1">
                    <label class="block text-sm font-medium text-gray-700">Tipo do Combo</label>
                    <input type="text" name="combo_type[]" placeholder="+70, +100, etc"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
                <div class="flex-1">
                    <label class="block text-sm font-medium text-gray-700">Valor (R$)</label>
                    <input type="number" name="combo_value[]" min="0.01" step="0.01"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
                <button type="button" onclick="this.parentElement.remove()"
                        class="mt-6 text-red-600 hover:text-red-800">
                    <i class="fas fa-trash"></i>
                </button>
            `;
            container.appendChild(div);
        }

        // Add initial combo price field if none exist
        document.addEventListener('DOMContentLoaded', () => {
            const container = document.getElementById('comboPrices');
            if (container.children.length === 0) {
                addComboPrice();
            }
        });
    </script>
</body>
</html>
