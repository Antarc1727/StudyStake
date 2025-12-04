<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = isset($_POST['email']) ? $_POST['email'] : null;
    $password = isset($_POST['password']) ? $_POST['password'] : null;
    $name = isset($_POST['name']) ? $_POST['name'] : null;
    $student_id = isset($_POST['student-id']) ? $_POST['student-id'] : null;

    $errors = [];

    if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email.";
    }

    if (!$password || strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters long.";
    }

    if (!$name || trim($name) === "") {
        $errors[] = "Full Name is required.";
    }

    if (!$student_id || trim($student_id) === "") {
        $errors[] = "Student ID is required.";
    }

    if (count($errors) > 0) {
        echo "<script>alert('" . implode("\n", $errors) . "'); window.history.back();</script>";
        exit();
    }

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $conn->prepare("INSERT INTO userdata (email, password, name, student_id) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $email, $hashed_password, $name, $student_id);

    if ($stmt->execute()) {
        header("Location: login.html");
        exit();
    } else {
        echo "<script>alert('Error: Unable to create account.'); window.history.back();</script>";
    }

    $stmt->close();
    $conn->close();
}
?>