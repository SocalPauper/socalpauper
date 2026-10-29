<?php
// api_decks.php
header('Content-Type: application/json');

ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Lax');
if (!empty($_SERVER['HTTPS']) &&$_SERVER['HTTPS'] !== 'off') {
    ini_set('session.cookie_secure', '1');
}

session_start();
require_once __DIR__ . '/db.php';
$pdo = getDBConnection();

$userId =$_SESSION['user_id'] ?? null;
$action = $_GET['action'] ?? '';$payload = json_decode(file_get_contents('php://input'), true) ?? [];

// Helper to enforce auth
function requireAuth($userId) {
    if (!$userId) {
        http_response_code(401);
        echo json_encode(['error' => 'Authentication required.']);
        exit;
    }
}

switch ($action) {
    // Fetch all decks and folders belonging to current user
    case 'list':
        requireAuth($userId);
        
        $folderStmt =$pdo->prepare("SELECT id, name, parent_id FROM deck_folders WHERE user_id = ? ORDER BY name ASC");
        $folderStmt->execute([$userId]);
        $folders =$folderStmt->fetchAll();

        $deckStmt =$pdo->prepare("SELECT id, folder_id, title, format, is_public, featured_card_art, updated_at FROM decks WHERE user_id = ? ORDER BY updated_at DESC");
        $deckStmt->execute([$userId]);
        $decks =$deckStmt->fetchAll();

        echo json_encode(['folders' => $folders, 'decks' =>$decks]);
        break;

    // Fetch single full deck with card data
    case 'get':
        $deckId = (int)($_GET['id'] ?? 0);
        $stmt =$pdo->prepare("SELECT * FROM decks WHERE id = ?");
        $stmt->execute([$deckId]);
        $deck =$stmt->fetch();

        if (!$deck) {
            http_response_code(404);
            echo json_encode(['error' => 'Deck not found.']);
            exit;
        }

        // Allow public decks or deck owner
        if (!$deck['is_public'] && $deck['user_id'] !==$userId) {
            http_response_code(403);
            echo json_encode(['error' => 'Unauthorized deck access.']);
            exit;
        }

        $deck['cards_data'] = json_decode($deck['cards_data'], true);
        echo json_encode(['deck' => $deck]);
        break;

    // Save or update deck
    case 'save':
        requireAuth($userId);

        $id = !empty($payload['id']) ? (int)$payload['id'] : null;
        $title = trim($payload['title'] ?? 'Untitled Pauper Brew');$folderId = !empty($payload['folder_id']) ? (int)$payload['folder_id'] : null;
        $format =$payload['format'] ?? 'pauper';
        $isPublic = !empty($payload['is_public']) ? 1 : 0;
        $featuredArt =$payload['featured_card_art'] ?? null;
        $cardsData = json_encode($payload['cards'] ?? ['main' => [], 'side' => []]);

        if ($id) {
            $stmt =$pdo->prepare("
                UPDATE decks 
                SET title = ?, folder_id = ?, format = ?, is_public = ?, featured_card_art = ?, cards_data = ?
                WHERE id = ? AND user_id = ?
            ");
            $stmt->execute([$title,$folderId, $format,$isPublic, $featuredArt,$cardsData, $id,$userId]);
            $deckId =$id;
        } else {
            $stmt =$pdo->prepare("
                INSERT INTO decks (user_id, folder_id, title, format, is_public, featured_card_art, cards_data)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$userId, $folderId,$title, $format,$isPublic, $featuredArt,$cardsData]);
            $deckId = (int)$pdo->lastInsertId();
        }

        echo json_encode(['success' => true, 'deck_id' => $deckId]);
        break;

    // Create a new folder
    case 'create_folder':
        requireAuth($userId);$name = trim($payload['name'] ?? '');$parentId = !empty($payload['parent_id']) ? (int)$payload['parent_id'] : null;

        if (!$name) {
            http_response_code(400);
            echo json_encode(['error' => 'Folder name is required.']);
            exit;
        }

        $stmt =$pdo->prepare("INSERT INTO deck_folders (user_id, name, parent_id) VALUES (?, ?, ?)");
        $stmt->execute([$userId, $name,$parentId]);
        echo json_encode(['success' => true, 'folder_id' => (int)$pdo->lastInsertId()]);
        break;

    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action.']);
        break;
}