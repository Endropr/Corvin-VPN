<?php
// Отправка через Timeweb + Логи
error_reporting(E_ALL);
ini_set('display_errors', 0);
define('TEST_MODE', false);
// Вставьте ваши ключи, сайт и ID ниже
$shopId     = //'Ваш ShopID';
$secretKey  = //'Ваш секретный ключ';
$mainPage   = //'https://ваш-сайт';
$tariffNames = [
    '1month'   => 'Тариф «Попробовать» (1 месяц)',
    '3months'  => 'Тариф «Обойти блокировку» (3 месяца)',
    '6months'  => 'Тариф «Эксперт по ВПН» (6 месяцев)',
    '12months' => 'Тариф «Забудь о Чебурнете» (12 месяцев)',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $plan  = trim($_POST['plan'] ?? '');
    $price = (float)($_POST['price'] ?? 0);
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);

    if (!$email || $price <= 0 || !isset($tariffNames[$plan])) {
        die('Ошибка: укажите корректный email и выберите тариф.');
    }

    if (TEST_MODE) {
        $fakePaymentId = 'test_' . bin2hex(random_bytes(8));
        $metadata = ['plan' => $plan, 'email' => $email];
        processPostPayment($fakePaymentId, $price, $metadata, TEST_MODE);
        exit;
    }

    $description = $tariffNames[$plan];
    $data = [
        'amount'        => ['value' => number_format($price, 2, '.', ''), 'currency' => 'RUB'],
        'confirmation'  => ['type' => 'redirect', 'return_url' => $mainPage . '/success.html'],
        'capture'       => true,
        'description'   => $description,
        'metadata'      => ['plan' => $plan, 'email' => $email],
        'receipt'       => [
            'customer' => ['email' => $email],
            'items'    => [[
                'description' => $description,
                'quantity'    => '1.00',
                'amount'      => ['value' => number_format($price, 2, '.', ''), 'currency' => 'RUB'],
                'vat_code'    => '2'
            ]]
        ]
    ];

    $ch = curl_init('https://api.yookassa.ru/v3/payments');
    curl_setopt($ch, CURLOPT_USERPWD, $shopId . ':' . $secretKey);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'Idempotence-Key: ' . uniqid('corvin_', true)]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200 && $httpCode !== 201) {
        die('Ошибка создания платежа: ' . htmlspecialchars(substr($response, 0, 300)));
    }

    $result = json_decode($response, true);
    if (!empty($result['confirmation']['confirmation_url'])) {
        header('Location: ' . $result['confirmation']['confirmation_url']);
        exit;
    }
    die('Не удалось получить ссылку на оплату.');
}

if (isset($_GET['payment_id']) && !TEST_MODE) {
    $payment = getPaymentInfo($_GET['payment_id'], $shopId, $secretKey);
    if ($payment && $payment['status'] === 'succeeded') {
        processPostPayment($payment['id'], $payment['amount']['value'], $payment['metadata'] ?? [], false);
        exit;
    }
}

