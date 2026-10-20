<?php
session_start();
require_once 'db.php';

function adminEsc($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
if (empty($_SESSION['user_id'])) {
    header('Location: Login.php');
    exit();
}
$adminId=(int)$_SESSION['user_id'];
try {
    $auth=$pdo->prepare('SELECT role FROM users WHERE id=:id LIMIT 1');
    $auth->execute([':id'=>$adminId]);
    $role=$auth->fetchColumn();
} catch(PDOException $e) {
    error_log('LOCO admin auth failed: '.$e->getMessage());
    http_response_code(503);
    exit('Administrator features are not configured. Import database/community_admin.sql.');
}
if ($role!=='admin') {
    http_response_code(403);
    exit('Access denied. This page is restricted to administrators.');
}
if(empty($_SESSION['loco_admin_csrf'])) $_SESSION['loco_admin_csrf']=bin2hex(random_bytes(24));
$csrf=$_SESSION['loco_admin_csrf'];
$notice='';$error='';
if ($_SERVER['REQUEST_METHOD']==='POST') {
    if(!hash_equals($csrf,(string)($_POST['csrf']??''))) $error='Invalid session token. Refresh and retry.';
    else {
        $action=(string)($_POST['action']??'');
        $target=filter_var($_POST['target_id']??null,FILTER_VALIDATE_INT);
        $note=trim((string)($_POST['note']??''));
        $publicResponse=trim((string)($_POST['admin_response']??''));
        if(!$target || $target<1) $error='Invalid moderation target.';
        else {
            try {
                $pdo->beginTransaction();
                $auditAction='';$type='';$details='';
                if($action==='resolve_report') {
                    $status=(string)($_POST['status']??'');
                    if(!in_array($status,['open','reviewing','resolved','dismissed'],true)) throw new RuntimeException('Invalid report status.');
                    if(strlen($publicResponse)>2000)throw new RuntimeException('Public response must be 2,000 bytes or less.');
                    $q=$pdo->prepare('SELECT id FROM marketplace_reports WHERE id=:id FOR UPDATE');
                    $q->execute([':id'=>$target]);
                    if(!$q->fetch()) throw new RuntimeException('Report not found.');
                    $q=$pdo->prepare("UPDATE marketplace_reports SET status=:status,moderator_notes=:note,admin_response=:response,resolved_at=CASE WHEN :is_closed=1 THEN CURRENT_TIMESTAMP ELSE NULL END WHERE id=:id");
                    $q->execute([':status'=>$status,':note'=>substr($note,0,500),':response'=>$publicResponse===''?null:$publicResponse,':is_closed'=>in_array($status,['resolved','dismissed'],true)?1:0,':id'=>$target]);
                    $auditAction='report_'.$status;$type='report';$details='Report status updated; private note '.($note!==''?'provided':'empty').'; public response '.($publicResponse!==''?'provided':'empty').'.';
                    $notice='Report status updated.';
                } elseif($action==='remove_listing') {
                    $q=$pdo->prepare("SELECT id FROM marketplace_listings WHERE id=:id AND status='active' FOR UPDATE");
                    $q->execute([':id'=>$target]);
                    if(!$q->fetch()) throw new RuntimeException('Listing is not active.');
                    $q=$pdo->prepare("UPDATE marketplace_listings SET status='removed' WHERE id=:id AND status='active'");
                    $q->execute([':id'=>$target]);
                    $auditAction='remove_listing';$type='listing';$details=substr($note,0,500);
                    $notice='Marketplace listing removed. Item remains in its owner\'s inventory.';
                } elseif($action==='delete_discussion') {
                    $q=$pdo->prepare('SELECT id FROM discussions WHERE id=:id FOR UPDATE');
                    $q->execute([':id'=>$target]);
                    if(!$q->fetch()) throw new RuntimeException('Discussion not found.');
                    $q=$pdo->prepare('DELETE FROM discussions WHERE id=:id');
                    $q->execute([':id'=>$target]);
                    $auditAction='delete_discussion';$type='discussion';$details=substr($note,0,500);
                    $notice='Discussion removed.';
                } else throw new RuntimeException('Unsupported moderation action.');
                $q=$pdo->prepare('INSERT INTO admin_audit_log (admin_user_id,action,target_type,target_id,details) VALUES (:uid,:action,:type,:target,:details)');
                $q->execute([':uid'=>$adminId,':action'=>$auditAction,':type'=>$type,':target'=>$target,':details'=>$details]);
                $pdo->commit();
            }catch(Throwable $e){
                if($pdo->inTransaction())$pdo->rollBack();
                if($e instanceof PDOException){error_log('LOCO admin action failed: '.$e->getMessage());$error='Moderation failed. Check the database migrations.';}
                else $error=$e->getMessage();
            }
        }
    }
}
$users=[];$reports=[];$reportHistory=[];$listings=[];$discussions=[];$audit=[];$stats=[];
try{
    $stats=[
      'users'=>(int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn(),
      'reports'=>(int)$pdo->query("SELECT COUNT(*) FROM marketplace_reports WHERE status IN ('open','reviewing')")->fetchColumn(),
      'listings'=>(int)$pdo->query("SELECT COUNT(*) FROM marketplace_listings WHERE status='active'")->fetchColumn(),
      'discussions'=>(int)$pdo->query('SELECT COUNT(*) FROM discussions')->fetchColumn()
    ];
    $users=$pdo->query('SELECT id,username,email,role,created_at FROM users ORDER BY id DESC LIMIT 100')->fetchAll(PDO::FETCH_ASSOC);
    $allReports=$pdo->query("SELECT mr.id,mr.listing_id,mr.reason,mr.status,mr.moderator_notes,mr.admin_response,mr.created_at,mr.resolved_at,u.username AS reporter,gi.item_name
       FROM marketplace_reports mr JOIN users u ON u.id=mr.reporter_id JOIN marketplace_listings ml ON ml.id=mr.listing_id
       JOIN game_items gi ON gi.id=ml.item_id ORDER BY mr.created_at DESC,mr.id DESC LIMIT 200")->fetchAll(PDO::FETCH_ASSOC);
    foreach($allReports as $reportRow){
        if(in_array($reportRow['status'],['open','reviewing'],true))$reports[]=$reportRow;
        elseif(in_array($reportRow['status'],['resolved','dismissed'],true))$reportHistory[]=$reportRow;
    }
    $listings=$pdo->query("SELECT ml.id,gi.item_name,ml.price,u.username AS seller FROM marketplace_listings ml JOIN game_items gi ON gi.id=ml.item_id JOIN users u ON u.id=ml.seller_id WHERE ml.status='active' ORDER BY ml.created_at DESC LIMIT 100")->fetchAll(PDO::FETCH_ASSOC);
    $discussions=$pdo->query('SELECT d.id,d.title,d.created_at,u.username AS author FROM discussions d JOIN users u ON u.id=d.user_id ORDER BY d.created_at DESC LIMIT 100')->fetchAll(PDO::FETCH_ASSOC);
    $audit=$pdo->query('SELECT al.*,u.username AS administrator FROM admin_audit_log al JOIN users u ON u.id=al.admin_user_id ORDER BY al.created_at DESC,al.id DESC LIMIT 40')->fetchAll(PDO::FETCH_ASSOC);
}catch(PDOException $e){error_log('LOCO admin dashboard failed: '.$e->getMessage());$error=$error?:'Could not load the admin dashboard. Verify community_admin.sql and loco_db_upgrade.sql.';}
?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>LOCO | Administration</title><link rel="stylesheet" href="style.css"></head>
<body class="loco-store-body">
<?php include 'nav.php'; ?>
<main class="loco-admin-page">
  <header class="loco-admin-header"><p class="loco-eyebrow">LOCO STAFF TOOLS</p><h1>Administration</h1><p>Moderator controls, account directory, and action history. All changes are saved in MySQL.</p></header>
  <?php if($notice): ?><p class="loco-notice success" role="status"><?=adminEsc($notice)?></p><?php endif; ?>
  <?php if($error): ?><p class="loco-notice error" role="alert"><?=adminEsc($error)?></p><?php endif; ?>
  <div class="loco-admin-stats">
    <?php foreach(['users'=>'Users','reports'=>'Open reports','listings'=>'Active listings','discussions'=>'Discussions'] as $name=>$label): ?>
      <div><strong><?=number_format($stats[$name]??0)?></strong><span><?=adminEsc($label)?></span></div>
    <?php endforeach; ?>
  </div>
  <nav class="loco-admin-jumps"><a href="#admin-reports">Active reports</a><a href="#admin-report-history">Report history</a><a href="#admin-listings">Listings</a><a href="#admin-discussions">Discussions</a><a href="#admin-users">Accounts</a><a href="#admin-audit">Audit log</a></nav>
  <section id="admin-reports" class="loco-admin-panel"><h2>Active report queue (<?=count($reports)?>)</h2>
    <p>Only Open and Reviewing reports appear here. Resolving or dismissing a report moves it into Report History automatically.</p>
    <?php if(!$reports): ?><p>All caught up — no open or reviewing reports.</p><?php endif; ?>
    <?php foreach($reports as $report): ?><article class="loco-admin-entry">
      <div><h3>#<?=$report['id']?> · <?=adminEsc($report['item_name'])?></h3><p>Reported by <?=adminEsc($report['reporter'])?> · <?=adminEsc($report['created_at'])?></p><p><?=adminEsc($report['reason'])?></p><small>Status: <?=adminEsc($report['status'])?> · Listing #<?=$report['listing_id']?></small></div>
      <form method="post" class="loco-admin-form">
        <input type="hidden" name="csrf" value="<?=adminEsc($csrf)?>"><input type="hidden" name="action" value="resolve_report"><input type="hidden" name="target_id" value="<?=$report['id']?>">
        <label>Review status<select name="status"><?php foreach(['open','reviewing','resolved','dismissed'] as $status): ?><option value="<?=$status?>" <?=$status===$report['status']?'selected':''?>><?=ucfirst($status)?></option><?php endforeach; ?></select></label>
        <label>Private moderator note (admins only)<textarea name="note" maxlength="500" rows="3" placeholder="Internal notes"><?=adminEsc($report['moderator_notes']??'')?></textarea></label>
        <label>Response to reporter (visible to user)<textarea name="admin_response" maxlength="2000" rows="4" placeholder="Explain the decision to the reporting player"><?=adminEsc($report['admin_response']??'')?></textarea></label>
        <button type="submit">Update report</button>
      </form></article><?php endforeach; ?>
  </section>
  <section id="admin-report-history" class="loco-admin-panel">
    <h2>Report history (<?=count($reportHistory)?>)</h2><p>Resolved and dismissed reports are archived here. Their records and responses stay available to administrators and the original reporter.</p>
    <?php if(!$reportHistory): ?><p>No closed reports yet.</p><?php endif; ?>
    <?php foreach($reportHistory as $report): ?><details class="loco-admin-history-entry">
      <summary><strong>#<?=$report['id']?> · <?=adminEsc($report['item_name'])?></strong><span><?=adminEsc(ucfirst($report['status']))?> · <?=adminEsc($report['resolved_at']??$report['created_at'])?></span></summary>
      <div class="loco-admin-entry">
        <div><p>Reported by <?=adminEsc($report['reporter'])?> · <?=adminEsc($report['created_at'])?></p><p>Original reason: <?=adminEsc($report['reason'])?></p>
          <p><strong>Response sent to reporter:</strong> <?=adminEsc($report['admin_response']?:'No public response')?></p>
          <p><strong>Private moderator note:</strong> <?=adminEsc($report['moderator_notes']?:'None')?></p>
          <small>Listing #<?=$report['listing_id']?></small>
        </div>
        <form method="post" class="loco-admin-form">
          <input type="hidden" name="csrf" value="<?=adminEsc($csrf)?>"><input type="hidden" name="action" value="resolve_report"><input type="hidden" name="target_id" value="<?=$report['id']?>">
          <label>Status<select name="status"><?php foreach(['resolved','dismissed','reviewing','open'] as $status): ?><option value="<?=$status?>" <?=$status===$report['status']?'selected':''?>><?=ucfirst($status)?></option><?php endforeach; ?></select></label>
          <label>Private note<textarea name="note" maxlength="500" rows="3"><?=adminEsc($report['moderator_notes']??'')?></textarea></label>
          <label>Response to reporter<textarea name="admin_response" maxlength="2000" rows="4"><?=adminEsc($report['admin_response']??'')?></textarea></label>
          <button type="submit">Save / Reopen report</button>
        </form>
      </div>
    </details><?php endforeach; ?>
  </section>
  <section id="admin-listings" class="loco-admin-panel"><h2>Active marketplace listings</h2><p>Removing a listing does not confiscate the owner's inventory item.</p>
    <?php if(!$listings): ?><p>No active listings.</p><?php endif; ?>
    <?php foreach($listings as $listing): ?><article class="loco-admin-entry"><div><h3>#<?=$listing['id']?> · <?=adminEsc($listing['item_name'])?></h3><p>Seller: <?=adminEsc($listing['seller'])?> · ₱<?=number_format((float)$listing['price'],2)?></p></div>
      <form method="post" onsubmit="return confirm('Remove this marketplace listing?')" class="loco-admin-form">
        <input type="hidden" name="csrf" value="<?=adminEsc($csrf)?>"><input type="hidden" name="action" value="remove_listing"><input type="hidden" name="target_id" value="<?=$listing['id']?>">
        <label>Reason<input name="note" maxlength="500" placeholder="Reason for removal"></label><button type="submit" class="loco-admin-danger">Remove listing</button>
      </form></article><?php endforeach; ?>
  </section>
  <section id="admin-discussions" class="loco-admin-panel"><h2>Community discussions</h2>
    <?php if(!$discussions): ?><p>No discussions yet.</p><?php endif; ?>
    <?php foreach($discussions as $post): ?><article class="loco-admin-entry"><div><h3>#<?=$post['id']?> · <?=adminEsc($post['title'])?></h3><p>By <?=adminEsc($post['author'])?> · <?=adminEsc($post['created_at'])?></p></div>
      <form method="post" class="loco-admin-form" onsubmit="return confirm('Permanently delete this discussion and its replies?')">
        <input type="hidden" name="csrf" value="<?=adminEsc($csrf)?>"><input type="hidden" name="action" value="delete_discussion"><input type="hidden" name="target_id" value="<?=$post['id']?>">
        <label>Reason<input name="note" maxlength="500" placeholder="Reason for removal"></label><button type="submit" class="loco-admin-danger">Delete discussion</button>
      </form></article><?php endforeach; ?>
  </section>
  <section id="admin-users" class="loco-admin-panel"><h2>User accounts (read-only)</h2><p>Account roles are managed directly in the database for this first admin release. Passwords are never shown.</p>
    <div class="loco-admin-table-wrap"><table><thead><tr><th>ID</th><th>Username</th><th>Email</th><th>Role</th><th>Registered</th></tr></thead><tbody>
    <?php foreach($users as $user): ?><tr><td><?=$user['id']?></td><td><?=adminEsc($user['username'])?></td><td><?=adminEsc($user['email'])?></td><td><?=adminEsc($user['role'])?></td><td><?=adminEsc($user['created_at'])?></td></tr><?php endforeach; ?>
    </tbody></table></div>
  </section>
  <section id="admin-audit" class="loco-admin-panel"><h2>Admin action history</h2><p>Recent moderation actions are recorded for accountability.</p>
    <?php if(!$audit): ?><p>No admin actions yet.</p><?php endif; ?>
    <?php foreach($audit as $row): ?><p class="loco-admin-audit">#<?=$row['id']?> · <?=adminEsc($row['administrator'])?> · <?=adminEsc($row['action'])?> on <?=adminEsc($row['target_type'])?> #<?=$row['target_id']?> · <?=adminEsc($row['created_at'])?><?php if(!empty($row['details'])): ?> · <?=adminEsc($row['details'])?><?php endif; ?></p><?php endforeach; ?>
  </section>
</main>
</body></html>
