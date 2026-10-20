<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: Login.php');
    exit();
}

require_once __DIR__ . '/db.php';

$userId = (int) $_SESSION['user_id'];
$stmt = $pdo->prepare(
    'SELECT g.id, g.title, g.description, g.price, g.image_url, g.publisher, l.purchase_date
     FROM library l
     INNER JOIN games g ON g.id = l.game_id
     WHERE l.user_id = :user_id
     ORDER BY l.purchase_date DESC, g.title ASC'
);
$stmt->execute(['user_id' => $userId]);
$games = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LOCO — My Library</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .loco-library-page { max-width: 1200px; margin: 0 auto; padding: 36px 24px 64px; color: #f4f4f5; }
        .loco-library-heading { display:flex; flex-wrap:wrap; justify-content:space-between; align-items:end; gap:16px; margin-bottom:28px; }
        .loco-library-heading h1 { margin:0 0 8px; font-size:clamp(1.8rem,4vw,2.7rem); }
        .loco-library-heading p { margin:0; color:#a7aab2; }
        .loco-library-count { color:#b9e769; font-weight:700; }
        .loco-library-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(220px,1fr)); gap:18px; }
        .loco-library-card { overflow:hidden; border:1px solid #30333b; border-radius:10px; background:#17191f; }
        .loco-library-card img { display:block; width:100%; aspect-ratio:16/9; object-fit:cover; background:#242731; }
        .loco-library-card-body { padding:16px; }
        .loco-library-card h2 { font-size:1.05rem; margin:0 0 8px; }
        .loco-library-card p { color:#a7aab2; font-size:.9rem; line-height:1.5; margin:0 0 12px; }
        .loco-library-meta { display:flex; justify-content:space-between; align-items:center; gap:8px; color:#b9e769; font-size:.8rem; }
        .loco-library-empty { border:1px dashed #414550; border-radius:12px; padding:44px 20px; text-align:center; background:#17191f; }
        .loco-library-empty p { color:#a7aab2; }
        .loco-library-action { display:inline-block; margin-top:12px; padding:10px 16px; border-radius:5px; background:#b9e769; color:#17191f; text-decoration:none; font-weight:800; }
        .loco-current-user { color:#fff; text-decoration:none; font-weight:bold; margin-right:15px; }
    </style>
</head>
<body class="loco-store-body">
    <?php require __DIR__ . '/nav.php'; ?>
    <main class="loco-library-page">
        <div class="loco-library-heading">
            <div>
                <h1>My Game Library</h1>
                <p>Your games, all in one place.</p>
            </div>
            <div class="loco-library-count"><?php echo count($games); ?> <?php echo count($games) === 1 ? 'GAME' : 'GAMES'; ?></div>
        </div>

        <?php if (!$games): ?>
            <section class="loco-library-empty">
                <h2>Your library is waiting</h2>
                <p>Games you purchase will appear here. Explore the store to find your next favorite.</p>
                <a class="loco-library-action" href="Products.php">Browse the Store</a>
            </section>
        <?php else: ?>
            <section class="loco-library-grid" aria-label="Owned games">
                <?php foreach ($games as $game): ?>
                    <article class="loco-library-card">
                        <a href="game_details.php?id=<?php echo (int) $game['id']; ?>" aria-label="View <?php echo htmlspecialchars($game['title'], ENT_QUOTES, 'UTF-8'); ?>">
                            <img src="<?php echo htmlspecialchars($game['image_url'] ?: 'images/logo.png', ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($game['title'], ENT_QUOTES, 'UTF-8'); ?> cover">
                        </a>
                        <div class="loco-library-card-body">
                            <h2><?php echo htmlspecialchars($game['title'], ENT_QUOTES, 'UTF-8'); ?></h2>
                            <p><?php echo htmlspecialchars(mb_strimwidth((string) ($game['description'] ?? ''), 0, 115, '…'), ENT_QUOTES, 'UTF-8'); ?></p>
                            <div class="loco-library-meta">
                                <span>Owned</span>
                                <span><?php echo htmlspecialchars(date('M j, Y', strtotime($game['purchase_date'] ?? 'now')), ENT_QUOTES, 'UTF-8'); ?></span>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </section>
        <?php endif; ?>
    </main>
</body>
</html>
