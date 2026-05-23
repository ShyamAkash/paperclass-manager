<?php
require_once __DIR__ . '/auth.php';
requireAdmin();
require_once __DIR__ . '/../db.php';

$db    = getDB();
$page  = 'students';
$error = '';
$flash = getFlash();

// ── Handle POST actions ────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // ADD STUDENT
    if ($action === 'add') {
        $index      = trim($_POST['index_number'] ?? '');
        $name       = trim($_POST['name'] ?? '');
        $batch_year = trim($_POST['batch_year'] ?? '');

        if (!$index || !$name) {
            $error = 'Index number and name are required.';
        } else {
            $stmt = $db->prepare("INSERT INTO students (index_number, name, batch_year) VALUES (?, ?, ?)");
            $batch_year = $batch_year ?: null;
            $stmt->bind_param('sss', $index, $name, $batch_year);
            if ($stmt->execute()) {
                setFlash('success', "Student '{$name}' added successfully.");
                header('Location: students.php'); exit;
            } else {
                $error = $db->errno === 1062
                    ? 'That index number already exists.'
                    : 'Database error: ' . $db->error;
            }
        }
    }

    // DELETE STUDENT
    if ($action === 'delete') {
        $id = (int)($_POST['student_id'] ?? 0);
        if ($id) {
            $db->query("DELETE FROM students WHERE id = $id");
            setFlash('success', 'Student deleted (and all their marks removed).');
            header('Location: students.php'); exit;
        }
    }

    // EDIT STUDENT
    if ($action === 'edit') {
        $id         = (int)($_POST['student_id'] ?? 0);
        $name       = trim($_POST['name'] ?? '');
        $batch_year = trim($_POST['batch_year'] ?? '') ?: null;
        if ($id && $name) {
            $stmt = $db->prepare("UPDATE students SET name=?, batch_year=? WHERE id=?");
            $stmt->bind_param('ssi', $name, $batch_year, $id);
            $stmt->execute();
            setFlash('success', 'Student updated.');
            header('Location: students.php'); exit;
        }
    }
}

// ── Fetch students ─────────────────────────────────────────
$search = trim($_GET['q'] ?? '');
$where  = '';
if ($search) {
    $s    = $db->real_escape_string($search);
    $where = "WHERE s.index_number LIKE '%$s%' OR s.name LIKE '%$s%'";
}

