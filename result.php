<?php
// result.php — Webhook ЮKassa + Remnawave + H1Cloud + Отправка через Timeweb + Логи
$shopId     = // 'Ваш ShopID юкассы';
$secretKey  = // 'Ваш секретный ключ юкассы';

// Remnawave API 
$RW_BASE_URL        = // 'Ссылка на вашу панель';
$RW_API_TOKEN       = // 'Ваш токен';
$RW_DEFAULT_SQUAD_UUID = //"Ваш UUID";

// H1Cloud VLESS API 
$H1_BASE_URL         = // 'Ссылка на роут';
$H1_API_TOKEN        = // 'Ваш токен';
$H1_SUB_URL_BASE     = // 'Ваша ссылка на h1';
$H1_DEVICE_LIMIT     = 3;

$planToDays = [
    '1month'   => 30,
    '3months'  => 90,
    '6months'  => 180,
    '12months' => 365,
];

$body = file_get_contents('php://input');
$data = json_decode($body, true);

if (!$data || !isset($data['event']) || $data['event'] !== 'payment.succeeded') {
    http_response_code(200);
    exit;
}

try {
    $ch = curl_init('https://api.yookassa.ru/v3/payments/' . $data['object']['id']);
    curl_setopt($ch, CURLOPT_USERPWD, $shopId . ':' . $secretKey);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);

    $payment = json_decode($response, true);
    if ($payment['status'] !== 'succeeded') {
        http_response_code(200);
        exit;
    }

    $metadata = $payment['metadata'] ?? [];
    $plan     = $metadata['plan'] ?? '1month';
    $email    = $metadata['email'] ?? '';
    $days     = $planToDays[$plan] ?? 30;
    $amount   = $payment['amount']['value'];
    $username = 'user_' . bin2hex(random_bytes(4));

    if (empty($email)) {
        http_response_code(200);
        exit;
    }

    // СОЗДАНИЕ В REMNAWAVE
    $rwUserData = [
        "username"             => $username,
        "email"                => $email,
        "trafficLimitBytes"    => 0,
        "trafficResetInterval" => "never",
        "expireAt"             => date('c', strtotime("+{$days} days")),
        "hwidDeviceLimit"      => 2,
        "activeInternalSquads" => [$RW_DEFAULT_SQUAD_UUID],
        "note"                 => "ЮKassa | {$plan} | {$amount}₽"
    ];

    $ch = curl_init(rtrim($RW_BASE_URL, '/') . '/api/users');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($rwUserData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer " . $RW_API_TOKEN,
        "Content-Type: application/json",
        "Accept: application/json"
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $rwApiResponse = curl_exec($ch);
    curl_close($ch);

    $rwResult = json_decode($rwApiResponse, true);
    $rwUser   = $rwResult['response'] ?? $rwResult['data'] ?? $rwResult ?? [];
    $rwSubUrl = $rwUser['subscriptionUrl'] ?? $RW_BASE_URL . "/sub/" . ($rwUser['shortUuid'] ?? $rwUser['uuid'] ?? '');

// СОЗДАНИЕ В H1CLOUD VLESS (Обход белых списков)
$h1UserData = [
    "name" => $username,
    "days" => $days
];

if ($H1_DEVICE_LIMIT > 0) {
    $h1UserData["device_limit"] = $H1_DEVICE_LIMIT;
}

$ch = curl_init(rtrim($H1_BASE_URL, '/') . '/api/create');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($h1UserData));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer " . $H1_API_TOKEN,
    "Content-Type: application/json",
    "Accept: application/json"
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);

$h1ApiResponse = curl_exec($ch);
$h1HttpCode    = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$h1Error       = curl_error($ch);
$h1Errno       = curl_errno($ch);
curl_close($ch);

$debugLog  = date('Y-m-d H:i:s') . " | H1Cloud CREATE (result.php)\n";
$debugLog .= "URL: " . rtrim($H1_BASE_URL, '/') . '/api/create' . "\n";
$debugLog .= "Body: " . json_encode($h1UserData, JSON_PRETTY_PRINT) . "\n";
$debugLog .= "Response HTTP: {$h1HttpCode}\n";
$debugLog .= "Response Body: " . substr($h1ApiResponse, 0, 1000) . "\n";
if ($h1Error) $debugLog .= "CURL Errno: {$h1Errno} | CURL Error: {$h1Error}\n";
$debugLog .= "----------------------------------------\n";
file_put_contents('h1_debug.log', $debugLog, FILE_APPEND);

