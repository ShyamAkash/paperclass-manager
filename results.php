<?php
require_once __DIR__ . '/db.php';

$index_number = trim($_GET['index'] ?? '');
$error = '';
$student = null;
$results = [];

if (empty($index_number)) {
    header('Location: index.php');
    exit;
}

$db = getDB();

// ── Fetch student ─────────────────────────────────────────
$stmt = $db->prepare("SELECT id, index_number, name, batch_year FROM students WHERE index_number = ?");
$stmt->bind_param('s', $index_number);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) {
    $error = 'No student found with index number <strong>' . htmlspecialchars($index_number) . '</strong>. Please check and try again.';
} else {
    $student = $res->fetch_assoc();

    // ── Fetch all marks for this student with paper details ───
    $sid = $student['id'];
    // Per-paper stats pre-aggregated in a derived table (GROUP BY paper_id)
    // so each paper gets its own mean/stddev regardless of MySQL version.
    $sql = "
        SELECT
            p.id              AS paper_id,
            p.paper_number,
            p.paper_name,
            p.paper_date,
            p.total_marks,
            p.batch_year,
            m.marks           AS student_marks,
            ps.mean_marks,
            ps.stddev_marks,
            ps.total_students,
            (SELECT COUNT(*) + 1
             FROM marks m2
             WHERE m2.paper_id = m.paper_id
               AND m2.marks > m.marks) AS rank_val
        FROM marks m
        INNER JOIN papers p ON p.id = m.paper_id
        INNER JOIN (
            SELECT
                paper_id,
                AVG(marks)         AS mean_marks,
                STDDEV_SAMP(marks) AS stddev_marks,
                COUNT(*)           AS total_students
            FROM marks
            GROUP BY paper_id
        ) ps ON ps.paper_id = m.paper_id
        WHERE m.student_id = ?
        ORDER BY p.paper_date DESC, p.paper_number ASC
    ";
    $stmt2 = $db->prepare($sql);
    $stmt2->bind_param('i', $sid);
    $stmt2->execute();
    $results = $stmt2->get_result()->fetch_all(MYSQLI_ASSOC);
}

// ── Helper: z-score ───────────────────────────────────────
function calcZScore($marks, $mean, $stddev): string {
    if ($stddev === null || $stddev === '' || floatval($stddev) == 0) return 'N/A';
    $z = (floatval($marks) - floatval($mean)) / floatval($stddev);
    return number_format($z, 3);
}

// ── Helper: percentage class ──────────────────────────────
function scoreClass($pct): string {
    if ($pct >= 75) return 'score-high';
    if ($pct >= 50) return 'score-mid';
    return 'score-low';
}

