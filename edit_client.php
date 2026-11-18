
<?php
require_once "db_connect.php";

// =====================================
// GET CLIENT ID
// =====================================

$client_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$client_id || $client_id < 1) {
    header("Location: index.php?error=invalid_id");
    exit;
}

// =====================================
// FETCH CLIENT
// =====================================

$stmt = $conn->prepare("
    SELECT * FROM clients
    WHERE client_id = :id
");

$stmt->execute([':id' => $client_id]);

$client = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$client) {
    header("Location: index.php?error=not_found");
    exit;
}

// =====================================
// INITIAL FORM VALUES
// =====================================

$full_name = $client['full_name'];
$email = $client['email'];
$phone = $client['phone'];
$company = $client['company'] ?? "";
$address = $client['address'] ?? "";
$status = $client['status'];

$errors = [];

// =====================================
// PROCESS UPDATE
// =====================================

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $company = trim($_POST['company'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $status = $_POST['status'] ?? 'Active';

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
        $errors[] = "Security verification failed. Please try again.";
    }

    // =====================================
    // REQUIRED FIELDS
    // =====================================

    if ($full_name === '') {
        $errors[] = "Full name is required.";
    }

    if ($email === '') {
        $errors[] = "Email address is required.";
    }

    if ($phone === '') {
        $errors[] = "Phone number is required.";
    }

    // =====================================
    // FULL NAME VALIDATION
    // =====================================

    if ($full_name !== '' && strlen($full_name) > 100) {
        $errors[] = "Full name must not exceed 100 characters.";
    }

    // =====================================
    // EMAIL VALIDATION
    // =====================================

    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email address.";
    }

    if (strlen($email) > 100) {
        $errors[] = "Email must not exceed 100 characters.";
    }

    // =====================================
    // PHONE VALIDATION
    // =====================================

    if ($phone !== '') {

        if (!preg_match('/^[0-9+\-\s()]+$/', $phone)) {
            $errors[] = "Phone number contains invalid characters.";
        }

        $digitsOnly = preg_replace('/\D/', '', $phone);

        if (strlen($digitsOnly) < 7 || strlen($digitsOnly) > 15) {
            $errors[] = "Phone number must contain 7 to 15 digits.";
        }

        if (strlen($phone) > 20) {
            $errors[] = "Phone number must not exceed 20 characters.";
        }
    }

    // =====================================
    // COMPANY VALIDATION
    // =====================================

    if (strlen($company) > 100) {
        $errors[] = "Company name must not exceed 100 characters.";
    }

    // =====================================
    // STATUS VALIDATION
    // =====================================

    if (!in_array($status, ['Active', 'Inactive'], true)) {
        $errors[] = "Please select a valid client status.";
    }

    // =====================================
    // CHECK DUPLICATE EMAIL
    // EXCLUDE CURRENT CLIENT
    // =====================================

    if (empty($errors)) {

        $duplicateStmt = $conn->prepare("
            SELECT client_id
            FROM clients
            WHERE email = :email
            AND client_id != :id
            LIMIT 1
        ");

        $duplicateStmt->execute([
            ':email' => $email,
            ':id' => $client_id
        ]);

        if ($duplicateStmt->fetch()) {
            $errors[] = "This email address is already registered to another client.";
        }
    }

    // =====================================
    // UPDATE DATABASE
    // =====================================

    if (empty($errors)) {

        try {

            $updateStmt = $conn->prepare("
                UPDATE clients
                SET
                    full_name = :full_name,
                    email = :email,
                    phone = :phone,
                    company = :company,
                    address = :address,
                    status = :status
                WHERE client_id = :id
            ");

            $updateStmt->execute([
                ':full_name' => $full_name,
                ':email' => $email,
                ':phone' => $phone,
                ':company' => $company,
                ':address' => $address,
                ':status' => $status,
                ':id' => $client_id
            ]);

            header("Location: index.php?success=updated");
            exit;

        } catch (PDOException $e) {

            $errors[] = "Unable to update the client. Please try again.";

        }
    }
}

// =====================================
// HTML ESCAPE FUNCTION
// =====================================

function e($value) {
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Edit Client - ClientHub</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- Custom CSS -->
    <link rel="stylesheet"
          href="assets/css/style.css">

</head>

<body>

<div class="dashboard-wrapper">

    <!-- SIDEBAR -->

    <aside class="sidebar">

        <div class="brand">
            <i class="bi bi-people-fill"></i>
            Client<span class="brand-light">Hub</span>
        </div>

        <div class="menu-label">MAIN MENU</div>

        <a href="index.php" class="nav-link">
            <i class="bi bi-grid"></i>
            Dashboard
        </a>

        <a href="index.php" class="nav-link">
            <i class="bi bi-person-lines-fill"></i>
            Client Records
        </a>

        <div class="sidebar-footer">
            <i class="bi bi-shield-check"></i>
            ClientHub Management System
        </div>

    </aside>

    <!-- MAIN CONTENT -->

    <main class="main-content">

        <!-- TOPBAR -->

        <header class="topbar">

            <div>
                <h5>Client Management</h5>
                <small>Manage your business clients easily.</small>
            </div>

            <div class="admin-profile">
                <i class="bi bi-person-circle"></i>
                Administrator
            </div>

        </header>

        <!-- CONTENT AREA -->

        <section class="content-area">

            <!-- PAGE TITLE -->

            <div class="welcome-section">

                <h2>Edit Client</h2>

                <p>
                    Update the client's information below.
                </p>

            </div>

            <!-- VALIDATION ERRORS -->

            <?php if (!empty($errors)): ?>

                <div class="alert alert-danger">

                    <strong>
                        <i class="bi bi-exclamation-triangle-fill"></i>
                        Please check the following:
                    </strong>

                    <ul class="mb-0 mt-2">

                        <?php foreach ($errors as $error): ?>

                            <li><?= e($error) ?></li>

                        <?php endforeach; ?>

                    </ul>

                </div>

            <?php endif; ?>

            <!-- EDIT CLIENT FORM -->

            <div class="table-container">

                <h5 class="mb-4">
                    <i class="bi bi-pencil-square"></i>
                    Client Information
                </h5>

                <form method="POST"
                      action="edit_client.php?id=<?= (int) $client_id ?>">

                    <!-- CSRF TOKEN -->

                    <input type="hidden"
                           name="csrf_token"
                           value="<?= e($_SESSION['csrf_token']) ?>">

                    <div class="row g-3">

                        <!-- FULL NAME -->

                        <div class="col-md-6">

                            <label class="form-label">
                                Full Name <span class="text-danger">*</span>
                            </label>

                            <input type="text"
                                   name="full_name"
                                   class="form-control"
                                   maxlength="100"
                                   value="<?= e($full_name) ?>"
                                   required>

                        </div>

                        <!-- EMAIL -->

                        <div class="col-md-6">

                            <label class="form-label">
                                Email Address <span class="text-danger">*</span>
                            </label>

                            <input type="email"
                                   name="email"
                                   class="form-control"
                                   maxlength="100"
                                   value="<?= e($email) ?>"
                                   required>

                        </div>

                        <!-- PHONE -->

                        <div class="col-md-6">

                            <label class="form-label">
                                Phone Number <span class="text-danger">*</span>
                            </label>

                            <input type="tel"
                                   name="phone"
                                   class="form-control"
                                   maxlength="20"
                                   value="<?= e($phone) ?>"
                                   required>

                        </div>

                        <!-- COMPANY -->

                        <div class="col-md-6">

                            <label class="form-label">
                                Company
                            </label>

                            <input type="text"
                                   name="company"
                                   class="form-control"
                                   maxlength="100"
                                   value="<?= e($company) ?>">

                        </div>

                        <!-- ADDRESS -->

                        <div class="col-12">

                            <label class="form-label">
                                Address
                            </label>

                            <textarea name="address"
                                      class="form-control"
                                      rows="3"><?= e($address) ?></textarea>

                        </div>

                        <!-- STATUS -->

                        <div class="col-md-6">

                            <label class="form-label">
                                Client Status
                            </label>

                            <select name="status"
                                    class="form-select">

                                <option value="Active"
                                    <?= $status === 'Active' ? 'selected' : '' ?>>
                                    Active
                                </option>

                                <option value="Inactive"
                                    <?= $status === 'Inactive' ? 'selected' : '' ?>>
                                    Inactive
                                </option>

                            </select>

                        </div>

                    </div>

                    <!-- FORM BUTTONS -->

                    <div class="d-flex flex-wrap gap-2 mt-4">

                        <button type="submit"
                                class="btn btn-primary">

                            <i class="bi bi-save"></i>
                            Update Client

                        </button>

                        <a href="index.php"
                           class="btn btn-outline-secondary">

                            <i class="bi bi-arrow-left"></i>
                            Back to Dashboard

                        </a>

                    </div>

                    <p class="text-muted small mt-3 mb-0">
                        <span class="text-danger">*</span>
                        Required fields
                    </p>

                </form>

            </div>

        </section>

        <!-- FOOTER -->

        <footer class="page-footer">
            © <?= date('Y') ?> ClientHub. All Rights Reserved.
        </footer>

    </main>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
