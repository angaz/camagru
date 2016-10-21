<?php

session_start();
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=UTF-8');

if ($_POST['uri']) {
    try {
        $server = 'localhost';
        $dbname = 'camagru';

        $uri = $_POST['uri'];
		$passwd = $_POST['passwd'];
		$ciphertext_dec = base64_decode($uri);
		$iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC);
		$iv_dec = substr($ciphertext_dec, 0, $iv_size);
		$ciphertext_dec = substr($ciphertext_dec, $iv_size);
		$key = pack('H*', 'A6A66F59E0D8127B2A45B648CA8C58ED3454DDF4C42085A8A556E777111D2F27');
		$username = mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $key, $ciphertext_dec, MCRYPT_MODE_CBC, $iv_dec);
		$username = trim($username);

        $conn = new PDO("mysql:host=$server;dbname=$dbname", 'root', 'sparewheel');
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $sql = $conn->prepare('UPDATE users SET username = SUBSTR(username, 2) WHERE username = :username;');
        $sql->execute([':username' => $username]);

		file_put_contents("log.log", $sql->rowCount."\n".$username."\n".$_POST['uri'], FILE_APPEND);
		if ($sql->rowCount() > 0) {
			echo json_encode(true);
		} else {
			echo json_encode(false);
		}
    } catch (PDOException $e) {
        error_log($e, 3, dirname(__DIR__).'/log/errors.log');
        echo json_encode(false);
    }
    $conn = null;
} else {
    echo json_encode("No URI");
}