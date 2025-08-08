<?php
session_start();

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

// Group commands by group
$commandsByGroup = [];
foreach ($commands as $cmd) {
    foreach ($cmd['groups'] as $group) {
        $commandsByGroup[$group][] = $cmd;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Remote Commander</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
<div class="container">
    <div class="header">
        <h1><i class="fas fa-terminal"></i> Remote Commander</h1>
        <p>Execute commands across multiple servers with ease</p>
    </div>

    <div class="content">
        <form id="commandForm" method="post" onsubmit="return false;">
            <div class="section">
                <h2 class="section-title"><i class="fas fa-server"></i> Select Target Servers</h2>
                <div class="tab-buttons">
                    <?php $i = 0; foreach ($groupedServers as $group => $srvList): ?>
                        <button type="button" class="tab-btn <?= $i === 0 ? 'active' : '' ?>" onclick="openTab('tab-<?= $i ?>', '<?= $group ?>')">
                            <?= htmlspecialchars($group) ?>
                            <span style="background: rgba(255,255,255,0.3); padding: 2px 8px; border-radius: 12px; font-size: 0.8em; margin-left: 5px;"><?= count($srvList) ?></span>
                        </button>
                    <?php $i++; endforeach; ?>
                </div>
                <!-- Tab Contents -->
                <div class="tab-contents">
                    <?php $i = 0; foreach ($groupedServers as $group => $srvList): ?>
                        <div id="tab-<?= $i ?>" class="tab-content" style="<?= $i === 0 ? '' : 'display:none;' ?>">
                            <h3 style="margin-bottom: 10px; color: #333;"><?= htmlspecialchars($group) ?></h3>
                            <div class="server-grid">
                                <?php foreach ($srvList as $item): ?>
                                    <?php $srv = $item['server']; $idx = $item['index']; ?>
                                    <label class="checkbox-label">
                                        <input type="radio" name="servers[]" value="<?= $idx ?>">
                                        <?= htmlspecialchars($srv['name']) ?>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php $i++; endforeach; ?>
                </div>
            </div>

            <div class="section">
                <!-- <h2 class="section-title"><i class="fas fa-code"></i> Choose Command</h2> -->
                <div class="command-section">
                    <label><i class="fas fa-list"></i> Select from saved commands</label>
                    <select name="command_select" id="command-select">
                        <option value="">-- Select from saved commands --</option>
                    </select>
                </div>

                <div id="extra-fields" style="display: none; margin-top: 15px;">
        <label>Site Name (.whitelist):</label>
        <select name="site_name" id="site-selector" required style="width:100%; padding: 8px;"></select>

        <label style="margin-top: 10px;">IP Address to Add:</label>
        <input type="text" name="ip_address" placeholder="เช่น 1.2.3.4" pattern="^(\d{1,3}\.){3}\d{1,3}$" required style="width:100%; padding: 8px;">
        </div>
</div>


            <div style="text-align: center; padding: 20px 0;">
                <button type="button" onclick="submitCommand()">
                    <i class="fas fa-play" style="margin-right: 8px;"></i> Run Command
                </button>
                <div id="loading-indicator" style="display:none;">
                    <i class="fas fa-spinner" style="animation: spin 1s linear infinite; margin-right: 10px;"></i>
                    Running command...
                </div>
            </div>
        </form>
        <div id="results"></div>
    </div>
</div>

<script>
const commandData = <?= json_encode($commandsByGroup) ?>;
const commandNeedInput = ['add_whitelist'];

function openTab(id, group) {
    document.querySelectorAll('.tab-content').forEach(el => el.style.display = 'none');
    document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));

    const tab = document.getElementById(id);
    if (tab) tab.style.display = 'block';

    const activeBtn = document.querySelector('[onclick*="' + id + '"]');
    if (activeBtn) activeBtn.classList.add('active');

    const select = document.getElementById('command-select');
    select.innerHTML = '<option value="">-- Select from saved commands --</option>';
    if (commandData[group]) {
        commandData[group].forEach(cmd => {
            const opt = document.createElement('option');
            opt.value = cmd.id;
            opt.textContent = cmd.label;
            select.appendChild(opt);
        });
    }

    document.getElementById('extra-fields').style.display = 'none';
}

