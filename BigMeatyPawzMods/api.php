<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');

$dataFile = __DIR__ . '/projects.json';
$uploadDir = __DIR__ . '/uploads/';
$adminPin = 'Mphase2027!';

if (!file_exists($dataFile)) {
    file_put_contents($dataFile, json_encode([]));
}

if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Helper: Resize and compress image using GD to WebP
function processAndSaveImage($tmpFile, $uploadDir, $maxDim = 1200, $quality = 82) {
    $info = @getimagesize($tmpFile);
    if (!$info) return false;

    $mime = $info['mime'];
    switch ($mime) {
        case 'image/jpeg':
            $src = @imagecreatefromjpeg($tmpFile);
            break;
        case 'image/png':
            $src = @imagecreatefrompng($tmpFile);
            break;
        case 'image/webp':
            $src = @imagecreatefromwebp($tmpFile);
            break;
        default:
            return false;
    }

    if (!$src) return false;

    $origW = imagesx($src);
    $origH = imagesy($src);

    // Calculate constrained dimensions
    if ($origW > $maxDim || $origH > $maxDim) {
        if ($origW > $origH) {
            $newW = $maxDim;
            $newH = (int) round(($origH / $origW) * $maxDim);
        } else {
            $newH = $maxDim;
            $newW = (int) round(($origW / $origH) * $maxDim);
        }
    } else {
        $newW = $origW;
        $newH = $origH;
    }

    $dest = imagecreatetruecolor($newW, $newH);

    // Preserve alpha transparency for PNG / WebP
    imagealphablending($dest, false);
    imagesavealpha($dest, true);

    imagecopyresampled($dest, $src, 0, 0, 0, 0, $newW, $newH, $origW, $origH);

    $fileName = 'bmp_' . time() . '_' . bin2hex(random_bytes(4)) . '.webp';
    $targetPath = $uploadDir . $fileName;

    imagewebp($dest, $targetPath, $quality);

    imagedestroy($src);
    imagedestroy($dest);

    return '/BigMeatyPawzMods/uploads/' . $fileName;
}

// --------------------------------------------------------------------------
// 1. GET: Return All Projects
// --------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    echo file_get_contents($dataFile);
    exit;
}

// --------------------------------------------------------------------------
// 2. POST: Create, Edit, or Delete Project
// --------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pin = $_POST['pin'] ?? '';
    if ($pin !== $adminPin) {
        http_response_code(401);
        echo json_encode(['error' => 'Invalid Admin Security PIN']);
        exit;
    }

    $action = $_POST['action'] ?? 'create';
    $projects = json_decode(file_get_contents($dataFile), true) ?: [];

    // --- Action: Delete Project ---
    if ($action === 'delete') {
        $id = $_POST['id'] ?? '';
        $filtered = [];
        $found = false;

        foreach ($projects as $p) {
            if ($p['id'] === $id) {
                $found = true;
                // Delete associated image files
                if (!empty($p['images']) && is_array($p['images'])) {
                    foreach ($p['images'] as $imgUrl) {
                        $baseName = basename($imgUrl);
                        $fullPath = $uploadDir . $baseName;
                        if (file_exists($fullPath)) {
                            @unlink($fullPath);
                        }
                    }
                }
            } else {
                $filtered[] = $p;
            }
        }

        if (!$found) {
            http_response_code(404);
            echo json_encode(['error' => 'Project ID not found']);
            exit;
        }

        file_put_contents($dataFile, json_encode($filtered, JSON_PRETTY_PRINT));
        echo json_encode(['success' => true]);
        exit;
    }

    // --- Action: Create or Edit Project ---
    $title = trim($_POST['title'] ?? '');
    $console = trim($_POST['console'] ?? 'Custom');
    $description = trim($_POST['description'] ?? '');

    if (empty($title) || empty($description)) {
        http_response_code(400);
        echo json_encode(['error' => 'Title and description are required']);
        exit;
    }

    // Process multiple uploaded files
    $uploadedImageUrls = [];
    if (isset($_FILES['photos'])) {
        $fileCount = is_array($_FILES['photos']['name']) ? count($_FILES['photos']['name']) : 0;
        for ($i = 0; $i < $fileCount; $i++) {
            if ($_FILES['photos']['error'][$i] === UPLOAD_ERR_OK) {
                $tmp = $_FILES['photos']['tmp_name'][$i];
                $url = processAndSaveImage($tmp, $uploadDir);
                if ($url) {
                    $uploadedImageUrls[] = $url;
                }
            }
        }
    }

    if ($action === 'edit') {
        $id = $_POST['id'] ?? '';
        $foundIndex = -1;
        for ($i = 0; $i < count($projects); $i++) {
            if ($projects[$i]['id'] === $id) {
                $foundIndex = $i;
                break;
            }
        }

        if ($foundIndex === -1) {
            http_response_code(404);
            echo json_encode(['error' => 'Project not found for editing']);
            exit;
        }

        // Merge existing photos with new ones
        $existingImages = $projects[$foundIndex]['images'] ?? [];
        if (!empty($projects[$foundIndex]['image']) && empty($existingImages)) {
            $existingImages[] = $projects[$foundIndex]['image'];
        }

        $allImages = array_merge($existingImages, $uploadedImageUrls);

        $projects[$foundIndex]['title'] = htmlspecialchars($title);
        $projects[$foundIndex]['console'] = htmlspecialchars($console);
        $projects[$foundIndex]['description'] = htmlspecialchars($description);
        $projects[$foundIndex]['images'] = $allImages;
        $projects[$foundIndex]['image'] = !empty($allImages) ? $allImages[0] : '';

        file_put_contents($dataFile, json_encode($projects, JSON_PRETTY_PRINT));
        echo json_encode(['success' => true, 'project' => $projects[$foundIndex]]);
        exit;
    }

    // Default Action: Create
    $newEntry = [
        'id' => uniqid(),
        'title' => htmlspecialchars($title),
        'console' => htmlspecialchars($console),
        'description' => htmlspecialchars($description),
        'images' => $uploadedImageUrls,
        'image' => !empty($uploadedImageUrls) ? $uploadedImageUrls[0] : '',
        'date' => date('M j, Y')
    ];

    array_unshift($projects, $newEntry);
    file_put_contents($dataFile, json_encode($projects, JSON_PRETTY_PRINT));
    echo json_encode(['success' => true, 'project' => $newEntry]);
    exit;
}