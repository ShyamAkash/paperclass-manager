<?php
require_once __DIR__ . '/auth.php';
requireAdmin();
require_once __DIR__ . '/../db.php';

$db    = getDB();
$page  = 'marks';
$flash = getFlash();

// ── All papers for selector ─────────────────────────────────
$papers = $db->query("
    SELECT p.*, COUNT(m.id) AS mark_count
    FROM papers p
    LEFT JOIN marks m ON m.paper_id = p.id
    GROUP BY p.id
    ORDER BY p.paper_date DESC, p.paper_number ASC
")->fetch_all(MYSQLI_ASSOC);

// Selected paper
$paper_id    = (int)($_GET['paper_id'] ?? $_POST['paper_id'] ?? 0);
$selPaper    = null;
$allStudents = [];
$marksMap    = [];

if ($paper_id) {
    $selPaper = $db->query("SELECT * FROM papers WHERE id=$paper_id")->fetch_assoc();
}

// ── SAVE MARKS ──────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_marks'])) {
    $pid    = (int)($_POST['paper_id'] ?? 0);
    $mdata  = $_POST['marks'] ?? []; // marks[student_id] = value
    $saved  = 0;
    $errors = [];

    foreach ($mdata as $sid => $val) {
        $sid = (int)$sid;
        $val = trim($val);
        if ($val === '' || $val === null) {
            // Delete existing mark if blank
            $db->query("DELETE FROM marks WHERE student_id=$sid AND paper_id=$pid");
            continue;
        }
        if (!is_numeric($val) || floatval($val) < 0) {
            $errors[] = "Invalid mark for student ID $sid";
            continue;
        }
        $markVal = floatval($val);
        // Validate against total
        $paperTotal = $selPaper ? floatval($selPaper['total_marks']) : 9999;
        if ($markVal > $paperTotal) {
            $errors[] = "Mark $markVal exceeds total $paperTotal for student ID $sid";
            continue;
        }
        $stmt = $db->prepare("
            INSERT INTO marks (student_id, paper_id, marks)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE marks = VALUES(marks)
        ");
        $stmt->bind_param('iid', $sid, $pid, $markVal);
        if ($stmt->execute()) $saved++;
        else $errors[] = $db->error;
    }

    if (empty($errors)) {
        setFlash('success', "$saved mark(s) saved for paper.");
    } else {
        setFlash('error', 'Some errors: ' . implode('; ', $errors));
    }
    header("Location: marks.php?paper_id=$pid"); exit;
}

// ── LOAD STUDENTS + EXISTING MARKS for selected paper ───────
if ($selPaper) {
    $allStudents = $db->query("
        SELECT id, index_number, name, batch_year
        FROM students
        ORDER BY batch_year DESC, index_number ASC
    ")->fetch_all(MYSQLI_ASSOC);

    $existingMarks = $db->query("
        SELECT student_id, marks FROM marks WHERE paper_id=$paper_id
    ")->fetch_all(MYSQLI_ASSOC);

    foreach ($existingMarks as $em) {
        $marksMap[$em['student_id']] = $em['marks'];
    }

    // ── Compute z-scores & ranks for display ─────────────────
    $markValues = array_values($marksMap);
    $n          = count($markValues);
    $mean       = $n > 0 ? array_sum($markValues) / $n : 0;
    $variance   = 0;
    if ($n > 1) {
        foreach ($markValues as $v) $variance += (floatval($v) - $mean) ** 2;
        $variance /= ($n - 1);          // sample variance (N-1), not population (N)
    }
    $stddev = ($n > 1) ? sqrt($variance) : 0;
}

function renderZScore($marks, $mean, $stddev): string {
    if ($stddev === null || floatval($stddev) == 0) return '<span class="text-muted">N/A</span>';
    $z   = (floatval($marks) - floatval($mean)) / floatval($stddev);
    $cls = $z >= 0 ? 'text-green' : 'text-red';
    return "<span class='$cls fw-600'>" . number_format($z, 3) . "</span>";
}

function renderRank(int $sid, array $marksMap): string {
    if (!isset($marksMap[$sid])) return '<span class="text-muted">—</span>';
    $myMark = $marksMap[$sid];
    $rank   = 1;
    foreach ($marksMap as $osid => $oval) {
        if ($osid !== $sid && $oval > $myMark) $rank++;
    }
    $cls = $rank == 1 ? 'rank-1' : ($rank == 2 ? 'rank-2' : ($rank == 3 ? 'rank-3' : 'rank-other'));
    return "<span class='rank-badge $cls'>$rank</span>";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Marks — Admin</title>
<link rel="stylesheet" href="../assets/style.css">
<style>
  .paper-selector { display:flex; flex-wrap:wrap; gap:.6rem; margin-bottom:2rem; }
  .paper-btn {
    background: var(--bg-card); border: 1px solid var(--border-l);
    border-radius: var(--r-sm); padding: .55rem 1rem;
    color: var(--text-2); font-family:'DM Sans',sans-serif;
    font-size:.85rem; font-weight:500; cursor:pointer;
    text-decoration:none; transition: all var(--transition);
    display:flex; flex-direction:column; align-items:flex-start; gap:.15rem;
  }
  .paper-btn:hover { border-color: var(--gold); color: var(--gold); }
  .paper-btn.selected { border-color: var(--gold); background: var(--gold-glow); color: var(--gold); }
  .paper-btn small { font-size:.72rem; color:var(--text-3); }
  .marks-table-header { display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem;flex-wrap:wrap;gap:.5rem; }
  .fill-bar { height:4px;background:var(--border);border-radius:2px;margin-top:.3rem; }
  .fill-bar span { display:block;height:100%;background:var(--gold);border-radius:2px;transition:width .3s; }
</style>
</head>
<body>
<div class="admin-layout">

  <?php include __DIR__ . '/sidebar.php'; ?>

  <main class="admin-main">
    <div class="admin-topbar">
      <h1>Marks</h1>
      <a href="papers.php" class="btn btn-outline btn-sm">+ Add Paper</a>
    </div>

    <?php if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] ?>"><?= htmlspecialchars($flash['msg']) ?></div>
    <?php endif; ?>

    <!-- PAPER SELECTOR -->
    <div class="card" style="margin-bottom:1.5rem;">
      <div class="card-header"><h3>Select a Paper</h3></div>
      <?php if (empty($papers)): ?>
        <div class="empty-state"><div class="empty-icon">📄</div>
          <p>No papers yet. <a href="papers.php">Add a paper first.</a></p></div>
      <?php else: ?>
      <div class="paper-selector">
        <?php foreach ($papers as $p): ?>
        <a href="marks.php?paper_id=<?= $p['id'] ?>"
           class="paper-btn <?= $paper_id == $p['id'] ? 'selected' : '' ?>">
          <span><?= htmlspecialchars($p['paper_number']) ?></span>
          <small>
            <?= $p['paper_name'] ? htmlspecialchars($p['paper_name']) . ' · ' : '' ?>
            <?= $p['mark_count'] ?> entries
            <?= $p['paper_date'] ? ' · ' . date('d M Y', strtotime($p['paper_date'])) : '' ?>
          </small>
        </a>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>

    <!-- MARKS ENTRY TABLE -->
    <?php if ($selPaper): ?>
    <div class="card">
      <div class="marks-table-header">
        <div>
          <h3>
            <?= htmlspecialchars($selPaper['paper_number']) ?>
            <?= $selPaper['paper_name'] ? '— ' . htmlspecialchars($selPaper['paper_name']) : '' ?>
          </h3>
          <span class="text-muted" style="font-size:.83rem;">
            Total marks: <?= $selPaper['total_marks'] ?>
            <?= $selPaper['paper_date'] ? ' · ' . date('d M Y', strtotime($selPaper['paper_date'])) : '' ?>
          </span>
          <div class="fill-bar" style="width:200px;">
            <span style="width:<?= $n > 0 ? round(($n/max(count($allStudents),1))*100) : 0 ?>%"></span>
          </div>
          <span class="text-muted" style="font-size:.78rem;"><?= $n ?> / <?= count($allStudents) ?> students have marks</span>
        </div>
        <div style="display:flex;gap:.5rem;flex-wrap:wrap;">
          <button onclick="clearBlanks()" class="btn btn-outline btn-sm">Hide Empty</button>
          <button form="marks-form" type="submit" class="btn btn-primary btn-sm">💾 Save All Marks</button>
        </div>
      </div>

      <?php if (empty($allStudents)): ?>
        <div class="empty-state"><div class="empty-icon">👤</div>
          <p>No students yet. <a href="students.php">Add students first.</a></p></div>
      <?php else: ?>
      <form method="POST" id="marks-form">
        <input type="hidden" name="paper_id" value="<?= $selPaper['id'] ?>">
        <input type="hidden" name="save_marks" value="1">

        <div class="table-wrapper">
          <table id="marks-table">
            <thead>
              <tr>
                <th>#</th>
                <th>Index</th>
                <th>Name</th>
                <th>Batch</th>
                <th>Marks <span class="text-muted">(/ <?= $selPaper['total_marks'] ?>)</span></th>
                <th>%</th>
                <th>Z-Score</th>
                <th>Rank</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($allStudents as $i => $s):
                $sid      = $s['id'];
                $existing = $marksMap[$sid] ?? '';
                $pct      = $existing !== '' ? round(($existing / $selPaper['total_marks']) * 100, 1) : '';
              ?>
              <tr data-has-mark="<?= $existing !== '' ? '1' : '0' ?>">
                <td class="muted" style="font-size:.8rem;"><?= $i+1 ?></td>
                <td style="font-family:'Cormorant Garamond',serif;font-size:.95rem;">
                  <?= htmlspecialchars($s['index_number']) ?>
                </td>
                <td class="fw-600"><?= htmlspecialchars($s['name']) ?></td>
                <td class="muted"><?= htmlspecialchars($s['batch_year'] ?? '—') ?></td>
                <td>
                  <input type="number"
                         class="marks-input mark-field"
                         name="marks[<?= $sid ?>]"
                         min="0"
                         max="<?= $selPaper['total_marks'] ?>"
                         step="0.5"
                         value="<?= $existing !== '' ? htmlspecialchars($existing) : '' ?>"
                         placeholder="—"
                         data-sid="<?= $sid ?>"
                         data-total="<?= $selPaper['total_marks'] ?>"
                         oninput="updateRow(this)">
                </td>
                <td class="pct-cell muted">
                  <?= $pct !== '' ? "<span class='score-pill " . ($pct>=75?'score-high':($pct>=50?'score-mid':'score-low')) . "'>$pct%</span>" : '—' ?>
                </td>
                <td class="zscore-cell">
                  <?= $existing !== '' ? renderZScore($existing, $mean, $stddev) : '<span class="text-muted">—</span>' ?>
                </td>
                <td class="rank-cell">
                  <?= $existing !== '' ? renderRank($sid, $marksMap) : '<span class="text-muted">—</span>' ?>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

        <div style="display:flex;justify-content:flex-end;margin-top:1.5rem;gap:.75rem;">
          <button type="submit" class="btn btn-primary">💾 Save All Marks</button>
        </div>
      </form>

      <p class="text-muted" style="font-size:.78rem;margin-top:.75rem;">
        💡 Leave a field blank to remove/skip that student's mark. Z-scores and ranks shown here are a live preview based on currently saved data.
      </p>
      <?php endif; ?>
    </div>

    <?php elseif (!empty($papers)): ?>
    <div class="empty-state">
      <div class="empty-icon">📊</div>
      <p>Select a paper above to enter or edit marks.</p>
    </div>
    <?php endif; ?>

  </main>
</div>

<script>
// Live percentage update (z-score and rank require server-side recalc on save)
function updateRow(input) {
  const tr    = input.closest('tr');
  const total = parseFloat(input.dataset.total) || 100;
  const val   = parseFloat(input.value);
  const pctCell = tr.querySelector('.pct-cell');

  tr.dataset.hasMark = input.value !== '' ? '1' : '0';

  if (!isNaN(val) && input.value !== '') {
    const pct = (val / total * 100).toFixed(1);
    const cls = pct >= 75 ? 'score-high' : (pct >= 50 ? 'score-mid' : 'score-low');
    pctCell.innerHTML = `<span class="score-pill ${cls}">${pct}%</span>`;
  } else {
    pctCell.innerHTML = '—';
  }
}

function clearBlanks() {
  const rows = document.querySelectorAll('#marks-table tbody tr');
  rows.forEach(r => {
    r.style.display = r.dataset.hasMark === '0' ? 'none' : '';
  });
}
</script>

</body>
</html>
