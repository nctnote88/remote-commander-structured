<?php
session_start();
require_once 'includes/functions.php';
require_once 'includes/ssh_helper.php';

header('Content-Type: application/json');

$servers = load_servers();
$serverIndex = $_POST['servers'][0] ?? null;
$site = trim($_POST['site_name'] ?? '');
$ip = trim($_POST['ip_address'] ?? '');

if ($serverIndex === null || empty($site) || empty($ip)) {
    echo json_encode(['status' => 'error', 'message' => 'Missing server, site, or IP.']);
    exit;
}

$server = $servers[$serverIndex] ?? null;
if (!$server) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid server index.']);
    exit;
}

// ✅ Validate input
if (!preg_match('/^[a-zA-Z0-9_.-]+$/', $site)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid site name.']);
    exit;
}
if (!filter_var($ip, FILTER_VALIDATE_IP)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid IP address.']);
    exit;
}

$whitelistFile = "/etc/nginx/conf.d/whitelist/{$site}.whitelist";

// ✅ Check if file exists
$checkFileCmd = "[ -f '$whitelistFile' ] && echo 'EXIST' || echo 'NOTFOUND'";
try {
    $result = trim(ssh_exec_command($server, $checkFileCmd));
    if ($result !== 'EXIST') {
        echo json_encode([
            'status' => 'error',
            'message' => "❌ ไม่พบไฟล์ $site.whitelist บนเซิร์ฟเวอร์"
        ]);
        exit;
    }
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    exit;
}

// ✅ Check if IP already exists
$checkIPCmd = "grep -q 'allow $ip;' '$whitelistFile'; echo \$?";
try {
    $ipResult = trim(ssh_exec_command($server, $checkIPCmd));
    if ($ipResult === "0") {
        echo json_encode([
            'status' => 'warning',
            'message' => "⚠️ IP นี้ ($ip) มีอยู่แล้วใน whitelist"
        ]);
        exit;
    }
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    exit;
}

// ✅ ตรวจสอบว่า marker #From Support app มีอยู่ใน whitelist file หรือไม่
$checkMarkerCmd = "grep -q '#From Support app' '$whitelistFile' && echo 'EXIST' || echo 'NOTFOUND'";
try {
    $markerResult = trim(ssh_exec_command($server, $checkMarkerCmd));
    if ($markerResult !== 'EXIST') {
        echo json_encode([
            'status' => 'error',
            'message' => "❌ ไม่พบบรรทัด '#From Support app' ในไฟล์ whitelist โปรดแจ้ง MIS"
        ]);
        exit;
    }
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    exit;
}


// ✅ Add IP and reload nginx
$cmdAdd = "sudo sed -i '/#From Support app/a allow $ip;' '$whitelistFile'";
$cmdReload = "sudo systemctl reload nginx";

try {
    $output1 = ssh_exec_command($server, $cmdAdd);
    $output2 = ssh_exec_command($server, $cmdReload);
    echo json_encode([
        'status' => 'success',
        'message' => "✅ เพิ่ม IP <strong>$ip</strong> สำเร็จ และ reload nginx เรียบร้อย",
        'output' => $output1 . "\n" . $output2,
        'server' => $server['name']
    ]);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    exit;
}
