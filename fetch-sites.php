<?php
require_once 'includes/functions.php';
require_once 'includes/ssh_helper.php';

$servers = load_servers();
$serverIndex = $_POST['server_index'] ?? null;

if (!isset($servers[$serverIndex])) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid server index']);
    exit;
}

$server = $servers[$serverIndex];

// คำสั่งดึงชื่อไฟล์ whitelist
$cmd = "ls /etc/nginx/conf.d/whitelist/*.whitelist 2>/dev/null | xargs -n1 basename | sed 's/\\.whitelist\$//'";

try {
    $output = ssh_exec_command($server, $cmd);
    $sites = array_filter(array_map('trim', explode("\n", $output)));

    echo json_encode(['status' => 'success', 'sites' => $sites]);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
