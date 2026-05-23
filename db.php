<?php
// ============================================================
//  DATABASE CONFIGURATION  — edit these four values only
// ============================================================
define('DB_HOST', 'localhost');
define('DB_USER', 'YOUR_DB_USERNAME');
define('DB_PASS', 'YOUR_DB_PASSWORD');
define('DB_NAME', 'YOUR_DB_NAME');
// ============================================================

function getDB(): mysqli {
    static $conn = null;
    if ($conn === null) {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($conn->connect_error) {
            http_response_code(500);
            die('
<!DOCTYPE html><html><head><meta charset="utf-8">
<title>DB Error</title>
<style>body{background:#07080e;color:#e05a6a;font-family:sans-serif;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0;}
.box{text-align:center;padding:2rem;border:1px solid #e05a6a30;border-radius:12px;max-width:480px;}
h2{margin-bottom:.5rem;}p{color:#8890a8;font-size:.9rem;}</style></head>
<body><div class="box"><h2>⚠ Database Connection Failed</h2>
<p>' . htmlspecialchars($conn->connect_error) . '</p>
<p style="margin-top:1rem">Check your <code>db.php</code> credentials.</p></div></body></html>');
        }
        $conn->set_charset('utf8mb4');
    }
    return $conn;
}
