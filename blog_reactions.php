<?php
header('Content-Type: application/json');
require_once 'db_conn.php';
require_once 'session.php';

$uid = $_SESSION['user_id'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $target = $_GET['target'] ?? '';
    if (!in_array($target, ['post', 'comment'])) {
        http_response_code(400);
        echo json_encode(['error' => 'invalid target']);
        exit;
    }
    $ids = $_GET['ids'] ?? '';
    if ($ids !== '') {
        $idArr = array_filter(array_map('intval', explode(',', $ids)), function ($x) { return $x > 0; });
        if ($idArr) {
            $ph = implode(',', array_fill(0, count($idArr), '?'));
            $cstmt = $pdo->prepare("SELECT target_id, COUNT(*) AS c FROM blog_reactions WHERE target_type=? AND target_id IN ($ph) GROUP BY target_id");
            $cstmt->execute(array_merge([$target], $idArr));
            $counts = [];
            foreach ($cstmt->fetchAll(PDO::FETCH_ASSOC) as $row) { $counts[$row['target_id']] = (int)$row['c']; }
            $userReacted = [];
            if ($uid) {
                $rstmt = $pdo->prepare("SELECT target_id FROM blog_reactions WHERE target_type=? AND user_id=? AND target_id IN ($ph)");
                $rstmt->execute(array_merge([$target, $uid], $idArr));
                foreach ($rstmt->fetchAll(PDO::FETCH_COLUMN) as $tid) { $userReacted[$tid] = true; }
            }
            $out = [];
            foreach ($idArr as $id) { $out[$id] = ['count' => $counts[$id] ?? 0, 'user_reacted' => !empty($userReacted[$id])]; }
            echo json_encode($out);
            exit;
        }
    }
    $id = intval($_GET['id'] ?? 0);
    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'id required']);
        exit;
    }
    $c = (int)$pdo->query("SELECT COUNT(*) FROM blog_reactions WHERE target_type='$target' AND target_id=$id")->fetchColumn();
    $ur = false;
    if ($uid) {
        $r = $pdo->prepare("SELECT 1 FROM blog_reactions WHERE target_type=? AND target_id=? AND user_id=?");
        $r->execute([$target, $id, $uid]);
        $ur = (bool)$r->fetchColumn();
    }
    echo json_encode(['count' => $c, 'user_reacted' => $ur]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$uid) {
        http_response_code(401);
        echo json_encode(['error' => 'Authentication required']);
        exit;
    }
    $input = json_decode(file_get_contents('php://input'), true);
    $target = $input['target'] ?? '';
    $id = intval($input['id'] ?? 0);
    $type = $input['type'] ?? 'like';
    if (!in_array($target, ['post', 'comment']) || $id <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'invalid']);
        exit;
    }
    $chk = $pdo->prepare("SELECT id FROM blog_reactions WHERE target_type=? AND target_id=? AND user_id=? AND reaction_type=?");
    $chk->execute([$target, $id, $uid, $type]);
    if ($chk->fetch()) {
        $pdo->prepare("DELETE FROM blog_reactions WHERE target_type=? AND target_id=? AND user_id=? AND reaction_type=?")->execute([$target, $id, $uid, $type]);
        $reacted = false;
    } else {
        $pdo->prepare("INSERT INTO blog_reactions (user_id, target_type, target_id, reaction_type) VALUES (?,?,?,?)")->execute([$uid, $target, $id, $type]);
        $reacted = true;
    }
    $c = (int)$pdo->query("SELECT COUNT(*) FROM blog_reactions WHERE target_type='$target' AND target_id=$id")->fetchColumn();
    echo json_encode(['success' => true, 'count' => $c, 'user_reacted' => $reacted]);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
