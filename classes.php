<?php require_once __DIR__ . '/includes/auth.php'; $user=require_role(['school_admin','principal','accountant']); $pdo=db(); check_tenant_active($pdo); $tid=require_tenant(); $base=''; $active='classes'; $pageTitle='Classes, Sections & Subjects';

if(is_post()){ verify_csrf(); $a=$_POST['action']??'';
 if($a==='class'){ if(!empty($_POST['id'])) $pdo->prepare('UPDATE classes SET name=?,numeric_name=?,class_teacher_id=? WHERE id=? AND tenant_id=?')->execute([trim($_POST['name']), (int)$_POST['numeric_name'], (int)($_POST['class_teacher_id']?:0)?:null,(int)$_POST['id'],$tid]); else $pdo->prepare('INSERT INTO classes(tenant_id,name,numeric_name,class_teacher_id,created_at) VALUES(?,?,?,?,NOW())')->execute([$tid,trim($_POST['name']),(int)$_POST['numeric_name'],(int)($_POST['class_teacher_id']?:0)?:null]); }
 elseif($a==='class_delete'){ $pdo->prepare('DELETE FROM classes WHERE id=? AND tenant_id=?')->execute([(int)$_POST['id'],$tid]); }
 elseif($a==='section'){ $pdo->prepare('INSERT INTO sections(tenant_id,class_id,name,capacity) VALUES(?,?,?,?) ON DUPLICATE KEY UPDATE capacity=VALUES(capacity)')->execute([$tid,(int)$_POST['class_id'],trim($_POST['name']),(int)($_POST['capacity']??40)]); }
 elseif($a==='section_delete'){ $pdo->prepare('DELETE FROM sections WHERE id=? AND tenant_id=?')->execute([(int)$_POST['id'],$tid]); }
 elseif($a==='subject'){ if(!empty($_POST['id'])) $pdo->prepare('UPDATE subjects SET name=?,code=?,class_id=? WHERE id=? AND tenant_id=?')->execute([trim($_POST['name']),trim($_POST['code']),(int)$_POST['class_id'],(int)$_POST['id'],$tid]); else $pdo->prepare('INSERT INTO subjects(tenant_id,name,code,class_id) VALUES(?,?,?,?)')->execute([$tid,trim($_POST['name']),trim($_POST['code']),(int)($_POST['class_id']?:0)?:null]); }
 elseif($a==='subject_delete'){ $pdo->prepare('DELETE FROM subjects WHERE id=? AND tenant_id=?')->execute([(int)$_POST['id'],$tid]); }
 elseif($a==='session'){ $pdo->prepare('UPDATE academic_sessions SET is_current=0 WHERE tenant_id=?')->execute([$tid]); $pdo->prepare('INSERT INTO academic_sessions(tenant_id,name,start_date,end_date,is_current) VALUES(?,?,?,?,1)')->execute([$tid,trim($_POST['name']),$_POST['start_date']?:null,$_POST['end_date']?:null]); }
 flash('success','Saved.'); redirect('classes.php'); }
