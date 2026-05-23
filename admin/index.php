<?php
require_once __DIR__ . '/auth.php';
requireAdmin();
require_once __DIR__ . '/../db.php';

$db = getDB();

// ── Stats ─────────────────────────────────────────────────
$totalStudents = $db->query("SELECT COUNT(*) FROM students")->fetch_row()[0];
$totalPapers   = $db->query("SELECT COUNT(*) FROM papers")->fetch_row()[0];
$totalMarks    = $db->query("SELECT COUNT(*) FROM marks")->fetch_row()[0];

// ── Recent marks entries ───────────────────────────────────
$recent = $db->query("
    SELECT s.name, s.index_number, p.paper_number, m.marks, p.total_marks, m.id
    FROM marks m
    INNER JOIN students s ON s.id = m.student_id
    INNER JOIN papers   p ON p.id = m.paper_id
    ORDER BY m.id DESC LIMIT 8
")->fetch_all(MYSQLI_ASSOC);

$flash = getFlash();
$page = 'dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Dashboard — Admin</title>
<link rel="stylesheet" href="../assets/style.css">
</head>
<body>
<div class="admin-layout">

  <?php include __DIR__ . '/sidebar.php'; ?>

  <main class="admin-main">
    <div class="admin-topbar">
      <h1>Dashboard</h1>
      <span class="text-muted" style="font-size:.85rem;">Welcome, <strong><?= htmlspecialchars($_SESSION['admin_user']) ?></strong></span>
    </div>

    <?php if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] ?>"><?= htmlspecialchars($flash['msg']) ?></div>
    <?php endif; ?>

    <!-- STATS -->
    <div class="stats-row">
      <div class="stat-card">
        <div class="stat-value"><?= $totalStudents ?></div>
        <div class="stat-label">Total Students</div>
      </div>
      <div class="stat-card">
        <div class="stat-value"><?= $totalPapers ?></div>
        <div class="stat-label">Papers</div>
      </div>
      <div class="stat-card">
        <div class="stat-value"><?= $totalMarks ?></div>
        <div class="stat-label">Marks Entries</div>
      </div>
    </div>

    <!-- QUICK ACTIONS -->
    <div class="card" style="margin-bottom:2rem;">
      <div class="card-header"><h3>Quick Actions</h3></div>
      <div style="display:flex;gap:.75rem;flex-wrap:wrap;">
        <a href="students.php?action=add" class="btn btn-primary btn-sm">+ Add Student</a>
        <a href="papers.php?action=add"   class="btn btn-primary btn-sm">+ Add Paper</a>
        <a href="marks.php"               class="btn btn-outline btn-sm">📊 Manage Marks</a>
        <a href="../index.php" target="_blank" class="btn btn-outline btn-sm">↗ Student Portal</a>
      </div>
    </div>

    <!-- RECENT ENTRIES -->
    <div class="card">
      <div class="card-header">
        <h3>Recent Marks Entries</h3>
        <a href="marks.php" class="btn btn-outline btn-sm">View All</a>
      </div>
      <?php if (empty($recent)): ?>
        <div class="empty-state"><div class="empty-icon">📝</div><p>No marks entered yet.</p></div>
      <?php else: ?>
      <div class="table-wrapper">
        <table>
          <thead>
            <tr><th>Student</th><th>Index</th><th>Paper</th><th>Marks</th><th>%</th></tr>
          </thead>
          <tbody>
            <?php foreach ($recent as $r):
              $pct = round(($r['marks'] / $r['total_marks']) * 100, 1);
            ?>
            <tr>
              <td><?= htmlspecialchars($r['name']) ?></td>
              <td class="muted"><?= htmlspecialchars($r['index_number']) ?></td>
              <td><span class="paper-tag"><?= htmlspecialchars($r['paper_number']) ?></span></td>
              <td class="fw-600"><?= $r['marks'] ?> / <?= $r['total_marks'] ?></td>
              <td><span class="score-pill <?= $pct>=75?'score-high':($pct>=50?'score-mid':'score-low') ?>"><?= $pct ?>%</span></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>
    </div>

  </main>
</div>
</body>
</html>