// Polling для получения UUID
$h1Uuid = null;
$h1Result = json_decode($h1ApiResponse, true);

if ($h1Result && isset($h1Result['ok']) && $h1Result['ok'] === true) {
    $h1Uuid = $h1Result['client']['uuid'] ?? $h1Result['uuid'] ?? null;
}

if (empty($h1Uuid)) {
    error_log("H1Cloud: Create timeout in result.php, trying polling for user {$username}");
    sleep(3);
    
    $infoEndpoints = [
        '/api/info?name=' . urlencode($username),
        '/api/get?name=' . urlencode($username),
        '/api/user?name=' . urlencode($username),
        '/api/list'
    ];
    
    foreach ($infoEndpoints as $endpoint) {
        $ch = curl_init(rtrim($H1_BASE_URL, '/') . $endpoint);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer " . $H1_API_TOKEN,
            "Accept: application/json"
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        
        $infoResponse = curl_exec($ch);
        $infoHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($infoHttpCode === 200 && !empty($infoResponse)) {
            $infoResult = json_decode($infoResponse, true);
            
            if ($endpoint === '/api/list') {
                $users = $infoResult['clients'] ?? $infoResult['users'] ?? $infoResult['data'] ?? $infoResult ?? [];
                foreach ($users as $user) {
                    $userName = $user['name'] ?? $user['username'] ?? '';
                    if ($userName === $username) {
                        $h1Uuid = $user['uuid'] ?? null;
                        break 2;
                    }
                }
            } else {
                $h1Uuid = $infoResult['client']['uuid'] ?? $infoResult['uuid'] ?? null;
                if (!empty($h1Uuid)) {
                    break;
                }
            }
        }
    }
}

if (empty($h1Uuid)) {
    throw new Exception("H1Cloud: Failed to get UUID for user {$username} after polling");
}

$h1SubUrl = rtrim($H1_SUB_URL_BASE, '/') . "/sub/{$h1Uuid}";

    // ОТПРАВКА ПИСЬМА 
    $subject = "Ваши ключи и подписки Corvin VPN";
    $message = "Здравствуйте!\n";
    $message .= "Спасибо за покупку! Ваши подписки успешно активированы.\n";
    $message .= "Тариф: " . ($planToDays[$plan]/30) . " месяцев\n";
    $message .= "Сумма: " . $amount . " ₽\n";
    $message .= "Трафик: БЕЗЛИМИТНЫЙ\n";
    $message .= "Имя пользователя: {$username}\n";
    $message .= "═══════════════════════════════════\n";
    $message .= "🔑 ПЕРВАЯ ПОДПИСКА (Основной сервер):\n";
    $message .= $rwSubUrl . "\n";
    $message .= "═══════════════════════════════════\n";
    $message .= "🔑 Обход белых списков:\n";
    $message .= $h1SubUrl . "\n";
    $message .= "═══════════════════════════════════\n";
    $message .= "📌 Инструкция по подключению:\n";
    $message .= "https://korvinvpn.com/setup.html\n";
    $message .= "Если возникнут вопросы — пишите в поддержку:\n";
    $message .= "https://t.me/corvinemigrate\n";
    $message .= "Приятного использования!\nКоманда Corvin VPN";

    $mail_ok = send_email_via_timeweb(
        $email, $subject, $message,
        "Corvin VPN", "support@korvinvpn.com"
    );

    $status = $mail_ok ? 'OK' : 'MAIL_ERR';
    $log = date('Y-m-d H:i:s') . " | #{$payment['id']} | {$amount}₽ | {$plan} | {$email} | Username: {$username} | RW: {$rwSubUrl} | H1: {$h1SubUrl} | {$status}\n";
    file_put_contents('orders.log', $log, FILE_APPEND);

    http_response_code(200);
    echo "OK";

} catch (Exception $e) {
    error_log("Webhook Error: " . $e->getMessage());
    file_put_contents('error.log', date('Y-m-d H:i:s') . " | " . $e->getMessage() . "\n", FILE_APPEND);
    http_response_code(500);
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