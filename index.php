
<?php
require_once "db_connect.php";

// =====================================
// SEARCH AND FILTER
// =====================================

$search = trim($_GET['search'] ?? '');
$statusFilter = $_GET['status'] ?? 'all';

if (!in_array($statusFilter, ['all', 'active', 'inactive'], true)) {
    $statusFilter = 'all';
}

// =====================================
// PAGINATION
// =====================================

$recordsPerPage = 5;

$currentPage = isset($_GET['page']) && is_numeric($_GET['page'])
    ? max(1, (int) $_GET['page'])
    : 1;

// =====================================
// SEARCH CONDITIONS
// =====================================

$whereConditions = [];
$queryParams = [];

if ($search !== '') {
    $whereConditions[] = "(
        full_name LIKE :name
        OR email LIKE :email
        OR phone LIKE :phone
        OR company LIKE :company
    )";

    $searchTerm = "%$search%";

    $queryParams[':name'] = $searchTerm;
    $queryParams[':email'] = $searchTerm;
    $queryParams[':phone'] = $searchTerm;
    $queryParams[':company'] = $searchTerm;
}

if ($statusFilter !== 'all') {
    $whereConditions[] = "status = :status";
    $queryParams[':status'] = ucfirst($statusFilter);
}

$whereSQL = '';

if (!empty($whereConditions)) {
    $whereSQL = 'WHERE ' . implode(' AND ', $whereConditions);
}

// =====================================
// COUNT FILTERED CLIENTS
// =====================================

