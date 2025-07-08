<?php
// student/order_history.php - Student's Past Orders

date_default_timezone_set('Asia/Kathmandu'); // Or your server's timezone
session_start();

// --- REQUIRE STUDENT LOGIN ---
// Ensure only logged-in students can access this
if (!isset($_SESSION['student_id']) || empty($_SESSION['student_id'])) {
    // If not logged in as student, redirect to the student login page
    // Adjust the path to your student login page if it's different
    $_SESSION['login_error_message'] = "You need to log in to view your order history.";
    header('Location: ../login.php'); // Assuming login.php is in the root
    exit();
}

$current_student_id = $_SESSION['student_id'];
$student_name = $_SESSION['student_full_name'] ?? 'Student'; // Assuming you store full_name in session

// Include database connection
// Path: From student/ UP to root (../) THEN into includes/
require_once '../includes/db_connection.php';
include '../includes/packages.php'; // For Tailwind, Flowbite, common CSS/JS - adjust path

// --- Pagination Variables ---
$page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT, ['options' => ['default' => 1, 'min_range' => 1]]);
$itemsPerPage = 10; // Number of orders per page
$offset = ($page - 1) * $itemsPerPage;
$totalRecords = 0;
$orders = [];

// --- Fetch Total Orders for Pagination ---
$sqlCount = "SELECT COUNT(*) AS total FROM transaction WHERE student_id = ?";
if ($stmtCount = mysqli_prepare($link, $sqlCount)) {
    mysqli_stmt_bind_param($stmtCount, "i", $current_student_id);
    mysqli_stmt_execute($stmtCount);
    $resultCount = mysqli_stmt_get_result($stmtCount);
    if ($rowCount = mysqli_fetch_assoc($resultCount)) {
        $totalRecords = (int)$rowCount['total'];
    }
    mysqli_stmt_close($stmtCount);
} else {
    error_log("DB Error (order_history.php - count): " . mysqli_error($link));
    // Handle error, maybe show a message
}

// --- Fetch Paginated Orders for the Current Student ---
if ($totalRecords > 0) {
    $sqlOrders = "SELECT txn_id, formatted_id, total_amount, status, transaction_time
                  FROM transaction
                  WHERE student_id = ?
                  ORDER BY transaction_time DESC
                  LIMIT ?, ?";
    if ($stmtOrders = mysqli_prepare($link, $sqlOrders)) {
        mysqli_stmt_bind_param($stmtOrders, "iii", $current_student_id, $offset, $itemsPerPage);
        mysqli_stmt_execute($stmtOrders);
        $resultOrders = mysqli_stmt_get_result($stmtOrders);
        while ($row = mysqli_fetch_assoc($resultOrders)) {
            $orders[] = $row;
        }
        mysqli_stmt_close($stmtOrders);
    } else {
        error_log("DB Error (order_history.php - fetch orders): " . mysqli_error($link));
        // Handle error
    }
}

$totalPages = ceil($totalRecords / $itemsPerPage);

