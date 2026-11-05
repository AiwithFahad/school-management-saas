<?php require_once __DIR__ . '/includes/auth.php'; $user=require_role(['school_admin','principal','teacher','accountant','student','parent']); $pdo=db(); check_tenant_active($pdo); $tid=require_tenant(); $base=''; $active='homework'; $pageTitle='Homework';

if(is_post() && in_array($user['role'],['school_admin','principal','teacher'],true)){ verify_csrf();
 if(($_POST['action']??'')==='delete') $pdo->prepare('DELETE FROM homework WHERE id=? AND tenant_id=?')->execute([(int)$_POST['id'],$tid]);
 else $pdo->prepare('INSERT INTO homework(tenant_id,class_id,subject_id,teacher_id,title,description,due_date,created_at) VALUES(?,?,?,?,?,?,?,NOW())')->execute([$tid,(int)$_POST['class_id'],(int)($_POST['subject_id']?:0)?:null,$user['id'],trim($_POST['title']),trim($_POST['description']??''),$_POST['due_date']?:null]);
 flash('success','Homework saved.'); redirect('homework.php'); }
$classes=$pdo->query("SELECT * FROM classes WHERE tenant_id=$tid ORDER BY numeric_name")->fetchAll();
$subjects=$pdo->query("SELECT * FROM subjects WHERE tenant_id=$tid ORDER BY name")->fetchAll();
$classF=(int)($_GET['class_id']??0); $where='h.tenant_id=?'; $args=[$tid]; if($classF){$where.=' AND h.class_id=?';$args[]=$classF;}
$s=$pdo->prepare("SELECT h.*,c.name class_name,sub.name subject_name,u.name teacher_name FROM homework h JOIN classes c ON c.id=h.class_id LEFT JOIN subjects sub ON sub.id=h.subject_id LEFT JOIN users u ON u.id=h.teacher_id WHERE $where ORDER BY h.due_date DESC,h.id DESC"); $s->execute($args); $rows=$s->fetchAll();
include __DIR__ . '/includes/header.php'; ?>
<form class="row g-2 mb-3" method="get"><div class="col-md-4"><select class="form-select" name="class_id"><option value="0">All classes</option><?php foreach($classes as $c): ?><option value="<?= $c['id'] ?>" <?= $classF==$c['id']?'selected':'' ?>><?= e($c['name']) ?></option><?php endforeach; ?></select></div><div class="col-md-2"><button class="btn btn-outline-primary w-100">Filter</button></div></form>
<?php if(in_array($user['role'],['school_admin','principal','teacher'],true)): ?><div class="card p-3 mb-3"><h6>Post homework</h6><form method="post" class="row g-2"><?= csrf_field() ?>
<div class="col-md-3"><select class="form-select" name="class_id" required><?php foreach($classes as $c): ?><option value="<?= $c['id'] ?>"><?= e($c['name']) ?></option><?php endforeach; ?></select></div>
<div class="col-md-3"><select class="form-select" name="subject_id"><option value="">Subject…</option><?php foreach($subjects as $sub): ?><option value="<?= $sub['id'] ?>"><?= e($sub['name']) ?></option><?php endforeach; ?></select></div>
<div class="col-md-4"><input class="form-control" name="title" placeholder="Homework title" required></div>
<div class="col-md-2"><input class="form-control" type="date" name="due_date"></div>
<div class="col-md-10"><input class="form-control" name="description" placeholder="Instructions / details"></div>
<div class="col-md-2"><button class="btn btn-primary w-100">Post</button></div></form></div><?php endif; ?>
<?php foreach($rows as $r): ?><div class="card p-3 mb-2"><div class="d-flex justify-content-between"><strong><?= e($r['title']) ?></strong><span class="badge bg-info">Due <?= e($r['due_date']??'—') ?></span></div>
<div class="small text-muted"><?= e($r['class_name']) ?> · <?= e($r['subject_name']??'') ?> · by <?= e($r['teacher_name']??'') ?></div><p class="mb-0"><?= e($r['description']??'') ?></p>
<?php if(in_array($user['role'],['school_admin','principal','teacher'],true)): ?><form method="post" class="mt-1"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $r['id'] ?>"><button class="btn btn-sm btn-outline-danger">Delete</button></form><?php endif; ?></div><?php endforeach; if(!$rows) echo '<div class="alert alert-info">No homework posted yet.</div>'; ?>
<?php include __DIR__ . '/includes/footer.php'; ?>
