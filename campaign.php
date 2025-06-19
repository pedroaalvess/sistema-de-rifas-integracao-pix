<?php
require_once 'config.php';
require_once 'api/blackcat.php';

// Validate campaign ID
if (!isset($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$campaign_id = (int)$_GET['id'];

// Fetch campaign details
$stmt = $mysqli->prepare("
    SELECT id, title, description, image_url, unit_price, combo_prices, draw_date, status
    FROM campaigns 
    WHERE id = ? AND status = 'active'
");
$stmt->bind_param('i', $campaign_id);
$stmt->execute();
$campaign = $stmt->get_result()->fetch_assoc();

if (!$campaign) {
    header('Location: index.php');
    exit;
}

// Decode combo prices
$combo_prices = json_decode($campaign['combo_prices'], true);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($campaign['title']) ?> - Sistema de Rifas</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body class="bg-gray-100 min-h-screen">
    <!-- Header -->
    <header class="bg-white shadow-sm">
        <div class="container mx-auto px-4 py-6">
            <div class="flex justify-between items-center">
                <a href="index.php" class="flex items-center">
                    <img src="assets/logos/logo.png" alt="Logo" class="h-12">
                    <h1 class="ml-4 text-2xl font-bold text-gray-800">Sistema de Rifas</h1>
                </a>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="container mx-auto px-4 py-8">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Campaign Image Section -->
            <div class="bg-white rounded-lg shadow-md p-4">
                <img src="<?= htmlspecialchars($campaign['image_url']) ?>" 
                     alt="<?= htmlspecialchars($campaign['title']) ?>"
                     class="w-full h-auto rounded-lg">
            </div>

            <!-- Campaign Details Section -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h1 class="text-3xl font-bold mb-4"><?= htmlspecialchars($campaign['title']) ?></h1>
                
                <div class="mb-6">
                    <p class="text-gray-700"><?= nl2br(htmlspecialchars($campaign['description'])) ?></p>
                </div>

                <div class="mb-6">
                    <p class="text-lg font-semibold">Data do Sorteio:</p>
                    <p class="text-gray-700"><?= date('d/m/Y', strtotime($campaign['draw_date'])) ?></p>
                </div>

                <!-- Pricing Options -->
                <form id="purchaseForm" action="checkout.php" method="POST" class="space-y-6">
                    <input type="hidden" name="campaign_id" value="<?= $campaign_id ?>">
                    
                    <div>
                        <h2 class="text-xl font-semibold mb-4">Escolha sua Quantidade</h2>
                        
                        <!-- Unit Price Option -->
                        <div class="mb-4">
                            <label class="flex items-center p-4 border rounded-lg cursor-pointer hover:bg-gray-50">
                                <input type="radio" name="combo_type" value="unit" 
                                       class="form-radio h-5 w-5 text-blue-600" checked>
                                <div class="ml-4 flex-1">
                                    <p class="font-medium">Número Individual</p>
                                    <p class="text-gray-600">R$ <?= number_format($campaign['unit_price'], 2, ',', '.') ?> cada</p>
                                </div>
                            </label>
                        </div>

                        <!-- Combo Options -->
                        <?php if ($combo_prices): ?>
                            <?php foreach ($combo_prices as $type => $price): ?>
                                <div class="mb-4">
                                    <label class="flex items-center p-4 border rounded-lg cursor-pointer hover:bg-gray-50">
                                        <input type="radio" name="combo_type" value="<?= htmlspecialchars($type) ?>" 
                                               class="form-radio h-5 w-5 text-blue-600">
                                        <div class="ml-4 flex-1">
                                            <p class="font-medium">Combo <?= htmlspecialchars($type) ?></p>
                                            <p class="text-gray-600">R$ <?= number_format($price, 2, ',', '.') ?> cada</p>
                                        </div>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>

                        <!-- Quantity Selection -->
                        <div class="mt-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Quantidade de números
                            </label>
                            <div class="flex items-center">
                                <button type="button" onclick="updateQuantity(-1)" 
                                        class="bg-gray-200 text-gray-600 px-4 py-2 rounded-l-md hover:bg-gray-300">
                                    -
                                </button>
                                <input type="number" name="quantity" id="quantity" value="1" min="1" max="999"
                                       class="w-20 text-center border-t border-b border-gray-300 py-2"
                                       readonly>
                                <button type="button" onclick="updateQuantity(1)"
                                        class="bg-gray-200 text-gray-600 px-4 py-2 rounded-r-md hover:bg-gray-300">
                                    +
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Total Price -->
                    <div class="border-t pt-4">
                        <div class="flex justify-between items-center mb-4">
                            <span class="text-lg font-semibold">Total:</span>
                            <span id="totalPrice" class="text-2xl font-bold text-green-600">
                                R$ <?= number_format($campaign['unit_price'], 2, ',', '.') ?>
                            </span>
                        </div>

                        <button type="submit" 
                                class="w-full bg-blue-600 text-white py-3 px-6 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                            Participar Agora
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Additional Information -->
        <div class="mt-8 bg-white rounded-lg shadow-md p-6">
            <h2 class="text-2xl font-bold mb-4">Informações Importantes</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="flex items-start">
                    <i class="fas fa-shield-alt text-blue-600 text-2xl mr-4 mt-1"></i>
                    <div>
                        <h3 class="font-semibold mb-2">Pagamento Seguro</h3>
                        <p class="text-gray-600">Utilizamos o sistema PIX para garantir sua segurança.</p>
                    </div>
                </div>
                <div class="flex items-start">
                    <i class="fas fa-clock text-blue-600 text-2xl mr-4 mt-1"></i>
                    <div>
                        <h3 class="font-semibold mb-2">Confirmação Rápida</h3>
                        <p class="text-gray-600">Seu número é confirmado assim que o pagamento for aprovado.</p>
                    </div>
                </div>
                <div class="flex items-start">
                    <i class="fas fa-trophy text-blue-600 text-2xl mr-4 mt-1"></i>
                    <div>
                        <h3 class="font-semibold mb-2">Sorteio Transparente</h3>
                        <p class="text-gray-600">Acompanhe o sorteio ao vivo em nossa plataforma.</p>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
        // Campaign pricing data
        const campaignData = {
            unitPrice: <?= $campaign['unit_price'] ?>,
            comboPrices: <?= json_encode($combo_prices) ?>
        };

        // Update quantity
        function updateQuantity(change) {
            const quantityInput = document.getElementById('quantity');
            let newQuantity = parseInt(quantityInput.value) + change;
            
            // Ensure quantity is between 1 and 999
            newQuantity = Math.max(1, Math.min(999, newQuantity));
            quantityInput.value = newQuantity;
            
            updateTotal();
        }

        // Update total price
        function updateTotal() {
            const quantity = parseInt(document.getElementById('quantity').value);
            const selectedCombo = document.querySelector('input[name="combo_type"]:checked').value;
            
            let pricePerUnit = campaignData.unitPrice;
            if (selectedCombo !== 'unit') {
                pricePerUnit = campaignData.comboPrices[selectedCombo];
            }
            
            const total = quantity * pricePerUnit;
            document.getElementById('totalPrice').textContent = 
                `R$ ${total.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
        }

        // Add event listeners
        document.querySelectorAll('input[name="combo_type"]').forEach(radio => {
            radio.addEventListener('change', updateTotal);
        });

        // Initialize total
        document.addEventListener('DOMContentLoaded', updateTotal);

        // Form validation
        document.getElementById('purchaseForm').addEventListener('submit', function(e) {
            const quantity = parseInt(document.getElementById('quantity').value);
            if (quantity < 1 || quantity > 999) {
                e.preventDefault();
                alert('Por favor, selecione uma quantidade válida (1-999).');
            }
        });
    </script>
</body>
</html>
