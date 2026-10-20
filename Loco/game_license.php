<?php
// LOCO-only simulated license IDs, tied to the current library owner.
// Requires database/game_resale.sql. Never represents an actual Steam activation key.
if(!function_exists('locoEnsureGameLicense')){
    function locoEnsureGameLicense(PDO $pdo,int $libraryId,int $userId): ?string {
        if($libraryId<=0 || $userId<=0)return null;
        $proposed=strtoupper(bin2hex(random_bytes(12))); // 24 unpredictable characters.
        $q=$pdo->prepare('UPDATE library SET license_key=:license WHERE id=:library AND user_id=:owner AND license_key IS NULL');
        $q->execute([':license'=>$proposed,':library'=>$libraryId,':owner'=>$userId]);
        $q=$pdo->prepare('SELECT license_key FROM library WHERE id=:library AND user_id=:owner LIMIT 1');
        $q->execute([':library'=>$libraryId,':owner'=>$userId]);
        $value=$q->fetchColumn();
        return $value===false||$value===null?null:(string)$value;
    }
    function locoFormatGameLicense(?string $key): string {
        if(!$key || !preg_match('/^[A-F0-9]{24}$/D',$key))return 'Not available';
        return 'LOCO-'.implode('-',str_split($key,6));
    }
}
