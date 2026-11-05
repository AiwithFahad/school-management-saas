<?php require_once __DIR__ . '/includes/auth.php'; $user=require_role(['school_admin','principal','accountant','teacher']); $pdo=db(); check_tenant_active($pdo); $tid=require_tenant(); $base=''; $active='fees'; $pageTitle='Fees & Invoices';

$receipt=null;
if(is_post()){ verify_csrf(); $a=$_POST['action']??'';
 if($a==='structure'){ $pdo->prepare('INSERT INTO fee_structures(tenant_id,name,class_id,amount,frequency,created_at) VALUES(?,?,?,?,?,NOW())')->execute([$tid,trim($_POST['name']),(int)($_POST['class_id']?:0)?:null,(float)$_POST['amount'],$_POST['frequency']??'monthly']); flash('success','Fee structure saved.'); }
 elseif($a==='structure_delete'){ $pdo->prepare('DELETE FROM fee_structures WHERE id=? AND tenant_id=?')->execute([(int)$_POST['id'],$tid]); flash('success','Fee structure deleted.'); }
 elseif($a==='generate'){ $s=$pdo->prepare('SELECT * FROM fee_structures WHERE id=? AND tenant_id=?'); $s->execute([(int)$_POST['structure_id'],$tid]); $fs=$s->fetch();
   if($fs){ $due=$_POST['due_date']?:date('Y-m-d',strtotime('+14 days')); $period=$_POST['period']??date('Y-m');
     $st=$pdo->prepare('SELECT id FROM students WHERE tenant_id=? AND status="active" AND (? IS NULL OR class_id=?)'); $st->execute([$tid,$fs['class_id'],$fs['class_id']]); $n=0;
     foreach($st->fetchAll() as $stu){ $pdo->prepare('INSERT INTO fee_invoices(tenant_id,student_id,structure_id,period,amount_due,amount_paid,status,due_date,created_at) VALUES(?,?,?,?,0,"unpaid",?,NOW())')->execute([$tid,$stu['id'],$fs['id'],$period,$fs['amount'],$due]); $n++; }
     log_activity($pdo,"Generated $n fee invoices for {$fs['name']}"); flash('success',"$n invoices generated."); } }
 elseif($a==='pay'){ $s=$pdo->prepare('SELECT * FROM fee_invoices WHERE id=? AND tenant_id=?'); $s->execute([(int)$_POST['invoice_id'],$tid]); $inv=$s->fetch();
   if($inv){ $amt=min((float)$_POST['amount'], (float)$inv['amount_due']-(float)$inv['amount_paid']); if($amt>0){ $newPaid=(float)$inv['amount_paid']+$amt; $status=$newPaid>=(float)$inv['amount_due']?'paid':'partial';
     $pdo->prepare('INSERT INTO payments(tenant_id,student_id,fee_invoice_id,amount,status,method,paid_at,created_at) VALUES(?,?,?,?, "paid",?,NOW(),NOW())')->execute([$tid,$inv['student_id'],$inv['id'],$amt,$_POST['method']??'cash']);
     $pdo->prepare('UPDATE fee_invoices SET amount_paid=?,status=? WHERE id=?')->execute([$newPaid,$status,$inv['id']]); flash('success','Payment recorded: '.money($amt)); } } }
 redirect('fees.php'); }
