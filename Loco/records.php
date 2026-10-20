<?php
session_start();
require_once 'db.php';

// This is a simple CRUD demonstration page for the games table.
// Require a signed-in user so the page is not publicly accessible.
if (!isset($_SESSION['user_id'])) {
    header('Location: Login.php');
    exit();
}

function h($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

if (empty($_SESSION['records_csrf'])) {
    $_SESSION['records_csrf'] = bin2hex(random_bytes(32));
}

$message = '';
$messageType = 'success';
$editingGame = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = (string)($_POST['csrf_token'] ?? '');

    if (!hash_equals($_SESSION['records_csrf'], $token)) {
        $message = 'Your session token was invalid. Please refresh the page and try again.';
        $messageType = 'error';
    } else {
        $action = (string)($_POST['action'] ?? '');

        try {
            if ($action === 'add' || $action === 'edit') {
                $title = trim((string)($_POST['title'] ?? ''));
                $description = trim((string)($_POST['description'] ?? ''));
                $publisher = trim((string)($_POST['publisher'] ?? ''));
                $imageUrl = trim((string)($_POST['image_url'] ?? ''));
                $priceInput = trim((string)($_POST['price'] ?? ''));
                $gameId = (int)($_POST['id'] ?? 0);

                if ($title === '' || $priceInput === '' || !is_numeric($priceInput) || (float)$priceInput < 0) {
                    throw new InvalidArgumentException('Enter a game title and a valid non-negative price.');
                }
                if (mb_strlen($title) > 150 || mb_strlen($publisher) > 100 || mb_strlen($imageUrl) > 255) {
                    throw new InvalidArgumentException('One or more fields exceed the allowed length.');
                }

                $price = number_format((float)$priceInput, 2, '.', '');

                if ($action === 'add') {
                    $stmt = $pdo->prepare(
                        'INSERT INTO games (title, description, price, image_url, publisher)
                         VALUES (:title, :description, :price, :image_url, :publisher)'
                    );
                    $stmt->execute([
                        ':title' => $title,
                        ':description' => $description !== '' ? $description : null,
                        ':price' => $price,
                        ':image_url' => $imageUrl !== '' ? $imageUrl : null,
                        ':publisher' => $publisher !== '' ? $publisher : null,
                    ]);
                    $message = 'Game record added successfully.';
                } else {
                    if ($gameId <= 0) {
                        throw new InvalidArgumentException('Choose a valid game record to edit.');
                    }
                    $stmt = $pdo->prepare(
                        'UPDATE games
                         SET title = :title, description = :description, price = :price,
                             image_url = :image_url, publisher = :publisher
                         WHERE id = :id'
                    );
                    $stmt->execute([
                        ':title' => $title,
                        ':description' => $description !== '' ? $description : null,
                        ':price' => $price,
                        ':image_url' => $imageUrl !== '' ? $imageUrl : null,
                        ':publisher' => $publisher !== '' ? $publisher : null,
                        ':id' => $gameId,
                    ]);
                    $message = $stmt->rowCount() > 0 ? 'Game record updated successfully.' : 'No changes were made, or that game record does not exist.';
                }
            } elseif ($action === 'delete') {
                $gameId = (int)($_POST['id'] ?? 0);
                if ($gameId <= 0) {
                    throw new InvalidArgumentException('Choose a valid game record to delete.');
                }

                // Keep game records that are already owned by users.
                $ownedStmt = $pdo->prepare('SELECT COUNT(*) FROM library WHERE game_id = :id');
                $ownedStmt->execute([':id' => $gameId]);
                if ((int)$ownedStmt->fetchColumn() > 0) {
                    throw new InvalidArgumentException('This game is in one or more users’ libraries, so it cannot be deleted.');
                }

                $stmt = $pdo->prepare('DELETE FROM games WHERE id = :id');
                $stmt->execute([':id' => $gameId]);
                $message = $stmt->rowCount() > 0 ? 'Game record deleted successfully.' : 'That game record was not found.';
            } else {
                throw new InvalidArgumentException('Unknown action. Please try again.');
            }
        } catch (InvalidArgumentException $e) {
            $message = $e->getMessage();
            $messageType = 'error';
        } catch (PDOException $e) {
            $message = 'The database could not complete that action. Check whether this game is referenced by another table.';
            $messageType = 'error';
        }
    }
}

// An edit link selects a row and fills the form with its current values.
if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    if ($editId > 0) {
        $stmt = $pdo->prepare('SELECT * FROM games WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $editId]);
        $editingGame = $stmt->fetch() ?: null;
    }
}

