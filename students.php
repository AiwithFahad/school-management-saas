<?php require_once __DIR__ . '/includes/auth.php'; $user=require_role(['school_admin','principal','teacher','accountant']); $pdo=db(); check_tenant_active($pdo); $tid=require_tenant(); $base=''; $active='students'; $pageTitle='Students';

if(isset($_GET['export'])){ $s=$pdo->prepare('SELECT admission_no,first_name,last_name,gender,date_of_birth,category,guardian_name,guardian_phone,status,created_at FROM students WHERE tenant_id=? ORDER BY id'); $s->execute([$tid]); export_csv($s->fetchAll(),'students.csv'); }
if(is_post()){ verify_csrf(); $a=$_POST['action']??'save';
 if($a==='delete'){ $pdo->prepare('DELETE FROM students WHERE id=? AND tenant_id=?')->execute([(int)$_POST['id'],$tid]); flash('success','Student deleted.'); }
 else {
   if(empty($_POST['id'])){ $lim=subscription_limits($pdo); $c=$pdo->prepare('SELECT COUNT(*) FROM students WHERE tenant_id=?'); $c->execute([$tid]); if((int)$c->fetchColumn()>=(int)($lim['max_students']??100)){ flash('error','Plan student limit reached ('.($lim['max_students']??100).'). Upgrade your plan.'); redirect('students.php'); } }
   $d=[trim($_POST['admission_no']??''),trim($_POST['first_name']??''),trim($_POST['last_name']??''),$_POST['gender']??'male',$_POST['date_of_birth']?:null,(int)($_POST['class_id']?:0)?:null,(int)($_POST['section_id']?:0)?:null,trim($_POST['category']??'general'),trim($_POST['guardian_name']??''),trim($_POST['guardian_phone']??''),trim($_POST['guardian_email']??''),trim($_POST['address']??''),$_POST['status']??'active'];
   if(!empty($_POST['id'])) $pdo->prepare('UPDATE students SET admission_no=?,first_name=?,last_name=?,gender=?,date_of_birth=?,class_id=?,section_id=?,category=?,guardian_name=?,guardian_phone=?,guardian_email=?,address=?,status=? WHERE id=? AND tenant_id=?')->execute([...$d,(int)$_POST['id'],$tid]);
   else { $pdo->prepare('INSERT INTO students(tenant_id,admission_no,first_name,last_name,gender,date_of_birth,class_id,section_id,category,guardian_name,guardian_phone,guardian_email,address,status,created_at) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,NOW())')->execute([$tid,...$d]); log_activity($pdo,'Student admitted: '.$d[1].' '.$d[2]); }
   audit_log($pdo,'student_save','students'); flash('success','Student saved.'); }
 redirect('students.php'); }
