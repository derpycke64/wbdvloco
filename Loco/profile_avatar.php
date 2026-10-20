<?php
session_start();
require_once 'db.php';
$id=filter_input(INPUT_GET,'id',FILTER_VALIDATE_INT);
if(!$id || $id<1){http_response_code(400);exit('Invalid user.');}
try{
    $q=$pdo->prepare('SELECT avatar_url FROM user_profiles WHERE user_id=:id LIMIT 1');
    $q->execute([':id'=>$id]);$stored=$q->fetchColumn();
}catch(PDOException $e){error_log('Avatar lookup failed: '.$e->getMessage());http_response_code(500);exit('Avatar unavailable.');}
if(!is_string($stored) || !preg_match('~^uploads/avatars/([a-f0-9]{32})\\.(jpg|png|webp)$~D',$stored,$m)){
    header('Location: images/default_avatar.png',true,302);exit;
}
$file=__DIR__.'/uploads/avatars/'.$m[1].'.'.$m[2];
if(!is_file($file) || !is_readable($file)){header('Location: images/default_avatar.png',true,302);exit;}
$types=['jpg'=>'image/jpeg','png'=>'image/png','webp'=>'image/webp'];
header('Content-Type: '.$types[$m[2]]);
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, max-age=300');
header('Content-Length: '.filesize($file));
readfile($file);
exit;