$students = $db->query("
    SELECT s.*, COUNT(m.id) AS mark_count
    FROM students s
    LEFT JOIN marks m ON m.student_id = s.id
    $where
    GROUP BY s.id
    ORDER BY s.batch_year DESC, s.index_number ASC
")->fetch_all(MYSQLI_ASSOC);

// Edit mode
$editStudent = null;
if (isset($_GET['edit'])) {
    $eid = (int)$_GET['edit'];
    $r   = $db->query("SELECT * FROM students WHERE id=$eid")->fetch_assoc();
    if ($r) $editStudent = $r;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Students — Admin</title>
<link rel="stylesheet" href="../assets/style.css">
</head>
<body>
<div class="admin-layout">

  <?php include __DIR__ . '/sidebar.php'; ?>

  <main class="admin-main">
    <div class="admin-topbar">
      <h1>Students</h1>
      <a href="students.php?action=add" class="btn btn-primary btn-sm">+ Add Student</a>
    </div>

    <?php if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] ?>"><?= htmlspecialchars($flash['msg']) ?></div>
    <?php endif; ?>

    <div class="grid-2" style="align-items:start;">

      <!-- ADD / EDIT FORM -->
      <div class="card">
        <div class="card-header">
          <h3><?= $editStudent ? 'Edit Student' : 'Add New Student' ?></h3>
          <?php if ($editStudent): ?><a href="students.php" class="btn btn-outline btn-sm">Cancel</a><?php endif; ?>
        </div>

        <?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

        <form method="POST" autocomplete="off">
          <input type="hidden" name="action"     value="<?= $editStudent ? 'edit' : 'add' ?>">
          <?php if ($editStudent): ?>
          <input type="hidden" name="student_id" value="<?= $editStudent['id'] ?>">
          <?php endif; ?>

          <?php if (!$editStudent): ?>
          <div class="form-group">
            <label>Index Number *</label>
            <input type="text" name="index_number"
                   placeholder="e.g. 2024/CM/001"
                   value="<?= htmlspecialchars($_POST['index_number'] ?? '') ?>"
                   required>
          </div>
          <?php else: ?>
          <div class="form-group">
            <label>Index Number</label>
            <input type="text" value="<?= htmlspecialchars($editStudent['index_number']) ?>" disabled style="opacity:.6;">
          </div>
          <?php endif; ?>

          <div class="form-group">
            <label>Full Name *</label>
            <input type="text" name="name"
                   placeholder="Student full name"
                   value="<?= htmlspecialchars($editStudent['name'] ?? $_POST['name'] ?? '') ?>"
                   required>
          </div>
          <div class="form-group">
            <label>Batch Year <span class="text-muted">(optional)</span></label>
            <input type="text" name="batch_year"
                   placeholder="e.g. 2026"
                   value="<?= htmlspecialchars($editStudent['batch_year'] ?? $_POST['batch_year'] ?? '') ?>">
          </div>
          <button type="submit" class="btn btn-primary btn-full">
            <?= $editStudent ? 'Update Student' : 'Add Student' ?>
          </button>
        </form>
      </div>

      <!-- STUDENTS LIST -->
      <div class="card">
        <div class="card-header">
          <h3>All Students <span class="text-muted" style="font-size:.9rem;">(<?= count($students) ?>)</span></h3>
        </div>

        <!-- SEARCH -->
        <form method="GET" style="margin-bottom:1rem;">
          <div style="display:flex;gap:.5rem;">
            <input type="text" name="q" placeholder="Search by name or index…"
                   value="<?= htmlspecialchars($search) ?>"
                   style="flex:1;background:var(--bg-2);border:1px solid var(--border-l);border-radius:var(--r-sm);color:var(--text);padding:.6rem .9rem;font-family:'DM Sans',sans-serif;outline:none;">
            <button type="submit" class="btn btn-outline btn-sm">Search</button>
            <?php if ($search): ?><a href="students.php" class="btn btn-outline btn-sm">Clear</a><?php endif; ?>
          </div>
        </form>

        <?php if (empty($students)): ?>
          <div class="empty-state"><div class="empty-icon">👤</div><p>No students found.</p></div>
        <?php else: ?>
        <div class="table-wrapper">
          <table>
            <thead>
              <tr><th>Index</th><th>Name</th><th>Batch</th><th>Papers</th><th>Actions</th></tr>
            </thead>
            <tbody>
              <?php foreach ($students as $s): ?>
              <tr <?= ($editStudent && $editStudent['id']==$s['id']) ? 'style="background:var(--gold-glow);"' : '' ?>>
                <td class="fw-600" style="font-family:'Cormorant Garamond',serif;font-size:1rem;">
                  <?= htmlspecialchars($s['index_number']) ?>
                </td>
                <td><?= htmlspecialchars($s['name']) ?></td>
                <td class="muted"><?= htmlspecialchars($s['batch_year'] ?? '—') ?></td>
                <td class="muted"><?= $s['mark_count'] ?></td>
                <td>
                  <div style="display:flex;gap:.4rem;">
                    <a href="students.php?edit=<?= $s['id'] ?>" class="btn btn-outline btn-sm">Edit</a>
                    <form method="POST" onsubmit="return confirm('Delete this student and ALL their marks?');" style="display:inline;">
                      <input type="hidden" name="action"     value="delete">
                      <input type="hidden" name="student_id" value="<?= $s['id'] ?>">
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
