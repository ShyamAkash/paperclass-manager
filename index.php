<?php
// Show any startup config errors early
if (!file_exists(__DIR__ . '/db.php')) {
    die('db.php not found');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Results Portal — Paper Class</title>
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
      <a href="index.php" class="active">Results</a>
    </div>
  </div>
</nav>

<!-- HERO -->
<main>
  <section class="hero">
    <div class="math-symbols">∑∫θ</div>
    <div class="container">
      <div class="hero-badge">G.C.E. Advanced Level Results</div>
      <h1>Check Your<br><em>Paper Results</em></h1>
      <p class="hero-sub">Enter your unique index number to view your marks, z-scores, and class ranks across all papers.</p>

      <!-- SEARCH FORM -->
      <div class="search-card">
        <h3>View My Results</h3>
        <p class="hint">Enter your index number as provided by your class teacher.</p>
        <form method="GET" action="results.php" autocomplete="off">
          <div class="form-group">
            <label for="index_number">Index Number</label>
            <input type="text" id="index_number" name="index"
                   placeholder="e.g. 2024/CM/001"
                   required
                   autocomplete="off"
                   style="font-size:1rem;font-family:'Cormorant Garamond',serif;letter-spacing:.04em;">
          </div>
          <button type="submit" class="btn btn-primary btn-full" style="font-size:1rem;padding:.9rem;">
            View Results →
          </button>
        </form>
      </div>
    </div>
  </section>

  <!-- INFO STRIP -->
  <section class="section" style="padding-top:0;">
    <div class="container">
      <div class="stats-row" style="max-width:700px;margin:0 auto;">
        <div class="stat-card">
          <div class="stat-value">Z</div>
          <div class="stat-label">Z-Score Per Paper</div>
        </div>
        <div class="stat-card">
          <div class="stat-value">#</div>
          <div class="stat-label">Class Rank</div>
        </div>
        <div class="stat-card">
          <div class="stat-value">%</div>
          <div class="stat-label">Percentage</div>
        </div>
        <div class="stat-card">
          <div class="stat-value">📋</div>
          <div class="stat-label">All Papers</div>
        </div>
      </div>
    </div>
  </section>
</main>

<footer>
  <div class="container">
    <p>© <?= date('Y') ?> <span>Paper Class</span> — Results Portal. Students only.</p>
  </div>
</footer>

</body>
</html>
