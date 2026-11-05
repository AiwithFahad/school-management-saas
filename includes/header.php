<?php $b = $base ?? ''; ?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= e($pageTitle ?? APP_NAME) ?> | <?= e(APP_NAME) ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="<?= $b ?>assets/css/style.css" rel="stylesheet">
<script>try{document.documentElement.setAttribute('data-theme',localStorage.getItem('edu-theme')||'light');}catch(e){}</script></head>
<body><div class="layout"><?php include __DIR__ . '/sidebar.php'; ?><main class="main">
<header class="topbar"><div class="d-flex align-items-center gap-2">
<button class="icon-btn" id="sidebarToggle" type="button" title="Collapse sidebar" aria-label="Collapse sidebar"><i class="bi bi-layout-sidebar-inset"></i></button>
<h1 class="h4 mb-0"><?= e($pageTitle ?? '') ?></h1></div>
<div class="d-flex align-items-center gap-2 flex-wrap">
<form class="search-wrap" method="get" action="<?= $b ?>students.php" role="search"><i class="bi bi-search"></i><input class="form-control" type="search" name="q" placeholder="Search students…" aria-label="Search students"></form>
<div class="dropdown"><button class="icon-btn" type="button" data-bs-toggle="dropdown" aria-label="Notifications"><i class="bi bi-bell"></i><span class="dot"></span></button>
<ul class="dropdown-menu dropdown-menu-end">
<li><a class="dropdown-item" href="<?= $b ?>attendance.php"><i class="bi bi-calendar-check text-success me-2"></i>Mark today's attendance</a></li>
<li><a class="dropdown-item" href="<?= $b ?>fees.php?status=unpaid"><i class="bi bi-cash-stack text-warning me-2"></i>Review pending fees</a></li>
<li><a class="dropdown-item" href="<?= $b ?>reports.php"><i class="bi bi-bar-chart text-primary me-2"></i>View latest reports</a></li>
</ul></div>
<button class="icon-btn" id="themeToggle" type="button" title="Toggle light / dark theme" aria-label="Toggle theme"><i class="bi bi-moon"></i></button>
<?php $__nm = trim((string)(current_user()['name'] ?? 'U')); $__ini = ''; foreach (explode(' ', $__nm) as $__p) { if ($__p !== '') $__ini .= strtoupper($__p[0]); if (strlen($__ini) >= 2) break; } ?>
<span class="avatar-circle" title="<?= e($__nm) ?>"><?= e($__ini) ?></span>
</div></header>
<?php if ($m = flash('success')): ?><div class="alert alert-success"><?= e($m) ?></div><?php endif; ?>
<?php if ($m = flash('error')): ?><div class="alert alert-danger"><?= e($m) ?></div><?php endif; ?>
