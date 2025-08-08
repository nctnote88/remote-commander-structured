<?php
require __DIR__ . '/../vendor/autoload.php';

use phpseclib3\Net\SSH2;
use phpseclib3\Crypt\PublicKeyLoader;

function ssh_exec_command($server, $command, $port = 22, $timeout = 10) {
    $ssh = new SSH2($server['ip'], $port);

    if ($server['auth_type'] === 'password') {
        if (!$ssh->login($server['user'], $server['password'])) {
            throw new Exception("SSH login failed for {$server['name']}");
        }
    } elseif ($server['auth_type'] === 'key') {
        $key = PublicKeyLoader::loadPrivateKey(file_get_contents($server['key_path']));
        if (!$ssh->login($server['user'], $key)) {
            throw new Exception("SSH login failed for {$server['name']}");
        }
    } else {
        throw new Exception("Unknown auth_type for {$server['name']}");
    }

    $ssh->setTimeout($timeout);
    return $ssh->exec($command);
}

function ssh_exec_command_stream($server, $command, $port = 22, $timeout = 10) {
    $connection = ssh2_connect($server['ip'], $port);
    if (!$connection) {
        throw new Exception("ไม่สามารถเชื่อมต่อ SSH ไปยัง " . $server['ip']);
    }

    $authSuccess = isset($server['auth_type']) && $server['auth_type'] === 'key'
        ? ssh2_auth_pubkey_file($connection, $server['user'], $server['key_path'] . '.pub', $server['key_path'])
        : ssh2_auth_password($connection, $server['user'], $server['password']);

    if (!$authSuccess) {
        throw new Exception("การยืนยันตัวตนล้มเหลวสำหรับ " . $server['user'] . "@" . $server['ip']);
    }

    $stream = ssh2_exec($connection, $command);
    if (!$stream) {
        throw new Exception("ไม่สามารถรันคำสั่ง: $command");
    }

    stream_set_blocking($stream, true);
    return $stream; // return stream for real-time usage
}
