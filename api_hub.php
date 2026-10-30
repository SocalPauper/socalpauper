<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');

// Security PINs
$adminPin = 'Mphase2027!';
$guestPin = 'PauperGuest2027!'; // Give this PIN to authorized partners/organizers

$eventsFile = __DIR__ . '/events.json';
$storesFile = __DIR__ . '/stores.json';

// Initialize flat files if missing
if (!file_exists($eventsFile)) file_put_contents($eventsFile, json_encode([]));
if (!file_exists($storesFile)) file_put_contents($storesFile, json_encode([]));

// Client IP resolver (supports Cloudflare and standard reverse proxies)
function getClientIP() {
    if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
        return $_SERVER['HTTP_CF_CONNECTING_IP'];
    }
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $parts = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        return trim($parts[0]);
    }
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

// Audit Logger
function logAuditActivity($role, $action, $type, $targetId, $targetTitle, $extra = '') {
    $logDir = __DIR__ . '/logs';
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0755, true);
        @file_put_contents($logDir . '/.htaccess', "Require all denied\n");
    }

    $entry = [
        'timestamp'   => date('Y-m-d H:i:s'),
        'role'        => $role,
        'ip'          => getClientIP(),
        'user_agent'  => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
        'type'        => $type,
        'action'      => $action,
        'target_id'   => $targetId,
        'target_name' => $targetTitle,
        'details'     => $extra
    ];

    @file_put_contents($logDir . '/audit.log', json_encode($entry) . PHP_EOL, FILE_APPEND | LOCK_EX);
}

$type = $_GET['type'] ?? $_POST['type'] ?? 'events';
$targetFile = ($type === 'stores') ? $storesFile : $eventsFile;

// --------------------------------------------------------------------------
// 1. GET: Return Data
// --------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $data = file_get_contents($targetFile);
    echo $data ?: '[]';
    exit;
}

