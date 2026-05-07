<?php
session_start();
$config_file = 'config.php';
if (!file_exists($config_file)) {
    file_put_contents($config_file, "<?php \$admin_pass = 'admin123'; ?>");
}
include $config_file;

if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php");
}

// تسجيل الدخول بسيط
if (isset($_POST['login'])) {
    if ($_POST['password'] === $admin_pass) {
        $_SESSION['logged_in'] = true;
    }
}

if (!isset($_SESSION['logged_in'])) {
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>تسجيل الدخول - Raian VPN</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Cairo', sans-serif; background: #0f172a; color: white; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0; }
        .login-card { background: rgba(30, 41, 59, 0.7); backdrop-filter: blur(10px); padding: 2.5rem; border-radius: 1.5rem; border: 1px solid rgba(255,255,255,0.1); width: 100%; max-width: 400px; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5); }
        h1 { text-align: center; color: #38bdf8; margin-bottom: 2rem; font-size: 2rem; }
        input { width: 100%; padding: 14px; margin: 10px 0; border-radius: 10px; border: 1px solid #334155; background: #1e293b; color: white; box-sizing: border-box; font-size: 1rem; }
        button { width: 100%; padding: 14px; border-radius: 10px; border: none; background: linear-gradient(135deg, #38bdf8, #818cf8); color: white; font-weight: bold; cursor: pointer; transition: 0.3s; font-size: 1.1rem; }
        button:hover { transform: translateY(-3px); box-shadow: 0 10px 20px rgba(56, 189, 248, 0.3); }
    </style>
</head>
<body>
    <div class="login-card">
        <h1>RAIAN VPN</h1>
        <form method="POST">
            <input type="password" name="password" placeholder="كلمة المرور الافتراضية: admin123" required>
            <button type="submit" name="login">دخول للوحة التحكم</button>
        </form>
    </div>
</body>
</html>
<?php
    exit;
}

$data_file = 'servers_data.json';
if (!file_exists($data_file)) {
    $initial_data = ["api_enabled" => true, "show_openvpn" => true, "show_wireguard" => true, "servers" => [], "wireguard_servers" => []];
    file_put_contents($data_file, json_encode($initial_data));
}
$data = json_decode(file_get_contents($data_file), true);

// معالجة الحفظ
if (isset($_POST['save_settings'])) {
    $data['api_enabled'] = isset($_POST['api_enabled']);
    $data['show_openvpn'] = isset($_POST['show_openvpn']);
    $data['show_wireguard'] = isset($_POST['show_wireguard']);
    file_put_contents($data_file, json_encode($data, JSON_PRETTY_PRINT));
    header("Location: index.php?success=1");
}

if (isset($_POST['add_ovpn'])) {
    $new_server = [
        "id" => (string)time(),
        "name" => $_POST['name'],
        "country" => $_POST['country'],
        "flag" => $_POST['flag'],
        "config" => $_POST['config'],
        "username" => $_POST['username'],
        "password" => $_POST['password'],
        "status" => (int)$_POST['status'],
        "server_ip" => $_POST['server_ip'],
        "port" => $_POST['port'],
        "protocol" => $_POST['protocol'],
        "slug" => $_POST['country'] . " " . ($_POST['status'] == 1 ? "Paid" : "Free"),
        "enabled" => true,
        "has_tcp" => $_POST['protocol'] == 'tcp',
        "has_udp" => $_POST['protocol'] == 'udp'
    ];
    $data['servers'][] = $new_server;
    file_put_contents($data_file, json_encode($data, JSON_PRETTY_PRINT));
    header("Location: index.php?success=1");
}

if (isset($_GET['del_ovpn'])) {
    $index = $_GET['del_ovpn'];
    array_splice($data['servers'], $index, 1);
    file_put_contents($data_file, json_encode($data, JSON_PRETTY_PRINT));
    header("Location: index.php?success=1");
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>لوحة تحكم Raian VPN</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700&family=Outfit:wght@500;700&display=swap" rel="stylesheet">
    <style>
        :root { --primary: #38bdf8; --secondary: #818cf8; --bg: #0f172a; --card: #1e293b; }
        body { font-family: 'Cairo', sans-serif; background: var(--bg); color: #f8fafc; margin: 0; padding: 20px; line-height: 1.6; }
        .container { max-width: 1100px; margin: 0 auto; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; padding: 25px; background: var(--card); border-radius: 1.5rem; border: 1px solid rgba(255,255,255,0.05); box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1); }
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 25px; margin-bottom: 30px; }
        .card { background: var(--card); padding: 30px; border-radius: 2rem; border: 1px solid rgba(255,255,255,0.05); box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); position: relative; overflow: hidden; }
        .card::before { content: ""; position: absolute; top: 0; left: 0; width: 100%; height: 4px; background: linear-gradient(90deg, var(--primary), var(--secondary)); }
        h2 { color: var(--primary); margin-top: 0; display: flex; align-items: center; gap: 12px; font-size: 1.4rem; }
        .btn { padding: 12px 24px; border-radius: 12px; border: none; cursor: pointer; font-weight: bold; transition: 0.3s; color: white; text-decoration: none; display: inline-block; text-align: center; }
        .btn-primary { background: linear-gradient(135deg, var(--primary), var(--secondary)); box-shadow: 0 4px 14px rgba(56, 189, 248, 0.4); }
        .btn-danger { background: #ef4444; }
        .btn:hover { transform: translateY(-2px); filter: brightness(1.1); }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th { background: rgba(255,255,255,0.02); color: #94a3b8; font-weight: normal; }
        th, td { padding: 18px; text-align: right; border-bottom: 1px solid rgba(255,255,255,0.05); }
        tr:hover { background: rgba(255,255,255,0.01); }
        .badge { padding: 5px 12px; border-radius: 8px; font-size: 0.8rem; font-weight: bold; }
        .badge-free { background: rgba(16, 185, 129, 0.2); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.3); }
        .badge-pro { background: rgba(245, 158, 11, 0.2); color: #f59e0b; border: 1px solid rgba(245, 158, 11, 0.3); }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; color: #94a3b8; font-size: 0.9rem; }
        input, select { width: 100%; padding: 12px; border-radius: 10px; border: 1px solid #334155; background: #0f172a; color: white; font-size: 0.95rem; box-sizing: border-box; }
        input:focus { border-color: var(--primary); outline: none; box-shadow: 0 0 0 2px rgba(56, 189, 248, 0.2); }
        .switch-group { display: flex; justify-content: space-between; align-items: center; padding: 15px 0; border-bottom: 1px solid rgba(255,255,255,0.05); }
        .toggle { position: relative; display: inline-block; width: 54px; height: 28px; }
        .toggle input { opacity: 0; width: 0; height: 0; }
        .slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #334155; transition: .4s; border-radius: 34px; }
        .slider:before { position: absolute; content: ""; height: 20px; width: 20px; left: 4px; bottom: 4px; background-color: white; transition: .4s; border-radius: 50%; }
        input:checked + .slider { background-color: var(--primary); }
        input:checked + .slider:before { transform: translateX(26px); }
        .api-box { background: #0f172a; padding: 15px; border-radius: 12px; border: 1px dashed var(--primary); color: var(--primary); font-family: monospace; word-break: break-all; margin-top: 10px; position: relative; }
        .success-msg { background: rgba(16, 185, 129, 0.1); color: #10b981; padding: 15px; border-radius: 10px; margin-bottom: 20px; text-align: center; border: 1px solid #10b981; }
    </style>
</head>
<body>
    <div class="container">
        <?php if(isset($_GET['success'])): ?>
            <div class="success-msg">✅ تم حفظ التغييرات بنجاح!</div>
        <?php endif; ?>

        <div class="header">
            <div>
                <h1 style="margin:0; font-size: 1.8rem; font-family: 'Outfit', sans-serif; letter-spacing: 1px;">RAIAN <span style="color:var(--primary)">VPN</span> PANEL</h1>
                <p style="color:#94a3b8; margin:5px 0 0 0;">إدارة خوادمك في الوقت الفعلي</p>
            </div>
            <a href="?logout=1" class="btn btn-danger">تسجيل خروج</a>
        </div>

        <div class="grid">
            <div class="card">
                <h2>⚙️ الإعدادات العامة</h2>
                <form method="POST">
                    <div class="switch-group">
                        <span>تفعيل الـ API للتطبيق</span>
                        <label class="toggle"><input type="checkbox" name="api_enabled" <?php echo $data['api_enabled'] ? 'checked' : ''; ?>><span class="slider"></span></label>
                    </div>
                    <div class="switch-group">
                        <span>إظهار زر OpenVPN</span>
                        <label class="toggle"><input type="checkbox" name="show_openvpn" <?php echo $data['show_openvpn'] ? 'checked' : ''; ?>><span class="slider"></span></label>
                    </div>
                    <div class="switch-group">
                        <span>إظهار زر WireGuard</span>
                        <label class="toggle"><input type="checkbox" name="show_wireguard" <?php echo $data['show_wireguard'] ? 'checked' : ''; ?>><span class="slider"></span></label>
                    </div>
                    <button type="submit" name="save_settings" class="btn btn-primary" style="width:100%; margin-top:30px;">تحديث الإعدادات</button>
                </form>
                
                <div style="margin-top:30px;">
                    <label>رابط الـ API الخاص بك (ضعه في Firebase):</label>
                    <div class="api-box">
                        http://<?php echo $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']); ?>/api.php
                    </div>
                </div>
            </div>

            <div class="card">
                <h2>➕ إضافة خادم OpenVPN جديد</h2>
                <form method="POST">
                    <div class="grid" style="grid-template-columns: 1fr 1fr; gap:15px; margin-bottom:15px;">
                        <div><label>اسم الخادم</label><input type="text" name="name" placeholder="مثلاً: Germany Free" required></div>
                        <div><label>الدولة</label><input type="text" name="country" placeholder="مثلاً: Germany" required></div>
                    </div>
                    <div class="form-group"><label>رابط العلم (URL) أو رمز الدولة (de, us, ...)</label><input type="text" name="flag" placeholder="https://..." required></div>
                    <div class="form-group"><label>رابط ملف الإعداد (.ovpn)</label><input type="text" name="config" placeholder="https://..." required></div>
                    <div class="grid" style="grid-template-columns: 1fr 1fr; gap:15px; margin-bottom:15px;">
                        <div><label>المستخدم</label><input type="text" name="username" placeholder="vpn"></div>
                        <div><label>كلمة المرور</label><input type="text" name="password" placeholder="vpn"></div>
                    </div>
                    <div class="grid" style="grid-template-columns: 1fr 1fr; gap:15px;">
                        <div><label>نوع الحساب</label><select name="status"><option value="0">مجاني (Free)</option><option value="1">مدفوع (Pro)</option></select></div>
                        <div><label>البروتوكول</label><select name="protocol"><option value="udp">UDP</option><option value="tcp">TCP</option></select></div>
                    </div>
                    <div class="grid" style="grid-template-columns: 2fr 1fr; gap:15px; margin-top:15px;">
                        <div><label>عنوان IP</label><input type="text" name="server_ip" placeholder="1.2.3.4"></div>
                        <div><label>المنفذ (Port)</label><input type="text" name="port" placeholder="1194" value="1194"></div>
                    </div>
                    <button type="submit" name="add_ovpn" class="btn btn-primary" style="width:100%; margin-top:25px;">إضافة الخادم الآن</button>
                </form>
            </div>
        </div>

        <div class="card">
            <h2>🖥️ خوادم OpenVPN المفعلة</h2>
            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <th width="50"></th>
                            <th>الاسم</th>
                            <th>الدولة</th>
                            <th>الحالة</th>
                            <th>البروتوكول</th>
                            <th>IP السيرفر</th>
                            <th width="100">التحكم</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($data['servers'])): ?>
                            <tr><td colspan="7" style="text-align:center; color:#475569;">لا توجد سيرفرات مضافة حالياً</td></tr>
                        <?php endif; ?>
                        <?php foreach($data['servers'] as $i => $s): ?>
                        <tr>
                            <td><img src="<?php echo (strlen($s['flag']) == 2) ? "https://flagcdn.com/48x36/".strtolower($s['flag']).".png" : $s['flag']; ?>" width="32" style="border-radius:6px; box-shadow: 0 4px 6px rgba(0,0,0,0.2);"></td>
                            <td style="font-weight:bold;"><?php echo $s['name']; ?></td>
                            <td><?php echo $s['country']; ?></td>
                            <td><span class="badge <?php echo $s['status'] == 1 ? 'badge-pro' : 'badge-free'; ?>"><?php echo $s['status'] == 1 ? 'PREMIUM' : 'FREE'; ?></span></td>
                            <td><code style="color:var(--secondary)"><?php echo strtoupper($s['protocol']); ?></code></td>
                            <td><small><?php echo $s['server_ip']; ?></small></td>
                            <td>
                                <a href="?del_ovpn=<?php echo $i; ?>" class="btn btn-danger" style="padding: 6px 12px; font-size: 0.8rem;" onclick="return confirm('هل أنت متأكد من حذف هذا الخادم؟')">حذف</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <div style="text-align:center; margin-top:50px; color:#475569; font-size:0.9rem;">
            &copy; <?php echo date('Y'); ?> RAIAN VPN CONTROL PANEL - صُمم بكل حب لدعم مشروعك.
        </div>
    </div>
</body>
</html>
