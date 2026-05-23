<?php
// ── Admin session guard ────────────────────────────────────
// Include this at the top of every admin page.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function requireAdmin(): void {
    if (empty($_SESSION['admin_id'])) {
        header('Location: ' . __DIR__ . '/../admin/login.php');
        // Relative redirect fallback
        header('Location: login.php');
        exit;
    }
}

function adminLoggedIn(): bool {
    return !empty($_SESSION['admin_id']);
}

// ── Flash message helpers ──────────────────────────────────
function setFlash(string $type, string $msg): void {
    $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
}

function getFlash(): ?array {
    if (!empty($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}
