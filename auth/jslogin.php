<?php
session_start();
include("../db/connect.php");

header('Content-Type: text/plain; charset=utf-8');

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email    = $_POST["email"]    ?? '';
    $password = $_POST["password"] ?? '';

    if (empty($email) || empty($password)) {
        echo "missing_fields";
        exit;
    }

    try {
        $stmt = $conn->prepare("SELECT * FROM Users WHERE email = ? LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            if (password_verify($password, $row["password"])) {
                // Set session variables
                $_SESSION["user_id"] = $row["user_id"];
                $_SESSION["name"]    = $row["name"];
                $_SESSION["email"]   = $row["email"];
                $_SESSION["role"]    = $row["role"];

                // Optional: Remember me (basic cookie example - enhance with secure tokens in production)
                if (isset($_POST['remember']) && $_POST['remember'] === '1') {
                    // In real app: use secure long-lived token instead of just session
                    setcookie("remember_email", $email, time() + (30 * 24 * 60 * 60), "/");
                }

                echo $row["role"] === "Admin" ? "admin" : "user";
            } else {
                echo "Invalid password";
            }
        } else {
            echo "User not found";
        }

        $stmt->close();
    } catch (Exception $e) {
        echo "Database error";
    }
    
    exit;
}
?>