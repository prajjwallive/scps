<?php
// admin/activity.php - Admin View All Activity Page

// Set the default timezone
date_default_timezone_set('Asia/Kathmandu'); // <<< Set your correct timezone here

// Start the session
session_start();

// --- REQUIRE ADMIN LOGIN ---
if (!isset($_SESSION['admin_id']) || empty($_SESSION['admin_id'])) {
    header('Location: login.php'); // login.php is in the same admin folder
    exit();
}
// --- END REQUIRE ADMIN LOGIN ---

// Include database connection (Needed for header or other includes)
require_once '../includes/db_connection.php'; // Ensure this path is correct and uses $link

// Get admin username and role from session for header
$admin_username = $_SESSION['admin_username'] ?? 'Admin';
$admin_role = $_SESSION['admin_role'] ?? 'N/A';

// Include necessary packages (CSS, Flowbite, Tailwind)
include '../includes/packages.php'; // Ensure this path is correct

// Include Admin Header HTML
include '../includes/admin_header.php'; // Include header ONCE here

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Activity Log</title>
    <style>
         /* Optional: Add custom styles for this page */
         .table-message {
             text-align: center;
             padding: 1.5rem; /* px-6 py-4 */
             color: #6b7280; /* gray-500 */
         }

          .table-message.error {
              color: #dc2626; /* red-600 */
          }
     </style>