if(!empty($_GET['receipt'])){ $s=$pdo->prepare('SELECT fi.*,s.first_name,s.last_name,s.admission_no,fs.name fee_name FROM fee_invoices fi JOIN students s ON s.id=fi.student_id LEFT JOIN fee_structures fs ON fs.id=fi.structure_id WHERE fi.id=? AND fi.tenant_id=?'); $s->execute([(int)$_GET['receipt'],$tid]); $receipt=$s->fetch(); }
$classes=$pdo->query("SELECT * FROM classes WHERE tenant_id=$tid ORDER BY numeric_name")->fetchAll();
$structures=$pdo->query("SELECT fs.*,c.name class_name FROM fee_structures fs LEFT JOIN classes c ON c.id=fs.class_id WHERE fs.tenant_id=$tid ORDER BY fs.id DESC")->fetchAll();
$statusF=$_GET['status']??''; $where='fi.tenant_id=?'; $args=[$tid]; if($statusF){$where.=' AND fi.status=?';$args[]=$statusF;}
$s=$pdo->prepare("SELECT fi.*,s.first_name,s.last_name,s.admission_no,fs.name fee_name FROM fee_invoices fi JOIN students s ON s.id=fi.student_id LEFT JOIN fee_structures fs ON fs.id=fi.structure_id WHERE $where ORDER BY fi.id DESC LIMIT 100"); $s->execute($args); $invoices=$s->fetchAll();
include __DIR__ . '/includes/header.php'; ?>
<?php if($receipt): ?><div class="card p-4 mb-3 border-success"><h5><i class="bi bi-receipt"></i> Fee Receipt</h5><p class="mb-1"><strong><?= e(APP_NAME) ?></strong> — Invoice #<?= $receipt['id'] ?></p>
<p class="mb-1">Student: <?= e($receipt['first_name'].' '.$receipt['last_name']) ?> (<?= e($receipt['admission_no']) ?>) · Fee: <?= e($receipt['fee_name']??'') ?> · Period: <?= e($receipt['period']) ?></p>
<p class="mb-0">Due: <?= money($receipt['amount_due']) ?> · Paid: <?= money($receipt['amount_paid']) ?> · Balance: <?= money($receipt['amount_due']-$receipt['amount_paid']) ?> · Status: <strong><?= e($receipt['status']) ?></strong></p></div><?php endif; ?>
<div class="row g-3"><div class="col-lg-5"><div class="card p-3"><h6>Fee structures</h6><form method="post" class="row g-2"><?= csrf_field() ?><input type="hidden" name="action" value="structure">
<div class="col-md-6"><input class="form-control" name="name" placeholder="e.g. Monthly Tuition" required></div>
<div class="col-md-6"><select class="form-select" name="class_id"><option value="">All classes</option><?php foreach($classes as $c): ?><option value="<?= $c['id'] ?>"><?= e($c['name']) ?></option><?php endforeach; ?></select></div>
<div class="col-md-6"><input class="form-control" type="number" step="any" name="amount" placeholder="Amount" required></div>
<div class="col-md-6"><select class="form-select" name="frequency"><option>monthly</option><option>term</option><option>annual</option><option>one_time</option></select></div>
<div class="col-md-12"><button class="btn btn-primary">Add structure</button></div></form>
<table class="table mt-2"><tr><th>Fee</th><th>Class</th><th>Amount</th><th></th></tr><?php foreach($structures as $fs): ?><tr><td><?= e($fs['name']) ?><br><small><?= e($fs['frequency']) ?></small></td><td><?= e($fs['class_name']??'All') ?></td><td><?= money($fs['amount']) ?></td><td><form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="structure_delete"><input type="hidden" name="id" value="<?= $fs['id'] ?>"><button class="btn btn-sm btn-outline-danger">Delete</button></form></td></tr><?php endforeach; ?></table></div>
<div class="card p-3 mt-3"><h6>Generate invoices for a class</h6><form method="post" class="row g-2"><?= csrf_field() ?><input type="hidden" name="action" value="generate">
<div class="col-md-12"><select class="form-select" name="structure_id" required><?php foreach($structures as $fs): ?><option value="<?= $fs['id'] ?>"><?= e($fs['name']) ?> — <?= money($fs['amount']) ?> (<?= e($fs['class_name']??'All classes') ?>)</option><?php endforeach; ?></select></div>
<div class="col-md-6"><input class="form-control" name="period" value="<?= date('Y-m') ?>"></div><div class="col-md-6"><input class="form-control" type="date" name="due_date"></div>
<div class="col-md-12"><button class="btn btn-success w-100">Generate invoices</button></div></form></div></div>
<div class="col-lg-7"><div class="card p-3"><h6>Invoices <?php if($statusF): ?>(<?= e($statusF) ?>)<?php endif; ?></h6>
<div class="mb-2"><a class="btn btn-sm btn-outline-secondary" href="fees.php">All</a> <a class="btn btn-sm btn-outline-warning" href="?status=unpaid">Pending / Unpaid</a> <a class="btn btn-sm btn-outline-info" href="?status=partial">Partial</a> <a class="btn btn-sm btn-outline-success" href="?status=paid">Paid</a></div>
<table class="table"><tr><th>#</th><th>Student</th><th>Fee</th><th>Due / Paid</th><th>Status</th><th>Pay</th><th></th></tr>
<?php foreach($invoices as $inv): ?><tr><td><?= $inv['id'] ?></td><td><?= e($inv['first_name'].' '.$inv['last_name']) ?><br><small><?= e($inv['admission_no']) ?> · <?= e($inv['period']) ?></small></td><td><?= e($inv['fee_name']??'') ?></td><td><?= money($inv['amount_due']) ?> / <?= money($inv['amount_paid']) ?></td><td><span class="badge bg-<?= $inv['status']==='paid'?'success':($inv['status']==='partial'?'info':'warning') ?>"><?= e($inv['status']) ?></span></td>
<td><?php if($inv['status']!=='paid'): ?><form method="post" class="d-flex gap-1"><?= csrf_field() ?><input type="hidden" name="action" value="pay"><input type="hidden" name="invoice_id" value="<?= $inv['id'] ?>"><input class="form-control form-control-sm" type="number" step="any" name="amount" value="<?= $inv['amount_due']-$inv['amount_paid'] ?>" style="width:90px"><select class="form-select form-select-sm" name="method" style="width:90px"><option>cash</option><option>card</option><option>bank</option></select><button class="btn btn-sm btn-primary">Pay</button></form><?php endif; ?></td>
<td><a class="btn btn-sm btn-outline-secondary" href="?receipt=<?= $inv['id'] ?>">Receipt</a></td></tr><?php endforeach; ?></table></div></div></div>
<?php include __DIR__ . '/includes/footer.php'; ?>