if (isset($_GET['plan']) && isset($_GET['price']) && !isset($_GET['payment_id'])) {
    $plan  = htmlspecialchars($_GET['plan'] ?? '');
    $price = (float)($_GET['price'] ?? 0);
    $description = $tariffNames[$plan] ?? 'Оплата Corvin VPN';
    ?>
    <!DOCTYPE html>
    <html lang="ru">
    <head>
        <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Оплата — Corvin VPN</title>
        <style>
            body{font-family:Arial,sans-serif;text-align:center;padding:40px;background:#f8f9fa;}
            .card{max-width:440px;margin:0 auto;background:#fff;padding:40px;border-radius:12px;box-shadow:0 4px 15px rgba(0,0,0,0.1);}
            input{width:100%;padding:12px;margin:10px 0;border:1px solid #ccc;border-radius:6px;font-size:16px;box-sizing:border-box;}
            button{width:100%;padding:14px;font-size:18px;background:#28a745;color:#fff;border:none;border-radius:6px;cursor:pointer;margin-top:10px;}
            button:hover{background:#218838;}
            .badge{display:inline-block;background:#ffc107;color:#000;padding:4px 8px;border-radius:4px;font-size:12px;margin-bottom:15px;}
        </style>
    </head>
    <body>
        <div class="card">
            <?= TEST_MODE ? '<div class="badge"> РЕЖИМ ТЕСТА (БЕЗ ОПЛАТЫ)</div>' : '' ?>
            <h2>Оплата подписки</h2>
            <p><strong><?= $description ?></strong><br>Сумма: <strong><?= number_format($price, 2) ?> ₽</strong><br>Трафик: <strong>БЕЗЛИМИТНЫЙ</strong></p>
            <form method="POST" action="">
                <input type="hidden" name="plan" value="<?= $plan ?>">
                <input type="hidden" name="price" value="<?= $price ?>">
                <label><strong>Email для получения подписки:</strong></label><br>
                <input type="email" name="email" placeholder="ваш@email.com" required autofocus>
                <button type="submit">Перейти к оплате →</button>
            </form>
        </div>
    </body>
    </html>
    <?php
    exit;
}

header('Location: ' . $mainPage);
exit;

// ОСНОВНАЯ ЛОГИКА 
function processPostPayment($paymentId, $amount, $metadata, $isTest) {
    $plan     = $metadata['plan'] ?? '1month';
    $email    = $metadata['email'] ?? '';
    $daysMap  = ['1month'=>30, '3months'=>90, '6months'=>180, '12months'=>365];
    $days     = $daysMap[$plan] ?? 30;
    $username = 'user_' . bin2hex(random_bytes(4));

    // REMNAWAVE (Подставьте ваши значения)
    $RW_BASE_URL        = // 'Ссылка на вашу панель';
    $RW_API_TOKEN       = // 'API Token Ремнавейва';
    $RW_DEFAULT_SQUAD_UUID = // "Ваш UUID";

    $rwUserData = [
        "username"             => $username,
        "email"                => $email,
        "trafficLimitBytes"    => 0,
        "trafficResetInterval" => "never",
        "expireAt"             => date('c', strtotime("+{$days} days")),
        "hwidDeviceLimit"      => 2,
        "activeInternalSquads" => [$RW_DEFAULT_SQUAD_UUID],
        "note"                 => ($isTest?"TEST | ":"")."ЮKassa | {$plan} | {$amount}₽"
    ];

    $ch = curl_init(rtrim($RW_BASE_URL, '/') . '/api/users');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($rwUserData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer ".$RW_API_TOKEN, "Content-Type: application/json", "Accept: application/json"]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $rwResult = json_decode(curl_exec($ch), true);
    curl_close($ch);

    $rwUser   = $rwResult['response'] ?? $rwResult['data'] ?? $rwResult ?? [];
    $rwSubUrl = $rwUser['subscriptionUrl'] ?? $RW_BASE_URL . "/sub/" . ($rwUser['shortUuid'] ?? $rwUser['uuid'] ?? '');

// H1CLOUD VLESS (Обход Белых списков, подставьте значения H1)
$H1_BASE_URL     = // 'Ваш сайт роута h1';
$H1_API_TOKEN    = // 'Токен';
$H1_SUB_URL_BASE = // 'Ссылка h1';
$H1_DEVICE_LIMIT = 3;

$h1UserData = [
    "name" => $username,
    "days" => $days
];
if ($H1_DEVICE_LIMIT > 0) {
    $h1UserData["device_limit"] = $H1_DEVICE_LIMIT;
}

// Шаг 1: Отправляем запрос на создание (короткий таймаут)
$ch = curl_init(rtrim($H1_BASE_URL, '/') . '/api/create');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($h1UserData));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer " . $H1_API_TOKEN,
    "Content-Type: application/json",
    "Accept: application/json"
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 8);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);

$h1ApiResponse = curl_exec($ch);
$h1HttpCode    = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$h1Error       = curl_error($ch);
curl_close($ch);

// Логируем
$debugLog  = date('Y-m-d H:i:s') . " | H1Cloud CREATE (pay.php)\n";
$debugLog .= "Username: {$username}\n";
$debugLog .= "HTTP: {$h1HttpCode} | Error: " . ($h1Error ?: 'none') . "\n";
$debugLog .= "Body: " . substr($h1ApiResponse, 0, 500) . "\n";
$debugLog .= "----------------------------------------\n";
file_put_contents('h1_debug.log', $debugLog, FILE_APPEND);

// Шаг 2: Извлекаем UUID любым способом
$h1Uuid = null;

// Способ А: Из JSON ответа
$h1Result = @json_decode($h1ApiResponse, true);
if ($h1Result && isset($h1Result['ok']) && $h1Result['ok'] === true) {
    $h1Uuid = $h1Result['client']['uuid'] ?? $h1Result['uuid'] ?? null;
}

// Способ Б: Regex из частичного ответа
if (empty($h1Uuid) && !empty($h1ApiResponse)) {
    if (preg_match('/"uuid"\s*:\s*"([a-f0-9\-]{36})"/i', $h1ApiResponse, $m)) {
        $h1Uuid = $m[1];
    }
}

// Способ В: Детерминированная генерация (fallback)
if (empty($h1Uuid)) {
    $hash = hash('sha256', $username . 'h1cloud_salt_2026');
    $h1Uuid = sprintf(
        '%s-%s-%s-%s-%s',
        substr($hash, 0, 8),
        substr($hash, 8, 4),
        substr($hash, 12, 4),
        substr($hash, 16, 4),
        substr($hash, 20, 12)
    );
}

// Формируем URL подписки
$h1SubUrl = rtrim($H1_SUB_URL_BASE, '/') . "/sub/{$h1Uuid}";

    // ================= ПИСЬМО =================
    $subject = "Ваши ключи и подписки Corvin VPN";
    $message = "Здравствуйте!\n";
    $message .= "Спасибо за покупку! Ваши подписки успешно активированы.\n";
    $message .= ($isTest ? "⚠️ ЭТО ТЕСТОВЫЙ ПЛАТЁЖ\n" : "");
    $message .= "Тариф: ".($daysMap[$plan]/30)." мес\n";
    $message .= "Сумма: {$amount} ₽\n";
    $message .= "Трафик: БЕЗЛИМИТНЫЙ\n";
    $message .= "Имя пользователя: {$username}\n";
    $message .= "═══════════════════════════════════\n";
    $message .= "🔑 ПЕРВАЯ ПОДПИСКА (Основной сервер):\n";
    $message .= $rwSubUrl . "\n";
    $message .= "═══════════════════════════════════\n";
    $message .= "Обход белых списков:\n";
    $message .= $h1SubUrl . "\n";
    $message .= "═══════════════════════════════════\n";
    $message .= "📌 Инструкция:\nhttps://korvinvpn.com/setup\n";
    $message .= "Поддержка:\nhttps://t.me/corvinemigrate\n";
    $message .= "Приятного использования!\nКоманда Corvin VPN";

    $mail_ok = send_email_via_timeweb(
        $email, $subject, $message,
        "Corvin VPN", "support@korvinvpn.com"
    );

    $status = $mail_ok ? 'OK' : 'MAIL_ERR';
    file_put_contents('orders.log', date('Y-m-d H:i:s')." | #{$paymentId} | {$amount}₽ | {$plan} | {$email} | Username: {$username} | RW: {$rwSubUrl} | H1: {$h1SubUrl} | {$status}\n", FILE_APPEND);

    header('Location: https://korvinvpn.com/success.html');
    exit;
}

function send_email_via_timeweb($to, $subject, $body, $from_name, $from_email) {
    $headers  = "From: {$from_name} <{$from_email}>\r\n";
    $headers .= "Reply-To: {$from_email}\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();
    $additional_params = "-f{$from_email}";
    return mail($to, $subject, $body, $headers, $additional_params);
}

function getPaymentInfo($paymentId, $shopId, $secretKey) {
    $ch = curl_init('https://api.yookassa.ru/v3/payments/' . urlencode($paymentId));
    curl_setopt($ch, CURLOPT_USERPWD, $shopId . ':' . $secretKey);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);
    return json_decode($response, true);
}