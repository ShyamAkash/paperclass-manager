<aside class="sidebar" id="sidebar">
  <div class="sidebar-brand">
    Paper<span>Class</span> <span style="font-size:.75rem;color:var(--text-3);font-family:'DM Sans',sans-serif;font-weight:400;margin-left:.25rem;">Admin</span>
  </div>
  <nav class="sidebar-nav">
    <a href="index.php"    class="<?= ($page??'')==='dashboard' ?'active':'' ?>">
      <span class="nav-icon">📊</span> Dashboard
    </a>
    <a href="students.php" class="<?= ($page??'')==='students'  ?'active':'' ?>">
      <span class="nav-icon">👤</span> Students
    </a>
    <a href="papers.php"   class="<?= ($page??'')==='papers'    ?'active':'' ?>">
      <span class="nav-icon">📄</span> Papers
    </a>
    <a href="marks.php"    class="<?= ($page??'')==='marks'     ?'active':'' ?>">
      <span class="nav-icon">✏️</span>  Marks
    </a>
    <hr style="border-color:var(--border);margin:.75rem 1rem;">
    <a href="../index.php" target="_blank">
      <span class="nav-icon">↗</span> Student Portal
    </a>
  </nav>
  <div class="sidebar-footer">
    <a href="logout.php">⬅ Sign Out</a>
  </div>
</aside>
