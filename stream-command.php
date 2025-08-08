<?php
require_once 'includes/functions.php';
require_once 'includes/ssh_helper.php';

header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');

$servers = load_servers();
$serverIndex = $_GET['server'] ?? null;
$command = $_GET['cmd'] ?? '';

if (!isset($servers[$serverIndex]) || empty($command)) {
    echo "data: ❌ Invalid server index or command.\n\n";
    flush();
    exit;
}

$server = $servers[$serverIndex];
try {
    $stream = ssh_exec_command_stream($server, $command);

    while (!feof($stream)) {
        $line = fgets($stream, 2048);
        if ($line !== false) {
            echo "data: " . rtrim($line) . "\n\n";
            ob_flush();
            flush();
        }
    }

    fclose($stream);
} catch (Exception $e) {
    echo "data: ❌ " . $e->getMessage() . "\n\n";
    flush();
    exit;
}
