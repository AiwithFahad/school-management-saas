<?php require_once __DIR__ . '/includes/auth.php'; $user=require_role(['school_admin','principal','teacher','accountant','student','parent']); $pdo=db(); check_tenant_active($pdo); $tid=require_tenant(); $base=''; $active='parent_portal'; $pageTitle='Parent Portal';

function linked_student(PDO $pdo,int $tid,array $user): ?array {
  $s=$pdo->prepare('SELECT * FROM students WHERE tenant_id=? AND user_id=? LIMIT 1'); $s->execute([$tid,$user['id']]); $r=$s->fetch(); if($r) return $r;
  if(!empty($_GET['student_id'])){ $s=$pdo->prepare('SELECT * FROM students WHERE tenant_id=? AND id=?'); $s->execute([$tid,(int)$_GET['student_id']]); return $s->fetch() ?: null; }
  $s=$pdo->prepare('SELECT * FROM students WHERE tenant_id=? ORDER BY id LIMIT 1'); $s->execute([$tid]); return $s->fetch() ?: null;
}
function student_block(PDO $pdo,int $tid,array $stu): void {
  $sid=(int)$stu['id'];
  $s=$pdo->prepare('SELECT COUNT(*) total,COALESCE(SUM(status="present"),0) present FROM attendance WHERE tenant_id=? AND student_id=?'); $s->execute([$tid,$sid]); $att=$s->fetch();
  $attPct=$att['total']?round($att['present']*100/$att['total'],1):0;
  $s=$pdo->prepare('SELECT * FROM fee_invoices WHERE tenant_id=? AND student_id=? ORDER BY id DESC'); $s->execute([$tid,$sid]); $fees=$s->fetchAll();
  $s=$pdo->prepare('SELECT h.*,sub.name subject_name FROM homework h LEFT JOIN subjects sub ON sub.id=h.subject_id WHERE h.tenant_id=? AND h.class_id=? ORDER BY h.due_date DESC LIMIT 8'); $s->execute([$tid,$stu['class_id']]); $hw=$s->fetchAll();
  $s=$pdo->prepare('SELECT em.marks_obtained,sub.name subject_name,e.name exam_name,e.max_marks FROM exam_marks em JOIN subjects sub ON sub.id=em.subject_id JOIN exams e ON e.id=em.exam_id WHERE em.tenant_id=? AND em.student_id=? ORDER BY e.id DESC LIMIT 20'); $s->execute([$tid,$sid]); $marks=$s->fetchAll();
  $days=[1=>'Mon',2=>'Tue',3=>'Wed',4=>'Thu',5=>'Fri',6=>'Sat'];
  $s=$pdo->prepare('SELECT t.*,sub.name subject_name FROM timetable t LEFT JOIN subjects sub ON sub.id=t.subject_id WHERE t.tenant_id=? AND t.class_id=? ORDER BY t.day_of_week,t.period_no'); $s->execute([$tid,$stu['class_id']]); $tt=$s->fetchAll();
  ?>
  <div class="row g-3">
  <div class="col-lg-4"><div class="card p-3"><h6><?= e($stu['first_name'].' '.$stu['last_name']) ?></h6><div class="small text-muted"><?= e($stu['admission_no']) ?> · Status <?= e($stu['status']) ?></div>
  <div class="fs-4 fw-bold mt-2"><?= $attPct ?>%</div><div class="small text-muted">Attendance (<?= (int)$att['present'] ?> present of <?= (int)$att['total'] ?> days)</div>
  <h6 class="mt-3">Timetable</h6><?php foreach($tt as $x): ?><div class="small border-bottom py-1"><?= $days[$x['day_of_week']]??'' ?> P<?= $x['period_no'] ?> — <?= e($x['subject_name']??'') ?> <span class="text-muted"><?= e(substr((string)($x['start_time']??''),0,5)) ?></span></div><?php endforeach; if(!$tt) echo '<p class="small text-muted mb-0">No timetable entries.</p>'; ?></div></div>
  <div class="col-lg-4"><div class="card p-3"><h6>Fees</h6><table class="table"><tr><th>Period</th><th>Due</th><th>Paid</th><th>Status</th></tr><?php foreach($fees as $f): ?><tr><td><?= e($f['period']) ?></td><td><?= money($f['amount_due']) ?></td><td><?= money($f['amount_paid']) ?></td><td><?= e($f['status']) ?></td></tr><?php endforeach; ?></table><?php if(!$fees) echo '<p class="small text-muted mb-0">No invoices.</p>'; ?>
  <h6 class="mt-2">Results</h6><?php foreach($marks as $m): ?><div class="small border-bottom py-1"><?= e($m['exam_name']) ?> · <?= e($m['subject_name']) ?>: <strong><?= $m['marks_obtained'] ?>/<?= $m['max_marks'] ?></strong></div><?php endforeach; if(!$marks) echo '<p class="small text-muted mb-0">No marks yet.</p>'; ?></div></div>
  <div class="col-lg-4"><div class="card p-3"><h6>Homework</h6><?php foreach($hw as $h): ?><div class="border-bottom py-2"><strong><?= e($h['title']) ?></strong><br><small><?= e($h['subject_name']??'') ?> · Due <?= e($h['due_date']??'') ?></small><br><span class="small"><?= e($h['description']??'') ?></span></div><?php endforeach; if(!$hw) echo '<p class="small text-muted mb-0">No homework.</p>'; ?></div></div>
  </div><?php
}

$s=$pdo->prepare('SELECT s.* FROM parent_student ps JOIN students s ON s.id=ps.student_id WHERE ps.tenant_id=? AND ps.parent_user_id=? ORDER BY s.first_name'); $s->execute([$tid,$user['id']]); $children=$s->fetchAll();
if(!$children){ $s=$pdo->prepare('SELECT * FROM students WHERE tenant_id=? ORDER BY id LIMIT 3'); $s->execute([$tid]); $children=$s->fetchAll(); $preview=true; }
include __DIR__ . '/includes/header.php'; ?>
<?php if(!empty($preview)): ?><div class="alert alert-info">No children are linked to your parent login yet, so this is a preview of the first students. Link children in the database (parent_student table) or ask the school admin.</div><?php endif; ?>
<h6 class="mb-3">Your children (<?= count($children) ?>)</h6>
<?php foreach($children as $stu): ?><div class="mb-4"><?php student_block($pdo,$tid,$stu); ?></div><?php endforeach; if(!$children) echo '<div class="alert alert-warning">No students found.</div>'; ?>
<?php include __DIR__ . '/includes/footer.php'; ?>
