<?php require_once __DIR__ . '/includes/auth.php'; $user=require_role(['school_admin','principal','teacher','accountant']); $pdo=db(); check_tenant_active($pdo); $tid=require_tenant(); $base=''; $active='exams'; $pageTitle='Exams & Results';

$classes=$pdo->query("SELECT * FROM classes WHERE tenant_id=$tid ORDER BY numeric_name")->fetchAll();
if(is_post()){ verify_csrf(); $a=$_POST['action']??'';
 if($a==='exam'){ if(!empty($_POST['id'])) $pdo->prepare('UPDATE exams SET name=?,class_id=?,term=?,start_date=?,end_date=?,max_marks=? WHERE id=? AND tenant_id=?')->execute([trim($_POST['name']),(int)($_POST['class_id']?:0)?:null,trim($_POST['term']??''),$_POST['start_date']?:null,$_POST['end_date']?:null,(int)($_POST['max_marks']??100),(int)$_POST['id'],$tid]); else $pdo->prepare('INSERT INTO exams(tenant_id,name,class_id,term,start_date,end_date,max_marks,created_at) VALUES(?,?,?,?,?,?,?,NOW())')->execute([$tid,trim($_POST['name']),(int)($_POST['class_id']?:0)?:null,trim($_POST['term']??''),$_POST['start_date']?:null,$_POST['end_date']?:null,(int)($_POST['max_marks']??100)]); flash('success','Exam saved.'); }
 elseif($a==='exam_delete'){ $pdo->prepare('DELETE FROM exams WHERE id=? AND tenant_id=?')->execute([(int)$_POST['id'],$tid]); flash('success','Exam deleted.'); }
 elseif($a==='marks'){ $examId=(int)$_POST['exam_id']; foreach(($_POST['marks']??[]) as $key=>$val){ if($val===''||$val===null) continue; [$sid,$subid]=array_map('intval',explode('_',$key)); $pdo->prepare('INSERT INTO exam_marks(tenant_id,exam_id,student_id,subject_id,marks_obtained) VALUES(?,?,?,?,?) ON DUPLICATE KEY UPDATE marks_obtained=VALUES(marks_obtained)')->execute([$tid,$examId,$sid,$subid,(float)$val]); } flash('success','Marks saved.'); }
 redirect('exams.php'.(!empty($_POST['exam_id'])?'?exam_id='.(int)$_POST['exam_id']:'')); }
$exams=$pdo->query("SELECT e.*,c.name class_name FROM exams e LEFT JOIN classes c ON c.id=e.class_id WHERE e.tenant_id=$tid ORDER BY e.id DESC")->fetchAll();
$examId=(int)($_GET['exam_id']??($exams[0]['id']??0)); $exam=null; foreach($exams as $e) if($e['id']==$examId) $exam=$e;
$students=[];$subjects=[];$marks=[];
if($exam){ $s=$pdo->prepare('SELECT * FROM students WHERE tenant_id=? AND (? IS NULL OR class_id=?) AND status="active" ORDER BY first_name'); $s->execute([$tid,$exam['class_id'],$exam['class_id']]); $students=$s->fetchAll();
 $s=$pdo->prepare('SELECT * FROM subjects WHERE tenant_id=? AND (class_id IS NULL OR class_id=?) ORDER BY name'); $s->execute([$tid,$exam['class_id']]); $subjects=$s->fetchAll();
 $s=$pdo->prepare('SELECT * FROM exam_marks WHERE tenant_id=? AND exam_id=?'); $s->execute([$tid,$examId]); foreach($s->fetchAll() as $m) $marks[$m['student_id'].'_'.$m['subject_id']]=$m['marks_obtained']; }
$card=null;
if(!empty($_GET['report_student']) && $exam){ $sid=(int)$_GET['report_student'];
 $s=$pdo->prepare('SELECT em.*,sub.name subject_name FROM exam_marks em JOIN subjects sub ON sub.id=em.subject_id WHERE em.tenant_id=? AND em.exam_id=? AND em.student_id=?'); $s->execute([$tid,$examId,$sid]); $rows=$s->fetchAll();
 $total=array_sum(array_map(fn($r)=>(float)$r['marks_obtained'],$rows)); $maxTotal=count($rows)* (int)$exam['max_marks']; $pct=$maxTotal?round($total*100/$maxTotal,2):0;
 $s=$pdo->prepare('SELECT student_id,SUM(marks_obtained) t FROM exam_marks WHERE tenant_id=? AND exam_id=? GROUP BY student_id ORDER BY t DESC'); $s->execute([$tid,$examId]); $rank=0;$pos=0; foreach($s->fetchAll() as $r){ $pos++; if($r['student_id']==$sid){$rank=$pos;break;} }
 $s=$pdo->prepare('SELECT * FROM students WHERE id=? AND tenant_id=?'); $s->execute([$sid,$tid]); $stu=$s->fetch();
 $card=['student'=>$stu,'rows'=>$rows,'total'=>$total,'max'=>$maxTotal,'pct'=>$pct,'grade'=>grade_for($pct),'rank'=>$rank]; }
