<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'], $_SESSION['username'])) {
    header('Location: Login.php');
    exit();
}

$gameId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$title = trim((string)($_GET['game'] ?? ''));

try {
    if ($gameId) {
        $stmt = $pdo->prepare('SELECT id, title FROM games WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $gameId]);
    } elseif ($title !== '') {
        // Compatibility with the existing storefront button, which sends the title.
        $stmt = $pdo->prepare('SELECT id, title FROM games WHERE title = :title LIMIT 1');
        $stmt->execute([':title' => $title]);
    } else {
        header('Location: products.php');
        exit();
    }

    $game = $stmt->fetch();
    if (!$game) {
        $_SESSION['cart_message'] = 'That game could not be found in the store.';
        header('Location: products.php');
        exit();
    }

    $owned = $pdo->prepare('SELECT id FROM library WHERE user_id = :user_id AND game_id = :game_id LIMIT 1');
    $owned->execute([':user_id' => (int)$_SESSION['user_id'], ':game_id' => (int)$game['id']]);
    if ($owned->fetch()) {
        $_SESSION['cart_message'] = 'You already own ' . $game['title'] . '.';
        header('Location: products.php');
        exit();
    }

    if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) $_SESSION['cart'] = [];
    $exists = false;
    foreach ($_SESSION['cart'] as $item) {
        if ((int)($item['game_id'] ?? 0) === (int)$game['id']) { $exists = true; break; }
    }
    if (!$exists) $_SESSION['cart'][] = ['game_id' => (int)$game['id']];
    header('Location: payment.php');
    exit();
} catch (PDOException $e) {
    error_log('LOCO add-to-cart failed: ' . $e->getMessage());
    http_response_code(500);
    exit('Unable to add this game to your cart right now.');
}
