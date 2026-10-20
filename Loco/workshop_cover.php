<?php
session_start();
require_once 'db.php';
if (!isset($_SESSION['user_id'])) { http_response_code(403); exit('Please log in.'); }
$id=filter_input(INPUT_GET,'id',FILTER_VALIDATE_INT);
if (!$id || $id<1) { http_response_code(400);exit('Invalid creation.'); }
try {
    $q=$pdo->prepare('SELECT cover_filename FROM workshop_creations WHERE id=:id LIMIT 1');
    $q->execute([':id'=>$id]);$name=$q->fetchColumn();
} catch(PDOException $e){ http_response_code(500);exit('Image unavailable.'); }
if (!$name || !preg_match('/^[a-f0-9]{32}\\.(jpg|png|webp)$/D',$name,$matches)) {http_response_code(404);exit('Cover not found.');}
$file=__DIR__.'/uploads/workshop/'.$name;
if(!is_file($file) || !is_readable($file)){http_response_code(404);exit('Cover missing.');}
$types=['jpg'=>'image/jpeg','png'=>'image/png','webp'=>'image/webp'];
header('Content-Type: '.$types[$matches[1]]);
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, max-age=3600');
header('Content-Length: '.filesize($file));
readfile($file);
exit;
