<?php require_once __DIR__ . '/includes/auth.php'; $user=require_role(['school_admin','principal','teacher','accountant']); $pdo=db(); check_tenant_active($pdo); $tid=require_tenant(); $base=''; $active='attendance'; $pageTitle='Attendance';

$classes=$pdo->query("SELECT * FROM classes WHERE tenant_id=$tid ORDER BY numeric_name")->fetchAll();
$classId=(int)($_REQUEST['class_id']??($classes[0]['id']??0)); $date=$_REQUEST['att_date']??date('Y-m-d');
if(is_post() && ($_POST['action']??'')==='mark'){ verify_csrf();
 foreach(($_POST['status']??[]) as $sid=>$st){ if(!in_array($st,['present','absent','late'],true)) continue;
   $pdo->prepare('INSERT INTO attendance(tenant_id,student_id,class_id,att_date,status,marked_by,created_at) VALUES(?,?,?,?,?,?,NOW()) ON DUPLICATE KEY UPDATE status=VALUES(status),marked_by=VALUES(marked_by)')->execute([$tid,(int)$sid,$classId,$date,$st,$user['id']]); }
 log_activity($pdo,'Attendance marked for class '.$classId.' on '.$date); flash('success','Attendance saved for '.$date.'.'); redirect('attendance.php?class_id='.$classId.'&att_date='.$date); }
$students=[]; $existing=[];
if($classId){ $s=$pdo->prepare('SELECT * FROM students WHERE tenant_id=? AND class_id=? AND status="active" ORDER BY first_name'); $s->execute([$tid,$classId]); $students=$s->fetchAll();
 $s=$pdo->prepare('SELECT student_id,status FROM attendance WHERE tenant_id=? AND class_id=? AND att_date=?'); $s->execute([$tid,$classId,$date]); foreach($s->fetchAll() as $r) $existing[$r['student_id']]=$r['status']; }
$s=$pdo->prepare('SELECT c.name class_name,a.att_date,COUNT(*) total,SUM(a.status="present") present,SUM(a.status="absent") absent,SUM(a.status="late") late FROM attendance a JOIN classes c ON c.id=a.class_id WHERE a.tenant_id=? GROUP BY a.class_id,a.att_date ORDER BY a.att_date DESC LIMIT 15'); $s->execute([$tid]); $report=$s->fetchAll();
include __DIR__ . '/includes/header.php'; ?>
<form class="row g-2 mb-3" method="get"><div class="col-md-4"><select class="form-select" name="class_id"><?php foreach($classes as $c): ?><option value="<?= $c['id'] ?>" <?= $classId==$c['id']?'selected':'' ?>><?= e($c['name']) ?></option><?php endforeach; ?></select></div>
<div class="col-md-3"><input class="form-control" type="date" name="att_date" value="<?= e($date) ?>"></div><div class="col-md-2"><button class="btn btn-outline-primary w-100">Load class</button></div></form>
<?php if($students): ?><div class="card p-3 mb-3"><h6>Mark attendance — <?= e($date) ?></h6><form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="mark"><input type="hidden" name="class_id" value="<?= $classId ?>"><input type="hidden" name="att_date" value="<?= e($date) ?>">
<table class="table"><tr><th>Student</th><th>Adm No</th><th>Status</th></tr>
<?php foreach($students as $st): $cur=$existing[$st['id']]??'present'; ?><tr><td><?= e($st['first_name'].' '.$st['last_name']) ?></td><td><?= e($st['admission_no']) ?></td><td>
<?php foreach(['present','absent','late'] as $o): ?><label class="me-2"><input type="radio" name="status[<?= $st['id'] ?>]" value="<?= $o ?>" <?= $cur===$o?'checked':'' ?>> <?= ucfirst($o) ?></label><?php endforeach; ?></td></tr><?php endforeach; ?></table>
<button class="btn btn-primary">Save attendance</button></form></div><?php else: ?><div class="alert alert-info">No active students in this class yet.</div><?php endif; ?>
<div class="card p-3"><h6>Attendance summary (recent days)</h6><table class="table mb-0"><tr><th>Class</th><th>Date</th><th>Total</th><th>Present</th><th>Absent</th><th>Late</th><th>% Present</th></tr>
<?php foreach($report as $r): ?><tr><td><?= e($r['class_name']) ?></td><td><?= e($r['att_date']) ?></td><td><?= $r['total'] ?></td><td><?= $r['present'] ?></td><td><?= $r['absent'] ?></td><td><?= $r['late'] ?></td><td><?= $r['total']?round($r['present']*100/$r['total'],1):0 ?>%</td></tr><?php endforeach; ?></table></div>
<?php include __DIR__ . '/includes/footer.php'; ?>
