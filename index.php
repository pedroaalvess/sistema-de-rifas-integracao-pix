<?php
require_once 'config.php';

// Fetch all active campaigns
$stmt = $pdo->prepare("
    SELECT id, title, description, image_url, unit_price, combo_prices, draw_date 
    FROM campaigns 
    WHERE status = 'active' 
    ORDER BY draw_date ASC
");
$stmt->execute();
$campaigns = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Rifas - Campanhas Ativas</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
        .campaign-card:hover {
            transform: translateY(-5px);
            transition: transform 0.3s ease;
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen">
    <!-- Header -->
    <header class="bg-white shadow-sm">
        <div class="container mx-auto px-4 py-6">
            <div class="flex justify-between items-center">
                <div class="flex items-center">
                    <img src="assets/logos/logo.png" alt="Logo" class="h-12">
                    <h1 class="ml-4 text-2xl font-bold text-gray-800">Sistema de Rifas</h1>
                </div>
                <nav>
                    <a href="#" class="text-gray-600 hover:text-gray-900">Como Funciona</a>
                    <a href="#" class="ml-6 text-gray-600 hover:text-gray-900">Ganhadores</a>
                    <a href="#" class="ml-6 text-gray-600 hover:text-gray-900">Contato</a>
                </nav>
            </div>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="bg-gradient-to-r from-blue-600 to-blue-800 text-white py-16">
        <div class="container mx-auto px-4 text-center">
            <h2 class="text-4xl font-bold mb-4">Campanhas Ativas</h2>
            <p class="text-xl opacity-90">Escolha sua sorte e concorra a prêmios incríveis!</p>
        </div>
    </section>

    <!-- Campaigns Grid -->
    <section class="container mx-auto px-4 py-12">
        <?php if (empty($campaigns)): ?>
            <div class="text-center py-12">
                <h3 class="text-2xl text-gray-600">Nenhuma campanha ativa no momento.</h3>
                <p class="mt-2 text-gray-500">Volte em breve para novas oportunidades!</p>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <?php foreach ($campaigns as $campaign): ?>
                    <div class="campaign-card bg-white rounded-lg shadow-md overflow-hidden">
                        <!-- Campaign Image -->
                        <img src="<?= htmlspecialchars($campaign['image_url']) ?>" 
                             alt="<?= htmlspecialchars($campaign['title']) ?>"
                             class="w-full h-48 object-cover">
                        
                        <!-- Campaign Details -->
                        <div class="p-6">
                            <h3 class="text-xl font-semibold mb-2">
                                <?= htmlspecialchars($campaign['title']) ?>
                            </h3>
                            
                            <p class="text-gray-600 mb-4">
                                <?= nl2br(htmlspecialchars(substr($campaign['description'], 0, 100))) ?>...
                            </p>
                            
                            <!-- Price and Date Info -->
                            <div class="mb-4">
                                <p class="text-green-600 font-semibold">
                                    A partir de R$ <?= number_format($campaign['unit_price'], 2, ',', '.') ?>
                                </p>
                                <p class="text-sm text-gray-500">
                                    Sorteio: <?= date('d/m/Y', strtotime($campaign['draw_date'])) ?>
                                </p>
                            </div>

                            <!-- Combo Prices Preview -->
                            <?php 
                            $combos = json_decode($campaign['combo_prices'], true);
                            if ($combos): 
                            ?>
                            <div class="mb-4">
                                <p class="text-sm font-medium text-gray-700 mb-2">Combos disponíveis:</p>
                                <div class="flex flex-wrap gap-2">
                                    <?php 
                                    $counter = 0;
                                    foreach ($combos as $type => $price): 
                                        if ($counter++ < 3):  // Show only first 3 combos
                                    ?>
                                        <span class="inline-block bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded">
                                            <?= htmlspecialchars($type) ?>
                                        </span>
                                    <?php 
                                        endif;
                                    endforeach; 
                                    if (count($combos) > 3):
                                    ?>
                                        <span class="inline-block text-blue-600 text-xs px-2 py-1">
                                            +<?= count($combos) - 3 ?> combos
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endif; ?>

                            <!-- Action Button -->
                            <a href="campaign.php?id=<?= $campaign['id'] ?>" 
                               class="block w-full text-center bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 transition duration-200">
                                Ver Detalhes
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <!-- Footer -->
    <footer class="bg-gray-800 text-white py-12">
        <div class="container mx-auto px-4">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div>
                    <h4 class="text-lg font-semibold mb-4">Sobre Nós</h4>
                    <p class="text-gray-400">
                        Sistema de rifas online seguro e transparente. 
                        Realize seus sonhos participando de nossas campanhas.
                    </p>
                </div>
                <div>
                    <h4 class="text-lg font-semibold mb-4">Links Úteis</h4>
                    <ul class="space-y-2">
                        <li><a href="#" class="text-gray-400 hover:text-white">Regulamento</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white">Política de Privacidade</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white">Termos de Uso</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="text-lg font-semibold mb-4">Contato</h4>
                    <ul class="space-y-2">
                        <li class="text-gray-400">
                            <i class="fas fa-envelope mr-2"></i> contato@rifas.com
                        </li>
                        <li class="text-gray-400">
                            <i class="fas fa-phone mr-2"></i> (11) 99999-9999
                        </li>
                    </ul>
                </div>
            </div>
            <div class="mt-8 pt-8 border-t border-gray-700 text-center text-gray-400">
                <p>&copy; <?= date('Y') ?> Sistema de Rifas. Todos os direitos reservados.</p>
            </div>
        </div>
    </footer>
</body>
</html>
