<?php require_once __DIR__ . '/includes/auth.php'; $user=require_role(['school_admin','principal','accountant','teacher']); $pdo=db(); check_tenant_active($pdo); $tid=require_tenant(); $base=''; $active='reports'; $pageTitle='Reports';

$from=$_GET['from']??date('Y-m-01'); $to=$_GET['to']??date('Y-m-d');
function rep(PDO $pdo,string $sql,array $a){$s=$pdo->prepare($sql);$s->execute($a);return $s->fetchAll();}
$att=rep($pdo,'SELECT c.name class_name,COUNT(*) total,SUM(a.status="present") present,SUM(a.status="absent") absent FROM attendance a JOIN classes c ON c.id=a.class_id WHERE a.tenant_id=? AND a.att_date BETWEEN ? AND ? GROUP BY c.name',[$tid,$from,$to]);
$fees=rep($pdo,'SELECT status,COUNT(*) invoices,COALESCE(SUM(amount_due),0) due,COALESCE(SUM(amount_paid),0) paid FROM fee_invoices WHERE tenant_id=? GROUP BY status',[$tid]);
$examAvg=rep($pdo,'SELECT sub.name subject,ROUND(AVG(em.marks_obtained),1) avg_marks,COUNT(*) entries FROM exam_marks em JOIN subjects sub ON sub.id=em.subject_id WHERE em.tenant_id=? GROUP BY sub.name',[$tid]);
$adm=rep($pdo,'SELECT c.name class_name,COUNT(*) students FROM students s LEFT JOIN classes c ON c.id=s.class_id WHERE s.tenant_id=? GROUP BY c.name ORDER BY students DESC',[$tid]);
if(isset($_GET['export'])) export_csv(array_merge($att,$fees,$examAvg,$adm),'school-report.csv');
include __DIR__ . '/includes/header.php'; ?>
<form class="row g-2 mb-3" method="get"><div class="col-md-3"><input class="form-control" type="date" name="from" value="<?= e($from) ?>"></div><div class="col-md-3"><input class="form-control" type="date" name="to" value="<?= e($to) ?>"></div><div class="col-md-2"><button class="btn btn-outline-primary w-100">Filter</button></div><div class="col-md-2"><a class="btn btn-outline-success w-100" href="?export=1&from=<?= e($from) ?>&to=<?= e($to) ?>">Export CSV</a></div></form>
<div class="row g-3">
<div class="col-lg-6"><div class="card p-3"><h6>Attendance by class</h6><table class="table"><tr><th>Class</th><th>Total</th><th>Present</th><th>Absent</th><th>%</th></tr><?php foreach($att as $r): ?><tr><td><?= e($r['class_name']) ?></td><td><?= $r['total'] ?></td><td><?= $r['present'] ?></td><td><?= $r['absent'] ?></td><td><?= $r['total']?round($r['present']*100/$r['total'],1):0 ?>%</td></tr><?php endforeach; ?></table></div>
<div class="card p-3 mt-3"><h6>Fee collection by status</h6><canvas id="feeChart" height="140"></canvas><table class="table mt-2"><tr><th>Status</th><th>Invoices</th><th>Due</th><th>Collected</th></tr><?php foreach($fees as $r): ?><tr><td><?= e($r['status']) ?></td><td><?= $r['invoices'] ?></td><td><?= money($r['due']) ?></td><td><?= money($r['paid']) ?></td></tr><?php endforeach; ?></table></div></div>
<div class="col-lg-6"><div class="card p-3"><h6>Average marks by subject</h6><table class="table"><tr><th>Subject</th><th>Avg marks</th><th>Entries</th></tr><?php foreach($examAvg as $r): ?><tr><td><?= e($r['subject']) ?></td><td><?= $r['avg_marks'] ?></td><td><?= $r['entries'] ?></td></tr><?php endforeach; ?></table></div>
<div class="card p-3 mt-3"><h6>Students by class</h6><table class="table"><tr><th>Class</th><th>Students</th></tr><?php foreach($adm as $r): ?><tr><td><?= e($r['class_name']??'Unassigned') ?></td><td><?= $r['students'] ?></td></tr><?php endforeach; ?></table></div></div>
</div>
<?php $extraJs='<script>makeChart("feeChart","doughnut",'.json_encode(array_column($fees,'status')).','.json_encode(array_map('floatval',array_column($fees,'paid'))).',"Collected");</script>'; include __DIR__ . '/includes/footer.php'; ?>
