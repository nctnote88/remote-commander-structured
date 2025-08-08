<?php
// session_start();

// // ✅ Block access if not logged in
// if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
//     header('Location: login.php');
//     exit;
// }

require 'includes/functions.php';

$servers = load_servers();
$commands = load_commands();
$config = load_config();

// Group servers by group name
$groupedServers = [];
foreach ($servers as $idx => $srv) {
    $group = $srv['group'] ?? 'Ungrouped';
    $groupedServers[$group][] = ['index' => $idx, 'server' => $srv];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Remote Commander</title>
    <link rel="stylesheet" href="assets/style.css">
    <style>
        body {
            background-color: #f4f7f9;
            color: #333;
            font-family: Arial, sans-serif;
        }
        .container {
            max-width: 800px;
            margin: auto;
            padding: 20px;
            background-color: #ffffff;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        .checkbox-label {
            display: block;
            margin: 5px 0;
        }
        .tab-buttons {
            display: flex;
            gap: 10px;
            margin-bottom: 10px;
        }
        .tab-btn {
            background: #dce4ec;
            color: #333;
            border: none;
            padding: 8px 12px;
            border-radius: 5px;
            cursor: pointer;
        }
        .tab-btn.active {
            background: #007BFF;
            color: #fff;
        }
        select, textarea, button {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border-radius: 5px;
            border: 1px solid #ccc;
            background: #f9f9f9;
            color: #333;
        }
        pre {
            background: #eef0f2;
            padding: 10px;
            border-radius: 5px;
            overflow-x: auto;
        }
        .output-block {
            margin-top: 20px;
            border: 1px solid #ddd;
            padding: 10px;
            border-radius: 5px;
            background: #fafafa;
        }
    </style>
</head>
<body>
<div class="container">
    <h1>Remote Commander</h1>

    <form id="commandForm" method="post" onsubmit="return false;">
        <h2>Select Servers</h2>

        <!-- Tabs -->
        <div class="tab-buttons">
            <?php $i = 0; foreach ($groupedServers as $group => $srvList): ?>
                <button type="button" class="tab-btn <?= $i === 0 ? 'active' : '' ?>" onclick="openTab('tab-<?= $i ?>')">
                    <?= htmlspecialchars($group) ?>
                </button>
            <?php $i++; endforeach; ?>
        </div>

        <!-- Tab Contents -->
        <div class="tab-contents">
            <?php $i = 0; foreach ($groupedServers as $group => $srvList): ?>
                <div id="tab-<?= $i ?>" class="tab-content" style="<?= $i === 0 ? '' : 'display:none;' ?>">
                    <?php foreach ($srvList as $item): ?>
                        <?php $srv = $item['server']; $idx = $item['index']; ?>
                        <label class="checkbox-label">
                            <input type="checkbox" name="servers[]" value="<?= $idx ?>" onchange="updateSelected()">
                            <?= htmlspecialchars($srv['name']) ?>
                        </label><br>
                    <?php endforeach; ?>
                </div>
            <?php $i++; endforeach; ?>
        </div>

        <p><strong>Selected Servers:</strong> <span id="selected-names">None</span></p>

        <h2>Select Command from List</h2>
        <select name="command_select">
            <option value="">-- Select from saved commands --</option>
            <?php foreach ($commands as $cmd): ?>
                <option value="<?= htmlspecialchars($cmd['id']) ?>">
                    <?= htmlspecialchars($cmd['label']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <h2>Or Enter Custom Command</h2>
        <textarea name="command_custom" rows="3" placeholder="Type your custom command here..."></textarea>

        <br><br>
        <button type="button" onclick="submitCommand()">▶ Run Command</button>
        <div id="loading-indicator" style="display:none; margin-top:10px;">⏳ Running command...</div>
    </form>

    <div id="results" style="margin-top:20px;"></div>
</div>

<script>
function updateSelected() {
    const checkboxes = document.querySelectorAll('input[name="servers[]"]:checked');
    const selected = Array.from(checkboxes).map(cb => `[${cb.parentElement.textContent.trim()}]`);
    document.getElementById('selected-names').textContent = selected.join(' ') || 'None';
}

function validateForm() {
    const checkboxes = document.querySelectorAll('input[name="servers[]"]:checked');
    if (checkboxes.length === 0) {
        alert('❗ กรุณาเลือกอย่างน้อย 1 server ก่อนส่งคำสั่ง');
        return false;
    }

    const cmdSelect = document.querySelector('select[name="command_select"]').value.trim();
    const cmdCustom = document.querySelector('textarea[name="command_custom"]').value.trim();

    if (!cmdSelect && !cmdCustom) {
        alert('❗ กรุณาเลือกคำสั่งจากรายการ หรือกรอกคำสั่งเอง');
        return false;
    }

    return true;
}

function submitCommand() {
    if (!validateForm()) return;

    const form = document.getElementById('commandForm');
    const formData = new FormData(form);

    document.getElementById('loading-indicator').style.display = 'block';
    document.getElementById('results').innerHTML = '';

    fetch('run-command.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.text())
    .then(html => {
        document.getElementById('results').innerHTML = html;
        document.getElementById('loading-indicator').style.display = 'none';
    })
    .catch(() => {
        alert('❌ Error running command.');
        document.getElementById('loading-indicator').style.display = 'none';
    });
}

function openTab(id) {
    document.querySelectorAll('.tab-content').forEach(el => el.style.display = 'none');
    document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
    document.getElementById(id).style.display = 'block';
    document.querySelector('[onclick="openTab(\'' + id + '\')"]').classList.add('active');
}
</script>

</body>
</html>
