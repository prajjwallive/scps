<?php
// admin/dashboard.php - Admin Dashboard (Coordinated Data Fetching for Charts)

// Set the default timezone (optional, but good practice)
// Choose a timezone identifier from https://www.php.net/manual/en/timezones.php
date_default_timezone_set('Asia/Kathmandu'); // <<< Set your correct timezone here

// Start the session
session_start();

// --- REQUIRE ADMIN LOGIN ---
// Check if admin_id is NOT set in the session or is empty
if (!isset($_SESSION['admin_id']) || empty($_SESSION['admin_id'])) {
    // If not logged in as admin, redirect to the admin login page
    header('Location: login.php'); // login.php is in the same admin folder
    exit(); // Stop script execution
}
// --- END REQUIRE ADMIN LOGIN ---

// Include database connection
// Path: From admin/ UP to root (../) THEN into includes/
require_once '../includes/db_connection.php'; // This file MUST correctly create the $link variable

// Get admin username and role from session for header (optional, for header display)
$admin_username = $_SESSION['admin_username'] ?? 'Admin';
$admin_role = $_SESSION['admin_role'] ?? 'N/A';

// Include necessary packages (like CSS) - Path from admin/ UP to root (../) then includes/
include '../includes/packages.php'; // Ensure this path is correct

// --- Fetch Data from Database (for top cards - PHP based) ---
// Note: This PHP fetching is kept from your original file.
// An alternative approach (used in the admin_sales_frontend immersive)
// is to fetch these stats via a dedicated API as well.

// Initialize variables to avoid undefined errors if queries fail
$total_sales = 0;
$items_sold = 0;
$total_unique_customers = 0; // Represents total unique customers for now
$avg_order_value = 0;

// Fetch Total Sales (Sum of total_amount from completed transactions)
// Using 'success' status based on your database schema enum
$sql_total_sales = "SELECT SUM(total_amount) AS total_revenue FROM transaction WHERE status = 'success'";
$result_total_sales = mysqli_query($link, $sql_total_sales); // Using $link

if ($result_total_sales) {
    $row_total_sales = mysqli_fetch_assoc($result_total_sales);
    $total_sales = $row_total_sales['total_revenue'] ?? 0;
} else {
    error_log("Error fetching total sales: " . mysqli_error($link)); // Using $link
}

// Fetch Total Items Sold (Sum of quantity from transaction_item for successful transactions)
// Joining transaction_item and transaction tables on txn_id
$sql_items_sold = "SELECT SUM(ti.quantity) AS total_items FROM transaction_item ti JOIN transaction t ON ti.txn_id = t.txn_id WHERE t.status = 'success'";
$result_items_sold = mysqli_query($link, $sql_items_sold); // Using $link

if ($result_items_sold) {
    $row_items_sold = mysqli_fetch_assoc($result_items_sold);
    $items_sold = $row_items_sold['total_items'] ?? 0;
} else {
    // FIX: Corrected the syntax error here - removed the extra double quote
    error_log("Error fetching total items sold: " . mysqli_error($link)); // Using $link
}

// Fetch Total Unique Customers (Count of distinct student_id in transaction table for successful transactions)
$sql_unique_customers = "SELECT COUNT(DISTINCT student_id) AS total_unique_customers FROM transaction WHERE student_id IS NOT NULL AND status = 'success'";
$result_unique_customers = mysqli_query($link, $sql_unique_customers); // Using $link

if ($result_unique_customers) {
    $row_unique_customers = mysqli_fetch_assoc($result_unique_customers);
    $total_unique_customers = $row_unique_customers['total_unique_customers'] ?? 0;
} else {
    error_log("Error fetching unique customers: " . mysqli_error($link)); // Using $link
}

// Calculate Avg. Order Value (Total Sales / Count of Successful Transactions)
$sql_successful_transactions_count = "SELECT COUNT(txn_id) AS successful_count FROM transaction WHERE status = 'success'";
$result_successful_transactions_count = mysqli_query($link, $sql_successful_transactions_count); // Using $link
$successful_transactions_count = 0;

if ($result_successful_transactions_count) {
    $row_successful_transactions_count = mysqli_fetch_assoc($result_successful_transactions_count);
    $successful_transactions_count = $row_successful_transactions_count['successful_count'] ?? 0;
} else {
     error_log("Error fetching successful transactions count: " . mysqli_error($link)); // Using $link
}

if ($successful_transactions_count > 0) {
    $avg_order_value = $total_sales / $successful_transactions_count;
} else {
    $avg_order_value = 0;
}

// --- End Fetch Data ---

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <?php // include "../includes/packages.php"; // Included above ?>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
     <style>
        /* Optional: Add any custom styles needed here */
        .chart-container {
            position: relative;
            height: 100%;
            width: 100%;
        }
         /* Style for the message displayed when no data or error in charts */
         .chart-message {
             position: absolute;
             top: 50%;
             left: 50%;
             transform: translate(-50%, -50%);
             text-align: center;
             font-size: 1rem;
             color: #6b7280; /* gray-500 */
             z-index: 1; /* Ensure message is above canvas */
         }

         .chart-message.error {
             color: #dc2626; /* red-600 */
         }
     </style>