include __DIR__ . '/includes/header.php'; ?>
<div class="row g-3"><div class="col-lg-4"><div class="card p-3"><h6>Create exam</h6><form method="post" class="row g-2"><?= csrf_field() ?><input type="hidden" name="action" value="exam">
<div class="col-md-12"><input class="form-control" name="name" placeholder="e.g. Mid-Term Exam" required></div>
<div class="col-md-6"><select class="form-select" name="class_id"><option value="">All classes</option><?php foreach($classes as $c): ?><option value="<?= $c['id'] ?>"><?= e($c['name']) ?></option><?php endforeach; ?></select></div>
<div class="col-md-6"><input class="form-control" name="term" placeholder="Term"></div>
<div class="col-md-6"><input class="form-control" type="date" name="start_date"></div><div class="col-md-6"><input class="form-control" type="date" name="end_date"></div>
<div class="col-md-6"><input class="form-control" type="number" name="max_marks" value="100"></div>
<div class="col-md-6"><button class="btn btn-primary w-100">Create exam</button></div></form>
<table class="table mt-3"><tr><th>Exam</th><th>Class</th><th></th></tr><?php foreach($exams as $e): ?><tr><td><a href="?exam_id=<?= $e['id'] ?>"><?= e($e['name']) ?></a><br><small><?= e($e['term']??'') ?></small></td><td><?= e($e['class_name']??'All') ?></td><td><form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="exam_delete"><input type="hidden" name="id" value="<?= $e['id'] ?>"><button class="btn btn-sm btn-outline-danger">Delete</button></form></td></tr><?php endforeach; ?></table></div></div>
<div class="col-lg-8">
<?php if($card): ?><div class="card p-4 mb-3 border-primary"><h5><i class="bi bi-award"></i> Report Card — <?= e($exam['name']) ?></h5>
<p class="mb-2"><strong><?= e($card['student']['first_name'].' '.$card['student']['last_name']) ?></strong> (<?= e($card['student']['admission_no']) ?>)</p>
<table class="table"><tr><th>Subject</th><th>Marks</th><th>Max</th></tr><?php foreach($card['rows'] as $r): ?><tr><td><?= e($r['subject_name']) ?></td><td><?= $r['marks_obtained'] ?></td><td><?= $exam['max_marks'] ?></td></tr><?php endforeach; ?></table>
<p class="mb-0 fs-5">Total: <strong><?= $card['total'] ?> / <?= $card['max'] ?></strong> · Percentage: <strong><?= $card['pct'] ?>%</strong> · Grade: <strong><?= e($card['grade']) ?></strong> · Rank in class: <strong>#<?= $card['rank'] ?></strong></p></div><?php endif; ?>
<?php if($exam && $students && $subjects): ?><div class="card p-3"><h6>Marks entry — <?= e($exam['name']) ?> (max <?= $exam['max_marks'] ?> per subject)</h6>
<form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="marks"><input type="hidden" name="exam_id" value="<?= $examId ?>">
<div class="table-responsive"><table class="table"><tr><th>Student</th><?php foreach($subjects as $sub): ?><th><?= e($sub['name']) ?></th><?php endforeach; ?><th>Report</th></tr>
<?php foreach($students as $st): ?><tr><td><?= e($st['first_name'].' '.$st['last_name']) ?></td><?php foreach($subjects as $sub): $k=$st['id'].'_'.$sub['id']; ?><td><input class="form-control form-control-sm" type="number" step="any" min="0" max="<?= $exam['max_marks'] ?>" name="marks[<?= $k ?>]" value="<?= e($marks[$k]??'') ?>" style="width:80px"></td><?php endforeach; ?><td><a class="btn btn-sm btn-outline-primary" href="?exam_id=<?= $examId ?>&report_student=<?= $st['id'] ?>">Report card</a></td></tr><?php endforeach; ?></table></div>
<button class="btn btn-primary">Save marks</button></form></div>
<?php elseif($exam): ?><div class="alert alert-info">Add students and subjects for this class first to enter marks.</div><?php endif; ?>
</div></div>
<?php include __DIR__ . '/includes/footer.php'; ?>
