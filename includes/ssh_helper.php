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