$s=$pdo->prepare('SELECT c.*,(SELECT COUNT(*) FROM students s WHERE s.class_id=c.id AND s.tenant_id=c.tenant_id) student_count,t.name teacher_name FROM classes c LEFT JOIN teachers t ON t.id=c.class_teacher_id WHERE c.tenant_id=? ORDER BY c.numeric_name,c.name'); $s->execute([$tid]); $classes=$s->fetchAll();
$sections=$pdo->query("SELECT sec.*,c.name class_name FROM sections sec JOIN classes c ON c.id=sec.class_id WHERE sec.tenant_id=$tid ORDER BY c.numeric_name,sec.name")->fetchAll();
$subjects=$pdo->query("SELECT sub.*,c.name class_name FROM subjects sub LEFT JOIN classes c ON c.id=sub.class_id WHERE sub.tenant_id=$tid ORDER BY sub.name")->fetchAll();
$teachers=$pdo->query("SELECT * FROM teachers WHERE tenant_id=$tid ORDER BY name")->fetchAll();
$sessions=$pdo->query("SELECT * FROM academic_sessions WHERE tenant_id=$tid ORDER BY id DESC")->fetchAll();
$edit=null; if(!empty($_GET['edit'])){$s=$pdo->prepare('SELECT * FROM classes WHERE id=? AND tenant_id=?');$s->execute([(int)$_GET['edit'],$tid]);$edit=$s->fetch();}
include __DIR__ . '/includes/header.php'; ?>
<div class="row g-3">
<div class="col-lg-6"><div class="card p-3"><h6><?= $edit?'Edit':'Add' ?> class</h6><form method="post" class="row g-2"><?= csrf_field() ?><input type="hidden" name="action" value="class"><input type="hidden" name="id" value="<?= e($edit['id']??'') ?>">
<div class="col-md-5"><input class="form-control" name="name" placeholder="Class name e.g. Grade 5" value="<?= e($edit['name']??'') ?>" required></div>
<div class="col-md-3"><input class="form-control" type="number" name="numeric_name" placeholder="Level" value="<?= e($edit['numeric_name']??'') ?>"></div>
<div class="col-md-4"><select class="form-select" name="class_teacher_id"><option value="">Class teacher…</option><?php foreach($teachers as $t): ?><option value="<?= $t['id'] ?>" <?= ($edit['class_teacher_id']??'')==$t['id']?'selected':'' ?>><?= e($t['name']) ?></option><?php endforeach; ?></select></div>
<div class="col-md-12"><button class="btn btn-primary">Save class</button></div></form>
<table class="table mt-3"><tr><th>Class</th><th>Students</th><th>Class teacher</th><th></th></tr><?php foreach($classes as $c): ?><tr><td><?= e($c['name']) ?></td><td><?= $c['student_count'] ?></td><td><?= e($c['teacher_name']??'') ?></td><td><a class="btn btn-sm btn-outline-primary" href="?edit=<?= $c['id'] ?>">Edit</a> <form method="post" class="d-inline"><?= csrf_field() ?><input type="hidden" name="action" value="class_delete"><input type="hidden" name="id" value="<?= $c['id'] ?>"><button class="btn btn-sm btn-outline-danger" data-confirm="Delete class?">Delete</button></form></td></tr><?php endforeach; ?></table></div>
<div class="card p-3 mt-3"><h6>Sections</h6><form method="post" class="row g-2"><?= csrf_field() ?><input type="hidden" name="action" value="section">
<div class="col-md-5"><select class="form-select" name="class_id" required><?php foreach($classes as $c): ?><option value="<?= $c['id'] ?>"><?= e($c['name']) ?></option><?php endforeach; ?></select></div>
<div class="col-md-3"><input class="form-control" name="name" placeholder="Section e.g. A" required></div>
<div class="col-md-2"><input class="form-control" type="number" name="capacity" placeholder="Cap." value="40"></div>
<div class="col-md-2"><button class="btn btn-primary w-100">Add</button></div></form>
<table class="table mt-2"><tr><th>Class</th><th>Section</th><th>Capacity</th><th></th></tr><?php foreach($sections as $sec): ?><tr><td><?= e($sec['class_name']) ?></td><td><?= e($sec['name']) ?></td><td><?= $sec['capacity'] ?></td><td><form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="section_delete"><input type="hidden" name="id" value="<?= $sec['id'] ?>"><button class="btn btn-sm btn-outline-danger">Delete</button></form></td></tr><?php endforeach; ?></table></div></div>
<div class="col-lg-6"><div class="card p-3"><h6>Subjects</h6><form method="post" class="row g-2"><?= csrf_field() ?><input type="hidden" name="action" value="subject">
<div class="col-md-4"><input class="form-control" name="name" placeholder="Subject name" required></div>
<div class="col-md-3"><input class="form-control" name="code" placeholder="Code"></div>
<div class="col-md-5"><select class="form-select" name="class_id"><option value="">All classes</option><?php foreach($classes as $c): ?><option value="<?= $c['id'] ?>"><?= e($c['name']) ?></option><?php endforeach; ?></select></div>
<div class="col-md-12"><button class="btn btn-primary">Add subject</button></div></form>
<table class="table mt-2"><tr><th>Subject</th><th>Code</th><th>Class</th><th></th></tr><?php foreach($subjects as $sub): ?><tr><td><?= e($sub['name']) ?></td><td><?= e($sub['code']??'') ?></td><td><?= e($sub['class_name']??'All') ?></td><td><form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="subject_delete"><input type="hidden" name="id" value="<?= $sub['id'] ?>"><button class="btn btn-sm btn-outline-danger">Delete</button></form></td></tr><?php endforeach; ?></table></div>
<div class="card p-3 mt-3"><h6>Academic sessions</h6><form method="post" class="row g-2"><?= csrf_field() ?><input type="hidden" name="action" value="session">
<div class="col-md-4"><input class="form-control" name="name" placeholder="e.g. 2026-27" required></div><div class="col-md-3"><input class="form-control" type="date" name="start_date"></div><div class="col-md-3"><input class="form-control" type="date" name="end_date"></div><div class="col-md-2"><button class="btn btn-primary w-100">Set current</button></div></form>
<ul class="small mt-2 mb-0"><?php foreach($sessions as $ss): ?><li><?= e($ss['name']) ?> <?= $ss['is_current']?'<span class="badge bg-success">current</span>':'' ?></li><?php endforeach; ?></ul></div></div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