// ── Overall summary stats ──────────────────────────────────
$totalPapers = count($results);
$avgPct  = 0;
$avgRank = 0;
if ($totalPapers > 0) {
    foreach ($results as $r) {
        $avgPct  += ($r['student_marks'] / $r['total_marks']) * 100;
        $avgRank += $r['rank_val'];
    }
    $avgPct  = round($avgPct  / $totalPapers, 1);
    $avgRank = round($avgRank / $totalPapers, 1);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= $student ? htmlspecialchars($student['name']) . ' — Results' : 'Results' ?></title>
<link rel="stylesheet" href="assets/style.css">
</head>
<body class="page">

<!-- NAV -->
<nav>
  <div class="container nav-inner">
    <div class="nav-brand">
      <div class="nav-logo">∑</div>
      Paper<span>Class</span>
    </div>
    <div class="nav-links">
      <a href="index.php">← New Search</a>
    </div>
  </div>
</nav>

<main class="section">
<div class="container">

<?php if ($error): ?>
  <div style="max-width:520px;margin:3rem auto;text-align:center;">
    <div style="font-size:3rem;margin-bottom:1rem;opacity:.5;">🔍</div>
    <h2 style="margin-bottom:.75rem;">Student Not Found</h2>
    <p class="text-muted" style="margin-bottom:1.5rem;"><?= $error ?></p>
    <a href="index.php" class="btn btn-primary">Try Again</a>
  </div>

<?php else: ?>

  <!-- STUDENT CARD -->
  <div class="student-card">
    <div class="student-avatar">
      <?= strtoupper(mb_substr($student['name'], 0, 1)) ?>
    </div>
    <div class="student-info">
      <h2><?= htmlspecialchars($student['name']) ?></h2>
      <div class="meta">
        <span class="meta-tag">📋 <?= htmlspecialchars($student['index_number']) ?></span>
        <?php if ($student['batch_year']): ?>
        <span class="meta-tag">📅 <?= htmlspecialchars($student['batch_year']) ?></span>
        <?php endif; ?>
        <span class="meta-tag">📄 <?= $totalPapers ?> Paper<?= $totalPapers !== 1 ? 's' : '' ?></span>
      </div>
    </div>
  </div>

  <?php if ($totalPapers === 0): ?>
    <div class="empty-state">
      <div class="empty-icon">📝</div>
      <p>No marks have been entered for your index number yet.<br>Please check back after the teacher enters results.</p>
    </div>

  <?php else: ?>

    <!-- SUMMARY STATS -->
    <div class="stats-row" style="margin-bottom:2.5rem;">
      <div class="stat-card">
        <div class="stat-value"><?= $totalPapers ?></div>
        <div class="stat-label">Papers Taken</div>
      </div>
      <div class="stat-card">
        <div class="stat-value"><?= $avgPct ?>%</div>
        <div class="stat-label">Avg Percentage</div>
      </div>
      <div class="stat-card">
        <div class="stat-value">#<?= $avgRank ?></div>
        <div class="stat-label">Avg Class Rank</div>
      </div>
    </div>

    <!-- TABLE VIEW -->
    <div class="section-title">
      <h3>All Paper Results</h3>
    </div>

    <div class="table-wrapper" style="margin-bottom:2rem;">
      <table>
        <thead>
          <tr>
            <th>Paper</th>
            <th>Date</th>
            <th>Marks</th>
            <th>Percentage</th>
            <th>Z-Score</th>
            <th>Rank</th>
            <th>Students</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($results as $r):
            $pct  = round(($r['student_marks'] / $r['total_marks']) * 100, 1);
            $z    = calcZScore($r['student_marks'], $r['mean_marks'], $r['stddev_marks']);
            $rank = $r['rank_val'];
            $rankClass = $rank == 1 ? 'rank-1' : ($rank == 2 ? 'rank-2' : ($rank == 3 ? 'rank-3' : 'rank-other'));
          ?>
          <tr>
            <td>
              <span class="fw-600"><?= htmlspecialchars($r['paper_number']) ?></span>
              <?php if ($r['paper_name']): ?>
              <br><span class="text-muted" style="font-size:.8rem;"><?= htmlspecialchars($r['paper_name']) ?></span>
              <?php endif; ?>
            </td>
            <td class="muted">
              <?= $r['paper_date'] ? date('d M Y', strtotime($r['paper_date'])) : '—' ?>
            </td>
            <td>
              <span class="fw-600"><?= number_format($r['student_marks'], 1) ?></span>
              <span class="text-muted"> / <?= number_format($r['total_marks'], 0) ?></span>
            </td>
            <td>
              <span class="score-pill <?= scoreClass($pct) ?>"><?= $pct ?>%</span>
            </td>
            <td class="<?= is_numeric($z) ? ($z >= 0 ? 'text-green' : 'text-red') : 'text-muted' ?>">
              <span class="fw-600"><?= $z ?></span>
            </td>
            <td>
              <span class="rank-badge <?= $rankClass ?>"><?= $rank ?></span>
            </td>
            <td class="muted"><?= $r['total_students'] ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <!-- DETAILED CARDS -->
    <div class="section-title" style="margin-top:2.5rem;">
      <h3>Detailed View</h3>
    </div>

    <?php foreach ($results as $r):
      $pct  = round(($r['student_marks'] / $r['total_marks']) * 100, 1);
      $z    = calcZScore($r['student_marks'], $r['mean_marks'], $r['stddev_marks']);
      $rank = $r['rank_val'];
      $rankClass = $rank == 1 ? 'rank-1' : ($rank == 2 ? 'rank-2' : ($rank == 3 ? 'rank-3' : 'rank-other'));
    ?>
    <div class="result-block">
      <div class="result-header">
        <div>
          <h4><?= htmlspecialchars($r['paper_number']) ?><?= $r['paper_name'] ? ' — ' . htmlspecialchars($r['paper_name']) : '' ?></h4>
          <div class="paper-meta">
            <?= $r['paper_date'] ? date('d M Y', strtotime($r['paper_date'])) : '' ?>
            <?= $r['batch_year'] ? ' · ' . htmlspecialchars($r['batch_year']) : '' ?>
            · <?= $r['total_students'] ?> students sat
          </div>
        </div>
        <span class="rank-badge <?= $rankClass ?>" style="width:40px;height:40px;font-size:1rem;"><?= $rank ?></span>
      </div>
      <div class="result-metrics">
        <div class="metric-cell">
          <div class="m-label">Marks</div>
          <div class="m-value"><?= number_format($r['student_marks'], 1) ?></div>
          <div class="m-sub">out of <?= number_format($r['total_marks'], 0) ?></div>
        </div>
        <div class="metric-cell">
          <div class="m-label">Percentage</div>
          <div class="m-value <?= $pct >= 75 ? 'green' : ($pct >= 50 ? 'gold' : '') ?>"><?= $pct ?>%</div>
          <div class="m-sub"><?= $pct >= 75 ? 'Distinction' : ($pct >= 50 ? 'Pass' : 'Below Pass') ?></div>
        </div>
        <div class="metric-cell">
          <div class="m-label">Z-Score</div>
          <div class="m-value <?= is_numeric($z) ? ($z >= 0 ? 'green' : '') : '' ?>"><?= $z ?></div>
          <div class="m-sub">
            <?php if (is_numeric($z)):
              if ($z >= 1.5)      echo 'Excellent';
              elseif ($z >= 0.5)  echo 'Above Average';
              elseif ($z >= -0.5) echo 'Average';
              elseif ($z >= -1.5) echo 'Below Average';
              else                echo 'Needs Improvement';
            else: echo '—'; endif; ?>
          </div>
        </div>
        <div class="metric-cell">
          <div class="m-label">Class Rank</div>
          <div class="m-value gold">#<?= $rank ?></div>
          <div class="m-sub">of <?= $r['total_students'] ?></div>
        </div>
        <div class="metric-cell">
          <div class="m-label">Class Mean</div>
          <div class="m-value" style="font-size:1.5rem;"><?= $r['mean_marks'] !== null ? number_format($r['mean_marks'], 1) : '—' ?></div>
          <div class="m-sub">average score</div>
        </div>
      </div>
    </div>
    <?php endforeach; ?>

  <?php endif; ?>
<?php endif; ?>

</div>
</main>

<footer>
  <div class="container">
    <p>© <?= date('Y') ?> <span>Paper Class</span> — Results Portal. For queries contact your class teacher.</p>
  </div>
</footer>

</body>
</html>
