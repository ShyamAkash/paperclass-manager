<?php
require_once __DIR__ . '/auth.php';
requireAdmin();
require_once __DIR__ . '/../db.php';

$db    = getDB();
$page  = 'papers';
$error = '';
$flash = getFlash();

// ── Handle POST ────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // ADD
    if ($action === 'add') {
        $pnum  = trim($_POST['paper_number'] ?? '');
        $pname = trim($_POST['paper_name']   ?? '') ?: null;
        $pdate = trim($_POST['paper_date']   ?? '') ?: null;
        $total = floatval($_POST['total_marks'] ?? 100);
        $batch = trim($_POST['batch_year']   ?? '') ?: null;

        if (!$pnum) {
            $error = 'Paper number is required.';
        } else {
            $stmt = $db->prepare("INSERT INTO papers (paper_number,paper_name,paper_date,total_marks,batch_year) VALUES (?,?,?,?,?)");
            $stmt->bind_param('sssds', $pnum, $pname, $pdate, $total, $batch);
            if ($stmt->execute()) {
                setFlash('success', "Paper '{$pnum}' added.");
                header('Location: papers.php'); exit;
            } else {
                $error = 'DB error: ' . $db->error;
            }
        }
    }

    // DELETE
    if ($action === 'delete') {
        $id = (int)($_POST['paper_id'] ?? 0);
        if ($id) {
            $db->query("DELETE FROM papers WHERE id=$id");
            setFlash('success', 'Paper deleted (and all marks for it removed).');
            header('Location: papers.php'); exit;
        }
    }

    // EDIT
    if ($action === 'edit') {
        $id    = (int)($_POST['paper_id'] ?? 0);
        $pnum  = trim($_POST['paper_number'] ?? '');
        $pname = trim($_POST['paper_name']   ?? '') ?: null;
        $pdate = trim($_POST['paper_date']   ?? '') ?: null;
        $total = floatval($_POST['total_marks'] ?? 100);
        $batch = trim($_POST['batch_year']   ?? '') ?: null;
        if ($id && $pnum) {
            $stmt = $db->prepare("UPDATE papers SET paper_number=?,paper_name=?,paper_date=?,total_marks=?,batch_year=? WHERE id=?");
            $stmt->bind_param('sssdsi', $pnum, $pname, $pdate, $total, $batch, $id);
            $stmt->execute();
            setFlash('success', 'Paper updated.');
            header('Location: papers.php'); exit;
        }
    }
}

