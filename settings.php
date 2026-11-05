<?php require_once __DIR__ . '/includes/auth.php'; $user=require_role(['school_admin','principal','teacher','accountant','student','parent']); $pdo=db(); check_tenant_active($pdo); $tid=require_tenant(); $base=''; 
$active='settings'; $pageTitle='Settings'; if (is_post()) { verify_csrf();
  if(($_POST['action']??'')==='profile'){ $pdo->prepare('UPDATE users SET name=? WHERE id=?')->execute([trim($_POST['name']??''),$user['id']]); $_SESSION['user']['name']=trim($_POST['name']??''); flash('success','Profile updated.'); }
  elseif(($_POST['action']??'')==='password'){ if(!password_verify($_POST['current']??'', $pdo->query('SELECT password_hash FROM users WHERE id='.(int)$user['id'])->fetchColumn())) flash('error','Current password is wrong.');
    else { $pdo->prepare('UPDATE users SET password_hash=? WHERE id=?')->execute([password_hash($_POST['new'],PASSWORD_DEFAULT),$user['id']]); flash('success','Password changed.'); } }
  else { foreach(['school_name','currency','timezone'] as $k){ $pdo->prepare('INSERT INTO settings(tenant_id,setting_key,setting_value) VALUES(?,?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)')->execute([$tid,$k,$_POST[$k]??'']); } flash('success','Settings saved.'); }
  redirect('settings.php'); }
$s=$pdo->prepare('SELECT setting_key,setting_value FROM settings WHERE tenant_id=?'); $s->execute([$tid]); $cfg=[]; foreach($s->fetchAll() as $r) $cfg[$r['setting_key']]=$r['setting_value'];
$s=$pdo->prepare('SELECT * FROM support_tickets WHERE tenant_id=? ORDER BY id DESC LIMIT 10'); $s->execute([$tid]); $tickets=$s->fetchAll();

include __DIR__ . '/includes/header.php'; ?>
<div class="row g-3"><div class="col-lg-6"><div class="card p-3"><h6>Workspace</h6><form method="post"><?= csrf_field() ?>
<div class="mb-2"><label class="form-label">School name</label><input class="form-control" name="school_name" value="<?= e($cfg['school_name']??'') ?>"></div>
<div class="mb-2"><label class="form-label">Currency</label><input class="form-control" name="currency" value="<?= e($cfg['currency']??'USD') ?>"></div>
<div class="mb-2"><label class="form-label">Timezone</label><input class="form-control" name="timezone" value="<?= e($cfg['timezone']??'Asia/Karachi') ?>"></div><button class="btn btn-primary">Save</button></form></div>
<div class="card p-3 mt-3"><h6>Change password</h6><form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="password"><input class="form-control mb-2" type="password" name="current" placeholder="Current password" required><input class="form-control mb-2" type="password" name="new" placeholder="New password (min 8)" required><button class="btn btn-outline-primary">Change password</button></form></div></div>
<div class="col-lg-6"><div class="card p-3"><h6>Profile</h6><form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="profile"><input class="form-control mb-2" name="name" value="<?= e($user['name']) ?>"><div class="text-muted small mb-2"><?= e($user['email']) ?> · <?= e($user['role']) ?></div><button class="btn btn-primary">Update profile</button></form></div>
<div class="card p-3 mt-3"><h6>Your support tickets</h6><?php foreach($tickets as $t): ?><div class="border-bottom py-2"><strong><?= e($t['subject']) ?></strong> <span class="badge bg-secondary"><?= e($t['status']) ?></span></div><?php endforeach; if(!$tickets) echo '<p class="text-muted small mb-0">No tickets. Create one from the Super Admin contact / support page.</p>'; ?></div></div></div>
<?php include __DIR__ . '/includes/footer.php'; ?>
