<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.html");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_SESSION['user_id'];
    $subject = isset($_POST['subject']) ? $_POST['subject'] : '';
    $target = isset($_POST['target']) ? $_POST['target'] : '';
    $bet = isset($_POST['bet']) ? $_POST['bet'] : '';
    $charity = isset($_POST['charity']) ? $_POST['charity'] : '';

    // Insert new data directly into the targets table
    $stmt = $conn->prepare("INSERT INTO targets (user_id, subject_code, target_grade, bet_amount, charity, status, created_at) VALUES (?, ?, ?, ?, ?, 'pending', NOW())");
    $stmt->bind_param("issds", $user_id, $subject, $target, $bet, $charity);

    if ($stmt->execute()) {
        header("Location: dashboard.php?msg=target_saved");
        exit();
    } else {
        echo "<script>alert('Error saving target.'); window.history.back();</script>";
    }

    $stmt->close();
}

$conn->close();