// --------------------------------------------------------------------------
// 2. POST: Create, Edit, or Delete
// --------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pin = trim($_POST['pin'] ?? '');

    // Determine authorization role
    $userRole = null;
    if ($pin === $adminPin) {
        $userRole = 'admin';
    } elseif ($pin === $guestPin) {
        $userRole = 'guest';
    } else {
        logAuditActivity('unauthorized', 'AUTH_FAILURE', $type, '', '', 'Invalid PIN submitted');
        http_response_code(401);
        echo json_encode(['error' => 'Invalid Security PIN']);
        exit;
    }

    $action = $_POST['action'] ?? 'create';
    $items = json_decode(file_get_contents($targetFile), true) ?: [];

    // --- Action: Delete ---
    if ($action === 'delete') {
        $id = isset($_POST['id']) ? trim((string)$_POST['id']) : '';
        $deleteScope = $_POST['deleteScope'] ?? 'series';
        $instanceDate = trim($_POST['instanceDate'] ?? '');

        // Security gate: Guests are strictly prevented from deleting records
        if ($userRole === 'guest') {
            logAuditActivity('guest', 'BLOCKED_DELETE_ATTEMPT', $type, $id, '', "Scope: {$deleteScope}, Date: {$instanceDate}");
            http_response_code(403);
            echo json_encode(['error' => 'Guest PIN holders cannot delete entries. Please contact an administrator to remove this listing.']);
            exit;
        }

        if ($id === '') {
            http_response_code(400);
            echo json_encode(['error' => 'Missing item ID for deletion']);
            exit;
        }

        // Delete ONLY a single instance of a recurring event
        if ($type === 'events' && $deleteScope === 'instance' && $instanceDate !== '') {
            $matchedTitle = '';
            foreach ($items as &$item) {
                if (trim((string)$item['id']) === $id) {
                    $matchedTitle = $item['title'] ?? '';
                    if (!isset($item['excludeDates']) || !is_array($item['excludeDates'])) {
                        $item['excludeDates'] = [];
                    }
                    if (!in_array($instanceDate, $item['excludeDates'])) {
                        $item['excludeDates'][] = $instanceDate;
                    }
                    break;
                }
            }
            unset($item);

            file_put_contents($targetFile, json_encode($items, JSON_PRETTY_PRINT));
            logAuditActivity('admin', 'DELETE_INSTANCE', $type, $id, $matchedTitle, "Excluded date: {$instanceDate}");
            echo json_encode(['success' => true, 'excluded' => $instanceDate]);
            exit;
        }

        // Delete entire series or single non-recurring entry
        $beforeCount = count($items);
        $deletedTitle = '';
        foreach ($items as $item) {
            if (trim((string)($item['id'] ?? '')) === $id) {
                $deletedTitle = $item['title'] ?? $item['name'] ?? '';
                break;
            }
        }

        $filteredItems = array_values(array_filter($items, function($item) use ($id) {
            $itemId = isset($item['id']) ? trim((string)$item['id']) : '';
            return $itemId !== $id;
        }));

        if ($beforeCount > 1 && count($filteredItems) === 0) {
            http_response_code(500);
            echo json_encode(['error' => 'Safety abort: unexpected mass deletion prevented']);
            exit;
        }

        file_put_contents($targetFile, json_encode($filteredItems, JSON_PRETTY_PRINT));
        logAuditActivity('admin', 'DELETE_SERIES', $type, $id, $deletedTitle, "Item deleted completely");
        echo json_encode(['success' => true, 'deleted_id' => $id]);
        exit;
    }

    // --- Action: Create or Edit (Events) ---
    if ($type === 'events') {
        $editScope = $_POST['editScope'] ?? 'series';
        $instanceDate = trim($_POST['instanceDate'] ?? '');

        // If editing ONLY a single instance of a recurring event:
        if ($action === 'edit' && $editScope === 'instance' && $instanceDate !== '') {
            foreach ($items as &$item) {
                if (trim((string)$item['id']) === trim((string)$_POST['id'])) {
                    if (!isset($item['excludeDates']) || !is_array($item['excludeDates'])) {
                        $item['excludeDates'] = [];
                    }
                    if (!in_array($instanceDate, $item['excludeDates'])) {
                        $item['excludeDates'][] = $instanceDate;
                    }
                    break;
                }
            }
            unset($item);

            // Add modified single-night event
            $singleEvent = [
                'id' => uniqid(),
                'title' => htmlspecialchars(trim($_POST['title'] ?? '')),
                'date' => htmlspecialchars(trim($_POST['date'] ?? $instanceDate)),
                'time' => htmlspecialchars(trim($_POST['time'] ?? '')),
                'location' => htmlspecialchars(trim($_POST['location'] ?? '')),
                'region' => htmlspecialchars(trim($_POST['region'] ?? 'Los Angeles')),
                'address' => htmlspecialchars(trim($_POST['address'] ?? '')),
                'desc' => htmlspecialchars(trim($_POST['desc'] ?? '')),
                'recurring' => false,
                'recurFreq' => '',
                'recurEnd' => ''
            ];

            array_unshift($items, $singleEvent);
            file_put_contents($targetFile, json_encode($items, JSON_PRETTY_PRINT));
            logAuditActivity($userRole, 'SPLIT_AND_EDIT_INSTANCE', 'events', $singleEvent['id'], $singleEvent['title'], "Branch from instance {$instanceDate}");
            echo json_encode(['success' => true, 'item' => $singleEvent]);
            exit;
        }

        // Standard event create/update
        $title = trim($_POST['title'] ?? '');
        $date = trim($_POST['date'] ?? '');
        $location = trim($_POST['location'] ?? '');

        if (!$title || !$date || !$location) {
            http_response_code(400);
            echo json_encode(['error' => 'Title, Date, and Store/Location are required']);
            exit;
        }

        $recurring = !empty($_POST['recurring']) && ($_POST['recurring'] === '1' || $_POST['recurring'] === 'true');
        $entryData = [
            'id' => ($action === 'edit' && !empty($_POST['id'])) ? trim((string)$_POST['id']) : uniqid(),
            'title' => htmlspecialchars($title),
            'date' => htmlspecialchars($date),
            'time' => htmlspecialchars(trim($_POST['time'] ?? '')),
            'location' => htmlspecialchars($location),
            'region' => htmlspecialchars(trim($_POST['region'] ?? 'Los Angeles')),
            'address' => htmlspecialchars(trim($_POST['address'] ?? '')),
            'desc' => htmlspecialchars(trim($_POST['desc'] ?? '')),
            'recurring' => $recurring,
            'recurFreq' => trim($_POST['recurFreq'] ?? 'weekly'),
            'recurEnd' => htmlspecialchars(trim($_POST['recurEnd'] ?? ''))
        ];

        if ($action === 'edit') {
            $found = false;
            foreach ($items as $idx => $item) {
                if (trim((string)$item['id']) === $entryData['id']) {
                    if (!empty($item['excludeDates'])) {
                        $entryData['excludeDates'] = $item['excludeDates'];
                    }
                    $items[$idx] = $entryData;
                    $found = true;
                    break;
                }
            }
            if (!$found) $items[] = $entryData;
            logAuditActivity($userRole, 'EDIT_EVENT', 'events', $entryData['id'], $entryData['title']);
        } else {
            array_unshift($items, $entryData);
            logAuditActivity($userRole, 'CREATE_EVENT', 'events', $entryData['id'], $entryData['title']);
        }

        file_put_contents($targetFile, json_encode($items, JSON_PRETTY_PRINT));
        echo json_encode(['success' => true, 'item' => $entryData]);
        exit;
    } else { // Stores / Friends
        $name = trim($_POST['name'] ?? '');
        $city = trim($_POST['city'] ?? '');

        if (!$name || !$city) {
            http_response_code(400);
            echo json_encode(['error' => 'Store Name and City are required']);
            exit;
        }

        $entryData = [
            'id' => ($action === 'edit' && !empty($_POST['id'])) ? trim((string)$_POST['id']) : uniqid(),
            'name' => htmlspecialchars($name),
            'city' => htmlspecialchars($city),
            'region' => htmlspecialchars(trim($_POST['region'] ?? 'Los Angeles')),
            'address' => htmlspecialchars(trim($_POST['address'] ?? '')),
            'phone' => htmlspecialchars(trim($_POST['phone'] ?? '')),
            'website' => filter_var(trim($_POST['website'] ?? ''), FILTER_SANITIZE_URL),
            'notes' => htmlspecialchars(trim($_POST['notes'] ?? ''))
        ];

        if ($action === 'edit') {
            $found = false;
            foreach ($items as $idx => $item) {
                if (trim((string)$item['id']) === $entryData['id']) {
                    $items[$idx] = $entryData;
                    $found = true;
                    break;
                }
            }
            if (!$found) $items[] = $entryData;
            logAuditActivity($userRole, 'EDIT_STORE', 'stores', $entryData['id'], $entryData['name']);
        } else {
            array_unshift($items, $entryData);
            logAuditActivity($userRole, 'CREATE_STORE', 'stores', $entryData['id'], $entryData['name']);
        }

        file_put_contents($targetFile, json_encode($items, JSON_PRETTY_PRINT));
        echo json_encode(['success' => true, 'item' => $entryData]);
        exit;
    }
}