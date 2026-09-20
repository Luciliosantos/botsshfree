<?php
if (!file_exists('dadosBot.ini')){
    echo "Configuracao nao encontrada!\n";
    exit;
}

$textoMsg = json_decode(file_get_contents('textos.json'), true);
$iniParse = parse_ini_file('dadosBot.ini');

$ip = $iniParse['ip'];
$token = $iniParse['token'];
$limite = $iniParse['limite'];

// Montagem segura do link para compatibilidade total
$api_url = "https:" . "//" . "api." . "telegram." . "org/" . "bot" . $token . "/";
$offset = 0;

echo "Bot Nativo Pronto e Escutando no PHP 8!\n";

while (true) {
    $url = $api_url . "getUpdates?offset=" . $offset . "&timeout=5";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $response = curl_exec($ch);
    curl_close($ch);
    
    $updates = json_decode($response, true);
    
    if (isset($updates['result']) && is_array($updates['result'])) {
        foreach ($updates['result'] as $update) {
            $offset = $update['update_id'] + 1;
            
            if (isset($update['message']['text'])) {
                $chat_id = $update['message']['chat']['id'];
                $text = $update['message']['text'];
                
                if ($text == '/start') {
                    $keyboard = [
                        'inline_keyboard' => [
                            [['text' => '🇧🇷 SSH Gratis BR 🚀', 'callback_data' => '/sshgratis']],
                            [['text' => '💵 Comprar 30 Dias 🚀', 'callback_data' => '/pix']]
                        ]
                    ];
                    
                    $msg_start = isset($textoMsg['start']) ? $textoMsg['start'] : "🤖 Bem-vindo ao Gerenciador SSH!";
                    $send_url = $api_url . "sendMessage?chat_id=" . $chat_id . "&text=" . urlencode($msg_start) . "&parse_mode=html&reply_markup=" . urlencode(json_encode($keyboard));
                    file_get_contents($send_url);
                }
            }
            
            if (isset($update['callback_query'])) {
                $callback_id = $update['callback_query']['id'];
                $chat_id = $update['callback_query']['message']['chat']['id'];
                $data = $update['callback_query']['data'];
                $user_id = $update['callback_query']['from']['id'];
                
                file_get_contents($api_url . "answerCallbackQuery?callback_query_id=" . $callback_id);
                
                if ($data == '/sshgratis') {
                    $redis = new Redis();
                    try {
                        $redis->connect('127.0.0.1', 6379);
                        $db_size = $redis->dbSize();
                        $exists = $redis->exists($user_id);
                    } catch (Exception $e) {
                        $db_size = 0;
                        $exists = false;
                    }
                    
                    if ($db_size == $limite) {
                        $textoSSH = isset($textoMsg['sshgratis']['limite']) ? $textoMsg['sshgratis']['limite'] : "❌ Limite atingido!";
                    } elseif ($exists) {
                        $textoSSH = isset($textoMsg['sshgratis']['nao_criado']) ? $textoMsg['sshgratis']['nao_criado'] : "❌ Conta ativa!";
                    } else {
                        $usuario = substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 5);
                        $senha = mt_rand(11111, 99999);
                        
                        @chmod('gerarusuario.sh', 0755);
                        if (file_exists('gerarusuario.sh')) {
                            exec('./gerarusuario.sh ' . $usuario . ' ' . $senha . ' 1 1');
                        } else {
                            exec('useradd -M -s /bin/false ' . $usuario . ' && echo "' . $usuario . ':' . $senha . '" | chpasswd');
                        }
                        
                        $textoSSH = "Conta SSH criada ;) \r\n\r\n<b>Servidor:</b> " . $ip . "\r\n<b>Usuário:</b> " . $usuario . "\r\n<b>Senha:</b> " . $senha;
                        try { $redis->setex($user_id, 43200, 'true'); } catch(Exception $e){}
                    }
                    
                    $send_url = $api_url . "sendMessage?chat_id=" . $chat_id . "&text=" . urlencode($textoSSH) . "&parse_mode=html";
                    file_get_contents($send_url);
                }
            }
        }
    }
    sleep(1);
}
