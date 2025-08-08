<?php
require_once 'ssh_helper.php';

function load_servers() {
    return json_decode(file_get_contents(__DIR__ . '/../servers.json'), true);
}

function load_commands() {
    $path = __DIR__ . '/../commands.json';
    if (!file_exists($path)) return [];

    $json = file_get_contents($path);
    return json_decode($json, true);
}

function load_commands_map() {
    $path = __DIR__ . '/../commands.json';
    if (!file_exists($path)) return [];

    $json = file_get_contents($path);
    $arr = json_decode($json, true);
    $map = [];
    foreach ($arr as $item) {
        $map[$item['id']] = $item;
    }
    return $map;
}

function load_config() {
    return json_decode(file_get_contents(__DIR__ . '/../config.json'), true);
}

function execute_ssh_command($server, $command) {
    $host = $server['ip'];
    $user = $server['user'];
    $authType = $server['auth_type'] ?? 'password';
    $port = $server['port'] ?? 22;

    if ($authType === 'key') {
        $keyPath = $server['key_path'];
        $connection = ssh2_connect($host, $port);
        if (!$connection) return "❌ ไม่สามารถเชื่อมต่อ SSH ไปยัง $host";

        $auth = ssh2_auth_pubkey_file(
            $connection,
            $user,
            $keyPath . '.pub',
            $keyPath
        );

        if (!$auth) return "❌ Authentication ล้มเหลว (key) สำหรับ $user@$host";
    } else {
        $password = $server['password'];
        $connection = ssh2_connect($host, $port);
        if (!$connection) return "❌ ไม่สามารถเชื่อมต่อ SSH ไปยัง $host";

        $auth = ssh2_auth_password($connection, $user, $password);
        if (!$auth) return "❌ Authentication ล้มเหลว (password) สำหรับ $user@$host";
    }

    $stream = ssh2_exec($connection, $command);
    if (!$stream) return "❌ คำสั่งไม่สามารถรันได้: $command";

    stream_set_blocking($stream, true);
    $output = stream_get_contents($stream);
    fclose($stream);
    return $output;
}

