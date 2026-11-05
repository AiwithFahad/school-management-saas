<?php require_once __DIR__ . '/includes/auth.php'; $user=require_role(['school_admin','principal','teacher','accountant']); $pdo=db(); check_tenant_active($pdo); $tid=require_tenant(); $base=''; $active='transport'; $pageTitle='Transport';

if(is_post()){ verify_csrf(); $a=$_POST['action']??'';
 if($a==='vehicle'){ $pdo->prepare('INSERT INTO vehicles(tenant_id,vehicle_no,model,driver_name,driver_phone,capacity) VALUES(?,?,?,?,?,?)')->execute([$tid,trim($_POST['vehicle_no']),trim($_POST['model']??''),trim($_POST['driver_name']??''),trim($_POST['driver_phone']??''),(int)($_POST['capacity']??40)]); }
 elseif($a==='vehicle_delete'){ $pdo->prepare('DELETE FROM vehicles WHERE id=? AND tenant_id=?')->execute([(int)$_POST['id'],$tid]); }
 elseif($a==='route'){ $pdo->prepare('INSERT INTO routes(tenant_id,name,vehicle_id,monthly_fee,stops) VALUES(?,?,?,?,?)')->execute([$tid,trim($_POST['name']),(int)($_POST['vehicle_id']?:0)?:null,(float)($_POST['monthly_fee']??0),trim($_POST['stops']??'')]); }
 elseif($a==='route_delete'){ $pdo->prepare('DELETE FROM routes WHERE id=? AND tenant_id=?')->execute([(int)$_POST['id'],$tid]); }
 elseif($a==='assign'){ $pdo->prepare('INSERT INTO transport_assignments(tenant_id,student_id,route_id,pickup_point) VALUES(?,?,?,?) ON DUPLICATE KEY UPDATE route_id=VALUES(route_id),pickup_point=VALUES(pickup_point)')->execute([$tid,(int)$_POST['student_id'],(int)$_POST['route_id'],trim($_POST['pickup_point']??'')]); }
 elseif($a==='unassign'){ $pdo->prepare('DELETE FROM transport_assignments WHERE id=? AND tenant_id=?')->execute([(int)$_POST['id'],$tid]); }
 flash('success','Transport saved.'); redirect('transport.php'); }
$vehicles=$pdo->query("SELECT * FROM vehicles WHERE tenant_id=$tid ORDER BY id DESC")->fetchAll();
$routes=$pdo->query("SELECT r.*,v.vehicle_no,v.driver_name FROM routes r LEFT JOIN vehicles v ON v.id=r.vehicle_id WHERE r.tenant_id=$tid ORDER BY r.id DESC")->fetchAll();
$students=$pdo->query("SELECT * FROM students WHERE tenant_id=$tid AND status='active' ORDER BY first_name")->fetchAll();
$s=$pdo->prepare('SELECT ta.*,s.first_name,s.last_name,r.name route_name FROM transport_assignments ta JOIN students s ON s.id=ta.student_id JOIN routes r ON r.id=ta.route_id WHERE ta.tenant_id=? ORDER BY ta.id DESC'); $s->execute([$tid]); $assigns=$s->fetchAll();
include __DIR__ . '/includes/header.php'; ?>
<div class="row g-3">
<div class="col-lg-4"><div class="card p-3"><h6>Vehicles & drivers</h6><form method="post" class="row g-2"><?= csrf_field() ?><input type="hidden" name="action" value="vehicle">
<div class="col-md-6"><input class="form-control" name="vehicle_no" placeholder="Vehicle no" required></div><div class="col-md-6"><input class="form-control" name="model" placeholder="Model"></div>
<div class="col-md-6"><input class="form-control" name="driver_name" placeholder="Driver name"></div><div class="col-md-6"><input class="form-control" name="driver_phone" placeholder="Driver phone"></div>
<div class="col-md-6"><input class="form-control" type="number" name="capacity" placeholder="Capacity" value="40"></div><div class="col-md-6"><button class="btn btn-primary w-100">Add vehicle</button></div></form>
<table class="table mt-2"><tr><th>Vehicle</th><th>Driver</th><th></th></tr><?php foreach($vehicles as $v): ?><tr><td><?= e($v['vehicle_no']) ?><br><small><?= e($v['model']??'') ?> · cap <?= $v['capacity'] ?></small></td><td><?= e($v['driver_name']??'') ?><br><small><?= e($v['driver_phone']??'') ?></small></td><td><form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="vehicle_delete"><input type="hidden" name="id" value="<?= $v['id'] ?>"><button class="btn btn-sm btn-outline-danger">Delete</button></form></td></tr><?php endforeach; ?></table></div></div>
<div class="col-lg-4"><div class="card p-3"><h6>Routes</h6><form method="post" class="row g-2"><?= csrf_field() ?><input type="hidden" name="action" value="route">
<div class="col-md-12"><input class="form-control" name="name" placeholder="Route name e.g. Route A — North" required></div>
<div class="col-md-6"><select class="form-select" name="vehicle_id"><option value="">Vehicle…</option><?php foreach($vehicles as $v): ?><option value="<?= $v['id'] ?>"><?= e($v['vehicle_no']) ?></option><?php endforeach; ?></select></div>
<div class="col-md-6"><input class="form-control" type="number" step="any" name="monthly_fee" placeholder="Monthly fee"></div>
<div class="col-md-12"><input class="form-control" name="stops" placeholder="Stops (comma separated)"></div>
<div class="col-md-12"><button class="btn btn-primary w-100">Add route</button></div></form>
<table class="table mt-2"><tr><th>Route</th><th>Vehicle</th><th>Fee</th><th></th></tr><?php foreach($routes as $r): ?><tr><td><?= e($r['name']) ?><br><small><?= e($r['stops']??'') ?></small></td><td><?= e($r['vehicle_no']??'') ?></td><td><?= money($r['monthly_fee']) ?></td><td><form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="route_delete"><input type="hidden" name="id" value="<?= $r['id'] ?>"><button class="btn btn-sm btn-outline-danger">Delete</button></form></td></tr><?php endforeach; ?></table></div></div>
<div class="col-lg-4"><div class="card p-3"><h6>Assign student to transport</h6><form method="post" class="row g-2"><?= csrf_field() ?><input type="hidden" name="action" value="assign">
<div class="col-md-12"><select class="form-select" name="student_id" required><?php foreach($students as $st): ?><option value="<?= $st['id'] ?>"><?= e($st['first_name'].' '.$st['last_name']) ?></option><?php endforeach; ?></select></div>
<div class="col-md-12"><select class="form-select" name="route_id" required><?php foreach($routes as $r): ?><option value="<?= $r['id'] ?>"><?= e($r['name']) ?></option><?php endforeach; ?></select></div>
<div class="col-md-12"><input class="form-control" name="pickup_point" placeholder="Pickup point"></div>
<div class="col-md-12"><button class="btn btn-success w-100">Assign</button></div></form>
<table class="table mt-2"><tr><th>Student</th><th>Route</th><th></th></tr><?php foreach($assigns as $as): ?><tr><td><?= e($as['first_name'].' '.$as['last_name']) ?><br><small><?= e($as['pickup_point']??'') ?></small></td><td><?= e($as['route_name']) ?></td><td><form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="unassign"><input type="hidden" name="id" value="<?= $as['id'] ?>"><button class="btn btn-sm btn-outline-danger">Remove</button></form></td></tr><?php endforeach; ?></table></div></div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
