<?php
// api_decks.php
header('Content-Type: application/json');

ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Lax');
if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
    ini_set('session.cookie_secure', '1');
}

session_start();
require_once __DIR__ . '/db.php';

try {
    $pdo = getDBConnection();
    $userId = $_SESSION['user_id'] ?? null;
    $userRole = $_SESSION['role'] ?? 'user';
    $action = $_GET['action'] ?? '';
    $payload = json_decode(file_get_contents('php://input'), true) ?? [];

    function checkAuth($userId) {
        if (!$userId) {
            http_response_code(401);
            echo json_encode(['error' => 'Authentication required.']);
            exit;
        }
    }

    function checkAdmin($userRole) {
        if ($userRole !== 'admin') {
            http_response_code(403);
            echo json_encode(['error' => 'Admin privileges required.']);
            exit;
        }
    }

    switch ($action) {
        case 'my_decks':
            checkAuth($userId);

            $folders = [];
            try {
                $folderStmt = $pdo->prepare("SELECT id, name, parent_id FROM deck_folders WHERE user_id = ? ORDER BY name ASC");
                $folderStmt->execute([$userId]);
                $folders = $folderStmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (Throwable $e) {
                $folders = [];
            }

            $decks = [];
            try {
                $deckStmt = $pdo->prepare("
                    SELECT id, folder_id, title, format, is_public, featured_card_art, cards_data, updated_at 
                    FROM decks 
                    WHERE user_id = ? 
                    ORDER BY updated_at DESC
                ");
                $deckStmt->execute([$userId]);
                $decks = $deckStmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (Throwable $e) {
                $decks = [];
            }

            foreach ($decks as &$d) {
                $parsed = json_decode($d['cards_data'] ?? '', true) ?? ['main' => [], 'side' => []];
                $mainCount = 0;
                $firstArt = null;
                foreach ($parsed['main'] ?? [] as $c) {
                    $mainCount += ($c['qty'] ?? 1);
                    if (!$firstArt && !empty($c['image'])) {
                        $firstArt = $c['image'];
                    }
                }
                $d['card_count'] = $mainCount;
                if (empty($d['featured_card_art']) && $firstArt) {
                    $d['featured_card_art'] = $firstArt;
                }
                unset($d['cards_data']);
            }

            echo json_encode(['folders' => $folders, 'decks' => $decks]);
            break;

        case 'community_decks':
            $decks = [];
            try {
                $stmt = $pdo->query("
                    SELECT d.id, d.title, d.format, d.featured_card_art, d.cards_data, d.updated_at, u.username 
                    FROM decks d
                    JOIN users u ON d.user_id = u.id
                    WHERE d.is_public = 1
                    ORDER BY d.updated_at DESC
                    LIMIT 60
                ");
                $decks = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (Throwable $e) {
                $decks = [];
            }

            foreach ($decks as &$d) {
                $parsed = json_decode($d['cards_data'] ?? '', true) ?? ['main' => [], 'side' => []];
                $mainCount = 0;
                $firstArt = null;
                foreach ($parsed['main'] ?? [] as $c) {
                    $mainCount += ($c['qty'] ?? 1);
                    if (!$firstArt && !empty($c['image'])) {
                        $firstArt = $c['image'];
                    }
                }
                $d['card_count'] = $mainCount;
                if (empty($d['featured_card_art']) && $firstArt) {
                    $d['featured_card_art'] = $firstArt;
                }
                unset($d['cards_data']);
            }

            echo json_encode(['decks' => $decks]);
            break;

        case 'get':
            $deckId = (int)($_GET['id'] ?? 0);
            $stmt = $pdo->prepare("SELECT * FROM decks WHERE id = ?");
            $stmt->execute([$deckId]);
            $deck = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$deck) {
                http_response_code(404);
                echo json_encode(['error' => 'Deck not found.']);
                exit;
            }

            if (!$deck['is_public'] && $deck['user_id'] !== $userId && $userRole !== 'admin') {
                http_response_code(403);
                echo json_encode(['error' => 'Unauthorized deck access.']);
                exit;
            }

            $deck['cards_data'] = json_decode($deck['cards_data'] ?? '', true);
            echo json_encode(['deck' => $deck]);
            break;

        case 'save':
            checkAuth($userId);

            $id = !empty($payload['id']) ? (int)$payload['id'] : null;
            $title = trim($payload['title'] ?? 'Untitled Pauper Brew');
            $folderId = !empty($payload['folder_id']) ? (int)$payload['folder_id'] : null;
            $isPublic = !empty($payload['is_public']) ? 1 : 0;
            $cardsData = json_encode($payload['cards'] ?? ['main' => [], 'side' => []]);

            $featuredArt = $payload['featured_card_art'] ?? null;
            if (!$featuredArt && !empty($payload['cards']['main'][0]['image'])) {
                $featuredArt = $payload['cards']['main'][0]['image'];
            }

            if ($id) {
                $checkStmt = $pdo->prepare("SELECT user_id FROM decks WHERE id = ?");
                $checkStmt->execute([$id]);
                $owner = $checkStmt->fetchColumn();

                if ($owner != $userId && $userRole !== 'admin') {
                    http_response_code(403);
                    echo json_encode(['error' => 'You do not own this deck.']);
                    exit;
                }

                $stmt = $pdo->prepare("UPDATE decks SET title = ?, folder_id = ?, is_public = ?, featured_card_art = ?, cards_data = ? WHERE id = ?");
                $stmt->execute([$title, $folderId, $isPublic, $featuredArt, $cardsData, $id]);
                $deckId = $id;
            } else {
                $stmt = $pdo->prepare("INSERT INTO decks (user_id, folder_id, title, is_public, featured_card_art, cards_data) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$userId, $folderId, $title, $isPublic, $featuredArt, $cardsData]);
                $deckId = (int)$pdo->lastInsertId();
            }

            echo json_encode(['success' => true, 'deck_id' => $deckId]);
            break;

        case 'rename':
            checkAuth($userId);
            $deckId = (int)($payload['id'] ?? 0);
            $newTitle = trim($payload['title'] ?? '');

            if (!$deckId || !$newTitle) {
                http_response_code(400);
                echo json_encode(['error' => 'Deck ID and new title required.']);
                exit;
            }

            $stmt = $pdo->prepare("UPDATE decks SET title = ? WHERE id = ? AND (user_id = ? OR ? = 'admin')");
            $stmt->execute([$newTitle, $deckId, $userId, $userRole]);
            echo json_encode(['success' => true]);
            break;

        case 'delete':
            checkAuth($userId);
            $deckId = (int)($payload['id'] ?? 0);

            if (!$deckId) {
                http_response_code(400);
                echo json_encode(['error' => 'Deck ID required.']);
                exit;
            }

            $stmt = $pdo->prepare("DELETE FROM decks WHERE id = ? AND (user_id = ? OR ? = 'admin')");
            $stmt->execute([$deckId, $userId, $userRole]);
            echo json_encode(['success' => true]);
            break;

        case 'clone':
            checkAuth($userId);
            $deckId = (int)($payload['id'] ?? 0);

            $stmt = $pdo->prepare("SELECT * FROM decks WHERE id = ?");
            $stmt->execute([$deckId]);
            $orig = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$orig) {
                http_response_code(404);
                echo json_encode(['error' => 'Original deck not found.']);
                exit;
            }

            $newTitle = $orig['title'] . ' (Copy)';
            $insert = $pdo->prepare("
                INSERT INTO decks (user_id, folder_id, title, format, is_public, featured_card_art, cards_data)
                VALUES (?, ?, ?, ?, 0, ?, ?)
            ");
            $insert->execute([
                $userId, 
                $orig['folder_id'] ?? null, 
                $newTitle, 
                $orig['format'] ?? 'pauper', 
                $orig['featured_card_art'], 
                $orig['cards_data']
            ]);

            echo json_encode(['success' => true, 'new_deck_id' => (int)$pdo->lastInsertId()]);
            break;

        case 'move_folder':
            checkAuth($userId);
            $deckId = (int)($payload['id'] ?? 0);
            $folderId = !empty($payload['folder_id']) ? (int)$payload['folder_id'] : null;

            $stmt = $pdo->prepare("UPDATE decks SET folder_id = ? WHERE id = ? AND user_id = ?");
            $stmt->execute([$folderId, $deckId, $userId]);
            echo json_encode(['success' => true]);
            break;

        case 'create_folder':
            checkAuth($userId);
            $name = trim($payload['name'] ?? '');
            if (!$name) {
                http_response_code(400);
                echo json_encode(['error' => 'Folder name is required.']);
                exit;
            }

            $stmt = $pdo->prepare("INSERT INTO deck_folders (user_id, name) VALUES (?, ?)");
            $stmt->execute([$userId, $name]);
            echo json_encode(['success' => true, 'folder_id' => (int)$pdo->lastInsertId()]);
            break;

        case 'delete_folder':
            checkAuth($userId);
            $folderId = (int)($payload['id'] ?? 0);

            $pdo->prepare("UPDATE decks SET folder_id = NULL WHERE folder_id = ? AND user_id = ?")->execute([$folderId, $userId]);
            $pdo->prepare("DELETE FROM deck_folders WHERE id = ? AND user_id = ?")->execute([$folderId, $userId]);
            echo json_encode(['success' => true]);
            break;

        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action.']);
            break;
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server Error: ' . $e->getMessage()]);
}