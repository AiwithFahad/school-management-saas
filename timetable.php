<?php require_once __DIR__ . '/includes/auth.php'; $user=require_role(['school_admin','principal','teacher','accountant']); $pdo=db(); check_tenant_active($pdo); $tid=require_tenant(); $base=''; $active='timetable'; $pageTitle='Timetable';

$classes=$pdo->query("SELECT * FROM classes WHERE tenant_id=$tid ORDER BY numeric_name")->fetchAll();
if(is_post()){ verify_csrf();
 if(($_POST['action']??'')==='delete') $pdo->prepare('DELETE FROM timetable WHERE id=? AND tenant_id=?')->execute([(int)$_POST['id'],$tid]);
 else $pdo->prepare('INSERT INTO timetable(tenant_id,class_id,subject_id,teacher_id,day_of_week,period_no,start_time,end_time,room) VALUES(?,?,?,?,?,?,?,?,?)')->execute([$tid,(int)$_POST['class_id'],(int)($_POST['subject_id']?:0)?:null,(int)($_POST['teacher_id']?:0)?:null,(int)$_POST['day_of_week'],(int)$_POST['period_no'],$_POST['start_time']?:null,$_POST['end_time']?:null,trim($_POST['room']??'')]);
 flash('success','Timetable saved.'); redirect('timetable.php?class_id='.(int)($_POST['class_id']??0)); }
$classId=(int)($_GET['class_id']??($classes[0]['id']??0));
$subjects=$pdo->query("SELECT * FROM subjects WHERE tenant_id=$tid ORDER BY name")->fetchAll();
$teachers=$pdo->query("SELECT * FROM teachers WHERE tenant_id=$tid ORDER BY name")->fetchAll();
$s=$pdo->prepare('SELECT t.*,sub.name subject_name,te.name teacher_name FROM timetable t LEFT JOIN subjects sub ON sub.id=t.subject_id LEFT JOIN teachers te ON te.id=t.teacher_id WHERE t.tenant_id=? AND t.class_id=? ORDER BY t.period_no'); $s->execute([$tid,$classId]); $entries=$s->fetchAll();
$grid=[]; foreach($entries as $en) $grid[$en['period_no']][$en['day_of_week']]=$en;
$days=[1=>'Mon',2=>'Tue',3=>'Wed',4=>'Thu',5=>'Fri',6=>'Sat'];
include __DIR__ . '/includes/header.php'; ?>
<form class="row g-2 mb-3" method="get"><div class="col-md-4"><select class="form-select" name="class_id"><?php foreach($classes as $c): ?><option value="<?= $c['id'] ?>" <?= $classId==$c['id']?'selected':'' ?>><?= e($c['name']) ?></option><?php endforeach; ?></select></div><div class="col-md-2"><button class="btn btn-outline-primary w-100">View</button></div></form>
<div class="card p-3 mb-3"><h6>Add period</h6><form method="post" class="row g-2"><?= csrf_field() ?><input type="hidden" name="class_id" value="<?= $classId ?>">
<div class="col-md-2"><select class="form-select" name="day_of_week"><?php foreach($days as $n=>$d): ?><option value="<?= $n ?>"><?= $d ?></option><?php endforeach; ?></select></div>
<div class="col-md-1"><input class="form-control" type="number" name="period_no" placeholder="Period" value="1" required></div>
<div class="col-md-3"><select class="form-select" name="subject_id"><option value="">Subject…</option><?php foreach($subjects as $sub): ?><option value="<?= $sub['id'] ?>"><?= e($sub['name']) ?></option><?php endforeach; ?></select></div>
<div class="col-md-2"><select class="form-select" name="teacher_id"><option value="">Teacher…</option><?php foreach($teachers as $t): ?><option value="<?= $t['id'] ?>"><?= e($t['name']) ?></option><?php endforeach; ?></select></div>
<div class="col-md-1"><input class="form-control" type="time" name="start_time"></div><div class="col-md-1"><input class="form-control" type="time" name="end_time"></div>
<div class="col-md-1"><input class="form-control" name="room" placeholder="Room"></div>
<div class="col-md-1"><button class="btn btn-primary w-100">Add</button></div></form></div>
<div class="card p-3"><h6>Weekly timetable</h6><div class="table-responsive"><table class="table table-bordered text-center"><tr><th>Period</th><?php foreach($days as $d): ?><th><?= $d ?></th><?php endforeach; ?></tr>
<?php foreach($grid as $period=>$rowE): ?><tr><td><strong><?= $period ?></strong></td><?php foreach($days as $n=>$d): $en=$rowE[$n]??null; ?><td><?php if($en): ?><strong><?= e($en['subject_name']??'') ?></strong><br><small><?= e($en['teacher_name']??'') ?> <?= e($en['room']??'') ?><br><?= e(substr((string)($en['start_time']??''),0,5)) ?>–<?= e(substr((string)($en['end_time']??''),0,5)) ?></small><form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $en['id'] ?>"><input type="hidden" name="class_id" value="<?= $classId ?>"><button class="btn btn-sm btn-outline-danger mt-1">×</button></form><?php endif; ?></td><?php endforeach; ?></tr><?php endforeach; ?></table></div>
<?php if(!$grid): ?><p class="text-muted small mb-0">No periods yet for this class.</p><?php endif; ?></div>
<?php include __DIR__ . '/includes/footer.php'; ?>
