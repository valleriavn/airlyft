<?php
session_start();
include("db/connect.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = mysqli_real_escape_string($conn, $_POST["email"]);
    $password = $_POST["password"];

    // Validate Gmail - more flexible
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header("Location: ../login.php?error=invalid_email");
        exit();
    }

    // Check if it's a Gmail address (case-insensitive)
    $email_lower = strtolower($email);
    if (!str_ends_with($email_lower, '@gmail.com')) {
        header("Location: ../login.php?error=invalid_gmail");
        exit();
    }

    // Validate password length
    if (strlen($password) < 8) {
        header("Location: ../login.php?error=short_password");
        exit();
    }

    $query = "SELECT * FROM Users WHERE LOWER(email) = LOWER('$email')";
    $result = mysqli_query($conn, $query);

    if ($row = mysqli_fetch_assoc($result)) {
        if (password_verify($password, $row["password"])) {
            // FIXED: Using correct column names from database
            $_SESSION["user_id"] = $row["user_id"];
            $_SESSION["firstname"] = $row["first_name"] ?? '';
            $_SESSION["lastname"] = $row["last_name"] ?? '';
            $_SESSION["fullname"] = ($row["first_name"] ?? '') . " " . ($row["last_name"] ?? '');
            $_SESSION["email"] = $row["email"];
            $_SESSION["role"] = $row["role"];

            if ($row["role"] == "Admin") {
                header("Location: admin_dashboard.php");
            } else {
                header("Location: ../booking/destinations.php");
            }
            exit();
        } else {
            header("Location: ../login.php?error=invalid_password");
            exit();
        }
    }

    header("Location: ../login.php?error=user_not_found");
    exit();
}
