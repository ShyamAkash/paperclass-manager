<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../db.php';

// Already logged in
if (adminLoggedIn()) {
    header('Location: index.php'); exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username && $password) {
        $db = getDB();
        $stmt = $db->prepare("SELECT id, password_hash FROM admins WHERE username = ? LIMIT 1");
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();

        if ($row && password_verify($password, $row['password_hash'])) {
            $_SESSION['admin_id']   = $row['id'];
            $_SESSION['admin_user'] = $username;
            header('Location: index.php'); exit;
        } else {
            $error = 'Invalid username or password.';
        }
    } else {
        $error = 'Please enter both username and password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Admin Login — Paper Class</title>
<link rel="stylesheet" href="../assets/style.css">
</head>
<body>
<div class="login-page">
  <div class="login-box">
    <div class="login-title">
      <div class="logo">🔐</div>
      <h2>Admin Portal</h2>
      <p>Sign in to manage students & results</p>
    </div>

    <?php if ($error): ?>
    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" autocomplete="off">
      <div class="form-group">
        <label for="username">Username</label>
        <input type="text" id="username" name="username"
               placeholder="admin"
               value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
               required autocomplete="off">
      </div>
      <div class="form-group">
        <label for="password">Password</label>
        <input type="password" id="password" name="password"
               placeholder="••••••••"
               required autocomplete="new-password">
      </div>
      <button type="submit" class="btn btn-primary btn-full" style="margin-top:.5rem;">
        Sign In →
      </button>
    </form>

    <p style="text-align:center;margin-top:1.5rem;font-size:.8rem;">
      <a href="../index.php" class="text-muted" style="color:var(--text-3);">← Back to Student Portal</a>
    </p>
  </div>
</div>
</body>
</html>
