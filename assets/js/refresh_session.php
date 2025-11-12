<?php
session_start();
if (isset($_SESSION['user_id'])) {
    $_SESSION['LAST_ACTIVITY'] = time();
    echo json_encode(['status' => 'success']);
} else {
    echo json_encode(['status' => 'expired']);
}
?>
