<?php
// admin/api/fetch_transactions.php - Backend API for fetching paginated and filtered transactions

// Set content type to JSON
header('Content-Type: application/json');

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check for admin login (basic security)
if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit();
}

// Include database connection
require_once '../../includes/db_connection.php'; // Adjust path as necessary

// Check if database connection is successful
if ($link === false) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed.']);
    exit();
}

// Get pagination parameters
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 20; // Default to 20 items per page

// Ensure limit is within a reasonable range (e.g., 1 to 100)
$limit = max(1, min(100, $limit));

// Calculate offset
$offset = ($page - 1) * $limit;

// Initialize filters
$conditions = [];
$params = [];
$types = '';

// Search term
$search = $_GET['search'] ?? '';
if (!empty($search)) {
    $searchTerm = '%' . $search . '%';
    // Search by student_name or nfc_id or txn_id
    $conditions[] = "(s.full_name LIKE ? OR t.nfc_id LIKE ? OR t.txn_id LIKE ?)";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $types .= 'sss'; // Adjusted types for 3 parameters
}

// Status filter
$status = $_GET['status'] ?? '';
if (!empty($status) && in_array($status, ['completed', 'pending'])) {
    $conditions[] = "t.status = ?";
    $params[] = $status;
    $types .= 's';
}

// Date range filter
$startDate = $_GET['start_date'] ?? '';
$endDate = $_GET['end_date'] ?? '';

if (!empty($startDate)) {
    $conditions[] = "DATE(t.transaction_time) >= ?"; // Changed t.timestamp to t.transaction_time
    $params[] = $startDate;
    $types .= 's';
}
if (!empty($endDate)) {
    $conditions[] = "DATE(t.transaction_time) <= ?"; // Changed t.timestamp to t.transaction_time
    $params[] = $endDate;
    $types .= 's';
}

// Build WHERE clause
$whereClause = '';
if (!empty($conditions)) {
    $whereClause = 'WHERE ' . implode(' AND ', $conditions);
}

// --- Fetch Total Transactions Count ---
$countSql = "SELECT COUNT(t.txn_id) AS total_transactions FROM `transaction` t LEFT JOIN `student` s ON t.student_id = s.student_id $whereClause";
$stmt = $link->prepare($countSql);

if ($stmt === false) {
    echo json_encode(['success' => false, 'message' => 'Failed to prepare count statement: ' . $link->error]);
    mysqli_close($link);
    exit();
}

if (!empty($conditions)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$countResult = $stmt->get_result();
$totalTransactions = $countResult->fetch_assoc()['total_transactions'];
$stmt->close();

// Calculate total pages
$totalPages = ceil($totalTransactions / $limit);
// Ensure current page is not greater than total pages (unless total pages is 0)
$page = ($totalPages > 0) ? min($page, $totalPages) : 1;
// Recalculate offset in case page was adjusted
$offset = ($page - 1) * $limit;


// --- Fetch Transactions Data ---
$sql = "SELECT
            t.txn_id,
            t.nfc_id,
            t.total_amount,
            t.transaction_time, -- Changed t.timestamp to t.transaction_time
            t.status,
            s.full_name AS student_name,
            s.student_id
        FROM `transaction` t
        LEFT JOIN `student` s ON t.student_id = s.student_id
        $whereClause
        ORDER BY t.transaction_time DESC -- Changed t.timestamp to t.transaction_time
        LIMIT ? OFFSET ?";

$stmt = $link->prepare($sql);

if ($stmt === false) {
    echo json_encode(['success' => false, 'message' => 'Failed to prepare statement: ' . $link->error]);
    mysqli_close($link);
    exit();
}

// Add limit and offset parameters to the binding
// Create a temporary array for parameters including limit and offset
$finalParams = $params;
$finalTypes = $types;

$finalTypes .= 'ii'; // Add 'ii' for limit and offset (integers)
$finalParams[] = $limit;
$finalParams[] = $offset;

// Use call_user_func_array for dynamic binding
if (!empty($finalParams)) {
    $bind_names = [$finalTypes];
    for ($i = 0; $i < count($finalParams); $i++) {
        $bind_name = 'bind' . $i;
        $$bind_name = $finalParams[$i];
        $bind_names[] = &$$bind_name;
    }
    call_user_func_array([$stmt, 'bind_param'], $bind_names);
}


$stmt->execute();
$result = $stmt->get_result();

$transactions = [];
while ($row = $result->fetch_assoc()) {
    $transactions[] = $row;
}

$stmt->close();
mysqli_close($link);

echo json_encode([
    'success' => true,
    'transactions' => $transactions,
    'current_page' => $page,
    'total_pages' => $totalPages,
    'total_transactions' => $totalTransactions,
    'items_per_page' => $limit
]);
?>