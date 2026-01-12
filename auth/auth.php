<?php
session_start();
include("db/connect.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // The input field is named "email" not "name"
    $email = mysqli_real_escape_string($conn, $_POST["email"]);
    $password = $_POST["password"];

    // Get user by email - table name should be "Users"
    $query = "SELECT * FROM Users WHERE email = '$email'";
    $result = mysqli_query($conn, $query);

    if ($row = mysqli_fetch_assoc($result)) {
        // Verify hashed password
        if (password_verify($password, $row["password"])) {
            $_SESSION["user_id"] = $row["user_id"];
            $_SESSION["name"] = $row["name"];
            $_SESSION["email"] = $row["email"];
            $_SESSION["role"] = $row["role"];

            // ROLE BASED REDIRECTION
            if ($row["role"] == "Admin") {
                header("Location: admin_dashboard.php");
            } else {
                header("Location: ../booking/destinations.php");
            }
            exit();
        }
    }

    header("Location: ../login.php?error=1");
    exit();
}
?>