$q=trim($_GET['q']??''); $classF=(int)($_GET['class_id']??0); $statusF=$_GET['status']??'';
$where='s.tenant_id=?'; $args=[$tid]; if($q){$where.=' AND (s.first_name LIKE ? OR s.last_name LIKE ? OR s.admission_no LIKE ?)'; $args[]="%$q%";$args[]="%$q%";$args[]="%$q%";} if($classF){$where.=' AND s.class_id=?';$args[]=$classF;} if($statusF){$where.=' AND s.status=?';$args[]=$statusF;}
$s=$pdo->prepare("SELECT COUNT(*) FROM students s WHERE $where"); $s->execute($args); [$page,$per,$off,$pages]=paginate((int)$s->fetchColumn());
$s=$pdo->prepare("SELECT s.*,c.name class_name,sec.name section_name FROM students s LEFT JOIN classes c ON c.id=s.class_id LEFT JOIN sections sec ON sec.id=s.section_id WHERE $where ORDER BY s.id DESC LIMIT $per OFFSET $off"); $s->execute($args); $rows=$s->fetchAll();
$classes=$pdo->query("SELECT * FROM classes WHERE tenant_id=$tid ORDER BY name")->fetchAll();
$sections=$pdo->query("SELECT * FROM sections WHERE tenant_id=$tid ORDER BY name")->fetchAll();
$edit=null; if(!empty($_GET['edit'])){$s=$pdo->prepare('SELECT * FROM students WHERE id=? AND tenant_id=?');$s->execute([(int)$_GET['edit'],$tid]);$edit=$s->fetch();}
include __DIR__ . '/includes/header.php'; ?>
<form class="row g-2 mb-3" method="get"><div class="col-md-4"><input class="form-control" name="q" value="<?= e($q) ?>" placeholder="Search name / admission no"></div>
<div class="col-md-3"><select class="form-select" name="class_id"><option value="0">All classes</option><?php foreach($classes as $c): ?><option value="<?= $c['id'] ?>" <?= $classF==$c['id']?'selected':'' ?>><?= e($c['name']) ?></option><?php endforeach; ?></select></div>
<div class="col-md-2"><select class="form-select" name="status"><option value="">Any status</option><?php foreach(['active','transferred','graduated','suspended'] as $st): ?><option <?= $statusF===$st?'selected':'' ?>><?= $st ?></option><?php endforeach; ?></select></div>
<div class="col-md-3"><button class="btn btn-outline-primary">Filter</button> <a class="btn btn-outline-success" href="?export=1">CSV Export</a></div></form>
<div class="card p-3 mb-3"><h6><?= $edit?'Edit':'Admit' ?> student</h6><form method="post" class="row g-2"><?= csrf_field() ?><input type="hidden" name="id" value="<?= e($edit['id']??'') ?>">
<?php foreach([['admission_no','Admission No'],['first_name','First name'],['last_name','Last name']] as [$f,$l]): ?><div class="col-md-3"><input class="form-control" name="<?= $f ?>" placeholder="<?= $l ?>" value="<?= e($edit[$f]??'') ?>" required></div><?php endforeach; ?>
<div class="col-md-3"><select class="form-select" name="gender"><?php foreach(['male','female','other'] as $g): ?><option <?= ($edit['gender']??'')===$g?'selected':'' ?>><?= $g ?></option><?php endforeach; ?></select></div>
<div class="col-md-3"><input class="form-control" type="date" name="date_of_birth" value="<?= e($edit['date_of_birth']??'') ?>"></div>
<div class="col-md-3"><select class="form-select" name="class_id"><option value="">Class…</option><?php foreach($classes as $c): ?><option value="<?= $c['id'] ?>" <?= ($edit['class_id']??'')==$c['id']?'selected':'' ?>><?= e($c['name']) ?></option><?php endforeach; ?></select></div>
<div class="col-md-3"><select class="form-select" name="section_id"><option value="">Section…</option><?php foreach($sections as $sec): ?><option value="<?= $sec['id'] ?>" <?= ($edit['section_id']??'')==$sec['id']?'selected':'' ?>><?= e($sec['name']) ?> (class <?= $sec['class_id'] ?>)</option><?php endforeach; ?></select></div>
<div class="col-md-3"><input class="form-control" name="category" placeholder="Category" value="<?= e($edit['category']??'general') ?>"></div>
<div class="col-md-3"><input class="form-control" name="guardian_name" placeholder="Guardian name" value="<?= e($edit['guardian_name']??'') ?>"></div>
<div class="col-md-3"><input class="form-control" name="guardian_phone" placeholder="Guardian phone" value="<?= e($edit['guardian_phone']??'') ?>"></div>
<div class="col-md-3"><input class="form-control" name="guardian_email" placeholder="Guardian email" value="<?= e($edit['guardian_email']??'') ?>"></div>
<div class="col-md-3"><select class="form-select" name="status"><?php foreach(['active','transferred','graduated','suspended'] as $st): ?><option <?= ($edit['status']??'active')===$st?'selected':'' ?>><?= $st ?></option><?php endforeach; ?></select></div>
<div class="col-md-6"><input class="form-control" name="address" placeholder="Address" value="<?= e($edit['address']??'') ?>"></div>
<div class="col-md-3"><button class="btn btn-primary w-100"><?= $edit?'Update':'Admit' ?> student</button></div></form></div>
<table class="table card"><tr><th>Adm No</th><th>Name</th><th>Class</th><th>Guardian</th><th>Category</th><th>Status</th><th></th></tr>
<?php foreach($rows as $r): ?><tr><td><?= e($r['admission_no']) ?></td><td><?= e($r['first_name'].' '.$r['last_name']) ?></td><td><?= e($r['class_name']??'') ?> <?= e($r['section_name']??'') ?></td><td><?= e($r['guardian_name']??'') ?><br><small><?= e($r['guardian_phone']??'') ?></small></td><td><?= e($r['category']) ?></td><td><span class="badge bg-secondary"><?= e($r['status']) ?></span></td>
<td><a class="btn btn-sm btn-outline-primary" href="?edit=<?= $r['id'] ?>">Edit</a> <form method="post" class="d-inline"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $r['id'] ?>"><button class="btn btn-sm btn-outline-danger" data-confirm="Delete student?">Delete</button></form></td></tr><?php endforeach; ?></table>
<nav><?php for($i=1;$i<=$pages;$i++): ?><a class="btn btn-sm <?= $i==$page?'btn-primary':'btn-outline-secondary' ?>" href="?page=<?= $i ?>&q=<?= e($q) ?>"><?= $i ?></a> <?php endfor; ?></nav>
<?php include __DIR__ . '/includes/footer.php'; ?>
