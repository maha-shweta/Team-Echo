<?php
session_start();
require_once '../db/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit(json_encode(['error' => 'Method not allowed']));
}

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit(json_encode(['error' => 'Unauthorized']));
}

$data = json_decode(file_get_contents('php://input'), true);
$parent_comment_id = $data['parent_comment_id'] ?? null;
$comment_text = trim($data['comment_text'] ?? '');
$user_id = $_SESSION['user_id'];

if (empty($parent_comment_id) || empty($comment_text)) {
    http_response_code(400);
    exit(json_encode(['error' => 'Missing required fields']));
}

try {
    // Verify parent comment exists and get feedback_id
    $stmt = $pdo->prepare("
        SELECT feedback_id 
        FROM feedback_comments 
        WHERE comment_id = ?
    ");
    $stmt->execute([$parent_comment_id]);
    $parent = $stmt->fetch();
    
    if (!$parent) {
        http_response_code(404);
        exit(json_encode(['error' => 'Parent comment not found']));
    }
    
    // Insert reply
    $stmt = $pdo->prepare("
        INSERT INTO feedback_comments 
        (feedback_id, user_id, comment_text, parent_comment_id, is_internal, created_at) 
        VALUES (?, ?, ?, ?, 0, NOW())
    ");
    
    $stmt->execute([
        $parent['feedback_id'],
        $user_id,
        $comment_text,
        $parent_comment_id
    ]);
    
    echo json_encode([
        'success' => true,
        'comment_id' => $pdo->lastInsertId(),
        'message' => 'Reply added successfully'
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
