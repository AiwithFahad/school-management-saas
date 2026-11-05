<?php require_once __DIR__ . '/../includes/auth.php'; $user=require_role(['super_admin'], '../auth/login.php'); $pdo=db(); $base='../'; 
$active='Schools'; $pageTitle='Schools';
if(is_post()){ verify_csrf(); $id=(int)$_POST['id']; $pdo->prepare('UPDATE schools SET status=IF(status="suspended","active","suspended") WHERE id=?')->execute([$id]); audit_log($pdo,'school_toggle','schools',$id); flash('success','School status updated.'); redirect('schools.php'); }
$rows=$pdo->query('SELECT t.*, (SELECT COUNT(*) FROM users u WHERE u.school_id=t.id) user_count, (SELECT p.name FROM subscriptions s JOIN plans p ON p.id=s.plan_id WHERE s.school_id=t.id ORDER BY s.id DESC LIMIT 1) plan_name FROM schools t ORDER BY t.id DESC')->fetchAll();
include __DIR__ . '/header.php'; ?>
<div class="card p-0 overflow-auto"><table class="table mb-0"><tr><th>School</th><th>Slug</th><th>Users</th><th>Plan</th><th>Status</th><th></th></tr>
<?php foreach($rows as $r): ?><tr><td><?= e($r['name']) ?></td><td><?= e($r['slug']) ?></td><td><?= $r['user_count'] ?></td><td><?= e($r['plan_name']??'—') ?></td><td><span class="badge bg-<?= $r['status']==='active'?'success':'danger' ?>"><?= e($r['status']) ?></span></td>
<td><form method="post"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $r['id'] ?>"><button class="btn btn-sm btn-outline-<?= $r['status']==='active'?'danger':'success' ?>" data-confirm="Toggle school status?"><?= $r['status']==='active'?'Suspend':'Reactivate' ?></button></form></td></tr><?php endforeach; ?></table></div>
<?php include __DIR__ . '/footer.php'; ?>
