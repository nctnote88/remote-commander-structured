<?php
require 'includes/functions.php';
require_once 'includes/ssh_helper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo '<div style="color:red">❌ Method Not Allowed</div>';
    exit;
}

$servers = load_servers();
$commands = load_commands();
$config = load_config();

$selectedServers = $_POST['servers'] ?? [];
$commandSelectId = trim($_POST['command_select'] ?? '');
$selectedCommand = '';

if ($commandSelectId && isset($commands[$commandSelectId])) {
    $selectedCommand = $commands[$commandSelectId]['command'];
}


$outputs = [];

if (empty($selectedServers) || empty($selectedCommand)) {
    echo '<div style="color:red">❌ Missing servers or command.</div>';
    exit;
}

foreach ($selectedServers as $srvIndex) {
    // ตรวจสอบ index ว่าถูกต้อง
    if (!isset($servers[$srvIndex])) {
        $outputs[] = [
            'server' => "Unknown Server (Index $srvIndex)",
            'output' => "❌ Invalid server index."
        ];
        continue;
    }

    $srv = $servers[$srvIndex];
    try {
        $result = ssh_exec_command($srv, $selectedCommand, $config['default_ssh_port'], $config['timeout']);
        $outputs[] = [
            'server' => $srv['name'],
            'output' => htmlspecialchars($result)
        ];
    } catch (Exception $e) {
        $outputs[] = [
            'server' => $srv['name'],
            'output' => "❌ Error: " . htmlspecialchars($e->getMessage())
        ];
    }
}

// แสดงผลลัพธ์
echo '<table class="result-table">';
echo '<thead><tr><th>Server</th><th>Output</th></tr></thead><tbody>';
foreach ($outputs as $out) {
    echo '<tr>';
    echo '<td>' . htmlspecialchars($out['server']) . '</td>';
    echo '<td><pre style="white-space: pre-wrap;">' . $out['output'] . '</pre></td>';
    echo '</tr>';
}
echo '</tbody></table>';

