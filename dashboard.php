<?php require_once __DIR__ . '/includes/auth.php'; $user=require_role(['school_admin','principal','teacher','accountant','student','parent']); $pdo=db(); check_tenant_active($pdo); $tid=require_tenant(); $base=''; $active='dashboard'; $pageTitle='School Dashboard';

function cnt(PDO $pdo,string $sql,array $a=[]){$s=$pdo->prepare($sql);$s->execute($a);return $s->fetchColumn();}
$totalStudents=(int)cnt($pdo,'SELECT COUNT(*) FROM students WHERE tenant_id=? AND status="active"',[$tid]);
$totalTeachers=(int)cnt($pdo,'SELECT COUNT(*) FROM teachers WHERE tenant_id=? AND status="active"',[$tid]);
$totalClasses=(int)cnt($pdo,'SELECT COUNT(*) FROM classes WHERE tenant_id=?',[$tid]);
$totalStaff=(int)cnt($pdo,'SELECT COUNT(*) FROM users WHERE tenant_id=?',[$tid]);
$todayAtt=cnt($pdo,'SELECT COUNT(*) FROM attendance WHERE tenant_id=? AND att_date=CURDATE()',[$tid]);
$presentAtt=cnt($pdo,'SELECT COUNT(*) FROM attendance WHERE tenant_id=? AND att_date=CURDATE() AND status="present"',[$tid]);
$attPct=$todayAtt?round($presentAtt*100/$todayAtt,1):0;
$monthFees=(float)cnt($pdo,'SELECT COALESCE(SUM(amount),0) FROM payments WHERE tenant_id=? AND status="paid" AND DATE_FORMAT(paid_at,"%Y-%m")=DATE_FORMAT(NOW(),"%Y-%m")',[$tid]);
$pendingFees=(float)cnt($pdo,'SELECT COALESCE(SUM(amount_due-amount_paid),0) FROM fee_invoices WHERE tenant_id=? AND status IN ("unpaid","partial")',[$tid]);
$s=$pdo->prepare('SELECT s.*,c.name class_name FROM students s LEFT JOIN classes c ON c.id=s.class_id WHERE s.tenant_id=? ORDER BY s.id DESC LIMIT 6');$s->execute([$tid]);$recent=$s->fetchAll();
$s=$pdo->prepare('SELECT DATE_FORMAT(paid_at,"%b") m,SUM(amount) amt FROM payments WHERE tenant_id=? AND status="paid" GROUP BY DATE_FORMAT(paid_at,"%Y-%m") ORDER BY MIN(paid_at)');$s->execute([$tid]);$chart=$s->fetchAll();
$s=$pdo->prepare('SELECT * FROM activities WHERE tenant_id=? ORDER BY id DESC LIMIT 6');$s->execute([$tid]);$acts=$s->fetchAll();
include __DIR__ . '/includes/header.php'; ?>
<div class="row g-3">
<?php foreach([['Total Students',$totalStudents,'bi-people'],['Total Teachers',$totalTeachers,'bi-person-badge'],['Classes',$totalClasses,'bi-door-open'],['Employees / Users',$totalStaff,'bi-person-gear'],['Attendance Today',$attPct.'%','bi-calendar-check'],['Fee Collection (month)',money($monthFees),'bi-cash-stack'],['Pending Fees',money($pendingFees),'bi-exclamation-circle']] as [$l,$v,$ic]): ?>
<div class="col-md-4 col-xl-2"><div class="card stat-card p-3"><i class="bi <?= $ic ?> fs-4 text-primary"></i><div class="fs-4 fw-bold"><?= e((string)$v) ?></div><div class="text-muted small"><?= e($l) ?></div></div></div><?php endforeach; ?>
</div>
<div class="row g-3 mt-1"><div class="col-lg-7"><div class="card p-3"><h6>Monthly fee collection</h6><canvas id="feeChart" height="180"></canvas><?php if(!$chart): ?><p class="text-muted small mb-0">No payments recorded yet — record a fee payment to see analytics.</p><?php endif; ?></div></div>
<div class="col-lg-5"><div class="card p-3"><h6>Recent admissions</h6><?php foreach($recent as $st): ?><div class="border-bottom py-1"><strong><?= e($st['first_name'].' '.$st['last_name']) ?></strong> <span class="text-muted small"><?= e($st['admission_no']) ?> · <?= e($st['class_name']??'') ?></span></div><?php endforeach; if(!$recent) echo '<p class="text-muted small mb-0">No students yet.</p>'; ?></div>
<div class="card p-3 mt-3"><h6>Recent activity</h6><?php foreach($acts as $a): ?><div class="small border-bottom py-1"><?= e($a['description']) ?></div><?php endforeach; ?></div></div></div>
<?php $extraJs='<script>makeChart("feeChart","bar",'.json_encode(array_column($chart,'m')).','.json_encode(array_map('floatval',array_column($chart,'amt'))).',"Fees collected");</script>'; include __DIR__ . '/includes/footer.php'; ?>
