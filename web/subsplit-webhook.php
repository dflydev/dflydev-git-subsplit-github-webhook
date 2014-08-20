<?php

require __DIR__.'/../vendor/autoload.php';

$configFilename = file_exists(__DIR__.'/../config.json')
    ? __DIR__.'/../config.json'
    : __DIR__.'/../config.json.dist';

$config = json_decode(file_get_contents($configFilename), true);

$allowedIps = isset($config['allowed-ips'])
    ? $config['allowed-ips']
    : array('207.97.227.253', '50.57.128.197', '108.171.174.178');

if (!in_array($_SERVER['REMOTE_ADDR'], $allowedIps)) {
    header('HTTP/1.1 403 Forbidden');
    echo sprintf("Host %s is not allowed to connect.\n", $_SERVER['REMOTE_ADDR']);
    exit;
}

$headers = getallheaders();
if (isset($headers['Content-Type']) && 'application/json' === $headers['Content-Type']) {
    $body = file_get_contents('php://input');
} else {
    $body = $_POST['payload'];
}

if (!$body) {
    header('HTTP/1.1 400 Bad Request', true, 400);
    exit;
}

$redis = new Predis\Client();
$redis->lpush('dflydev-git-subsplit:incoming', $body);

echo "Thanks.\n";