document.getElementById('command-select').addEventListener('change', function () {
    const selected = this.value;
    const extra = document.getElementById('extra-fields');
    extra.style.display = commandNeedInput.includes(selected) ? 'block' : 'none';

    if (selected === 'add_whitelist') {
        const selectedServer = document.querySelector('input[name="servers[]"]:checked');
        if (!selectedServer) {
            Swal.fire('กรุณาเลือก Server ก่อน', '', 'warning');
            this.value = ''; // reset selection
            extra.style.display = 'none';
            return;
        }

        const serverIndex = selectedServer.value;
        fetch('fetch-sites.php', {
            method: 'POST',
            body: new URLSearchParams({ server_index: serverIndex })
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                const siteSelector = document.getElementById('site-selector');
                siteSelector.innerHTML = '';
                data.sites.forEach(site => {
                    const opt = document.createElement('option');
                    opt.value = site;
                    opt.textContent = site;
                    siteSelector.appendChild(opt);
                });
            } else {
                Swal.fire('เกิดข้อผิดพลาด', data.message || 'ไม่สามารถโหลดรายชื่อ whitelist ได้', 'error');
            }
        })
        .catch(() => {
            Swal.fire('เกิดข้อผิดพลาด', 'ไม่สามารถติดต่อ fetch-sites.php ได้', 'error');
        });
    }
});




function validateForm() {
    const selectedRadio = document.querySelector('input[name="servers[]"]:checked');
    if (!selectedRadio) {
        alert('❗ กรุณาเลือก server ก่อนส่งคำสั่ง');
        return false;
    }
    const cmdSelect = document.querySelector('select[name="command_select"]').value.trim();
    if (!cmdSelect) {
        alert('❗ กรุณาเลือกคำสั่งจากรายการ');
        return false;
    }
    return true;
}

function submitCommand() {
    if (!validateForm()) return;

    const form = document.getElementById('commandForm');
    const formData = new FormData(form);
    const selectedServer = document.querySelector('input[name="servers[]"]:checked');
    const selectedServerLabel = selectedServer ? selectedServer.parentElement.textContent.trim() : 'Unknown Server';
    const selectedCommand = document.querySelector('select[name="command_select"] option:checked');
    const commandLabel = selectedCommand ? selectedCommand.textContent.trim() : 'Unknown Command';
    const commandId = selectedCommand ? selectedCommand.value : '';

    Swal.fire({
        title: 'ยืนยันการรันคำสั่ง',
        html: `<strong>${commandLabel}</strong><br>ที่เซิร์ฟเวอร์ <strong>${selectedServerLabel}</strong>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'ยืนยัน',
        cancelButtonText: 'ยกเลิก',
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'กำลังดำเนินการ...',
                html: 'กรุณารอสักครู่',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            const targetUrl = commandId === 'add_whitelist' ? 'run-whitelist.php' : 'run-command.php';

            fetch(targetUrl, {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                Swal.close();

                if (data.status === 'error') {
                    Swal.fire('เกิดข้อผิดพลาด', data.message, 'error');
                    return;
                }

                if (data.status === 'warning') {
                    Swal.fire('คำเตือน', data.message, 'warning');
                    return;
                }

                // ✅ success
                const outputHtml = `
                    <h3>ผลลัพธ์จาก Server: ${data.server}</h3>
                    <pre style="background:#f8f8f8; padding:10px; border-radius:5px;">${data.output}</pre>
                    <div style="margin-top: 10px; padding: 10px; background: #e0ffe0; color: #006600; border-radius: 6px;">
                        ${data.message}
                    </div>
                `;
                document.getElementById('results').innerHTML = outputHtml;

                Swal.fire({
                    icon: 'success',
                    title: 'สำเร็จ',
                    text: 'ระบบจะโหลดหน้าใหม่ในไม่กี่วินาที...',
                    timer: 3000,
                    showConfirmButton: false
                }).then(() => {
                    location.reload();
                });
            })
            .catch(() => {
                Swal.close();
                Swal.fire('เกิดข้อผิดพลาด', 'ไม่สามารถส่งคำสั่งได้', 'error');
            });
        }
    });
}

window.onload = function () {
    const firstTab = document.querySelector('.tab-btn');
    if (firstTab) firstTab.click();
};


</script>

<style>
@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
</style>
</body>
</html>
