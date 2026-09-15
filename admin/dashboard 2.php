<?php
session_start();
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");
require_once '../config/db.php';

// Sirf admin yahan aa sakta hai
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

// Request delete
if (isset($_GET['delete_request'])) {
    $id = intval($_GET['delete_request']);
    $stmt = $pdo->prepare("DELETE FROM requests WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: dashboard.php");
    exit();
}

// Property delete - pehle requests phir property
if (isset($_GET['delete_property'])) {
    $id = intval($_GET['delete_property']);
    $stmt = $pdo->prepare("DELETE FROM requests WHERE property_id = ?");
    $stmt->execute([$id]);
    $stmt = $pdo->prepare("DELETE FROM properties WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: dashboard.php");
    exit();
}

// Saare users lo - admin khud nahi
$users = $pdo->query("SELECT * FROM users WHERE role != 'admin'")->fetchAll(PDO::FETCH_ASSOC);

// Properties with filter
$prop_query  = "SELECT properties.*, users.name as owner_name 
                FROM properties 
                JOIN users ON properties.user_id = users.id
                WHERE 1=1";
$prop_params = [];

if (!empty($_GET['location'])) {
    $prop_query   .= " AND properties.location LIKE ?";
    $prop_params[] = '%' . $_GET['location'] . '%';
}

if (!empty($_GET['max_rent'])) {
    $prop_query   .= " AND properties.rent <= ?";
    $prop_params[] = $_GET['max_rent'];
}

$stmt = $pdo->prepare($prop_query);
$stmt->execute($prop_params);
$properties = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Saari requests with JOIN
$requests = $pdo->query("SELECT requests.*, 
                          users.name as renter_name,
                          properties.title as property_title
                          FROM requests
                          JOIN users ON requests.renter_id = users.id
                          JOIN properties ON requests.property_id = properties.id
                          ORDER BY requests.created_at DESC")
                          ->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard - KirayaHub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-dark bg-danger px-4">
    <span class="navbar-brand d-flex align-items-center">
        <svg width="28" height="28" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg" class="me-2">
            <polygon points="50,10 90,45 80,45 80,90 20,90 20,45 10,45" fill="white"/>
            <rect x="38" y="60" width="24" height="30" fill="#4a4a8a"/>
            <rect x="55" y="48" width="15" height="15" fill="#1a1a2e"/>
        </svg>
        KirayaHub Admin
    </span>
    <div>
        <span class="text-white me-3">Welcome, <?php echo $_SESSION['user_name']; ?></span>
        <a href="../auth/logout.php" class="btn btn-outline-light btn-sm">Logout</a>
    </div>
</nav>

<div class="container mt-4">

    <!-- Stats -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card text-white bg-primary shadow-sm">
                <div class="card-body">
                    <h5>Total Users</h5>
                    <h2><?php echo count($users); ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-white bg-success shadow-sm">
                <div class="card-body">
                    <h5>Total Properties</h5>
                    <h2><?php echo count($properties); ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-white bg-warning shadow-sm">
                <div class="card-body">
                    <h5>Total Requests</h5>
                    <h2><?php echo count($requests); ?></h2>
                </div>
            </div>
        </div>
    </div>

    <!-- Users -->
    <h5 class="mb-3">All Users</h5>
    <table class="table table-bordered mb-5">
        <thead class="table-dark">
            <tr><th>ID</th><th>Name</th><th>Email</th><th>Role</th><th>Joined</th></tr>
        </thead>
        <tbody>
            <?php foreach ($users as $user): ?>
                <tr>
                    <td><?php echo $user['id']; ?></td>
                    <td><?php echo $user['name']; ?></td>
                    <td><?php echo $user['email']; ?></td>
                    <td><?php echo ucfirst($user['role']); ?></td>
                    <td><?php echo $user['created_at']; ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- Properties with Filter -->
    <h5 class="mb-3">All Properties</h5>
    <form method="GET" class="row g-3 mb-3">
        <div class="col-md-4">
            <input type="text" name="location" class="form-control"
                   placeholder="Filter by location"
                   value="<?php echo $_GET['location'] ?? ''; ?>">
        </div>
        <div class="col-md-3">
            <input type="number" name="max_rent" class="form-control"
                   placeholder="Max Rent"
                   value="<?php echo $_GET['max_rent'] ?? ''; ?>">
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-primary w-100">Filter</button>
        </div>
        <div class="col-md-2">
            <a href="dashboard.php" class="btn btn-secondary w-100">Reset</a>
        </div>
    </form>

    <table class="table table-bordered mb-5">
        <thead class="table-dark">
            <tr><th>ID</th><th>Title</th><th>Location</th><th>Rent</th><th>Owner</th><th>Status</th><th>Action</th></tr>
        </thead>
        <tbody>
            <?php foreach ($properties as $property): ?>
                <tr>
                    <td><?php echo $property['id']; ?></td>
                    <td><?php echo $property['title']; ?></td>
                    <td><?php echo $property['location']; ?></td>
                    <td>Rs. <?php echo $property['rent']; ?></td>
                    <td><?php echo $property['owner_name']; ?></td>
                    <td><?php echo ucfirst($property['status']); ?></td>
                    <td>
                        <a href="dashboard.php?delete_property=<?php echo $property['id']; ?>"
                           class="btn btn-danger btn-sm"
                           onclick="return confirm('Delete this property and all its requests?')">Delete</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- Requests -->
    <h5 class="mb-3">All Requests</h5>
    <table class="table table-bordered mb-5">
        <thead class="table-dark">
            <tr><th>ID</th><th>Renter</th><th>Property</th><th>Status</th><th>Date</th><th>Action</th></tr>
        </thead>
        <tbody>
            <?php foreach ($requests as $req): ?>
                <tr>
                    <td><?php echo $req['id']; ?></td>
                    <td><?php echo $req['renter_name']; ?></td>
                    <td><?php echo $req['property_title']; ?></td>
                    <td>
                        <?php
                        $badge = 'warning';
                        if ($req['status'] == 'accepted') $badge = 'success';
                        if ($req['status'] == 'rejected') $badge = 'danger';
                        ?>
                        <span class="badge bg-<?php echo $badge; ?>">
                            <?php echo ucfirst($req['status']); ?>
                        </span>
                    </td>
                    <td><?php echo $req['created_at']; ?></td>
                    <td>
                        <a href="dashboard.php?delete_request=<?php echo $req['id']; ?>"
                           class="btn btn-danger btn-sm"
                           onclick="return confirm('Delete this request?')">Delete</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
</body>
</html>