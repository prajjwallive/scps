<?php
// admin/sales.php - Admin Sales Dashboard Frontend (JavaScript Moved to Separate File, Reduced Chart Height)

// Set the default timezone to match where transactions are recorded (e.g., Nepal Time)
// This is CRITICAL for correct date comparisons and grouping by period.
// Choose a timezone identifier from https://www.php.net/manual/en/timezones.php
// 'Asia/Kathmandu' is used here as an example for Nepal Time (UTC+5:45)
date_default_timezone_set('Asia/Kathmandu'); // <<< Set your correct timezone here


// Start the session
session_start();

// --- REQUIRE ADMIN LOGIN ---
// This script should only be accessible to logged-in admins
if (!isset($_SESSION['admin_id']) || empty($_SESSION['admin_id'])) {
    // If not logged in as admin, redirect to the admin login page
    header('Location: login.php'); // login.php is in the same admin folder
    exit(); // Stop script execution
}
// --- END REQUIRE ADMIN LOGIN ---

// Include database connection (Might be needed for header or other includes, even if data is via AJAX)
// Path: From admin/ UP to root (../) THEN into includes/
require_once '../includes/db_connection.php'; // Ensure this path is correct and uses $link

// Get admin username and role from session for header (optional, for header display)
$admin_username = $_SESSION['admin_username'] ?? 'Admin';
$admin_role = $_SESSION['admin_role'] ?? 'N/A';

// Include necessary packages (like CSS, Flowbite, Tailwind)
// Path from admin/ UP to root (../) then includes/
include '../includes/packages.php'; // Ensure this path is correct

// Include Admin Header HTML - ENSURE THIS LINE APPEARS ONLY ONCE IN THIS FILE
// Ensure admin_header.php exists in includes/ folder
// Path: From admin/ UP to root (../) THEN into includes/admin_header.php
include '../includes/admin_header.php'; // Include header ONCE here

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Sales</title>
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
             color: #374151 !important; /* gray-700 - Ensured with !important */
             z-index: 1; /* Ensure message is above canvas */
         }

         .chart-message.error {
             color: #dc2626; /* red-600 */
         }

         /* Style for the table container when loading or no data */
         .table-message {
             text-align: center;
             padding: 1.5rem; /* px-6 py-4 */
             color: #374151 !important; /* gray-700 - Ensured with !important */
         }

          .table-message.error {
              color: #dc2626; /* red-600 */
          }

        /* Add dark text enforcement for all table content */
        .bg-white td, .bg-white th {
            color: #111827 !important; /* Tailwind's gray-900 - Ensured with !important */
        }
        
        /* Improved contrast for status-like elements (if used in sales.php) */
        /* Note: Based on current sales.php, these might not be directly used, but are included as per DeepSeek's suggestion */
        .status-indicator {
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            font-size: 0.75rem;
            font-weight: 500;
            display: inline-block; /* Ensure it respects padding/margin */
        }
        .status-indicator.positive {
            background-color: #d1fae5; /* green-100 */
            color: #065f46; /* green-800 */
        }
        .status-indicator.negative {
            background-color: #fee2e2; /* red-100 */
            color: #991b1b; /* red-800 */
        }
     </style>
</head>

