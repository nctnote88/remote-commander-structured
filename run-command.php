<?php
require 'includes/functions.php';
require_once 'includes/ssh_helper.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method Not Allowed']);
    exit;
}

$servers = load_servers();
$commandsMap = load_commands_map();
$config = load_config();

$selectedServers = $_POST['servers'] ?? [];
$commandSelectId = trim($_POST['command_select'] ?? '');

if (empty($selectedServers) || empty($commandSelectId)) {
    echo json_encode(['status' => 'error', 'message' => 'Missing servers or command.']);
    exit;
}

if (!isset($commandsMap[$commandSelectId])) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid command selected.']);
    exit;
}

$selectedCommand = $commandsMap[$commandSelectId]['command'];
$serverIndex = $selectedServers[0]; // ใช้เซิร์ฟเวอร์ตัวแรก
if (!isset($servers[$serverIndex])) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid server index.']);
    exit;
}

$server = $servers[$serverIndex];
try {
    $result = ssh_exec_command($server, $selectedCommand, $config['default_ssh_port'], $config['timeout']);
    echo json_encode([
        'status' => 'success',
        'server' => $server['name'],
        'output' => $result,
        'message' => "✅ คำสั่ง \"{$commandsMap[$commandSelectId]['label']}\" ดำเนินการสำเร็จ"
    ]);
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