</head>
<body class="bg-gray-100 font-sans">

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        <div id="admin-confirmation" class="fixed top-5 left-1/2 -translate-x-1/2 bg-green-500 text-white p-4 rounded-md shadow-lg z-50 opacity-0 hidden transition-all duration-500 ease-in-out transform">
            Action successful!
        </div>

        <h1 class="text-3xl font-bold text-gray-900 mb-6">Activity Log</h1>

        <div class="flex flex-wrap gap-4 mb-6 items-center">
             <div>
                 <label for="activityDateRange" class="block text-sm font-medium text-gray-700 mb-1">Date Range:</label>
                 <select id="activityDateRange" class="block appearance-none w-full bg-white border border-gray-300 hover:border-gray-400 px-4 py-2 pr-8 rounded shadow leading-tight focus:outline-none focus:shadow-outline text-sm">
                     <option value="all_time">All Time</option>
                     <option value="today">Today</option>
                     <option value="this_week">This Week</option>
                     <option value="this_month">This Month</option>
                     <option value="last_month">Last Month</option>
                     <option value="this_year">This Year</option>
                     </select>
             </div>

             <div>
                 <label for="activityTypeFilter" class="block text-sm font-medium text-gray-700 mb-1">Activity Type:</label>
                 <select id="activityTypeFilter" class="block appearance-none w-full bg-white border border-gray-300 hover:border-gray-400 px-4 py-2 pr-8 rounded shadow leading-tight focus:outline-none focus:shadow-outline text-sm">
                     <option value="all">All Types</option>
                     <option value="admin_login">Login</option>
                     <option value="product_added">Product Added</option>
                     <option value="product_updated">Product Updated</option>
                     <option value="product_deleted">Product Deleted</option>
                     <option value="order_processed">Order Processed</option>
                     </select>
             </div>

             <div>
                  <label for="activityAdminFilter" class="block text-sm font-medium text-gray-700 mb-1">Admin User:</label>
                  <select id="activityAdminFilter" class="block appearance-none w-full bg-white border border-gray-300 hover:border-gray-400 px-4 py-2 pr-8 rounded shadow leading-tight focus:outline-none focus:shadow-outline text-sm">
                      <option value="all">All Admins</option>
                      </select>
              </div>

             </div>


        <div class="bg-white shadow rounded-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Timestamp
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Admin User
                            </th>
                             <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                 Activity Type
                             </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Description
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200" id="activityLogTableBody">
                        <tr>
                            <td colspan="4" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center table-message">Loading activity log...</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="px-6 py-4 flex items-center justify-between border-t border-gray-200">
                <div class="flex-1 flex justify-between sm:hidden">
                    <button id="activityPrevMobile" class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"> Previous </button>
                    <button id="activityNextMobile" class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"> Next </button>
                </div>
                <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                    <div>
                        <p class="text-sm text-gray-700">
                            Showing <span class="font-medium" id="activityShowingFrom">0</span> to <span class="font-medium" id="activityShowingTo">0</span> of <span class="font-medium" id="activityTotalRecords">0</span> results
                        </p>
                    </div>
                    <div>
                        <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                            <button id="activityPrev" class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                <span class="sr-only">Previous</span>
                                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                                </svg>
                            </button>
                            <span id="activityPageNumbers" class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">
                                </span>
                            <button id="activityNext" class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
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

    <script>
        // Helper function to show confirmation message (reused pattern)
       const adminConfirmation = document.getElementById('admin-confirmation');
       function showAdminConfirmation(message = 'Success!', color = 'green') {
           if (!adminConfirmation) return;
           adminConfirmation.textContent = message;
           adminConfirmation.className = `fixed top-5 left-1/2 -translate-x-1/2 text-white p-4 rounded-md shadow-lg z-50 opacity-0 hidden transition-all duration-500 ease-in-out transform bg-${color}-500 block`; // Reset classes
           setTimeout(() => { adminConfirmation.classList.add('opacity-100'); }, 10);
           setTimeout(() => {
               adminConfirmation.classList.remove('opacity-100');
               setTimeout(() => { adminConfirmation.classList.remove('block'); adminConfirmation.classList.add('hidden'); }, 500);
           }, 3000); // Show for 3 seconds
       }

       // Helper to format date and time
       function formatDateTime(dateTimeStr) {
            if (!dateTimeStr) return 'N/A';
           try {
               const date = new Date(dateTimeStr);
               if (isNaN(date.getTime())) { return 'Invalid Date'; }
               const options = { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true };
               return date.toLocaleDateString('en-IN', options);
           } catch (e) {
               console.error("Error formatting date/time:", dateTimeStr, e);
               return 'Invalid Date';
           }
       }

        // Helper function for HTML escaping
        function htmlspecialchars(str) {
            if (typeof str !== 'string' && str !== null && str !== undefined) { str = String(str); }
            else if (str === null || str === undefined) { return ''; }
            const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
            return str.replace(/[&<>"']/g, function(m) { return map[m]; });
        }


        // Variables to hold fetched data and pagination state
        let activityLogData = [];
        let activityCurrentPage = 1;
        let activityItemsPerPage = 10; // Default items per page
        let activityTotalRecords = 0;

        // --- Fetch Activity Log Data ---
        async function fetchActivityLog(page = 1, itemsPerPage = 10, dateRange = 'all_time', activityType = 'all', adminUser = 'all') {
            const tableBody = document.getElementById('activityLogTableBody');
            if (!tableBody) { console.error('Activity Log Table Body not found!'); return; }

            // Show loading state
            tableBody.innerHTML = `<tr><td colspan="4" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center table-message">Loading activity log...</td></tr>`;

            const queryParams = new URLSearchParams();
            queryParams.append('page', page);
            queryParams.append('itemsPerPage', itemsPerPage);
            queryParams.append('dateRange', dateRange);
            queryParams.append('activityType', activityType);
            queryParams.append('adminUser', adminUser);
            // Add search parameter if implemented
            // const searchInput = document.getElementById('activitySearch');
            // if (searchInput && searchInput.value) queryParams.append('search', searchInput.value);

            console.log(`Fetching activity log: Page=${page}, ItemsPerPage=${itemsPerPage}, DateRange=${dateRange}, Type=${activityType}, Admin=${adminUser}`);


            try {
                // Assuming you will create this API: admin/api/fetch_all_activity.php
                const response = await fetch(`./api/fetch_all_activity.php?${queryParams.toString()}`);
                const data = await response.json();

                console.log('API Response:', data);


                if (response.ok && data.success && data.activity_log && data.activity_log.length > 0) {
                    activityLogData = data.activity_log; // Store data
                    activityTotalRecords = data.total_records || 0; // Store total records
                    activityCurrentPage = data.current_page || page; // Update current page from API if provided
                    updateActivityLogTable(activityLogData); // Update the table
                    updateActivityLogPagination(); // Update pagination controls
                    showAdminConfirmation(data.message || 'Activity log fetched.', 'green');
                } else {
                    console.warn('fetchActivityLog: Backend reported no data:', data.message);
                    activityLogData = []; // Store empty data
                    activityTotalRecords = 0; // Reset total records
                    activityCurrentPage = 1; // Reset current page
                    updateActivityLogTable([]); // Update table with empty data
                    updateActivityLogPagination(); // Update pagination controls
                    showAdminConfirmation(data.message || 'No activity found for the selected filters.', 'orange');
                }
            } catch (error) {
                console.error('fetchActivityLog: Fetch Error:', error);
                showAdminConfirmation('Error fetching activity log!', 'red');
                activityLogData = []; // Clear data on error
                activityTotalRecords = 0; // Reset total records
                activityCurrentPage = 1; // Reset current page
                updateActivityLogTable([]); // Update table with empty data
                updateActivityLogPagination(); // Update pagination controls
                 tableBody.innerHTML = `<tr><td colspan="4" class="px-6 py-4 whitespace-nowrap text-sm text-red-600 text-center table-message">Error loading activity log.</td></tr>`;
            }
        }

        // --- Update Activity Log Table ---
        function updateActivityLogTable(activity) { // Takes the array of activity entries
            const tableBody = document.getElementById('activityLogTableBody');
            if (!tableBody) { console.error('Activity Log Table Body not found for update!'); return; }

            tableBody.innerHTML = ''; // Clear existing rows

            if (activity && activity.length > 0) {
                activity.forEach(entry => {
                    // Assuming the API returns objects with keys: timestamp, admin_username, activity_type, description
                    const row = `
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                ${formatDateTime(entry.timestamp || null)}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                ${htmlspecialchars(entry.admin_username || 'N/A')}
                            </td>
                             <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                 ${htmlspecialchars(entry.activity_type || 'N/A')}
                             </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                ${htmlspecialchars(entry.description || 'No description')}
                            </td>
                        </tr>
                    `;
                    tableBody.innerHTML += row;
                });
            } else {
                 tableBody.innerHTML = `
                     <tr>
                         <td colspan="4" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center table-message">No activity found.</td>
                     </tr>
                 `;
            }
        }

        // --- Update Activity Log Pagination Controls ---
        function updateActivityLogPagination() {
            const totalRecords = activityTotalRecords;
            const itemsPerPage = activityItemsPerPage;
            const currentPage = activityCurrentPage;
            const totalPages = Math.ceil(totalRecords / itemsPerPage);

            console.log(`Updating Pagination: CurrentPage=${currentPage}, TotalRecords=${totalRecords}, TotalPages=${totalPages}`);


            const showingFromEl = document.getElementById('activityShowingFrom');
            const showingToEl = document.getElementById('activityShowingTo');
            const totalRecordsEl = document.getElementById('activityTotalRecords');
            const prevBtn = document.getElementById('activityPrev');
            const nextBtn = document.getElementById('activityNext');
            const prevMobileBtn = document.getElementById('activityPrevMobile');
            const nextMobileBtn = document.getElementById('activityNextMobile');
            const pageNumbersSpan = document.getElementById('activityPageNumbers');

            if (totalRecordsEl) totalRecordsEl.textContent = totalRecords;

            if (totalRecords === 0) {
                if (showingFromEl) showingFromEl.textContent = '0';
                if (showingToEl) showingToEl.textContent = '0';
            } else {
                const showingFrom = (currentPage - 1) * itemsPerPage + 1;
                const showingTo = Math.min(currentPage * itemsPerPage, totalRecords);
                if (showingFromEl) showingFromEl.textContent = showingFrom;
                if (showingToEl) showingToEl.textContent = showingTo;
            }

            // Enable/disable Prev/Next buttons (visual only, not actual disabled attribute)
            if (prevBtn) {
                prevBtn.classList.toggle('opacity-50', currentPage <= 1);
                prevBtn.classList.toggle('cursor-not-allowed', currentPage <= 1);
            }
            if (nextBtn) {
                nextBtn.classList.toggle('opacity-50', currentPage >= totalPages);
                nextBtn.classList.toggle('cursor-not-allowed', currentPage >= totalPages);
            }
            if (prevMobileBtn) {
                prevMobileBtn.classList.toggle('opacity-50', currentPage <= 1);
                prevMobileBtn.classList.toggle('cursor-not-allowed', currentPage <= 1);
            }
            if (nextMobileBtn) {
                nextMobileBtn.classList.toggle('opacity-50', currentPage >= totalPages);
                nextMobileBtn.classList.toggle('cursor-not-allowed', currentPage >= totalPages);
            }

            // Update page numbers display
            if (pageNumbersSpan) {
                pageNumbersSpan.innerHTML = ''; // Clear existing content
                if (totalPages > 0) {
                    for (let i = 1; i <= totalPages; i++) {
                        const pageButton = document.createElement('button');
                        pageButton.textContent = i;
                        pageButton.classList.add('relative', 'inline-flex', 'items-center', 'px-4', 'py-2', 'border', 'text-sm', 'font-medium');
                        if (i === currentPage) {
                            pageButton.classList.add('z-10', 'bg-blue-50', 'border-blue-500', 'text-blue-600');
                        } else {
                            pageButton.classList.add('bg-white', 'border-gray-300', 'text-gray-500', 'hover:bg-gray-50');
                        }
                        // Use a data attribute to store the page number
                        pageButton.dataset.page = i;
                        pageNumbersSpan.appendChild(pageButton);
                    }
                } else {
                    pageNumbersSpan.textContent = 'No Pages';
                }
                // Attach listeners to dynamically created page number buttons
                pageNumbersSpan.querySelectorAll('button').forEach(button => {
                    button.addEventListener('click', handlePageClick);
                });
            }
        }

        // --- Handle Activity Log Pagination Button Clicks ---
        function handleActivityPaginationClick(event) {
            // Use currentTarget to ensure we get the button element, not its child SVG
            const targetId = event.currentTarget.id;
            const dateRange = document.getElementById('activityDateRange').value;
            const activityType = document.getElementById('activityTypeFilter').value;
            const adminUser = document.getElementById('activityAdminFilter').value;

            const totalPages = Math.ceil(activityTotalRecords / activityItemsPerPage); // Recalculate totalPages here

            if (targetId === 'activityPrev' || targetId === 'activityPrevMobile') {
                if (activityCurrentPage > 1) {
                    activityCurrentPage--;
                    fetchActivityLog(activityCurrentPage, activityItemsPerPage, dateRange, activityType, adminUser);
                } else {
                    console.log('Already on the first page.');
                }
            } else if (targetId === 'activityNext' || targetId === 'activityNextMobile') {
                if (activityCurrentPage < totalPages) {
                    activityCurrentPage++;
                    fetchActivityLog(activityCurrentPage, activityItemsPerPage, dateRange, activityType, adminUser);
                } else {
                    console.log('Already on the last page.');
                }
            }
        }

        // Handle clicks on individual page number buttons
        function handlePageClick(event) {
            const page = parseInt(event.currentTarget.dataset.page); // Get page from data attribute
            if (page && page !== activityCurrentPage) {
                activityCurrentPage = page;
                const dateRange = document.getElementById('activityDateRange').value;
                const activityType = document.getElementById('activityTypeFilter').value;
                const adminUser = document.getElementById('activityAdminFilter').value;
                fetchActivityLog(activityCurrentPage, activityItemsPerPage, dateRange, activityType, adminUser);
            }
        }


         // --- Fetch Admin Users for Filter Dropdown ---
         async function fetchAdminUsersForFilter() {
             const adminFilterSelect = document.getElementById('activityAdminFilter');
             if (!adminFilterSelect) { console.error('Admin Filter Select not found!'); return; }

             try {
                 // Assuming you will create this API: admin/api/fetch_admin_users.php
                 const response = await fetch('./api/fetch_admin_users.php');
                 const data = await response.json();

                 if (response.ok && data.success && data.admin_users && data.admin_users.length > 0) {
                     // Clear existing options except 'all'
                     adminFilterSelect.innerHTML = '<option value="all">All Admins</option>';
                     data.admin_users.forEach(admin => {
                         const option = document.createElement('option');
                         option.value = admin.staff_id; // Assuming API returns staff_id
                         option.textContent = htmlspecialchars(admin.username || `Admin ID ${admin.staff_id}`); // Assuming API returns username
                         adminFilterSelect.appendChild(option);
                     });
                 } else {
                     console.warn('fetchAdminUsersForFilter: No admin users found or error:', data.message);
                     // Keep default "All Admins" option
                 }
             } catch (error) {
                 console.error('fetchAdminUsersForFilter: Fetch Error:', error);
                 // Keep default "All Admins" option
             }
         }


        // --- Initial Load and Event Listeners ---
        document.addEventListener('DOMContentLoaded', function () {
            // Initial fetch of activity log (first page, default filters)
            fetchActivityLog(activityCurrentPage, activityItemsPerPage);

            // Fetch admin users for the filter dropdown
            fetchAdminUsersForFilter();

            // Add event listeners for filters
            const activityDateRangeSelect = document.getElementById('activityDateRange');
            const activityTypeFilterSelect = document.getElementById('activityTypeFilter');
            const activityAdminFilterSelect = document.getElementById('activityAdminFilter');


            if (activityDateRangeSelect) activityDateRangeSelect.addEventListener('change', function() {
                activityCurrentPage = 1; // Reset to first page on filter change
                fetchActivityLog(activityCurrentPage, activityItemsPerPage, this.value, activityTypeFilterSelect.value, activityAdminFilterSelect.value); // Pass all current filters
            });

            if (activityTypeFilterSelect) activityTypeFilterSelect.addEventListener('change', function() {
                activityCurrentPage = 1; // Reset to first page on filter change
                fetchActivityLog(activityCurrentPage, activityItemsPerPage, activityDateRangeSelect.value, this.value, activityAdminFilterSelect.value); // Pass all current filters
            });

            if (activityAdminFilterSelect) activityAdminFilterSelect.addEventListener('change', function() {
                 activityCurrentPage = 1; // Reset to first page on filter change
                 fetchActivityLog(activityCurrentPage, activityItemsPerPage, activityDateRangeSelect.value, activityTypeFilterSelect.value, this.value); // Pass all current filters
             });


            // Add event listeners for pagination buttons
            const activityPrevBtn = document.getElementById('activityPrev');
            const activityNextBtn = document.getElementById('activityNext');
            const activityPrevMobileBtn = document.getElementById('activityPrevMobile');
            const activityNextMobileBtn = document.getElementById('activityNextMobile');

            // Attach event listeners to the buttons themselves
            if (activityPrevBtn) activityPrevBtn.addEventListener('click', handleActivityPaginationClick);
            if (activityNextBtn) activityNextBtn.addEventListener('click', handleActivityPaginationClick);
            if (activityPrevMobileBtn) activityPrevMobileBtn.addEventListener('click', handleActivityPaginationClick);
            if (activityNextMobileBtn) activityNextMobileBtn.addEventListener('click', handleActivityPaginationClick);

        });

    </script>

</body>
</html>
<?php
// Close the database connection
if (isset($link)) {
    mysqli_close($link);
}
?>