</head>

<body class="bg-gray-100 font-sans">
    <?php include '../includes/admin_header.php'; // Include header ONCE here ?>


    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        <div id="admin-confirmation" class="fixed top-5 left-1/2 -translate-x-1/2 bg-green-500 text-white p-4 rounded-md shadow-lg z-50 opacity-0 hidden transition-all duration-500 ease-in-out transform">
            Action successful!
        </div>


        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4 mb-8">
            <div class="bg-white overflow-hidden shadow rounded-lg"> <div class="px-4 py-5 sm:p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 bg-indigo-500 rounded-md p-3">
                            <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0-2.08-.402-2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dt class="text-sm font-medium text-gray-500 truncate"> Total Sales (Success)
                            </dt>
                            <dd class="flex items-baseline">
                                <div class="text-2xl font-semibold text-gray-900"> ₹<?php echo number_format($total_sales, 2); ?>
                                </div>
                                 <div class="ml-2 flex items-baseline text-sm font-semibold text-gray-500"> --
                                </div>
                            </dd>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow rounded-lg"> <div class="px-4 py-5 sm:p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 bg-green-500 rounded-md p-3">
                            <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                            </svg>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dt class="text-sm font-medium text-gray-500 truncate"> Total Items Sold (Success Orders)
                            </dt>
                            <dd class="flex items-baseline">
                                <div class="text-2xl font-semibold text-gray-900"> <?php echo number_format($items_sold); ?>
                                </div>
                                 <div class="ml-2 flex items-baseline text-sm font-semibold text-gray-500"> --
                                </div>
                            </dd>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow rounded-lg"> <div class="px-4 py-5 sm:p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 bg-blue-500 rounded-md p-3">
                            <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dt class="text-sm font-medium text-gray-500 truncate"> Total Unique Customers
                            </dt>
                            <dd class="flex items-baseline">
                                <div class="text-2xl font-semibold text-gray-900"> <?php echo number_format($total_unique_customers); ?>
                                </div>
                                 <div class="ml-2 flex items-baseline text-sm font-semibold text-gray-500"> --
                                </div>
                            </dd>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow rounded-lg"> <div class="px-4 py-5 sm:p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 bg-yellow-500 rounded-md p-3">
                            <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2zM10 8.5a.5.5 0 11-1 0 .5.5 0 011 0zm5 5a.5.5 0 11-1 0 .5.5 0 011 0z" />
                            </svg>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dt class="text-sm font-medium text-gray-500 truncate"> Avg. Order Value (Success Orders)
                            </dt>
                            <dd class="flex items-baseline">
                                <div class="text-2xl font-semibold text-gray-900"> ₹<?php echo number_format($avg_order_value, 2); ?>
                                </div>
                                 <div class="ml-2 flex items-baseline text-sm font-semibold text-gray-500"> --
                                </div>
                            </dd>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
            <div class="bg-white shadow rounded-lg p-6">
                <div class="flex flex-wrap items-center justify-between gap-4 mb-4">
                    <h2 class="text-lg font-medium text-gray-900 flex-shrink-0">Sales Overview</h2>
                    <div class="flex items-center gap-2 flex-grow justify-end">
                        <label for="dashboardDateRange" class="text-sm font-medium text-gray-700 flex-shrink-0">Date Range:</label>
                        <select id="dashboardDateRange" class="block appearance-none bg-white border border-gray-300 hover:border-gray-400 px-2 py-1 pr-6 rounded shadow leading-tight focus:outline-none focus:shadow-outline text-sm flex-shrink-0">
                            <option value="today">Today</option>
                            <option value="this_week">This Week</option>
                            <option value="this_month" selected>This Month</option>
                            <option value="last_month">Last Month</option>
                            <option value="this_year">This Year</option>
                            <option value="all_time">All Time</option>
                            <option value="custom">Custom</option>
                        </select>
                        <div id="dashboardCustomDateRangeContainer" class="hidden flex flex-row flex-wrap gap-2 items-center">
                            <label for="dashboardCustomStartDate" class="text-sm font-medium text-gray-700 sr-only">From:</label>
                            <input type="date" id="dashboardCustomStartDate" class="px-2 py-1 border border-gray-300 rounded-md shadow-sm text-sm">
                            <label for="dashboardCustomEndDate" class="text-sm font-medium text-gray-700 sr-only">To:</label>
                            <input type="date" id="dashboardCustomEndDate" class="px-2 py-1 border border-gray-300 rounded-md shadow-sm text-sm">
                            <button id="applyDashboardCustomRangeBtn" class="bg-blue-500 text-white px-3 py-1.5 rounded-md hover:bg-blue-600 transition-colors text-sm">Apply</button>
                        </div>
                        <div class="flex gap-1">
                            <button data-granularity="daily" class="px-3 py-1.5 rounded-md text-sm font-medium bg-gray-100 text-gray-800 hover:bg-gray-200">D</button>
                            <button data-granularity="weekly" class="px-3 py-1.5 rounded-md text-sm font-medium bg-gray-100 text-gray-800 hover:bg-gray-200">W</button>
                            <button data-granularity="monthly" class="px-3 py-1.5 rounded-md text-sm font-medium bg-indigo-100 text-indigo-800">M</button>
                        </div>
                    </div>
                </div>
                <div class="h-80 chart-container">
                    <canvas id="salesChart"></canvas>
                </div>
            </div>

            <div class="bg-white shadow rounded-lg p-6">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-lg font-medium text-gray-900">Top Selling Products</h2>
                    <div>
                        <label for="dashboardCategoryFilter" class="sr-only">Filter by Category</label>
                        <select id="dashboardCategoryFilter" class="block appearance-none w-full bg-white border border-gray-300 hover:border-gray-400 px-4 py-2 pr-8 rounded shadow leading-tight focus:outline-none focus:shadow-outline text-sm">
                            <option value="all">All Categories</option>
                        </select>
                    </div>
                </div>
                <div class="h-80 chart-container">
                    <canvas id="productsChart"></canvas>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <div class="bg-white shadow rounded-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200"> <h2 class="text-lg font-medium text-gray-900">Recent Orders</h2> </div>
                <div class="divide-y divide-gray-200" id="recentOrdersList"> <div class="px-6 py-4 text-center text-gray-500">Loading recent orders...</div> </div>
                <div class="px-6 py-4 border-t border-gray-200"> <a href="transaction.php" class="text-sm font-medium text-indigo-600 hover:text-indigo-500">View all orders</a> </div>
            </div>

            <div class="bg-white shadow rounded-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200"> <h2 class="text-lg font-medium text-gray-900">Activity Feed</h2> </div>
                <div class="divide-y divide-gray-200" id="activityFeedList"> <div class="px-6 py-4 text-center text-gray-500">Loading activity feed...</div> </div>
                <div class="px-6 py-4 border-t border-gray-200"> <a href="activity.php" class="text-sm font-medium text-indigo-600 hover:text-indigo-500">View all activity</a> </div>
            </div>
        </div>
    </main>

    <footer class="bg-white border-t border-gray-200 mt-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <p class="text-center text-sm text-gray-500">&copy; 2023 Your Company. All rights reserved.</p> </div>
    </footer>

    <script>
         // Helper function to show confirmation message (reused pattern)
        const adminConfirmation = document.getElementById('admin-confirmation');
        function showConfirmation(message = 'Success!', color = 'green') {
            if (!adminConfirmation) return;
            adminConfirmation.textContent = message;
            adminConfirmation.className = `fixed top-5 left-1/2 transform -translate-x-1/2 p-4 rounded-md shadow-lg z-50 opacity-0 hidden transition-all duration-500 ease-in-out transform bg-${color}-500 block`; // Reset classes
            setTimeout(() => { adminConfirmation.classList.add('opacity-100'); }, 10);
            setTimeout(() => {
                adminConfirmation.classList.remove('opacity-100');
                setTimeout(() => { adminConfirmation.classList.remove('block'); adminConfirmation.classList.add('hidden'); }, 500);
            }, 3000); // Show for 3 seconds
        }

         // Helper to format currency
         function formatCurrency(amount) {
              const number = parseFloat(amount) || 0;
              return `${number.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
          }

          // Helper to format number
           function formatNumber(number) {
               const num = parseFloat(number) || 0;
               return num.toLocaleString('en-IN');
           }

         // Helper to format date and time
         function formatDateTime(dateTimeStr) {
              if (!dateTimeStr) return 'N/A';
             try {
                 const date = new Date(dateTimeStr);
                 // Ensure date is a valid Date object
                 if (isNaN(date.getTime())) {
                     console.error("Invalid date provided to formatDateTime:", dateTimeStr);
                     return 'Invalid Date';
                 }
                 // Format to 'D M Y, h:i A' (e.g., 9 May 2025, 10:15 AM)
                 const options = { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit', hour12: true };
                 return date.toLocaleDateString('en-IN', options);
             } catch (e) {
                 console.error("Error formatting date/time:", dateTimeStr, e);
                 return 'Invalid Date';
             }
         }

        function htmlspecialchars(str) {
            if (typeof str !== 'string' && str !== null && str !== undefined) { str = String(str); }
            else if (str === null || str === undefined) { return ''; }
            const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
            return str.replace(/[&<>"']/g, function(m) { return map[m]; });
        }


        // Chart Instances - Keep global or in a scope accessible by initCharts
        let salesChart, productsChart;

        // Variables to hold fetched data
        let dashboardSalesData = [];
        let dashboardTopProductsData = [];

        // Flags to track data fetching completion
        let salesDataFetched = false;
        let topProductsDataFetched = false;

        // --- Date Range Handling Elements ---
        const dashboardDateRangeSelect = document.getElementById('dashboardDateRange');
        const dashboardCustomDateRangeContainer = document.getElementById('dashboardCustomDateRangeContainer');
        const dashboardCustomStartDateInput = document.getElementById('dashboardCustomStartDate');
        const dashboardCustomEndDateInput = document.getElementById('dashboardCustomEndDate');
        const applyDashboardCustomRangeBtn = document.getElementById('applyDashboardCustomRangeBtn');

        // --- Category Filter Elements ---
        const dashboardCategoryFilterSelect = document.getElementById('dashboardCategoryFilter');


        // Initialize charts (call this AFTER data is loaded and stored)
         function initCharts() { // Removed parameters, uses global/scoped data
             // Destroy existing charts if they exist to prevent duplicates
            if (salesChart) salesChart.destroy();
            if (productsChart) productsChart.destroy();

             const chartBaseOptions = {
                 responsive: true,
                 maintainAspectRatio: false,
                  plugins: {
                      legend: {
                          position: 'top',
                           labels: {
                                color: '#6b7280' // Light mode text color
                            }
                       },
                       tooltip: {
                           backgroundColor: '#fff', // Light theme
                           titleColor: '#6b7280', // Light theme
                           bodyColor: '#6b7280', // Light theme
                           borderColor: '#e5e7eb',
                           borderWidth: 1
                       }
                  },
                   scales: { // Base scale options
                       y: {
                           beginAtZero: true,
                            ticks: { color: '#6b7280' }, // Light mode text color
                            grid: { color: '#e5e7eb' } // Light mode grid color
                       },
                        x: {
                           ticks: { color: '#6b7280' }, // Light mode text color
                            grid: { color: '#e5e7eb' } // Light mode grid color
                        }
                   }
             };


            // Sales Chart (Monthly Overview)
            const salesCtx = document.getElementById('salesChart');
            if (salesCtx) {
                 let salesCanvas = document.getElementById('salesChart');
                 let salesChartContainer = salesCanvas ? salesCanvas.parentElement : null;
                 if (!salesCanvas || !salesChartContainer) {
                     console.error('Sales Chart container or canvas not found for initialization!');
                 } else {
                      salesChartContainer.querySelectorAll('.chart-message').forEach(msgEl => msgEl.remove());

                       if (dashboardSalesData && dashboardSalesData.length > 0) {
                            if (salesCanvas) salesCanvas.style.display = 'block';

                            const salesChartOptions = JSON.parse(JSON.stringify(chartBaseOptions));
                            salesChartOptions.scales.y.title = { display: true, text: 'Sales (₹)', color: '#6b7280' };
                            salesChartOptions.scales.y1 = {
                                beginAtZero: true,
                                position: 'right',
                                grid: { drawOnChartArea: false, color: '#e5e7eb' },
                                title: { display: true, text: 'Items Sold', color: '#6b7280' }
                            };

                           salesChart = new Chart(salesCtx, {
                               type: 'line',
                               data: {
                                   labels: dashboardSalesData.map(item => `${item.period}`),
                                   datasets: [{
                                       label: 'Total Sales',
                                       data: dashboardSalesData.map(item => item.revenue ?? 0),
                                        backgroundColor: 'rgba(79, 70, 229, 0.05)',
                                        borderColor: 'rgba(79, 70, 229, 1)',
                                       borderWidth: 2,
                                       tension: 0.1,
                                       fill: true,
                                        yAxisID: 'y'
                                   }, {
                                       label: 'Items Sold',
                                       data: dashboardSalesData.map(item => item.items_sold ?? 0),
                                        backgroundColor: 'rgba(16, 185, 129, 0.05)',
                                        borderColor: 'rgba(16, 185, 129, 1)',
                                       borderWidth: 2,
                                       tension: 0.1,
                                       fill: true,
                                       yAxisID: 'y1'
                                   }]
                               },
                               options: salesChartOptions
                           });
                       } else {
                            if (salesCanvas) salesCanvas.style.display = 'none';
                            const noDataMessage = document.createElement('p');
                            noDataMessage.textContent = 'No sales data found for this period.';
                            noDataMessage.classList.add('chart-message', 'text-gray-500');
                            salesChartContainer.appendChild(noDataMessage);
                       }
                 }
            }


            // Products Chart (Top Selling)
            const productsCtx = document.getElementById('productsChart');
             if (productsCtx) {
                 let productsCanvas = document.getElementById('productsChart');
                 let productsChartContainer = productsCanvas ? productsCanvas.parentElement : null;

                 if (!productsCanvas || !productsChartContainer) {
                      console.error('Products Chart container or canvas not found for initialization!');
                 } else {
                      productsChartContainer.querySelectorAll('.chart-message').forEach(msgEl => msgEl.remove());

                      if (dashboardTopProductsData && dashboardTopProductsData.length > 0) {
                           if (productsCanvas) productsCanvas.style.display = 'block';

                           const productsChartOptions = JSON.parse(JSON.stringify(chartBaseOptions));
                           productsChartOptions.plugins.legend.display = false;
                           productsChartOptions.scales.y.title = { display: true, text: 'Revenue (₹)', color: '#6b7280' };

                           productsChart = new Chart(productsCtx, {
                               type: 'bar',
                               data: {
                                   labels: dashboardTopProductsData.map(item => htmlspecialchars(item.name)),
                                   datasets: [{
                                       label: 'Revenue',
                                       data: dashboardTopProductsData.map(item => item.total_revenue ?? 0),
                                       backgroundColor: [
                                            'rgba(79, 70, 229, 0.7)',
                                            'rgba(16, 185, 129, 0.7)',
                                            'rgba(59, 130, 246, 0.7)',
                                            'rgba(245, 158, 11, 0.7)',
                                            'rgba(239, 68, 68, 0.7)'
                                       ],
                                       borderColor: [
                                            'rgba(79, 70, 229, 1)',
                                            'rgba(16, 185, 129, 1)',
                                            'rgba(59, 130, 246, 1)',
                                            'rgba(245, 158, 11, 1)',
                                            'rgba(239, 68, 68, 0.7)'
                                       ],
                                       borderWidth: 1
                                   }]
                               },
                               options: productsChartOptions
                           });
                       } else {
                            if (productsCanvas) productsCanvas.style.display = 'none';
                           const noDataMessage = document.createElement('p');
                           noDataMessage.textContent = 'No top selling items found for this period.';
                           noDataMessage.classList.add('chart-message', 'text-gray-500');
                           productsChartContainer.appendChild(noDataMessage);
                       }
                      }
            }
        }

        // --- Fetch Data Functions (Using APIs) ---

         function checkAndInitCharts() {
             if (salesDataFetched && topProductsDataFetched) {
                 initCharts();
             }
         }

        function getSelectedDashboardDateRange() {
            const today = new Date();
            const options = { timeZone: 'Asia/Kathmandu' };
            let startDate = null;
            let endDate = today.toLocaleDateString('en-CA', options).replace(/\//g, '-');

            const selectedRange = dashboardDateRangeSelect ? dashboardDateRangeSelect.value : 'this_month';

            switch (selectedRange) {
                case 'today':
                    startDate = today.toLocaleDateString('en-CA', options).replace(/\//g, '-');
                    break;
                case 'this_week':
                    const firstDayOfWeek = new Date(today);
                    firstDayOfWeek.setDate(today.getDate() - today.getDay());
                    startDate = firstDayOfWeek.toLocaleDateString('en-CA', options).replace(/\//g, '-');
                    break;
                case 'this_month':
                    const firstDayOfMonth = new Date(today.getFullYear(), today.getMonth(), 1);
                    startDate = firstDayOfMonth.toLocaleDateString('en-CA', options).replace(/\//g, '-');
                    break;
                case 'last_month':
                    const firstDayOfLastMonth = new Date(today.getFullYear(), today.getMonth() - 1, 1);
                    const lastDayOfLastMonth = new Date(today.getFullYear(), today.getMonth(), 0);
                    startDate = firstDayOfLastMonth.toLocaleDateString('en-CA', options).replace(/\//g, '-');
                    endDate = lastDayOfLastMonth.toLocaleDateString('en-CA', options).replace(/\//g, '-');
                    break;
                case 'this_year':
                    const firstDayOfYear = new Date(today.getFullYear(), 0, 1);
                    startDate = firstDayOfYear.toLocaleDateString('en-CA', options).replace(/\//g, '-');
                    break;
                case 'all_time':
                    startDate = null;
                    endDate = null;
                    break;
                case 'custom':
                    startDate = dashboardCustomStartDateInput.value;
                    endDate = dashboardCustomEndDateInput.value;
                    if (!startDate || !endDate || new Date(startDate) > new Date(endDate)) {
                        showConfirmation('Please select valid start and end dates for custom range.', 'orange');
                        const defaultFirstDayOfMonth = new Date(today.getFullYear(), today.getMonth(), 1);
                        startDate = defaultFirstDayOfMonth.toLocaleDateString('en-CA', options).replace(/\//g, '-');
                        endDate = today.toLocaleDateString('en-CA', options).replace(/\//g, '-');
                        dashboardDateRangeSelect.value = 'this_month';
                        dashboardCustomDateRangeContainer.classList.add('hidden');
                    }
                    break;
                default:
                    const defaultFirstDayOfMonth = new Date(today.getFullYear(), today.getMonth(), 1);
                    startDate = defaultFirstDayOfMonth.toLocaleDateString('en-CA', options).replace(/\//g, '-');
                    endDate = today.toLocaleDateString('en-CA', options).replace(/\//g, '-');
                    break;
            }
            return { startDate, endDate };
        }

        function getSelectedDashboardCategory() {
            return dashboardCategoryFilterSelect ? dashboardCategoryFilterSelect.value : 'all';
        }


         async function fetchRevenueTrend(granularity = 'monthly') {
              const chartContainer = document.getElementById('salesChart').parentElement;
              if (!chartContainer) {
                   console.error('Sales Chart container not found!');
                   salesDataFetched = true;
                   checkAndInitCharts();
                   return;
              }

              let salesCanvas = document.getElementById('salesChart');
              if (!salesCanvas) {
                  chartContainer.innerHTML = '<canvas id="salesChart"></canvas>';
                  salesCanvas = document.getElementById('salesChart');
              }

              chartContainer.querySelectorAll('.chart-message').forEach(msgEl => msgEl.remove());
              const loadingMessage = document.createElement('p');
              loadingMessage.textContent = 'Loading Sales Overview...';
              loadingMessage.classList.add('chart-message', 'text-gray-500');
              chartContainer.appendChild(loadingMessage);

              if(salesCanvas) salesCanvas.style.display = 'none';

            const { startDate, endDate } = getSelectedDashboardDateRange();

             const queryParams = new URLSearchParams();
             if (startDate) queryParams.append('startDate', startDate);
             if (endDate) queryParams.append('endDate', endDate);
             queryParams.append('granularity', granularity);


             try {
                 const response = await fetch(`./api/fetch_revenue_trend.php?${queryParams.toString()}`);

                 if (loadingMessage) loadingMessage.remove();


                 if (!response.ok) {
                       console.error('fetchRevenueTrend: HTTP Error:', response.status, response.statusText);
                       showConfirmation(`HTTP Error fetching revenue trend: ${response.status} ${response.statusText}`, 'error');
                        dashboardSalesData = [];
                        salesDataFetched = true;
                        checkAndInitCharts();
                        if (chartContainer) {
                            let salesCanvas = document.getElementById('salesChart');
                            if(salesCanvas) salesCanvas.style.display = 'none';
                             const errorMessage = document.createElement('p');
                             errorMessage.textContent = `Network Error loading trend data: ${response.status} ${response.statusText}`;
                             errorMessage.classList.add('chart-message', 'error');
                             chartContainer.appendChild(errorMessage);
                        }
                        return;
                  }

                  const data = await response.json();

                 if (data.success && data.trend && data.trend.length > 0) {
                       dashboardSalesData = data.trend;
                       showConfirmation(data.message || 'Revenue trend data fetched successfully.', 'success');
                  } else {
                      console.warn('fetchRevenueTrend: Backend reported no revenue trend data:', data.message);
                       dashboardSalesData = [];
                       showConfirmation(data.message || 'No revenue trend data found for this period.', 'info');

                       if (chartContainer) {
                           let salesCanvas = document.getElementById('salesChart');
                           if(salesCanvas) salesCanvas.style.display = 'none';
                            const noDataMessage = document.createElement('p');
                            noDataMessage.textContent = htmlspecialchars(data.message || 'No data found');
                            noDataMessage.classList.add('chart-message', 'text-gray-500');
                            chartContainer.appendChild(noDataMessage);
                       }
                  }
             } catch (error) {
                 console.error('fetchRevenueTrend: Fetch Error:', error);
                 showConfirmation('Error fetching revenue trend!', 'error');
                  dashboardSalesData = [];
                  if (chartContainer) {
                      let salesCanvas = document.getElementById('salesChart');
                      if(salesCanvas) salesCanvas.style.display = 'none';
                       const errorMessage = document.createElement('p');
                       errorMessage.textContent = 'Network Error loading trend data.';
                       errorMessage.classList.add('chart-message', 'error');
                       chartContainer.appendChild(errorMessage);
                  }
             }
             salesDataFetched = true;
             checkAndInitCharts();
         }

          async function fetchTopSellingItems() {
               const chartContainer = document.getElementById('productsChart').parentElement;
                if (!chartContainer) {
                    console.error('Top Items Chart container not found!');
                    topProductsDataFetched = true;
                    checkAndInitCharts();
                    return;
                }

                let topItemsCanvas = document.getElementById('productsChart');
                if (!topItemsCanvas) {
                    chartContainer.innerHTML = '<canvas id="productsChart"></canvas>';
                    topItemsCanvas = document.getElementById('productsChart');
                }

                chartContainer.querySelectorAll('.chart-message').forEach(msgEl => msgEl.remove());
                 const loadingMessage = document.createElement('p');
                 loadingMessage.textContent = 'Loading Top Selling Products...';
                 loadingMessage.classList.add('chart-message', 'text-gray-500');
                 chartContainer.appendChild(loadingMessage);

                 if(topItemsCanvas) topItemsCanvas.style.display = 'none';

              const { startDate, endDate } = getSelectedDashboardDateRange();
              const category = getSelectedDashboardCategory();


              const queryParams = new URLSearchParams();
              if (startDate) queryParams.append('startDate', startDate);
              if (endDate) queryParams.append('endDate', endDate);
              if (category) queryParams.append('category', category);


              try {
                  const response = await fetch(`./api/fetch_top_selling_items.php?${queryParams.toString()}`);

                  if (loadingMessage) loadingMessage.remove();


                  if (!response.ok) {
                       console.error('fetchTopSellingItems: HTTP Error:', response.status, response.statusText);
                       showConfirmation(`HTTP Error fetching top selling items: ${response.status} ${response.statusText}`, 'error');
                       dashboardTopProductsData = [];
                       topProductsDataFetched = true;
                       checkAndInitCharts();
                        if (productsChart) { productsChart.destroy(); productsChart = null; }
                        if (chartContainer) {
                            let topItemsCanvas = document.getElementById('productsChart');
                            if(topItemsCanvas) topItemsCanvas.style.display = 'none';
                             const errorMessage = document.createElement('p');
                             errorMessage.textContent = `Network Error loading top items: ${response.status} ${response.statusText}`;
                             errorMessage.classList.add('chart-message', 'error');
                             chartContainer.appendChild(errorMessage);
                        }
                        return;
                  }

                  const data = await response.json();

                  if (data.success && data.top_items && data.top_items.length > 0) {
                       console.log('fetchTopSellingItems: Data found. Updating UI.');
                       dashboardTopProductsData = data.top_items;
                       showConfirmation(data.message || 'Top selling items data fetched successfully.', 'success');

                  } else {
                      console.warn('fetchTopSellingItems: Backend reported no top selling items for this filter:', data.message);
                      dashboardTopProductsData = [];
                      showConfirmation(data.message || 'No top selling items found for this period/category.', 'info');

                       if (chartContainer) {
                           let topItemsCanvas = document.getElementById('productsChart');
                           if(topItemsCanvas) topItemsCanvas.style.display = 'none';
                            const noDataMessage = document.createElement('p');
                            noDataMessage.textContent = htmlspecialchars(data.message || 'No data found');
                            noDataMessage.classList.add('chart-message', 'text-gray-500');
                            chartContainer.appendChild(noDataMessage);
                       }

                  }
               } catch (error) {
                  console.error('fetchTopSellingItems: Fetch Error:', error);
                  showConfirmation('Error fetching top selling items!', 'error');
                  dashboardTopProductsData = [];
                   if (productsChart) {
                       productsChart.destroy();
                       productsChart = null;
                   }
                   if (chartContainer) {
                       let topItemsCanvas = document.getElementById('productsChart');
                       if(topItemsCanvas) topItemsCanvas.style.display = 'none';
                        const errorMessage = document.createElement('p');
                        errorMessage.textContent = 'Network Error loading top items.';
                        errorMessage.classList.add('chart-message', 'error');
                        chartContainer.appendChild(errorMessage);
                   }
              }
              topProductsDataFetched = true;
              checkAndInitCharts();
         }

        async function fetchCategoriesForDashboard() {
            try {
                const response = await fetch('./api/fetch_categories.php');
                const data = await response.json();

                if (response.ok && data.success && data.categories && data.categories.length > 0) {
                    updateDashboardCategoryFilter(data.categories);
                } else {
                    console.warn('fetchCategoriesForDashboard: No categories found or error:', data.message);
                }
            } catch (error) {
                console.error('fetchCategoriesForDashboard: Fetch Error:', error);
            }
        }

        function updateDashboardCategoryFilter(categories) {
            if (dashboardCategoryFilterSelect) {
                dashboardCategoryFilterSelect.innerHTML = '<option value="all">All Categories</option>';
                categories.forEach(category => {
                    const option = document.createElement('option');
                    option.value = category.name;
                    option.textContent = htmlspecialchars(category.name);
                    dashboardCategoryFilterSelect.appendChild(option);
                });
            }
        }


         async function fetchRecentOrders() {
             const recentOrdersListEl = document.getElementById('recentOrdersList');
             if (!recentOrdersListEl) {
                 console.error("Recent Orders List element not found!");
                 return;
             }

             recentOrdersListEl.innerHTML = '<div class="px-6 py-4 text-center text-gray-500">Loading recent orders...</div>';

             try {
                 const response = await fetch('./api/fetch_recent_orders.php');
                 const data = await response.json();

                 if (response.ok && data.success && data.recent_orders && data.recent_orders.length > 0) {
                     updateRecentOrdersList(data.recent_orders);
                 } else {
                     const message = data.message || 'No recent orders found.';
                     recentOrdersListEl.innerHTML = `<div class="px-6 py-4 text-center text-gray-500">${htmlspecialchars(message)}</div>`;
                     if (!data.success) {
                          showConfirmation(data.message || 'Failed to fetch recent orders.', 'error');
                     } else {
                          showConfirmation(data.message || 'No recent orders found.', 'info');
                     }
                 }
             } catch (error) {
                 recentOrdersListEl.innerHTML = `<div class="px-6 py-4 text-center text-red-600">Error loading recent orders.</div>`;
                 showConfirmation('Error fetching recent orders!', 'error');
             }
         }

         function updateRecentOrdersList(orders) {
             const recentOrdersListEl = document.getElementById('recentOrdersList');
             if (!recentOrdersListEl) {
                 console.error("Recent Orders List element not found for update!");
                 return;
             }

             recentOrdersListEl.innerHTML = '';

             if (orders && orders.length > 0) {
                 orders.forEach(order => {
                      const orderElement = `
                         <div class="px-6 py-4 hover:bg-gray-50">
                             <div class="flex items-center justify-between">
                                 <div class="flex items-center">
                                      <div class="ml-0">
                                          <div class="text-sm font-medium text-gray-900">#TRN-${htmlspecialchars(order.transaction_id || 'N/A')}</div>
                                          <div class="text-sm text-gray-500">${formatNumber(order.total_items_count || 0)} item(s) • ₹${formatCurrency(order.total_amount || 0)}</div>
                                      </div>
                                 </div>
                                 <div class="text-sm text-gray-500">${htmlspecialchars(order.formatted_date || 'N/A')}</div>
                             </div>
                         </div>
                     `;
                     recentOrdersListEl.innerHTML += orderElement;
                 });
             } else {
                 recentOrdersListEl.innerHTML = `
                      <div class="px-6 py-4 text-center text-gray-500">No recent orders found.</div>
                 `;
             }
         }

         async function fetchActivityFeed() {
             const activityFeedListEl = document.getElementById('activityFeedList');
             if (!activityFeedListEl) {
                  console.error("Activity Feed List element not found!");
                 return;
             }

             activityFeedListEl.innerHTML = '<div class="px-6 py-4 text-center text-gray-500">Loading activity feed...</div>';

             try {
                 const response = await fetch('./api/fetch_activity_feed.php');
                 const data = await response.json();

                 if (response.ok && data.success && data.activity_feed && data.activity_feed.length > 0) {
                     updateActivityFeedList(data.activity_feed);
                 } else {
                     const message = data.message || 'No recent activity found.';
                     activityFeedListEl.innerHTML = `<div class="px-6 py-4 text-center text-gray-500">${htmlspecialchars(message)}</div>`;
                      if (!data.success) {
                           showConfirmation(data.message || 'Failed to fetch activity feed.', 'error');
                      } else {
                           showConfirmation(data.message || 'No recent activity found.', 'info');
                      }
                 }
             } catch (error) {
                 activityFeedListEl.innerHTML = `<div class="px-6 py-4 text-center text-red-600">Error loading activity feed.</div>`;
                 showConfirmation('Error fetching activity feed!', 'error');
             }
         }

         function updateActivityFeedList(activity) {
             const activityFeedListEl = document.getElementById('activityFeedList');
              if (!activityFeedListEl) {
                  console.error("Activity Feed List element not found for update!");
                 return;
             }

             activityFeedListEl.innerHTML = '';

             if (activity && activity.length > 0) {
                 activity.forEach(entry => {
                      const description = entry.description || 'N/A';
                      const formattedTimestamp = entry.formatted_timestamp || 'N/A';

                      const activityElement = `
                           <div class="px-6 py-4 hover:bg-gray-50">
                               <div class="flex space-x-3">
                                   <div class="min-w-0 flex-1">
                                       <p class="text-sm text-gray-800">${htmlspecialchars(description)}</p>
                                       <p class="text-sm text-gray-500 mt-1">${htmlspecialchars(formattedTimestamp)}</p>
                                   </div>
                               </div>
                           </div>
                       `;
                       activityFeedListEl.innerHTML += activityElement;
                  });
              } else {
                   activityFeedListEl.innerHTML = `
                       <div class="px-6 py-4 text-center text-gray-500">No recent activity.</div>
                  `;
              }
         }


         function setupEventListeners() {
             if (dashboardDateRangeSelect) {
                 dashboardDateRangeSelect.addEventListener('change', function() {
                     if (this.value === 'custom') {
                         dashboardCustomDateRangeContainer.classList.remove('hidden');
                         if (!dashboardCustomStartDateInput.value || !dashboardCustomEndDateInput.value) {
                             const endDate = new Date();
                             const startDate = new Date();
                             startDate.setDate(endDate.getDate() - 7);
                             dashboardCustomStartDateInput.valueAsDate = startDate;
                             dashboardCustomEndDateInput.valueAsDate = endDate;
                         }
                     } else {
                         dashboardCustomDateRangeContainer.classList.add('hidden');
                         fetchRevenueTrend(getSelectedDashboardGranularity());
                         fetchTopSellingItems();
                     }
                 });
             }

             if (applyDashboardCustomRangeBtn) {
                 applyDashboardCustomRangeBtn.addEventListener('click', function() {
                     fetchRevenueTrend(getSelectedDashboardGranularity());
                     fetchTopSellingItems();
                 });
             }

             document.querySelectorAll('button[data-granularity]').forEach(button => {
                 button.addEventListener('click', function() {
                     const granularity = this.dataset.granularity;
                     document.querySelectorAll('button[data-granularity]').forEach(btn => {
                         btn.classList.remove('bg-indigo-100', 'text-indigo-800');
                         btn.classList.add('bg-gray-100', 'text-gray-800', 'hover:bg-gray-200');
                     });
                     this.classList.add('bg-indigo-100', 'text-indigo-800');
                     this.classList.remove('bg-gray-100', 'text-gray-800', 'hover:bg-gray-200');

                     fetchRevenueTrend(granularity);
                 });
             });

             if (dashboardCategoryFilterSelect) {
                 dashboardCategoryFilterSelect.addEventListener('change', function() {
                     fetchTopSellingItems();
                 });
             }
         }

        function getSelectedDashboardGranularity() {
            const activeBtn = document.querySelector('button[data-granularity].bg-indigo-100');
            return activeBtn ? activeBtn.dataset.granularity : 'monthly';
        }


        document.addEventListener('DOMContentLoaded', function () {
             if (dashboardDateRangeSelect) {
                 dashboardDateRangeSelect.value = 'this_month';
             }

             initCharts();

             setupEventListeners();

             fetchRevenueTrend(getSelectedDashboardGranularity());

             fetchTopSellingItems();

             fetchRecentOrders();

             fetchActivityFeed();

             fetchCategoriesForDashboard();
        });
    </script>
</body>

</html>
<?php
// Close the database connection at the end of the script
// Use $link instead of $conn
if (isset($link)) {
    mysqli_close($link);
}
?>