$countStmt = $conn->prepare("
    SELECT COUNT(*)
    FROM clients
    $whereSQL
");

$countStmt->execute($queryParams);

$totalClients = (int) $countStmt->fetchColumn();

$totalPages = max(
    1,
    (int) ceil($totalClients / $recordsPerPage)
);

$currentPage = min($currentPage, $totalPages);

$offset = ($currentPage - 1) * $recordsPerPage;

// =====================================
// DASHBOARD STATISTICS
// =====================================

$statsStmt = $conn->query("
    SELECT
        COUNT(*) AS total_count,
        SUM(CASE WHEN status = 'Active' THEN 1 ELSE 0 END) AS active_count,
        SUM(CASE WHEN status = 'Inactive' THEN 1 ELSE 0 END) AS inactive_count
    FROM clients
");

$stats = $statsStmt->fetch(PDO::FETCH_ASSOC);

$allClients = (int) ($stats['total_count'] ?? 0);
$activeClients = (int) ($stats['active_count'] ?? 0);
$inactiveClients = (int) ($stats['inactive_count'] ?? 0);

// =====================================
// GET CLIENT RECORDS
// OLDEST FIRST, NEWEST LAST
// =====================================

$stmt = $conn->prepare("
    SELECT *
    FROM clients
    $whereSQL
    ORDER BY client_id ASC
    LIMIT :limit OFFSET :offset
");

foreach ($queryParams as $key => $value) {
    $stmt->bindValue($key, $value);
}

$stmt->bindValue(':limit', $recordsPerPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

$stmt->execute();

$clients = $stmt->fetchAll(PDO::FETCH_ASSOC);

// =====================================
// PAGINATION URL
// =====================================

function pageUrl($page, $search, $status) {
    return '?' . http_build_query([
        'page' => $page,
        'search' => $search,
        'status' => $status
    ]);
}

// =====================================
// NOTIFICATIONS
// =====================================

$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? '';

$successMessages = [
    'added' => 'Client successfully added!',
    'updated' => 'Client information successfully updated!',
    'deleted' => 'Client successfully deleted!'
];

$errorMessages = [
    'invalid_id' => 'Invalid client ID.',
    'not_found' => 'Client record was not found.',
    'invalid_token' => 'Security verification failed. Please try again.'
];

// =====================================
// ESCAPE OUTPUT
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

    <title>Client Management Dashboard</title>

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

        <a href="index.php" class="nav-link active">
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

            <!-- WELCOME SECTION -->

            <div class="welcome-section d-flex justify-content-between align-items-center flex-wrap gap-3">

                <div>
                    <h2>Dashboard Overview</h2>
                    <p>Welcome! Here's your client management summary.</p>
                </div>

                <a href="add_client.php" class="btn btn-primary">
                    <i class="bi bi-person-plus-fill"></i>
                    Add New Client
                </a>

            </div>

            <!-- NOTIFICATIONS -->

            <?php if (isset($successMessages[$success])): ?>

                <div class="alert alert-success alert-dismissible fade show">

                    <i class="bi bi-check-circle-fill"></i>
                    <?= e($successMessages[$success]) ?>

                    <button type="button"
                            class="btn-close"
                            data-bs-dismiss="alert"></button>

                </div>

            <?php endif; ?>

            <?php if (isset($errorMessages[$error])): ?>

                <div class="alert alert-danger alert-dismissible fade show">

                    <i class="bi bi-exclamation-circle-fill"></i>
                    <?= e($errorMessages[$error]) ?>

                    <button type="button"
                            class="btn-close"
                            data-bs-dismiss="alert"></button>

                </div>

            <?php endif; ?>

            <!-- STATISTICS CARDS -->

            <div class="row g-4">

                <div class="col-md-4">

                    <div class="stat-card">

                        <div class="stat-icon blue">
                            <i class="bi bi-people"></i>
                        </div>

                        <h6>Total Clients</h6>
                        <h3><?= $allClients ?></h3>

                    </div>

                </div>

                <div class="col-md-4">

                    <div class="stat-card">

                        <div class="stat-icon green">
                            <i class="bi bi-person-check"></i>
                        </div>

                        <h6>Active Clients</h6>
                        <h3><?= $activeClients ?></h3>

                    </div>

                </div>

                <div class="col-md-4">

                    <div class="stat-card">

                        <div class="stat-icon orange">
                            <i class="bi bi-person-dash"></i>
                        </div>

                        <h6>Inactive Clients</h6>
                        <h3><?= $inactiveClients ?></h3>

                    </div>

                </div>

            </div>

            <!-- CLIENT RECORDS -->

            <div class="table-container">

                <div class="table-header">

                    <div>
                        <h5>Client Records</h5>

                        <p class="text-muted mb-0">
                            View and manage your registered clients.
                        </p>
                    </div>

                    <!-- SEARCH AND FILTER -->

                    <form method="GET"
                          action="index.php"
                          class="d-flex flex-wrap gap-2">

                        <div class="search-box">

                            <div class="input-group">

                                <span class="input-group-text bg-white">
                                    <i class="bi bi-search"></i>
                                </span>

                                <input type="text"
                                       name="search"
                                       class="form-control"
                                       placeholder="Search clients..."
                                       value="<?= e($search) ?>">

                            </div>

                        </div>

                        <select name="status"
                                class="form-select"
                                onchange="this.form.submit()">

                            <option value="all"
                                <?= $statusFilter === 'all' ? 'selected' : '' ?>>
                                All Status
                            </option>

                            <option value="active"
                                <?= $statusFilter === 'active' ? 'selected' : '' ?>>
                                Active
                            </option>

                            <option value="inactive"
                                <?= $statusFilter === 'inactive' ? 'selected' : '' ?>>
                                Inactive
                            </option>

                        </select>

                        <button type="submit" class="btn btn-primary">
                            Search
                        </button>

                        <?php if ($search !== '' || $statusFilter !== 'all'): ?>

                            <a href="index.php"
                               class="btn btn-outline-secondary">
                                Reset
                            </a>

                        <?php endif; ?>

                    </form>

                </div>

                <!-- CLIENT TABLE -->

                <div class="table-responsive">

                    <table class="table">

                        <thead>
                            <tr>
                                <th>Client ID</th>
                                <th>Full Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Company</th>
                                <th>Status</th>
                                <th>Date Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>

                        <tbody>

                            <?php if (count($clients) > 0): ?>

                                <?php $rowNumber = $offset + 1; ?>

                                <?php foreach ($clients as $client): ?>

                                    <tr>

                                        <td>#<?= $rowNumber++ ?></td>

                                        <td><?= e($client['full_name']) ?></td>

                                        <td><?= e($client['email']) ?></td>

                                        <td><?= e($client['phone']) ?></td>

                                        <td><?= e($client['company']) ?></td>

                                        <td>
                                            <span class="status-badge <?= strtolower(e($client['status'])) ?>">
                                                <?= e($client['status']) ?>
                                            </span>
                                        </td>

                                        <td><?= e($client['date_created']) ?></td>

                                        <td>

                                            <div class="d-flex gap-2">

                                                <!-- EDIT -->

                                                <a href="edit_client.php?id=<?= (int) $client['client_id'] ?>"
                                                   class="btn btn-primary btn-sm">

                                                    <i class="bi bi-pencil-square"></i>
                                                    Edit

                                                </a>

                                                <!-- DELETE WITH CSRF PROTECTION -->

                                                <form action="delete_client.php"
                                                      method="POST"
                                                      onsubmit="return confirm('Are you sure you want to delete this client?');">

                                                    <input type="hidden"
                                                           name="csrf_token"
                                                           value="<?= e($_SESSION['csrf_token']) ?>">

                                                    <input type="hidden"
                                                           name="client_id"
                                                           value="<?= (int) $client['client_id'] ?>">

                                                    <button type="submit"
                                                            class="btn btn-danger btn-sm">

                                                        <i class="bi bi-trash"></i>
                                                        Delete

                                                    </button>

                                                </form>

                                            </div>

                                        </td>

                                    </tr>

                                <?php endforeach; ?>

                            <?php else: ?>

                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">

                                        <i class="bi bi-search fs-3 d-block mb-2"></i>

                                        No matching client records found.

                                    </td>
                                </tr>

                            <?php endif; ?>

                        </tbody>

                    </table>

                </div>

                <!-- PAGINATION -->

                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mt-3">

                    <div class="text-muted small">

                        Showing
                        <?= $totalClients > 0 ? $offset + 1 : 0 ?>

                        to

                        <?= min($offset + count($clients), $totalClients) ?>

                        of

                        <?= $totalClients ?>

                        matching clients

                    </div>

                    <nav aria-label="Client pagination">

                        <ul class="pagination">

                            <li class="page-item <?= $currentPage <= 1 ? 'disabled' : '' ?>">

                                <a class="page-link"
                                   href="<?= e(pageUrl(max(1, $currentPage - 1), $search, $statusFilter)) ?>">

                                    <i class="bi bi-chevron-left"></i>
                                    Previous

                                </a>

                            </li>

                            <li class="page-item active">

                                <span class="page-link">
                                    <?= $currentPage ?> / <?= $totalPages ?>
                                </span>

                            </li>

                            <li class="page-item <?= $currentPage >= $totalPages ? 'disabled' : '' ?>">

                                <a class="page-link"
                                   href="<?= e(pageUrl(min($totalPages, $currentPage + 1), $search, $statusFilter)) ?>">

                                    Next
                                    <i class="bi bi-chevron-right"></i>

                                </a>

                            </li>

                        </ul>

                    </nav>

                </div>

            </div>

        </section>

        <!-- FOOTER -->

        <footer class="page-footer">
            © <?= date('Y') ?> ClientHub. All Rights Reserved.
        </footer>

    </main>

</div>

<!-- BOOTSTRAP JAVASCRIPT -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
