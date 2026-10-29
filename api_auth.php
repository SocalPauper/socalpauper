<?php
// api_auth.php
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
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]);
    exit;
}

$action = $_GET['action'] ?? '';
$payload = json_decode(file_get_contents('php://input'), true) ?? [];

switch ($action) {
    // 1. Check if user is currently logged in via Session
    case 'check':
        if (!empty($_SESSION['user_id'])) {
            $stmt = $pdo->prepare("SELECT id, username, email, role FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                $_SESSION['role'] = $user['role'];
                echo json_encode([
                    'authenticated' => true,
                    'user' => [
                        'id' => (int)$user['id'],
                        'username' => $user['username'],
                        'email' => $user['email'],
                        'role' => $user['role']
                    ]
                ]);
                exit;
            }
        }

        echo json_encode(['authenticated' => false, 'user' => null]);
        break;

    // 2. Query MySQL and verify credentials
    case 'login':
        $username = trim($payload['username'] ?? '');
        $password = $payload['password'] ?? '';

        if (!$username || !$password) {
            http_response_code(400);
            echo json_encode(['error' => 'Username and password are required.']);
            exit;
        }

        // Fetch user from DB by username or email
        $stmt = $pdo->prepare("SELECT id, username, password_hash, email, role FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Verify password against DB hash
        if (!$user || !password_verify($password, $user['password_hash'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Invalid username or password.']);
            exit;
        }

        session_regenerate_id(true);
        $_SESSION['user_id'] = (int)$user['id'];
        $_SESSION['role'] = $user['role'];

        echo json_encode([
            'success' => true,
            'user' => [
                'id' => (int)$user['id'],
                'username' => $user['username'],
                'email' => $user['email'],
                'role' => $user['role']
            ]
        ]);
        break;

    // 3. Insert new user into MySQL
    case 'register':
        $username = trim($payload['username'] ?? '');
        $password = $payload['password'] ?? '';
        $email = trim($payload['email'] ?? '');
        $notifyEvents = !empty($payload['notify_events']) ? 1 : 0;
        $notifyPromos = !empty($payload['notify_promotions']) ? 1 : 0;

        if (strlen($username) < 3) {
            http_response_code(400);
            echo json_encode(['error' => 'Username must be at least 3 characters.']);
            exit;
        }

        if (strlen($password) < 8) {
            http_response_code(400);
            echo json_encode(['error' => 'Password must be at least 8 characters.']);
            exit;
        }

        // Check if username taken
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            http_response_code(409);
            echo json_encode(['error' => 'That username is already taken.']);
            exit;
        }

        // Check if email taken
        if ($email !== '') {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                http_response_code(409);
                echo json_encode(['error' => 'An account with that email already exists.']);
                exit;
            }
        }

        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        try {
            $stmt = $pdo->prepare("
                INSERT INTO users (username, password_hash, email, role, notify_events, notify_promotions) 
                VALUES (?, ?, ?, 'user', ?, ?)
            ");
            $stmt->execute([
                $username,
                $passwordHash,
                $email !== '' ? $email : null,
                $notifyEvents,
                $notifyPromos
            ]);
        } catch (Exception $e) {
            // Fallback if notify columns are omitted
            $stmt = $pdo->prepare("
                INSERT INTO users (username, password_hash, email, role) 
                VALUES (?, ?, ?, 'user')
            ");
            $stmt->execute([
                $username,
                $passwordHash,
                $email !== '' ? $email : null
            ]);
        }

        $newUserId = (int)$pdo->lastInsertId();

        session_regenerate_id(true);
        $_SESSION['user_id'] = $newUserId;
        $_SESSION['role'] = 'user';

        echo json_encode([
            'success' => true,
            'user' => [
                'id' => $newUserId,
                'username' => $username,
                'email' => $email,
                'role' => 'user'
            ]
        ]);
        break;

    // 4. Destroy session
    case 'logout':
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params['path'], $params['domain'],
                $params['secure'], $params['httponly']
            );
        }
        session_destroy();
        echo json_encode(['success' => true]);
        break;

    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid auth action.']);
        break;
}