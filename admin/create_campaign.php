<?php
require_once '../config.php';
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
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
    if (strtotime($draw_date) < strtotime('today')) $errors[] = "A data do sorteio deve ser futura";

    if (empty($errors)) {
        try {
            // Insert campaign into database
            $stmt = $mysqli->prepare("
                INSERT INTO campaigns (
                    title, description, image_url, unit_price, 
                    combo_prices, draw_date, status
                ) VALUES (?, ?, ?, ?, ?, ?, 'active')
            ");

            $combo_prices_json = json_encode($combo_prices);
            $stmt->bind_param(
                'sssdss',
                $title,
                $description,
                $image_url,
                $unit_price,
                $combo_prices_json,
                $draw_date
            );

            if ($stmt->execute()) {
                $success = "Campanha criada com sucesso!";
                // Clear form data
                $_POST = [];
            } else {
                $error = "Erro ao criar campanha: " . $mysqli->error;
            }
        } catch (Exception $e) {
            $error = "Erro ao criar campanha: " . $e->getMessage();
        }
    } else {
        $error = implode("<br>", $errors);
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Criar Nova Campanha - Sistema de Rifas</title>
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
        <div class="bg-white shadow-sm rounded-lg">
            <div class="p-6">
                <h1 class="text-2xl font-bold text-gray-900 mb-6">Criar Nova Campanha</h1>

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
                                   value="<?= htmlspecialchars($_POST['title'] ?? '') ?>"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>

                        <div>
                            <label for="description" class="block text-sm font-medium text-gray-700">
                                Descrição
                            </label>
                            <textarea id="description" name="description" rows="4" required
                                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                        </div>

                        <div>
                            <label for="image_url" class="block text-sm font-medium text-gray-700">
                                URL da Imagem
                            </label>
                            <input type="url" id="image_url" name="image_url" required
                                   value="<?= htmlspecialchars($_POST['image_url'] ?? '') ?>"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
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
                                       value="<?= htmlspecialchars($_POST['unit_price'] ?? '') ?>"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            </div>

                            <div>
                                <label for="draw_date" class="block text-sm font-medium text-gray-700">
                                    Data do Sorteio
                                </label>
                                <input type="datetime-local" id="draw_date" name="draw_date" required
                                       value="<?= htmlspecialchars($_POST['draw_date'] ?? '') ?>"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            </div>
                        </div>
                    </div>

                    <!-- Combo Prices -->
                    <div class="border-t pt-6">
                        <h2 class="text-lg font-medium text-gray-900 mb-4">Preços dos Combos</h2>
                        
                        <div id="comboPrices" class="space-y-4">
                            <!-- Combo price inputs will be added here -->
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
                            Criar Campanha
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        let comboCount = 0;

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
            comboCount++;
        }

        // Add initial combo price field
        document.addEventListener('DOMContentLoaded', () => {
            addComboPrice();
        });
    </script>
</body>
</html>
