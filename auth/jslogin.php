<?php
// auth/jslogin.php
session_start();
include("../db/connect.php");

header('Content-Type: text/plain; charset=utf-8');

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Get and sanitize input
    $email = trim($_POST["email"] ?? '');
    $password = $_POST["password"] ?? '';
    $remember = isset($_POST['remember']) && $_POST['remember'] === '1';

    // Validate input
    if (empty($email) || empty($password)) {
        echo "missing_fields";
        exit;
    }

    // Email validation
    $email_lower = strtolower($email);
    if (!filter_var($email_lower, FILTER_VALIDATE_EMAIL)) {
        echo "invalid_email";
        exit;
    }

    // Gmail validation
    if (!preg_match('/@gmail\.com$/i', $email_lower)) {
        echo "invalid_gmail";
        exit;
    }

    // Password length validation
    if (strlen($password) < 8) {
        echo "short_password";
        exit;
    }

    try {
        // Try lowercase table name first
        $stmt = $conn->prepare("SELECT user_id, first_name, last_name, email, password, role FROM users WHERE LOWER(email) = ? LIMIT 1");

        if (!$stmt) {
            // If fails, try uppercase table name
            $stmt = $conn->prepare("SELECT user_id, first_name, last_name, email, password, role FROM Users WHERE LOWER(email) = ? LIMIT 1");
        }

        if (!$stmt) {
            throw new Exception("Database query preparation failed: " . $conn->error);
        }

        $stmt->bind_param("s", $email_lower);

        if (!$stmt->execute()) {
            throw new Exception("Query execution failed: " . $stmt->error);
        }

        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $row = $result->fetch_assoc();

            // DEBUG: Log password info
            error_log("Login attempt for: " . $row['email']);

            // Verify password
            if (password_verify($password, $row["password"])) {
                // Set session variables
                $_SESSION["user_id"] = $row["user_id"];
                $_SESSION["firstname"] = $row["first_name"] ?? '';
                $_SESSION["lastname"] = $row["last_name"] ?? '';
                $_SESSION["fullname"] = ($row["first_name"] ?? '') . " " . ($row["last_name"] ?? '');
                $_SESSION["email"] = $row["email"];
                $_SESSION["role"] = $row["role"];
                $_SESSION["name"] = $_SESSION["fullname"];

                error_log("Login SUCCESS for user ID: " . $row["user_id"]);

                // Set remember me cookie if requested
                if ($remember) {
                    $cookie_value = base64_encode($email_lower . '|' . time());
                    setcookie("remember_email", $cookie_value, time() + (30 * 24 * 60 * 60), "/", "", true, true);
                }

                // Return success based on role
                echo ($row["role"] === "Admin") ? "admin" : "user";
            } else {
                error_log("Password verification FAILED");
                echo "invalid_password";
            }
        } else {
            error_log("User not found: " . $email_lower);
            echo "user_not_found";
        }

        $stmt->close();
    } catch (Exception $e) {
        error_log("Login error: " . $e->getMessage());
        echo "database_error";
    }

    exit;
}

// Handle cookie-based auto-login
if (!isset($_SESSION["user_id"]) && isset($_COOKIE["remember_email"])) {
    try {
        $cookie_data = base64_decode($_COOKIE["remember_email"]);
        $parts = explode('|', $cookie_data);

        if (count($parts) === 2) {
            $email_lower = $parts[0];
            $timestamp = $parts[1];

            // Check if cookie is still valid (within 30 days)
            if (time() - $timestamp < 30 * 24 * 60 * 60) {
                $stmt = $conn->prepare("SELECT user_id, first_name, last_name, email, role FROM users WHERE LOWER(email) = ? LIMIT 1");
                if (!$stmt) {
                    $stmt = $conn->prepare("SELECT user_id, first_name, last_name, email, role FROM Users WHERE LOWER(email) = ? LIMIT 1");
                }

                if ($stmt) {
                    $stmt->bind_param("s", $email_lower);
                    $stmt->execute();
                    $result = $stmt->get_result();

                    if ($result->num_rows === 1) {
                        $row = $result->fetch_assoc();

                        $_SESSION["user_id"] = $row["user_id"];
                        $_SESSION["firstname"] = $row["first_name"] ?? '';
                        $_SESSION["lastname"] = $row["last_name"] ?? '';
                        $_SESSION["fullname"] = $row["first_name"] . " " . $row["last_name"];
                        $_SESSION["email"] = $row["email"];
                        $_SESSION["role"] = $row["role"];
                        $_SESSION["name"] = $_SESSION["fullname"];
                    }

                    $stmt->close();
                }
            }
        }
    } catch (Exception $e) {
        error_log("Cookie auto-login error: " . $e->getMessage());
    }
}
