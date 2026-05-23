<?php
/**
 * PAPER CLASS RESULTS SYSTEM — INSTALLER
 * =======================================
 * Run this once after uploading files to your server.
 * DELETE this file immediately after setup is complete!
 */

$step    = (int)($_POST['step'] ?? 1);
$message = '';
$success = false;

// ── Step 2: Check DB connection ───────────────────────────
$dbOk = false;
if (file_exists(__DIR__ . '/db.php')) {
    $dbConfig = file_get_contents(__DIR__ . '/db.php');
    // Check if user has edited the placeholders
    if (strpos($dbConfig, 'YOUR_DB_USERNAME') !== false) {
        $dbOk = false;
        $dbNote = '⚠ You have not edited db.php yet. Fill in your database credentials first.';
    } else {
        require_once __DIR__ . '/db.php';
        try {
            $db   = getDB();
            $dbOk = true;
            $dbNote = '✓ Database connection successful.';
        } catch (Exception $e) {
            $dbOk   = false;
            $dbNote = '✗ Connection failed: ' . $e->getMessage();
        }
    }
} else {
    $dbNote = '✗ db.php not found.';
}

// ── Step 3: Create tables + admin ─────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $step === 3 && $dbOk) {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password']         ?? '';
    $confirm  = $_POST['confirm']          ?? '';

    if (!$username || !$password) {
        $message = 'Username and password are required.';
    } elseif (strlen($password) < 6) {
        $message = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirm) {
        $message = 'Passwords do not match.';
    } else {
        // Create tables from setup.sql
        $sql   = file_get_contents(__DIR__ . '/setup.sql');
        $stmts = array_filter(array_map('trim', explode(';', $sql)));
        $errs  = [];
        foreach ($stmts as $s) {
            if (!empty($s) && stripos($s, '--') !== 0) {
                if (!$db->query($s)) $errs[] = $db->error;
            }
        }

        if (!empty($errs)) {
            $message = 'Table creation errors: ' . implode(', ', $errs);
        } else {
            // Create admin
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $db->prepare("INSERT INTO admins (username, password_hash) VALUES (?, ?) ON DUPLICATE KEY UPDATE password_hash = VALUES(password_hash)");
            $stmt->bind_param('ss', $username, $hash);
            if ($stmt->execute()) {
                $success = true;
                $message = 'Setup complete!';
            } else {
                $message = 'Could not create admin: ' . $db->error;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Install — Paper Class Results</title>
<link rel="stylesheet" href="assets/style.css">
<style>
  .install-page { min-height:100vh; display:flex; align-items:center; justify-content:center; padding:2rem; }
  .install-box { background:var(--bg-card); border:1px solid var(--border); border-radius:var(--r); padding:2.5rem; width:100%; max-width:520px; box-shadow:var(--shadow); }
  .step-indicator { display:flex; gap:.5rem; margin-bottom:2rem; }
  .step { flex:1; height:4px; border-radius:2px; background:var(--border); }
  .step.done { background:var(--green); }
  .step.active { background:var(--gold); }
  .db-status { padding:.75rem 1rem; border-radius:var(--r-sm); font-size:.88rem; margin-bottom:1.5rem; }
  .db-ok  { background:rgba(77,189,140,.1); color:var(--green); border:1px solid rgba(77,189,140,.3); }
  .db-err { background:rgba(224,90,106,.1); color:var(--red);   border:1px solid rgba(224,90,106,.3); }
  .db-warn{ background:rgba(212,168,67,.1); color:var(--gold);  border:1px solid rgba(212,168,67,.3); }
  .success-box { text-align:center; }
  .success-icon { font-size:3rem; margin-bottom:1rem; }
  ul.checklist { list-style:none; padding:0; margin:1rem 0; }
  ul.checklist li { padding:.3rem 0; font-size:.88rem; color:var(--text-2); }
  ul.checklist li::before { content:'✓ '; color:var(--green); font-weight:700; }
</style>
</head>
<body>
<div class="install-page">
  <div class="install-box">

    <h2 style="margin-bottom:.4rem;">📦 System Setup</h2>
    <p class="text-muted" style="font-size:.88rem;margin-bottom:1.5rem;">Paper Class Results Portal — First-time installation</p>

    <div class="step-indicator">
      <div class="step done"></div>
      <div class="step <?= $dbOk ? 'done' : 'active' ?>"></div>
      <div class="step <?= $success ? 'done' : ($dbOk ? 'active' : '') ?>"></div>
    </div>

    <?php if ($success): ?>
    <!-- SUCCESS -->
    <div class="success-box">
      <div class="success-icon">🎉</div>
      <h3 style="color:var(--green);margin-bottom:.75rem;">Setup Complete!</h3>
      <ul class="checklist">
        <li>Database tables created</li>
        <li>Admin account configured</li>
        <li>System ready for use</li>
      </ul>
      <div class="alert alert-error" style="text-align:left;margin:1.5rem 0;">
        <strong>⚠ SECURITY:</strong> Delete <code>install.php</code> from your server NOW via FTP/file manager before continuing!
      </div>
      <div style="display:flex;gap:.75rem;flex-wrap:wrap;justify-content:center;">
        <a href="admin/login.php" class="btn btn-primary">→ Go to Admin</a>
        <a href="index.php"       class="btn btn-outline">Student Portal</a>
      </div>
    </div>

    <?php else: ?>

    <!-- DB STATUS -->
    <div class="db-status <?= $dbOk ? 'db-ok' : (isset($dbNote) && strpos($dbNote,'⚠')!==false ? 'db-warn' : 'db-err') ?>">
      <?= $dbNote ?? '' ?>
    </div>

    <?php if (!$dbOk): ?>
    <!-- DB NOT READY -->
    <h3 style="margin-bottom:.75rem;">Step 1: Configure Database</h3>
    <p class="text-muted" style="font-size:.88rem;margin-bottom:1rem;">
      Edit <code>db.php</code> and fill in your InfinityFree MySQL credentials, then refresh this page.
    </p>
    <div style="background:var(--bg-2);border:1px solid var(--border-l);border-radius:var(--r-sm);padding:1rem;font-size:.82rem;color:var(--text-2);font-family:monospace;">
      define('DB_HOST', '<strong style="color:var(--gold)">localhost</strong>');<br>
      define('DB_USER', '<strong style="color:var(--gold)">your_username</strong>');<br>
      define('DB_PASS', '<strong style="color:var(--gold)">your_password</strong>');<br>
      define('DB_NAME', '<strong style="color:var(--gold)">your_db_name</strong>');
    </div>
    <p class="text-muted" style="font-size:.8rem;margin-top:1rem;">
      Find these in your InfinityFree cPanel → MySQL Databases.
    </p>
    <button onclick="location.reload()" class="btn btn-primary btn-full" style="margin-top:1.5rem;">
      🔄 Re-check Connection
    </button>

    <?php else: ?>
    <!-- CREATE ADMIN -->
    <h3 style="margin-bottom:.25rem;">Step 2: Create Admin Account</h3>
    <p class="text-muted" style="font-size:.88rem;margin-bottom:1.25rem;">
      This will create all database tables and your admin login.
    </p>

    <?php if ($message): ?>
    <div class="alert alert-error"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <form method="POST">
      <input type="hidden" name="step" value="3">
      <div class="form-group">
        <label>Admin Username</label>
        <input type="text" name="username" placeholder="admin"
               value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required>
      </div>
      <div class="form-group">
        <label>Password</label>
        <input type="password" name="password" placeholder="Min. 6 characters" required>
      </div>
      <div class="form-group">
        <label>Confirm Password</label>
        <input type="password" name="confirm" placeholder="Repeat password" required>
      </div>
      <button type="submit" class="btn btn-primary btn-full">🚀 Install & Create Admin</button>
    </form>
    <?php endif; ?>

    <?php endif; ?>

  </div>
</div>
</body>
</html>