// ── Fetch papers ───────────────────────────────────────────
$papers = $db->query("
    SELECT p.*, COUNT(m.id) AS mark_count
    FROM papers p
    LEFT JOIN marks m ON m.paper_id = p.id
    GROUP BY p.id
    ORDER BY p.paper_date DESC, p.paper_number ASC
")->fetch_all(MYSQLI_ASSOC);

$editPaper = null;
if (isset($_GET['edit'])) {
    $eid = (int)$_GET['edit'];
    $r   = $db->query("SELECT * FROM papers WHERE id=$eid")->fetch_assoc();
    if ($r) $editPaper = $r;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Papers — Admin</title>
<link rel="stylesheet" href="../assets/style.css">
</head>
<body>
<div class="admin-layout">

  <?php include __DIR__ . '/sidebar.php'; ?>

  <main class="admin-main">
    <div class="admin-topbar">
      <h1>Papers</h1>
      <a href="marks.php" class="btn btn-primary btn-sm">✏️ Manage Marks</a>
    </div>

    <?php if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] ?>"><?= htmlspecialchars($flash['msg']) ?></div>
    <?php endif; ?>

    <div class="grid-2" style="align-items:start;">

      <!-- ADD / EDIT FORM -->
      <div class="card">
        <div class="card-header">
          <h3><?= $editPaper ? 'Edit Paper' : 'Add New Paper' ?></h3>
          <?php if ($editPaper): ?><a href="papers.php" class="btn btn-outline btn-sm">Cancel</a><?php endif; ?>
        </div>

        <?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

        <form method="POST">
          <input type="hidden" name="action"   value="<?= $editPaper ? 'edit' : 'add' ?>">
          <?php if ($editPaper): ?>
          <input type="hidden" name="paper_id" value="<?= $editPaper['id'] ?>">
          <?php endif; ?>

          <div class="form-group">
            <label>Paper Number *</label>
            <input type="text" name="paper_number"
                   placeholder="e.g. Paper 01 / P1-2024"
                   value="<?= htmlspecialchars($editPaper['paper_number'] ?? $_POST['paper_number'] ?? '') ?>"
                   required>
          </div>
          <div class="form-group">
            <label>Paper Name <span class="text-muted">(optional)</span></label>
            <input type="text" name="paper_name"
                   placeholder="e.g. Pure Mathematics Mock I"
                   value="<?= htmlspecialchars($editPaper['paper_name'] ?? $_POST['paper_name'] ?? '') ?>">
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>Paper Date</label>
              <input type="date" name="paper_date"
                     value="<?= htmlspecialchars($editPaper['paper_date'] ?? $_POST['paper_date'] ?? '') ?>">
            </div>
            <div class="form-group">
              <label>Total Marks</label>
              <input type="number" name="total_marks" step="0.5" min="1"
                     placeholder="100"
                     value="<?= htmlspecialchars($editPaper['total_marks'] ?? $_POST['total_marks'] ?? '100') ?>">
            </div>
          </div>
          <div class="form-group">
            <label>Batch Year <span class="text-muted">(optional)</span></label>
            <input type="text" name="batch_year"
                   placeholder="e.g. 2026"
                   value="<?= htmlspecialchars($editPaper['batch_year'] ?? $_POST['batch_year'] ?? '') ?>">
          </div>

          <button type="submit" class="btn btn-primary btn-full">
            <?= $editPaper ? 'Update Paper' : 'Add Paper' ?>
          </button>
        </form>
      </div>

      <!-- PAPERS LIST -->
      <div class="card">
        <div class="card-header">
          <h3>All Papers <span class="text-muted" style="font-size:.9rem;">(<?= count($papers) ?>)</span></h3>
        </div>

        <?php if (empty($papers)): ?>
          <div class="empty-state"><div class="empty-icon">📄</div><p>No papers added yet.</p></div>
        <?php else: ?>
        <div class="table-wrapper">
          <table>
            <thead>
              <tr><th>Paper #</th><th>Name</th><th>Date</th><th>Total</th><th>Entries</th><th>Actions</th></tr>
            </thead>
            <tbody>
              <?php foreach ($papers as $p): ?>
              <tr <?= ($editPaper && $editPaper['id']==$p['id']) ? 'style="background:var(--gold-glow);"' : '' ?>>
                <td><span class="paper-tag"><?= htmlspecialchars($p['paper_number']) ?></span></td>
                <td class="muted" style="font-size:.85rem;"><?= htmlspecialchars($p['paper_name'] ?? '—') ?></td>
                <td class="muted" style="font-size:.82rem;">
                  <?= $p['paper_date'] ? date('d M Y', strtotime($p['paper_date'])) : '—' ?>
                </td>
                <td class="muted"><?= $p['total_marks'] ?></td>
                <td class="muted"><?= $p['mark_count'] ?></td>
                <td>
                  <div style="display:flex;gap:.4rem;">
                    <a href="papers.php?edit=<?= $p['id'] ?>" class="btn btn-outline btn-sm">Edit</a>
                    <a href="marks.php?paper_id=<?= $p['id'] ?>" class="btn btn-outline btn-sm">Marks</a>
                    <form method="POST" onsubmit="return confirm('Delete paper + all marks?');" style="display:inline;">
                      <input type="hidden" name="action"   value="delete">
                      <input type="hidden" name="paper_id" value="<?= $p['id'] ?>">
                      <button type="submit" class="btn btn-danger btn-sm">Del</button>
                    </form>
                  </div>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php endif; ?>
      </div>

    </div>
  </main>
</div>
</body>
</html>