<body class="bg-gray-100 font-sans">
    <?php // include '../includes/admin_header.php'; // Included above ?>


    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        <div id="admin-confirmation" class="fixed top-5 left-1/2 -translate-x-1/2 bg-green-500 text-white p-4 rounded-md shadow-lg z-50 opacity-0 hidden transition-all duration-500 ease-in-out transform">
            Action successful!
        </div>


        <h1 class="text-3xl font-bold text-gray-900 mb-6">Sales Overview</h1>

        <div class="flex flex-wrap gap-4 mb-8 items-center">
            <div>
                <label for="salesDateRange" class="block text-sm font-medium text-gray-700 mb-1">Date Range:</label>
                <select id="salesDateRange" class="block appearance-none w-full bg-white border border-gray-300 hover:border-gray-400 px-4 py-2 pr-8 rounded shadow leading-tight focus:outline-none focus:shadow-outline">
                    <option value="today">Today</option>
                    <option value="this_week">This Week</option>
                    <option value="this_month" selected>This Month</option>
                    <option value="last_month">Last Month</option>
                    <option value="this_year">This Year</option>
                    <option value="all_time">All Time</option>
                    <option value="custom">Custom Range</option> </select>
            </div>
            <div id="customDateRangeContainer" class="hidden flex-wrap gap-4 items-end">
                <div>
                    <label for="customStartDate" class="block text-sm font-medium text-gray-700 mb-1">From:</label>
                    <input type="date" id="customStartDate" class="p-2 border border-gray-300 rounded-md shadow-sm">
                </div>
                <div>
                    <label for="customEndDate" class="block text-sm font-medium text-gray-700 mb-1">To:</label>
                    <input type="date" id="customEndDate" class="p-2 border border-gray-300 rounded-md shadow-sm">
                </div>
                <button id="applyCustomRangeBtn" class="bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-blue-600 transition-colors">Apply Custom Range</button>
            </div>

             <div>
                 <label for="salesCategoryFilter" class="block text-sm font-medium text-gray-700 mb-1">Category:</label>
                 <select id="salesCategoryFilter" class="block appearance-none w-full bg-white border border-gray-300 hover:border-gray-400 px-4 py-2 pr-8 rounded shadow leading-tight focus:outline-none focus:shadow-outline">
                     <option value="all">All Categories</option>
                     </select>
             </div>
            </div>


        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4 mb-8">
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 bg-indigo-500 rounded-md p-3">
                             <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0-2.08-.402-2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dt class="text-sm font-medium text-gray-500 truncate"> Total Revenue
                            </dt>
                            <dd class="flex items-baseline">
                                <div class="text-2xl font-semibold text-gray-900" id="totalRevenueCard"> ₹0.00
                                </div>
                            </dd>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 bg-green-500 rounded-md p-3">
                             <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                            </svg>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dt class="text-sm font-medium text-gray-500 truncate"> Total Items Sold
                            </dt>
                            <dd class="flex items-baseline">
                                <div class="text-2xl font-semibold text-gray-900" id="totalItemsSoldCard"> 0
                                </div>
                            </dd>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 bg-blue-500 rounded-md p-3">
                             <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dt class="text-sm font-medium text-gray-500 truncate"> Total Transactions
                            </dt>
                            <dd class="flex items-baseline">
                                <div class="text-2xl font-semibold text-gray-900" id="totalTransactionsCard"> 0
                                </div>
                            </dd>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 bg-yellow-500 rounded-md p-3">
                             <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2zM10 8.5a.5.5 0 11-1 0 .5.5 0 011 0zm5 5a.5.5 0 11-1 0 .5.5 0 011 0z" />
                            </svg>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dt class="text-sm font-medium text-gray-500 truncate"> Total Customers
                            </dt>
                            <dd class="flex items-baseline">
                                <div class="text-2xl font-semibold text-gray-900" id="totalCustomersCard"> 0
                                </div>
                            </dd>
                        </div>
                    </div>
                </div>
            </div>
        </div>


        <div class="bg-white shadow rounded-lg p-6 mb-8">
            <h2 class="text-lg font-medium text-gray-900 mb-4">Revenue & Items Sold Trend</h2>
            <div class="flex gap-4 mb-4">
                 <button data-granularity="daily" class="px-4 py-2 rounded-md text-sm font-medium bg-gray-100 text-gray-800 hover:bg-gray-200">Daily</button>
                 <button data-granularity="weekly" class="px-4 py-2 rounded-md text-sm font-medium bg-gray-100 text-gray-800 hover:bg-gray-200">Weekly</button>
                 <button data-granularity="monthly" class="px-4 py-2 rounded-md text-sm font-medium bg-indigo-100 text-indigo-800">Monthly</button>
            </div>
            <div class="h-48 chart-container"> <canvas id="revenueItemsChart"></canvas>
            </div>
        </div>

        <div class="bg-white shadow rounded-lg p-6 mb-8">
            <h2 class="text-lg font-medium text-gray-900 mb-4">Top Selling Products (Chart)</h2>
             <div class="h-48 chart-container"> <canvas id="topProductsChart"></canvas>
             </div>
        </div>

        <div class="bg-white shadow rounded-lg overflow-hidden mb-8">
             <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                 <h2 class="text-lg font-medium text-gray-900">Top Selling Items (Table)</h2>
                 <div>
                     <label for="topItemsTableCategoryFilter" class="sr-only">Filter by Category</label>
                     <select id="topItemsTableCategoryFilter" class="block appearance-none w-full bg-white border border-gray-300 hover:border-gray-400 px-4 py-2 pr-8 rounded shadow leading-tight focus:outline-none focus:shadow-outline text-sm">
                         <option value="all">All Categories</option>
                         </select>
                 </div>
             </div>
             <div class="overflow-x-auto">
                 <table class="min-w-full divide-y divide-gray-200">
                     <thead class="bg-gray-50">
                         <tr>
                             <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">
                                 ITEM
                             </th>
                             <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">
                                 CATEGORY
                             </th>
                             <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">
                                 QUANTITY SOLD
                             </th>
                             <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">
                                 TOTAL REVENUE
                             </th>
                             <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">
                                 % OF SALES
                             </th>
                         </tr>
                     </thead>
                     <tbody class="bg-white divide-y divide-gray-200" id="topItemsTableBody">
                         <tr>
                             <td colspan="5" class="px-6 py-4 whitespace-nowrap text-sm text-center table-message">Loading top selling items...</td>
                         </tr>
                     </tbody>
                 </table>
             </div>
         </div>
        <div class="bg-white shadow rounded-lg overflow-hidden mb-8">
             <div class="px-6 py-4 border-b border-gray-200">
                 <h2 class="text-lg font-medium text-gray-900">Customer Activity by Period</h2>
             </div>
             <div class="overflow-x-auto">
                 <table class="min-w-full divide-y divide-gray-200">
                     <thead class="bg-gray-50">
                         <tr>
                             <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">
                                 PERIOD
                             </th>
                             <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">
                                 NEW CUSTOMERS
                             </th>
                             <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">
                                 REPEAT CUSTOMERS
                             </th>
                             <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">
                                 TOTAL CUSTOMERS (CUMULATIVE)
                             </th>
                             <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">
                                 GROWTH RATE (VS PREV PERIOD)
                             </th>
                         </tr>
                     </thead>
                     <tbody class="bg-white divide-y divide-gray-200" id="customerGrowthTableBody">
                         <tr>
                             <td colspan="5" class="px-6 py-4 whitespace-nowrap text-sm text-center table-message">Loading customer activity...</td>
                         </tr>
                     </tbody>
                 </table>
             </div>
             <div class="px-6 py-4 flex items-center justify-between border-t border-gray-200">
                 <div class="flex-1 flex justify-between sm:hidden">
                     <button id="customerGrowthPrevMobile" class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"> Previous </button>
                     <button id="customerGrowthNextMobile" class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"> Next </button>
                 </div>
                 <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                     <div>
                         <p class="text-sm text-gray-700">
                             Showing <span class="font-medium" id="customerGrowthShowingFrom">0</span> to <span class="font-medium" id="customerGrowthShowingTo">0</span> of <span class="font-medium" id="customerGrowthTotalRecords">0</span> results
                         </p>
                     </div>
                     <div>
                         <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                             <button id="customerGrowthPrev" class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                 <span class="sr-only">Previous</span>
                                 <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                     <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                                 </svg>
                             </button>
                             <span id="customerGrowthPageNumbers" class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">
                                 ...
                             </span>
                             <button id="customerGrowthNext" class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                 <span class="sr-only">Next</span>
                                 <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                     <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10l-3.293-3.293a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                                 </svg>
                             </button>
                         </nav>
                     </div>
                 </div>
             </div>
         </div>
        </main>

    <footer class="bg-white border-t border-gray-200 mt-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <p class="text-center text-sm text-gray-500">&copy; 2023 Your Company. All rights reserved.</p>
        </div>
    </footer>

    <script src="js/sales.js"></script>

</body>

</html>
<?php
// Close the database connection at the end of the script
// Use $link instead of $conn
if (isset($link)) {
    mysqli_close($link);
}
?>
