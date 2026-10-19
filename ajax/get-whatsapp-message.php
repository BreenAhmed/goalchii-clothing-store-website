<?php
require_once '../config/database.php';
require_once '../config/session.php';

header('Content-Type: application/json');

// Require login
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$logId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($logId <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid log ID']);
    exit;
}

try {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT * FROM whatsapp_logs WHERE id = ?");
    $stmt->execute([$logId]);
    $log = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($log) {
        echo json_encode([
            'success' => true,
            'log' => $log
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Log not found']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}



