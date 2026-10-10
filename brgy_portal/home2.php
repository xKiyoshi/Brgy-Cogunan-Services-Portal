<?php
session_start();
require_once "db.php";

if (!isset($_SESSION["user_id"], $_SESSION["role"])) {
    header("Location: index.php");
    exit;
}

if ($_SESSION["role"] !== "admin") {
    header("Location: home.php");
    exit;
}

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

$error = "";
$success = isset($_GET["updated"]) ? "Status updated." : "";

$statuses = ["Pending", "Processing", "Ready for Pickup", "Rejected"];

// UPDATE REQUEST STATUS

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $requestId = (int) ($_POST["request_id"] ?? 0);
    $status = $_POST["status"] ?? "";

    if ($requestId <= 0 || !in_array($status, $statuses, true)) {
        $error = "Invalid status update.";
    } else {
        $stmt = $conn->prepare("UPDATE requests SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $status, $requestId);

        if ($stmt->execute()) {
            $stmt->close();
            header("Location: home2.php?updated=1");
            exit;
        } else {
            $error = "Update failed. Please try again.";
        }

        $stmt->close();
    }
}

// SUMMARY NUMBERS

$totalUsers = $conn->query(
    "SELECT COUNT(*) AS total FROM users WHERE role = 'user'"
)->fetch_assoc()["total"];

$totalRequests = $conn->query(
    "SELECT COUNT(*) AS total FROM requests"
)->fetch_assoc()["total"];

$pendingRequests = $conn->query(
    "SELECT COUNT(*) AS total FROM requests WHERE status = 'Pending'"
)->fetch_assoc()["total"];

// ALL REQUESTS WITH USERNAME

$requests = $conn->query(
    "SELECT r.id, r.document_type, r.purpose, r.status, r.created_at, u.username
     FROM requests r
     JOIN users u ON u.id = r.user_id
     ORDER BY r.created_at DESC, r.id DESC"
);

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
    <title>Admin | Barangay Cogunan</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>

<div class="navbar">
    <span class="name">Barangay Cogunan &middot; Admin</span>
    <div class="right">
        <a class="logout" href="home2.php?logout=1">Logout</a>
    </div>
</div>

<div class="container">

    <div class="stats">
        <div class="stat">
            <p>Total Users</p>
            <h2><?= (int) $totalUsers ?></h2>
        </div>
        <div class="stat">
            <p>Total Requests</p>
            <h2><?= (int) $totalRequests ?></h2>
        </div>
        <div class="stat">
            <p>Pending Requests</p>
            <h2><?= (int) $pendingRequests ?></h2>
        </div>
    </div>

    <div class="card">
        <h2>All Requests</h2>

        <?php if ($error !== ""): ?>
            <div class="alert error"><?= e($error) ?></div>
        <?php endif; ?>

        <?php if ($success !== ""): ?>
            <div class="alert success"><?= e($success) ?></div>
        <?php endif; ?>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Resident</th>
                        <th>Document</th>
                        <th>Purpose</th>
                        <th>Status</th>
                        <th>Update</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($requests->num_rows === 0): ?>
                        <tr><td colspan="6" class="empty">No requests yet.</td></tr>
                    <?php else: ?>
                        <?php while ($row = $requests->fetch_assoc()): ?>
                            <tr>
                                <td><?= e(date("M d, Y", strtotime($row["created_at"]))) ?></td>
                                <td><?= e($row["username"]) ?></td>
                                <td><?= e($row["document_type"]) ?></td>
                                <td><?= e($row["purpose"]) ?></td>
                                <td>
                                    <span class="badge <?= badgeClass($row["status"]) ?>">
                                        <?= e($row["status"]) ?>
                                    </span>
                                </td>
                                <td>
                                    <form method="POST" action="home2.php" class="status-form">
                                        <input type="hidden" name="request_id" value="<?= (int) $row["id"] ?>">
                                        <select name="status">
                                            <?php foreach ($statuses as $s): ?>
                                                <option value="<?= e($s) ?>"
                                                    <?= $s === $row["status"] ? "selected" : "" ?>>
                                                    <?= e($s) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button type="submit" class="btn-small">Update</button>
                                    </form>
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
