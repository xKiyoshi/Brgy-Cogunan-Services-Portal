<?php
session_start();
require_once "db.php";

// Must be logged in
if (!isset($_SESSION["user_id"], $_SESSION["role"])) {
    header("Location: index.php");
    exit;
}

// Admins belong on home2.php
if ($_SESSION["role"] !== "user") {
    header("Location: home2.php");
    exit;
}

// Logout: clear the session and its cookie
if (isset($_GET["logout"])) {
    $_SESSION = [];

    if (ini_get("session.use_cookies")) {
        $p = session_get_cookie_params();
        setcookie(session_name(), "", time() - 42000,
            $p["path"], $p["domain"], $p["secure"], $p["httponly"]);
    }

    session_destroy();
    header("Location: index.php");
    exit;
}

$userId = $_SESSION["user_id"];
$username = $_SESSION["username"];
$error = "";
$success = isset($_GET["sent"]) ? "Your request has been submitted." : "";

$documents = [
    "Barangay Clearance",
    "Certificate of Indigency",
    "Certificate of Residency"
];

// SUBMIT A REQUEST

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $document = $_POST["document_type"] ?? "";
    $purpose = trim($_POST["purpose"] ?? "");

    if ($document === "" || $purpose === "") {
        $error = "Please choose a document and enter the purpose.";
    } elseif (!in_array($document, $documents, true)) {
        $error = "Invalid document type.";
    } elseif (strlen($purpose) > 255) {
        $error = "Purpose must not exceed 255 characters.";
    } else {
        $stmt = $conn->prepare(
            "INSERT INTO requests (user_id, document_type, purpose)
             VALUES (?, ?, ?)"
        );
        $stmt->bind_param("iss", $userId, $document, $purpose);

        if ($stmt->execute()) {
            $stmt->close();
            header("Location: home.php?sent=1");
            exit;
        } else {
            $error = "Request failed. Please try again.";
        }

        $stmt->close();
    }
}

// GET THIS USER'S REQUESTS ONLY

$stmt = $conn->prepare(
    "SELECT document_type, purpose, status, created_at
     FROM requests
     WHERE user_id = ?
     ORDER BY created_at DESC, id DESC"
);
$stmt->bind_param("i", $userId);
$stmt->execute();
$requests = $stmt->get_result();

// Badge color for each status
function badgeClass($status) {
    if ($status === "Processing") return "processing";
    if ($status === "Ready for Pickup") return "ready";
    if ($status === "Rejected") return "rejected";
    return "pending";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Home | Barangay Cogunan</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>

<div class="navbar">
    <span class="name">Barangay Cogunan</span>
    <div class="right">
        <span><?= e($username) ?></span>
        <a class="logout" href="home.php?logout=1">Logout</a>
    </div>
</div>

<div class="container">

    <div class="banner">
        <h1>Good day, <?= e($username) ?>!</h1>
    </div>

    <div class="card">
        <h2>Request a Document</h2>

        <?php if ($error !== ""): ?>
            <div class="alert error"><?= e($error) ?></div>
        <?php endif; ?>

        <?php if ($success !== ""): ?>
            <div class="alert success"><?= e($success) ?></div>
        <?php endif; ?>

        <form method="POST" action="home.php">
            <label for="document_type">Document</label>
            <select id="document_type" name="document_type" required>
                <option value="">Select a document</option>
                <?php foreach ($documents as $doc): ?>
                    <option value="<?= e($doc) ?>"><?= e($doc) ?></option>
                <?php endforeach; ?>
            </select>

            <label for="purpose">Purpose</label>
            <textarea id="purpose" name="purpose" rows="3"
                      maxlength="255" required></textarea>

            <button type="submit" class="btn">Submit Request</button>
        </form>
    </div>

    <div class="card">
        <h2>My Requests</h2>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Document</th>
                        <th>Purpose</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($requests->num_rows === 0): ?>
                        <tr><td colspan="4" class="empty">No requests yet.</td></tr>
                    <?php else: ?>
                        <?php while ($row = $requests->fetch_assoc()): ?>
                            <tr>
                                <td><?= e(date("M d, Y", strtotime($row["created_at"]))) ?></td>
                                <td><?= e($row["document_type"]) ?></td>
                                <td><?= e($row["purpose"]) ?></td>
                                <td>
                                    <span class="badge <?= badgeClass($row["status"]) ?>">
                                        <?= e($row["status"]) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

</body>
</html>
