<?php
session_start();
require_once 'db.php';
if (!isset($_SESSION['user_id'])) { http_response_code(403); exit('Please log in first.'); }
$id=filter_input(INPUT_GET,'id',FILTER_VALIDATE_INT);
if (!$id || $id<1) { http_response_code(400); exit('Invalid workshop creation.'); }
try {
    $q=$pdo->prepare("SELECT wc.archive_filename,wc.original_filename,wc.user_id,
        EXISTS(SELECT 1 FROM workshop_subscriptions ws WHERE ws.creation_id=wc.id AND ws.user_id=:subscriber) AS subscribed
        FROM workshop_creations wc WHERE wc.id=:id LIMIT 1");
    $q->execute([':subscriber'=>(int)$_SESSION['user_id'],':id'=>$id]);
    $creation=$q->fetch(PDO::FETCH_ASSOC);
} catch(PDOException $e) { error_log('Workshop download failed: '.$e->getMessage());http_response_code(500);exit('Workshop database unavailable.'); }
if (!$creation) { http_response_code(404);exit('Creation not found.'); }
if ((int)$creation['user_id']!==(int)$_SESSION['user_id'] && !(int)$creation['subscribed']) { http_response_code(403);exit('Subscribe first to download this creation.'); }
$name=(string)$creation['archive_filename'];
if (!preg_match('/^[a-f0-9]{32}\\.zip$/D',$name)) { http_response_code(404);exit('Archive unavailable.'); }
$path=__DIR__.'/uploads/workshop/'.$name;
if (!is_file($path) || !is_readable($path)) { http_response_code(404);exit('Archive file is missing.'); }
$download=preg_replace('/[^A-Za-z0-9._-]/','_',basename((string)$creation['original_filename']));
if (!$download || !preg_match('/\\.zip$/i',$download)) $download='creation.zip';
header('Content-Type: application/zip');
header('X-Content-Type-Options: nosniff');
header('Content-Disposition: attachment; filename="'.$download.'"');
header('Content-Length: '.filesize($path));
header('Cache-Control: private, no-store');
readfile($path);
exit;
