<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');

$adminPin = 'Mphase2027!';
$eventsFile = __DIR__ . '/events.json';$storesFile = __DIR__ . '/stores.json';

// Initialize flat files if missing
if (!file_exists($eventsFile)) file_put_contents($eventsFile, json_encode([]));
if (!file_exists($storesFile)) file_put_contents($storesFile, json_encode([]));

$type = $_GET['type'] ?? $_POST['type'] ?? 'events';
$targetFile = ($type === 'stores') ? $storesFile :$eventsFile;

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
    $pin =$_POST['pin'] ?? '';
    if ($pin !==$adminPin) {
        http_response_code(401);
        echo json_encode(['error' => 'Invalid Admin PIN']);
        exit;
    }

    $action =$_POST['action'] ?? 'create';
    $items = json_decode(file_get_contents($targetFile), true) ?: [];

    // --- Action: Delete ---
    if ($action === 'delete') {$id = isset($_POST['id']) ? trim((string)$_POST['id']) : '';
        $deleteScope =$_POST['deleteScope'] ?? 'series';
        $instanceDate = trim($_POST['instanceDate'] ?? '');

        if ($id === '') {
            http_response_code(400);
            echo json_encode(['error' => 'Missing item ID for deletion']);
            exit;
        }

        // Delete ONLY a single instance of a recurring event
        if ($type === 'events' && $deleteScope === 'instance' &&$instanceDate !== '') {
            foreach ($items as &$item) {
                if (trim((string)$item['id']) ===$id) {
                    if (!isset($item['excludeDates']) || !is_array($item['excludeDates'])) {$item['excludeDates'] = [];
                    }
                    if (!in_array($instanceDate,$item['excludeDates'])) {
                        $item['excludeDates'][] =$instanceDate;
                    }
                    break;
                }
            }
            unset($item);
            file_put_contents($targetFile, json_encode($items, JSON_PRETTY_PRINT));
            echo json_encode(['success' => true, 'excluded' => $instanceDate]);
            exit;
        }

        // Delete entire series or single non-recurring entry
        $beforeCount = count($items);$filteredItems = array_values(array_filter($items, function($item) use ($id) {$itemId = isset($item['id']) ? trim((string)$item['id']) : '';
            return $itemId !==$id;
        }));

        if ($beforeCount > 1 && count($filteredItems) === 0) {
            http_response_code(500);
            echo json_encode(['error' => 'Safety abort: unexpected mass deletion prevented']);
            exit;
        }

        file_put_contents($targetFile, json_encode($filteredItems, JSON_PRETTY_PRINT));
        echo json_encode(['success' => true, 'deleted_id' => $id]);
        exit;
    }

    // --- Action: Create or Edit ---
    if ($type === 'events') {
        $editScope =$_POST['editScope'] ?? 'series';
        $instanceDate = trim($_POST['instanceDate'] ?? '');

        // If editing ONLY a single instance of a recurring event:
        if ($action === 'edit' && $editScope === 'instance' &&$instanceDate !== '') {
            foreach ($items as &$item) {
                if (trim((string)$item['id']) === trim((string)$_POST['id'])) {
                    if (!isset($item['excludeDates']) || !is_array($item['excludeDates'])) {$item['excludeDates'] = [];
                    }
                    if (!in_array($instanceDate,$item['excludeDates'])) {
                        $item['excludeDates'][] =$instanceDate;
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

            array_unshift($items,$singleEvent);
            file_put_contents($targetFile, json_encode($items, JSON_PRETTY_PRINT));
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

        $recurring = !empty($_POST['recurring']) && ($_POST['recurring'] === '1' || $_POST['recurring'] === 'true');$entryData = [
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

        if ($action === 'edit') {$found = false;
            foreach ($items as $idx =>$item) {
                if (trim((string)$item['id']) ===$entryData['id']) {
                    if (!empty($item['excludeDates'])) {
                        $entryData['excludeDates'] =$item['excludeDates'];
                    }
                    $items[$idx] =$entryData;
                    $found = true;
                    break;
                }
            }
            if (!$found) $items[] =$entryData;
        } else {
            array_unshift($items,$entryData);
        }

        file_put_contents($targetFile, json_encode($items, JSON_PRETTY_PRINT));
        echo json_encode(['success' => true, 'item' => $entryData]);
        exit;
    } else { // stores
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

        if ($action === 'edit') {$found = false;
            foreach ($items as $idx =>$item) {
                if (trim((string)$item['id']) === $entryData['id']) {$items[$idx] =$entryData;
                    $found = true;
                    break;
                }
            }
            if (!$found) $items[] =$entryData;
        } else {
            array_unshift($items,$entryData);
        }

        file_put_contents($targetFile, json_encode($items, JSON_PRETTY_PRINT));
        echo json_encode(['success' => true, 'item' => $entryData]);
        exit;
    }
}