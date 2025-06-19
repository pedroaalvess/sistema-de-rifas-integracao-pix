<?php
require_once 'config.php';
require_once 'api/blackcat.php';

session_start();

// Validate incoming data
if (!isset($_POST['campaign_id'], $_POST['quantity'], $_POST['combo_type'])) {
    header('Location: index.php');
    exit;
}

$campaign_id = (int)$_POST['campaign_id'];
$quantity = (int)$_POST['quantity'];
$combo_type = $_POST['combo_type'];

// Fetch campaign details
$stmt = $mysqli->prepare("SELECT title, unit_price, combo_prices FROM campaigns WHERE id = ? AND status = 'active'");
$stmt->bind_param('i', $campaign_id);
$stmt->execute();
$result = $stmt->get_result();
$campaign = $result->fetch_assoc();

if (!$campaign) {
    header('Location: index.php');
    exit;
}

// Calculate total amount based on quantity and combo type
$total_amount = 0;
if ($combo_type === 'unit') {
    $total_amount = $campaign['unit_price'] * $quantity;
} else {
    $combo_prices = json_decode($campaign['combo_prices'], true);
    if (isset($combo_prices[$combo_type])) {
        $total_amount = $combo_prices[$combo_type] * $quantity;
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
    $errors = [];
    
    // Validate inputs
    $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
    $cpf = preg_replace('/[^0-9]/', '', $_POST['cpf']);
    $cellphone = preg_replace('/[^0-9]/', '', $_POST['cellphone']);
    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    $address = filter_input(INPUT_POST, 'address', FILTER_SANITIZE_STRING);

    if (empty($name)) $errors[] = "Nome é obrigatório";
    if (strlen($cpf) !== 11) $errors[] = "CPF inválido";
    if (strlen($cellphone) < 10) $errors[] = "Celular inválido";
    if (!$email) $errors[] = "Email inválido";

    if (empty($errors)) {
        // Start transaction
        $mysqli->begin_transaction();

        try {
            // Insert buyer
            $stmt = $mysqli->prepare("INSERT INTO buyers (name, cpf, cellphone, email, address) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param('sssss', $name, $cpf, $cellphone, $email, $address);
            $stmt->execute();
            $buyer_id = $mysqli->insert_id;

            // Create BlackCat PIX payment
            $blackcat = new BlackCatAPI();
            $payment_data = $blackcat->createPixPayment($total_amount, [
                'campaign_id' => $campaign_id,
                'buyer_id' => $buyer_id,
                'quantity' => $quantity,
                'combo_type' => $combo_type
            ]);

            if (!$payment_data) {
                throw new Exception("Erro ao gerar pagamento PIX");
            }

            // Insert payment record
            $stmt = $mysqli->prepare("
                INSERT INTO payments (
                    buyer_id, campaign_id, amount, quantity, combo_type,
                    pix_code, qr_code_url, expires_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->bind_param(
                'iidiisss',
                $buyer_id,
                $campaign_id,
                $total_amount,
                $quantity,
                $combo_type,
                $payment_data['pix_code'],
                $payment_data['qr_code_url'],
                $payment_data['expires_at']
            );
            $stmt->execute();
            $payment_id = $mysqli->insert_id;

            // Reserve raffle numbers
            $base_number = 1; // You might want to calculate this based on existing numbers
            for ($i = 0; $i < $quantity; $i++) {
                $number = $base_number + $i;
                $stmt = $mysqli->prepare("
                    INSERT INTO raffle_numbers (payment_id, campaign_id, number, status)
                    VALUES (?, ?, ?, 'reserved')
                ");
                $stmt->bind_param('iii', $payment_id, $campaign_id, $number);
                $stmt->execute();
            }

            $mysqli->commit();

            // Redirect to payment page
            $_SESSION['payment_id'] = $payment_id;
            header('Location: payment.php');
            exit;

        } catch (Exception $e) {
            $mysqli->rollback();
            $errors[] = "Erro ao processar pagamento: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - <?= htmlspecialchars($campaign['title']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="bg-gray-100 min-h-screen">
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-2xl mx-auto bg-white rounded-lg shadow-md p-6">
            <h1 class="text-2xl font-bold mb-6">Finalizar Compra</h1>
            
            <?php if (!empty($errors)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?= htmlspecialchars($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <div class="mb-6 p-4 bg-gray-50 rounded">
                <h2 class="font-semibold mb-2">Resumo da Compra</h2>
                <p>Campanha: <?= htmlspecialchars($campaign['title']) ?></p>
                <p>Quantidade: <?= $quantity ?></p>
                <p>Tipo: <?= $combo_type === 'unit' ? 'Unitário' : $combo_type ?></p>
                <p class="font-bold mt-2">Total: R$ <?= number_format($total_amount, 2, ',', '.') ?></p>
            </div>

            <form method="POST" class="space-y-4">
                <input type="hidden" name="campaign_id" value="<?= $campaign_id ?>">
                <input type="hidden" name="quantity" value="<?= $quantity ?>">
                <input type="hidden" name="combo_type" value="<?= $combo_type ?>">

                <div>
                    <label class="block text-sm font-medium text-gray-700">Nome Completo</label>
                    <input type="text" name="name" required
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">CPF</label>
                    <input type="text" name="cpf" required pattern="\d{3}\.?\d{3}\.?\d{3}-?\d{2}"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Celular</label>
                    <input type="tel" name="cellphone" required
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">E-mail</label>
                    <input type="email" name="email" required
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Endereço (opcional)</label>
                    <textarea name="address"
                              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
                </div>

                <button type="submit" name="submit"
                        class="w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                    Gerar Pagamento PIX
                </button>
            </form>
        </div>
    </div>

    <script>
        // Format CPF input
        document.querySelector('input[name="cpf"]').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length <= 11) {
                value = value.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4');
                e.target.value = value;
            }
        });

        // Format phone input
        document.querySelector('input[name="cellphone"]').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length <= 11) {
                value = value.replace(/(\d{2})(\d{5})(\d{4})/, '($1) $2-$3');
                e.target.value = value;
            }
        });
    </script>
</body>
</html>