$stmt = $pdo->query('SELECT id, title, description, price, image_url, publisher FROM games ORDER BY id DESC');
$games = $stmt->fetchAll();
$formAction = $editingGame ? 'edit' : 'add';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LOCO - Manage Game Records</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .records-wrap { max-width: 1180px; margin: 32px auto; padding: 0 20px 40px; color: #eee; }
        .records-panel { background: #1b1b1b; border: 1px solid #333; border-radius: 8px; padding: 22px; margin-bottom: 24px; }
        .records-panel h1, .records-panel h2 { margin-top: 0; }
        .records-note { color: #aaa; line-height: 1.5; }
        .records-message { padding: 12px 14px; border-radius: 5px; margin-bottom: 18px; }
        .records-message.success { background: #153d2a; border: 1px solid #2b7650; }
        .records-message.error { background: #421d20; border: 1px solid #8b353b; }
        .records-form-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 15px; }
        .records-field { display: flex; flex-direction: column; gap: 7px; }
        .records-field.full { grid-column: 1 / -1; }
        .records-field label { color: #ccc; font-size: 12px; font-weight: 700; }
        .records-field input, .records-field textarea { box-sizing: border-box; width: 100%; padding: 11px; border: 1px solid #444; border-radius: 4px; background: #101010; color: #fff; }
        .records-field textarea { min-height: 90px; resize: vertical; }
        .records-actions { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; }
        .records-btn { display: inline-block; padding: 9px 13px; border: 0; border-radius: 4px; background: #d32f2f; color: #fff; text-decoration: none; cursor: pointer; font-weight: 700; font-size: 13px; }
        .records-btn.secondary { background: #444; }
        .records-btn.delete { background: #70252a; }
        .records-table-wrap { overflow-x: auto; }
        .records-table { width: 100%; border-collapse: collapse; min-width: 720px; }
        .records-table th, .records-table td { padding: 12px 10px; border-bottom: 1px solid #333; text-align: left; vertical-align: top; }
        .records-table th { color: #aaa; font-size: 11px; }
        .records-table td { font-size: 13px; }
        .records-game-title { font-weight: 700; }
        @media (max-width: 650px) { .records-form-grid { grid-template-columns: 1fr; } .records-field.full { grid-column: auto; } }
    </style>
</head>
<body class="loco-store-body">
<?php include_once 'nav.php'; ?>
<main class="records-wrap">
    <section class="records-panel">
        <p class="loco-eyebrow">DATABASE MANAGEMENT</p>
        <h1>Game Records</h1>
        <p class="records-note">Use this page to demonstrate adding, editing, and deleting records in the <code>games</code> table of <code>loco_db</code>. This is a basic project CRUD page.</p>
        <?php if ($message !== ''): ?>
            <div class="records-message <?=h($messageType)?>" role="status"><?=h($message)?></div>
        <?php endif; ?>
        <h2><?= $editingGame ? 'Edit Game Record #' . (int)$editingGame['id'] : 'Add a Game Record' ?></h2>
        <form method="post" action="records.php">
            <input type="hidden" name="csrf_token" value="<?=h($_SESSION['records_csrf'])?>">
            <input type="hidden" name="action" value="<?=h($formAction)?>">
            <?php if ($editingGame): ?><input type="hidden" name="id" value="<?=(int)$editingGame['id']?>"><?php endif; ?>
            <div class="records-form-grid">
                <div class="records-field">
                    <label for="title">Game title *</label>
                    <input id="title" name="title" maxlength="150" required value="<?=h($editingGame['title'] ?? '')?>">
                </div>
                <div class="records-field">
                    <label for="publisher">Publisher</label>
                    <input id="publisher" name="publisher" maxlength="100" value="<?=h($editingGame['publisher'] ?? '')?>">
                </div>
                <div class="records-field">
                    <label for="price">Price (₱) *</label>
                    <input id="price" name="price" type="number" min="0" step="0.01" required value="<?=h($editingGame['price'] ?? '0.00')?>">
                </div>
                <div class="records-field">
                    <label for="image_url">Image URL or path</label>
                    <input id="image_url" name="image_url" maxlength="255" placeholder="images/gameplay_01.jpg" value="<?=h($editingGame['image_url'] ?? '')?>">
                </div>
                <div class="records-field full">
                    <label for="description">Description</label>
                    <textarea id="description" name="description"><?=h($editingGame['description'] ?? '')?></textarea>
                </div>
            </div>
            <div class="records-actions" style="margin-top: 16px;">
                <button class="records-btn" type="submit"><?= $editingGame ? 'Save Changes' : 'Add Record' ?></button>
                <?php if ($editingGame): ?><a class="records-btn secondary" href="records.php">Cancel Edit</a><?php endif; ?>
            </div>
        </form>
    </section>

    <section class="records-panel">
        <h2>Existing Game Records (<?=count($games)?>)</h2>
        <p class="records-note">Records currently stored in the games table. Games already owned by users are protected from deletion.</p>
        <div class="records-table-wrap">
            <table class="records-table">
                <thead><tr><th>ID</th><th>Game</th><th>Publisher</th><th>Price</th><th>Actions</th></tr></thead>
                <tbody>
                <?php if (!$games): ?>
                    <tr><td colspan="5">No game records found.</td></tr>
                <?php else: foreach ($games as $game): ?>
                    <tr>
                        <td><?=(int)$game['id']?></td>
                        <td><div class="records-game-title"><?=h($game['title'])?></div><div class="records-note"><?=h(mb_strimwidth((string)$game['description'], 0, 90, '…'))?></div></td>
                        <td><?=h($game['publisher'] ?? '—')?></td>
                        <td>₱<?=number_format((float)$game['price'], 2)?></td>
                        <td>
                            <div class="records-actions">
                                <a class="records-btn secondary" href="records.php?edit=<?=(int)$game['id']?>">Edit</a>
                                <form method="post" action="records.php" onsubmit="return confirm('Delete this game record? This action cannot be undone.');">
                                    <input type="hidden" name="csrf_token" value="<?=h($_SESSION['records_csrf'])?>">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?=(int)$game['id']?>">
                                    <button class="records-btn delete" type="submit">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </section>
    <p class="records-note"><a href="products.php" style="color:#fff;">← Back to Store</a></p>
</main>
</body>
</html>