// No longer including header.php here
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Order History - <?php echo htmlspecialchars($student_name); ?></title>
    <style>
        /* Basic styling - integrate with your main CSS or Tailwind */
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f3f4f6; /* Light gray background for the body */
            color: #1f2937; /* Default text color */
        }
        .container { max-width: 900px; margin: 2rem auto; padding: 0 1rem; }
        .order-table { width: 100%; border-collapse: collapse; margin-top: 1.5rem; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .order-table th, .order-table td { border: 1px solid #e2e8f0; padding: 0.75rem 1rem; text-align: left; color: #1f2937; }
        .order-table th { background-color: #f7fafc; font-weight: 600; color: #4b5563; }
        .order-table tr:nth-child(even) { background-color: #f9fafb; } /* Slightly different shade for even rows */
        .order-table a { color: #4299e1; text-decoration: none; }
        .order-table a:hover { text-decoration: underline; }
        .status-success { color: #38a169; font-weight: bold; }
        .status-failed { color: #e53e3e; font-weight: bold; }
        .status-refunded { color: #dd6b20; font-weight: bold; }
        .pagination { margin-top: 2rem; text-align: center; }
        .pagination a, .pagination span { display: inline-block; padding: 0.5rem 0.75rem; margin: 0 0.25rem; border: 1px solid #cbd5e0; border-radius: 0.25rem; text-decoration: none; color: #4a5568; }
        .pagination a:hover { background-color: #e2e8f0; }
        .pagination .current-page { background-color: #4299e1; color: white; border-color: #4299e1; }
        .pagination .disabled { color: #a0aec0; pointer-events: none; }
        .no-orders { text-align: center; padding: 2rem; background-color: #fff; border-radius: 0.5rem; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-top: 1.5rem; }
    </style>
</head>
<body class="bg-gray-100 text-gray-900 font-sans min-h-screen flex flex-col">

    <header class="bg-white text-blue-600 p-4 shadow-md">
        <div class="container mx-auto flex justify-between items-center">
            <h1 class="text-3xl font-bold">Smart Canteen</h1>
            <nav>
                <a href="../index.php" class="text-blue-600 hover:text-blue-800 px-4 py-2 rounded-lg transition duration-200 font-semibold">Home</a>
                <a href="./order_history.php" class="text-blue-600 hover:text-blue-800 px-4 py-2 rounded-lg transition duration-200 font-semibold">Order History</a>
                <a href="./profile.php" class="text-blue-600 hover:text-blue-800 px-4 py-2 rounded-lg transition duration-200 font-semibold">Profile</a>
                <?php if (isset($_SESSION['current_student_info'])): ?>
                    <a href="../api/logout.php" class="bg-blue-600 text-white px-6 py-2 rounded-lg shadow hover:bg-blue-700 transition duration-200 font-semibold">Logout</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>

    <main class="flex-grow">
        <div class="container mx-auto px-4 py-8">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-3xl font-bold text-gray-800">My Order History</h1>
                <a href="../index.php" class="text-indigo-600 hover:text-indigo-800">&larr; Back to Canteen</a>
            </div>

            <?php if (empty($orders)): ?>
                <div class="no-orders">
                    <p class="text-xl">You haven't placed any orders yet.</p>
                    <a href="../index.php" class="mt-4 inline-block bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2 px-4 rounded">
                        Start Shopping
                    </a>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto bg-white shadow-md rounded-lg">
                    <table class="min-w-full order-table">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Order ID</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date & Time</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Total Amount</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php foreach ($orders as $order): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-800"><?php echo htmlspecialchars($order['formatted_id']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                        <?php
                                        try {
                                            $date = new DateTime($order['transaction_time'], new DateTimeZone('UTC')); // Assuming DB time is UTC
                                            $date->setTimezone(new DateTimeZone(date_default_timezone_get())); // Convert to server's/local timezone
                                            echo $date->format('M j, Y, g:i A');
                                        } catch (Exception $e) {
                                            echo htmlspecialchars($order['transaction_time']); // Fallback
                                        }
                                        ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-700">₹<?php echo number_format($order['total_amount'], 2); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-center">
                                        <span class="status-<?php echo htmlspecialchars(strtolower($order['status'])); ?>">
                                            <?php echo ucfirst(htmlspecialchars($order['status'])); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-center">
                                        <a href="order_details.php?txn_id=<?php echo $order['txn_id']; ?>" class="text-indigo-600 hover:text-indigo-900">View Details</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($totalPages > 1): ?>
                <nav class="pagination mt-8" aria-label="Pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?>" class="hover:bg-gray-200">&laquo; Previous</a>
                    <?php else: ?>
                        <span class="disabled">&laquo; Previous</span>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <?php if ($i == $page): ?>
                            <span class="current-page"><?php echo $i; ?></span>
                        <?php else: ?>
                            <a href="?page=<?php echo $i; ?>" class="hover:bg-gray-200"><?php echo $i; ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>

                    <?php if ($page < $totalPages): ?>
                        <a href="?page=<?php echo $page + 1; ?>" class="hover:bg-gray-200">Next &raquo;</a>
                    <?php else: ?>
                        <span class="disabled">Next &raquo;</span>
                    <?php endif; ?>
                </nav>
                <?php endif; ?>

            <?php endif; ?>
        </div>
    </main>

    <footer class="bg-gray-800 text-white p-4 text-center mt-auto">
        <div class="container mx-auto">
            &copy; <?= date('Y') ?> Smart Canteen. All rights reserved.
        </div>
    </footer>

    <?php
    if (isset($link) && $link) {
        mysqli_close($link);
    }
    ?>
</body>
</html>
