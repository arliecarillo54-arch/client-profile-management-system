
<?php
require_once "db_connect.php";

// =====================================
// ALLOW POST REQUESTS ONLY
// =====================================

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit;
}

// =====================================
// CSRF TOKEN VALIDATION
// =====================================

$submittedToken = $_POST['csrf_token'] ?? '';
$sessionToken = $_SESSION['csrf_token'] ?? '';

if (
    empty($submittedToken) ||
    empty($sessionToken) ||
    !hash_equals($sessionToken, $submittedToken)
) {
    header("Location: index.php?error=invalid_token");
    exit;
}

// =====================================
// VALIDATE CLIENT ID
// =====================================

$clientId = filter_input(
    INPUT_POST,
    'client_id',
    FILTER_VALIDATE_INT
);

if (!$clientId || $clientId <= 0) {
    header("Location: index.php?error=invalid_id");
    exit;
}

// =====================================
// CHECK IF CLIENT EXISTS
// =====================================

$checkStmt = $conn->prepare("
    SELECT client_id
    FROM clients
    WHERE client_id = :id
");

$checkStmt->execute([
    ':id' => $clientId
]);

if (!$checkStmt->fetch()) {
    header("Location: index.php?error=not_found");
    exit;
}

// =====================================
// DELETE CLIENT
// =====================================

$deleteStmt = $conn->prepare("
    DELETE FROM clients
    WHERE client_id = :id
");

$deleteStmt->execute([
    ':id' => $clientId
]);

// =====================================
// REDIRECT AFTER SUCCESS
// =====================================

header("Location: index.php?success=deleted");
exit;
?>
