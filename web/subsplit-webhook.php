<?php

require __DIR__.'/../vendor/autoload.php';

$configFilename = file_exists(__DIR__.'/../config.json')
    ? __DIR__.'/../config.json'
    : __DIR__.'/../config.json.dist';

$config = json_decode(file_get_contents($configFilename), true);

/**
 * See https://help.github.com/articles/what-ip-addresses-does-github-use-that-i-should-whitelist
 * to get current list of used IP addresses by GitHub.
 */
$allowedIps = isset($config['allowed-ips'])
    ? $config['allowed-ips']
    : array('192.30.252.0/22');

if (!checkIPv4Address($_SERVER['REMOTE_ADDR'], $allowedIps)) {
    header('HTTP/1.1 403 Forbidden');
    echo sprintf("Host %s is not allowed to connect.\n", $_SERVER['REMOTE_ADDR']);
    exit;
}

$body = $_POST['payload'];

$redis = new Predis\Client();

$redis->lpush('dflydev-git-subsplit:incoming', $body);

echo "Thanks.\n";
