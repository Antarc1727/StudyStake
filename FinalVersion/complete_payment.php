<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['last_target_id'])) {
    header("Location: dashboard.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $target_id = $_SESSION['last_target_id'];

    // Simulate payment verification (replace with actual logic)
    $payment_verified = true; // Assume payment is verified

    if ($payment_verified) {
        $stmt = $conn->prepare("UPDATE targets SET status='active', created_at=NOW() WHERE id=?");
        $stmt->bind_param("i", $target_id);
        $stmt->execute();
        $stmt->close();
        $conn->close();

        unset($_SESSION['last_target_id']);
        header("Location: dashboard.php");
        exit();
    } else {
        echo "<script>alert('Payment verification failed.'); window.history.back();</script>";
    }
}