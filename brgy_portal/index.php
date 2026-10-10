<?php
session_start();
require_once "db.php";

if (isset($_SESSION["user_id"], $_SESSION["role"])) {
    header("Location: " .
        ($_SESSION["role"] === "admin" ? "home2.php" : "home.php"));
    exit;
}

$error = "";
$success = "";
$page = $_GET["page"] ?? "login";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST["action"] ?? "";

    // REGISTRATION

    if ($action === "register") {
        $username = trim($_POST["username"] ?? "");
        $password = $_POST["password"] ?? "";
        $confirm = $_POST["confirm_password"] ?? "";
        $page = "register";

        if ($username === "" || $password === "" || $confirm === "") {
            $error = "All fields are required.";
        } elseif (!preg_match('/^[a-zA-Z0-9_]{3,50}$/', $username)) {
            $error = "Username must be 3-50 characters using letters, numbers, or underscores.";
        } elseif (strlen($password) < 8) {
            $error = "Password must be at least 8 characters.";
        } elseif ($password !== $confirm) {
            $error = "Passwords do not match.";
        } else {
            $check = $conn->prepare(
                "SELECT id FROM users WHERE username = ? LIMIT 1"
            );
            $check->bind_param("s", $username);
            $check->execute();
            $check->store_result();

            if ($check->num_rows > 0) {
                $error = "Username already exists.";
            } else {
                $hashedPassword = password_hash(
                    $password,
                    PASSWORD_DEFAULT
                );


                $stmt = $conn->prepare(
                    "INSERT INTO users (username, password, role)
                     VALUES (?, ?, 'user')"
                );
                $stmt->bind_param("ss", $username, $hashedPassword);

                if ($stmt->execute()) {
                    $success = "Registration successful. You can now log in.";
                    $page = "login";
                } else {
                    $error = "Registration failed. Please try again.";
                }

                $stmt->close();
            }

            $check->close();
        }

    // LOGIN

    } elseif ($action === "login") {
        $username = trim($_POST["username"] ?? "");
        $password = $_POST["password"] ?? "";
        $page = "login";

        if ($username === "" || $password === "") {
            $error = "Please fill in all fields.";
        } else {
            $stmt = $conn->prepare(
                "SELECT id, username, password, role
                 FROM users
                 WHERE username = ?
                 LIMIT 1"
            );
            $stmt->bind_param("s", $username);
            $stmt->execute();

            $result = $stmt->get_result();
            $user = $result->fetch_assoc();

            if ($user && password_verify($password, $user["password"])) {
                session_regenerate_id(true);

                $_SESSION["user_id"] = $user["id"];
                $_SESSION["username"] = $user["username"];
                $_SESSION["role"] = $user["role"];

                header("Location: " .
                    ($user["role"] === "admin" ? "home2.php" : "home.php"));
                exit;
            } else {
                $error = "Invalid username or password.";
            }

            $stmt->close();
        }
    } else {
        $error = "Invalid request.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Barangay Cogunan Services Portal</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>

<div class="auth-wrap">
    <div class="auth-card">

        <div class="brand">
            <h1>Barangay Cogunan</h1>
            <p>Fast, easy, and no queues.</p>
        </div>

        <?php if ($error !== ""): ?>
            <div class="alert error"><?= e($error) ?></div>
        <?php endif; ?>

        <?php if ($success !== ""): ?>
            <div class="alert success"><?= e($success) ?></div>
        <?php endif; ?>

        <?php if ($page === "register"): ?>

            <h2>Create an Account</h2>

            <form method="POST" action="index.php?page=register">
                <input type="hidden" name="action" value="register">

                <label for="username">Username</label>
                <input type="text" id="username" name="username"
                       minlength="3" maxlength="50" required>

                <label for="password">Password</label>
                <div class="password-field">
                    <input type="password" id="password" name="password"
                           minlength="8" required>
                    <button type="button" class="toggle" data-target="password">Show</button>
                </div>

                <label for="confirm_password">Confirm Password</label>
                <div class="password-field">
                    <input type="password" id="confirm_password" name="confirm_password"
                           minlength="8" required>
                    <button type="button" class="toggle" data-target="confirm_password">Show</button>
                </div>

                <button type="submit" class="btn">Register</button>
            </form>

            <p class="switch">Already have an account? <a href="index.php">Login</a></p>

        <?php else: ?>

            <h2>Login</h2>

            <form method="POST" action="index.php">
                <input type="hidden" name="action" value="login">

                <label for="username">Username</label>
                <input type="text" id="username" name="username" maxlength="50" required>

                <label for="password">Password</label>
                <div class="password-field">
                    <input type="password" id="password" name="password" required>
                    <button type="button" class="toggle" data-target="password">Show</button>
                </div>

                <button type="submit" class="btn">Login</button>
            </form>

            <p class="switch">Don't have an account? <a href="index.php?page=register">Register</a></p>

        <?php endif; ?>

    </div>
</div>

<script>
// Show / hide password
document.querySelectorAll(".toggle").forEach(function (btn) {
    btn.addEventListener("click", function () {
        var input = document.getElementById(btn.dataset.target);
        var hidden = input.type === "password";
        input.type = hidden ? "text" : "password";
        btn.textContent = hidden ? "Hide" : "Show";
    });
});
</script>

</body>
</html>
