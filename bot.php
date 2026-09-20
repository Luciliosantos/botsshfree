<?php

// Bot criado por @DARKNETSSH código original para o @DARKNETSSHBOT

date_default_timezone_set ('America/Sao_Paulo'); // define timestamp padrão

// Incluindo arquivos nescessários
include __DIR__.'/Telegram.php';

if (!file_exists('dadosBot.ini')){

	echo "Faça a instalação do bot antes!";
	exit;

}

$textoMsg=json_decode (file_get_contents('textos.json'));
$iniParse=parse_ini_file('dadosBot.ini');

$ip=$iniParse ['ip'];
$token=$iniParse ['token'];
$limite=$iniParse ['limite'];

define ('TOKEN', $token); // token do bot criado no @botfather

// Instancia das classes
$tlg=new Telegram (TOKEN);
$redis=new Redis ();
$redis->connect ('localhost', 6379); //redis usando porta padrão

// BLOCO USADO EM LONG POLLING

while (true){

$updates=$tlg->getUpdates();

if (!$updates || !is_array($updates)) {
    sleep(1);
    continue;
}

for ($i=0; $i < $tlg->UpdateCount(); $i++){

if (!isset($updates[$i])) {
    continue;
}

$tlg->serveUpdate($i);

$msg_txt = (is_object($tlg) && is_string($tlg->Text())) ? $tlg->Text() : "";

switch ($msg_txt){

	case '/start':

	$tlg->sendMessage ([
		'chat_id' => $tlg->ChatID (),
		'text' => $textoMsg->start,
		'parse_mode' => 'html',
		'reply_markup' => $tlg->buildInlineKeyBoard ([
			[$tlg->buildInlineKeyboardButton ('🇧🇷 SSH Gratis BR 🇧🇷', null, '/sshgratis')]
		    [$tlg->buildInlineKeyboardButton ('🇧🇷 Comprar 30 Dias 🇧🇷', null, '/pix')]
		])
	]);

	break;
	case '/sobre':

	$tlg->sendMessage ([
		'chat_id' => $tlg->ChatID (),
		'text' => 'Bot original @Technet1bot por @NETxx0'
	]);

	break;
	case '/total':

	$tlg->sendMessage ([
		'chat_id' => $tlg->ChatID (),
		'text' => 'Foram criadas <b>'.$redis->dbSize ().'</b> contas nas ultimas 24h',
		'parse_mode' => 'html'
	]);
	
    break;
	case '/pix':

	$tlg->answerCallbackQuery ([
	'callback_query_id' => $tlg->Callback_ID()
	]);

	break;
	case '/sshgratis':

	$tlg->answerCallbackQuery ([
	'callback_query_id' => $tlg->Callback_ID()
	]);

	if ($redis->dbSize () == $limite){

		$textoSSH=$textoMsg->sshgratis->limite;

	} elseif ($redis->exists ($tlg->UserID ())){

		$textoSSH=$textoMsg->sshgratis->nao_criado;

	} else {

		$usuario=substr (str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 6);
		$senha=mt_rand(11111, 999999);

		exec ('./gerarusuario.sh '.$usuario.' '.$senha.' 1 1');

		$textoSSH="🇧🇷 Conta SSH criada ;)\r\n\r\n<b>Servidor:</b> <code>".$ip."</code>\r\n<b>Usuario:</b> <code>".$usuario."</code>\r\n<b>Senha:</b> <code>".$senha."</code>\r\n<b>Logins:</b> 1\r\n<b>Validade:</b> ".date ('d/m', strtotime('+3 hour'))."\r\n\r\n🤙 Cortesia do @NETxx0";

		$redis->setex ($tlg->UserID (), 43200, 'true'); //define registro para ser guardado por 12h

	}

	$tlg->sendMessage ([
		'chat_id' => $tlg->ChatID (),
		'text' => $textoSSH,
		'parse_mode' => 'html'
	]);

	break;

}

}}
