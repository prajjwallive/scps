<?php
// admin/staff.php - Manage Admin/Staff Users

date_default_timezone_set('Asia/Kathmandu');
session_start();

// --- REQUIRE ADMIN LOGIN ---
if (!isset($_SESSION['admin_id']) || empty($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}
// --- END REQUIRE ADMIN LOGIN ---

// Get current admin's role from session
$current_admin_id = $_SESSION['admin_id'] ?? null;
$admin_username = $_SESSION['admin_username'] ?? 'Admin';
$admin_role = $_SESSION['admin_role'] ?? 'N/A'; // Crucial: Get the logged-in admin's role

require_once '../includes/db_connection.php';

include '../includes/packages.php'; // Tailwind, Flowbite, etc.
include '../includes/admin_header.php';

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Staff</title>
    <?php // packages.php is included above ?>
    <style>
        /* Ensure body background is consistently white and text is dark */
        body {
            background-color: #ffffff; /* Pure white background */
            font-family: 'Inter', sans-serif;
            color: #1a202c; /* Dark gray for text */
        }
        /* Remove specific dark mode background for body to prevent override */
        /* .dark body {
            background-color: #1a202c;
            color: #f8f8f8;
        } */

        .table-message {
            text-align: center;
            padding: 1.5rem;
            color: #6b7280; /* gray-500 */
        }
        /* Removed dark mode specific styles for table-message */
        /* .dark .table-message {
            color: #9ca3af;
        } */
        .action-btn {
            margin-right: 0.5rem; /* Spacing between action buttons */
        }
    </style>
</head>
<body class="bg-white font-sans"> <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        <div id="staff-action-confirmation" class="fixed top-5 left-1/2 -translate-x-1/2 bg-green-500 text-white p-4 rounded-md shadow-lg z-50 opacity-0 hidden transition-all duration-500 ease-in-out transform">
            Action successful!
        </div>

        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold text-gray-900">Staff Management</h1> <?php if ($admin_role === 'super_administrator'|| $admin_role === 'administrator'): ?>
            <a href="add_staff.php" id="addStaffButton" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 inline-block mr-1 align-middle">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Add New Staff
            </a>
            <?php endif; ?>
        </div>

        <div class="bg-white shadow rounded-lg overflow-hidden"> <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200"> <thead class="bg-gray-50"> <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Full Name</th> <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Username</th> <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th> <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th> <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Last Login</th> <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th> </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200" id="staffTableBody"> <tr>
                            <td colspan="6" class="table-message">Loading staff members...</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="px-6 py-4 flex items-center justify-between border-t border-gray-200" id="staffPaginationContainer" style="display: none;"> <div class="flex-1 flex justify-between sm:hidden">
                    <button id="staffPrevMobile" class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"> Previous </button> <button id="staffNextMobile" class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"> Next </button> </div>
                <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                    <div>
                        <p class="text-sm text-gray-700"> Showing <span class="font-medium" id="staffShowingFrom">0</span> to <span class="font-medium" id="staffShowingTo">0</span> of <span class="font-medium" id="staffTotalRecords">0</span> results
                        </p>
                    </div>
                    <div>
                        <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                            <button id="staffPrev" class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                <span class="sr-only">Previous</span>
                                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>
                            </button> <span id="staffPageNumbers" class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">...</span> <button id="staffNext" class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                <span class="sr-only">Next</span>
                                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10l-3.293-3.293a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" /></svg>
                            </button> </nav>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php // Removed include '../includes/footer.php'; // Standard footer ?>

    <script>
        const staffActionConfirmation = document.getElementById('staff-action-confirmation');
        function showStaffActionConfirmation(message = 'Success!', type = 'success') { // type can be 'success', 'error', 'warning'
            if (!staffActionConfirmation) return;
            let bgColor = 'bg-green-500'; // Default success
            if (type === 'error') bgColor = 'bg-red-500';
            else if (type === 'warning') bgColor = 'bg-yellow-500';

            staffActionConfirmation.className = `fixed top-5 left-1/2 -translate-x-1/2 text-white p-4 rounded-md shadow-lg z-50 opacity-0 hidden transition-all duration-500 ease-in-out transform ${bgColor} block`;
            setTimeout(() => { staffActionConfirmation.classList.add('opacity-100'); }, 10);
            setTimeout(() => {
                staffActionConfirmation.classList.remove('opacity-100');
                setTimeout(() => { staffActionConfirmation.classList.remove('block'); staffActionConfirmation.classList.add('hidden'); }, 500);
            }, 3000);
        }

        function htmlspecialchars(str) {
            if (typeof str !== 'string' && str !== null && str !== undefined) { str = String(str); }
            else if (str === null || str === undefined) { return ''; }
            const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
            return str.replace(/[&<>"']/g, function(m) { return map[m]; });
        }

        function formatDateTime(dateTimeStr) {
            // Handle null, undefined, or empty string explicitly
            if (!dateTimeStr || dateTimeStr === '0000-00-00 00:00:00') return 'Never';
            try {
                const date = new Date(dateTimeStr);
                if (isNaN(date.getTime())) {
                    console.warn("Invalid date provided to formatDateTime:", dateTimeStr);
                    return 'Invalid Date';
                }
                const options = { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit', hour12: true };
                return date.toLocaleDateString('en-IN', options); // Adjust locale as needed
            } catch (e) {
                console.error("Error formatting date/time:", dateTimeStr, e);
                return 'Invalid Date';
            }
        }

        let staffCurrentPage = 1;
        let staffItemsPerPage = 10; // Or load from user preference
        let staffTotalRecords = 0;

        // PHP variables made available to JavaScript
        const currentAdminId = <?php echo json_encode($current_admin_id); ?>;
        const currentAdminRole = <?php echo json_encode($admin_role); ?>;

        async function fetchStaffMembers(page = 1, itemsPerPage = 10, searchTerm = '') {
            const tableBody = document.getElementById('staffTableBody');
            const paginationContainer = document.getElementById('staffPaginationContainer');
            if (!tableBody || !paginationContainer) {
                console.error('Staff table body or pagination container not found!');
                return;
            }

            tableBody.innerHTML = `<tr><td colspan="6" class="table-message">Loading staff members...</td></tr>`;
            paginationContainer.style.display = 'none'; // Hide pagination while loading

            const queryParams = new URLSearchParams({
                page: page,
                itemsPerPage: itemsPerPage,
                // search: searchTerm // Uncomment if search is implemented
            });

            try {
                // Assuming admin/api/fetch_staff.php exists and works correctly
                const response = await fetch(`./api/fetch_staff.php?${queryParams.toString()}`);
                const data = await response.json();

                if (response.ok && data.success) {
                    staffTotalRecords = data.total_records || 0;
                    updateStaffTable(data.staff || []);
                    updateStaffPagination();
                    if (data.staff && data.staff.length > 0) {
                        paginationContainer.style.display = 'flex';
                    }
                } else {
                    tableBody.innerHTML = `<tr><td colspan="6" class="table-message">${htmlspecialchars(data.message || 'No staff members found or error loading.')}</td></tr>`;
                    showStaffActionConfirmation(data.message || 'Could not fetch staff.', 'error');
                    staffTotalRecords = 0;
                    updateStaffPagination(); // Update to show 0 records
                }
            } catch (error) {
                console.error('fetchStaffMembers: Fetch Error:', error);
                tableBody.innerHTML = `<tr><td colspan="6" class="table-message error">Error loading staff members. Check console.</td></tr>`;
                showStaffActionConfirmation('Network error fetching staff!', 'error');
                staffTotalRecords = 0;
                updateStaffPagination();
            }
        }

        function updateStaffTable(staffList) {
            const tableBody = document.getElementById('staffTableBody');
            tableBody.innerHTML = ''; // Clear existing rows

            if (staffList && staffList.length > 0) {
                staffList.forEach(staff => {
                    const statusBadgeClass = staff.is_active == 1
                        ? 'bg-green-100 text-green-800' // Removed dark:bg-green-700 dark:text-green-200
                        : 'bg-red-100 text-red-800'; // Removed dark:bg-red-700 dark:text-red-200
                    const statusText = staff.is_active == 1 ? 'Active' : 'Inactive';

                    let actionButtonsHtml = '';

                    // Determine if the current logged-in admin can modify this staff member
                    const canModify =
                        currentAdminRole === 'super_administrator' || // Super admin can modify anyone
                        (currentAdminId !== staff.staff_id && // Cannot modify self through this table
                        (staff.role !== 'super_administrator' && staff.role !== 'administrator')); // Regular admin cannot modify super admins or other regular admins

                    // Add edit button
                    if (canModify) {
                        actionButtonsHtml += `
                            <a href="edit_staff.php?id=${staff.staff_id}" class="text-indigo-600 hover:text-indigo-900 action-btn edit-staff-btn" data-staff-id="${staff.staff_id}" title="Edit"> <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 inline-block"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" /></svg>
                            </a>
                        `;

                        // Add toggle status button
                        actionButtonsHtml += `
                            <button class="text-red-600 hover:text-red-900 action-btn delete-staff-btn" data-staff-id="${staff.staff_id}" data-staff-username="${htmlspecialchars(staff.username)}" data-staff-role="${htmlspecialchars(staff.role)}" title="${staff.is_active == 1 ? 'Deactivate' : 'Activate'}"> ${staff.is_active == 1 ?
                                 `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 inline-block"><path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 0 0 5.636 5.636m12.728 12.728A9 9 0 0 1 5.636 5.636m12.728 12.728L5.636 5.636" /></svg>` :
                                 `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 inline-block"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>`
                               }
                            </button>
                        `;
                    } else if (currentAdminId == staff.staff_id) { // Use == for comparison as types might differ
                        // If it's the logged-in admin's own row, and they can't modify themselves via these buttons
                        // Provide a link to their profile/settings page instead
                        actionButtonsHtml += `
                            <a href="settings.php" class="text-blue-600 hover:text-blue-900 action-btn" title="Manage your own profile"> <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 inline-block"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h9.75M10.5 6a1.5 1.5 0 1 1-3 0m3 0a1.5 1.5 0 1 0-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-9.75 0h9.75" /></svg>
                                Manage Self
                            </a>
                        `;
                    } else {
                        // If no modification is allowed, display a dash or "N/A"
                        actionButtonsHtml = `<span class="text-gray-500">N/A</span>`; }


                    const row = `
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">${htmlspecialchars(staff.full_name)}</td> <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${htmlspecialchars(staff.username)}</td> <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${htmlspecialchars(staff.role)}</td> <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${statusBadgeClass}">
                                    ${statusText}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${formatDateTime(staff.last_login)}</td> <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                ${actionButtonsHtml}
                            </td>
                        </tr>
                    `;
                    tableBody.innerHTML += row;
                });
                 // Add event listeners for the newly created delete/toggle buttons
                document.querySelectorAll('.delete-staff-btn').forEach(button => {
                    button.addEventListener('click', handleDeleteStaff);
                });
                // Edit buttons go to a new page (edit_staff.php), so no complex listener needed here.

            } else {
                tableBody.innerHTML = `<tr><td colspan="6" class="table-message">No staff members found.</td></tr>`;
            }
        }


        function updateStaffPagination() {
            const totalPages = Math.ceil(staffTotalRecords / staffItemsPerPage);
            const showingFromEl = document.getElementById('staffShowingFrom');
            const showingToEl = document.getElementById('staffShowingTo');
            const totalRecordsEl = document.getElementById('staffTotalRecords');
            const prevBtn = document.getElementById('staffPrev');
            const nextBtn = document.getElementById('staffNext');
            const prevMobileBtn = document.getElementById('staffPrevMobile');
            const nextMobileBtn = document.getElementById('staffNextMobile');
            const pageNumbersSpan = document.getElementById('staffPageNumbers');
            const paginationContainer = document.getElementById('staffPaginationContainer');

            if (!paginationContainer) return;


            if (staffTotalRecords === 0) {
                if (showingFromEl) showingFromEl.textContent = '0';
                if (showingToEl) showingToEl.textContent = '0';
                paginationContainer.style.display = 'none';
            } else {
                const from = (staffCurrentPage - 1) * staffItemsPerPage + 1;
                const to = Math.min(staffCurrentPage * staffItemsPerPage, staffTotalRecords);
                if (showingFromEl) showingFromEl.textContent = from;
                if (showingToEl) showingToEl.textContent = to;
                paginationContainer.style.display = 'flex';
            }
            if (totalRecordsEl) totalRecordsEl.textContent = staffTotalRecords;


            if (prevBtn) prevBtn.disabled = staffCurrentPage <= 1;
            if (nextBtn) nextBtn.disabled = staffCurrentPage >= totalPages;
            if (prevMobileBtn) prevMobileBtn.disabled = staffCurrentPage <= 1;
            if (nextMobileBtn) nextMobileBtn.disabled = staffCurrentPage >= totalPages;

            if (pageNumbersSpan) {
                if (totalPages <= 1) {
                    pageNumbersSpan.textContent = '1';
                } else {
                    pageNumbersSpan.textContent = `Page ${staffCurrentPage} of ${totalPages}`;
                }
            }
        }

        function handleStaffPaginationClick(event) {
            const targetId = event.target.closest('button')?.id; // Ensure we get button id even if svg is clicked
            // const searchTerm = document.getElementById('staffSearch')?.value || ''; // if search implemented

            if (targetId === 'staffPrev' || targetId === 'staffPrevMobile') {
                if (staffCurrentPage > 1) {
                    staffCurrentPage--;
                    fetchStaffMembers(staffCurrentPage, staffItemsPerPage /*, searchTerm*/);
                }
            } else if (targetId === 'staffNext' || targetId === 'staffNextMobile') {
                const totalPages = Math.ceil(staffTotalRecords / staffItemsPerPage);
                if (staffCurrentPage < totalPages) {
                    staffCurrentPage++;
                    fetchStaffMembers(staffCurrentPage, staffItemsPerPage /*, searchTerm*/);
                }
            }
        }

        async function handleDeleteStaff(event) {
            const button = event.currentTarget;
            const staffId = button.dataset.staffId;
            const staffUsername = button.dataset.staffUsername;
            const staffRole = button.dataset.staffRole; // Get the target staff's role
            const isActive = button.title.toLowerCase().includes('deactivate'); // True if current action is to deactivate

            const actionText = isActive ? 'deactivate' : 'activate';

            // Frontend check: Prevent regular admin from modifying super admin or other regular admins
            if (currentAdminRole !== 'super_administrator' && (staffRole === 'super_administrator' || staffRole === 'administrator')) {
                 showStaffActionConfirmation(`You do not have permission to ${actionText} a staff member with role "${staffRole}".`, 'error');
                 return;
            }
            // Also prevent modifying self via this button if it's the current user
            if (currentAdminId == staffId) {
                showStaffActionConfirmation('You cannot change your own status from this table. Please use your profile settings.', 'warning');
                return;
            }


            // Using native confirm() as per your original file's logic
            const confirmAction = confirm(`Are you sure you want to ${actionText} staff member "${staffUsername}" (ID: ${staffId})?`);

            if (confirmAction) {
                try {
                    // Assuming admin/api/toggle_staff_status.php exists and works correctly
                    const response = await fetch('./api/toggle_staff_status.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            staff_id: staffId,
                            action: actionText // Send 'deactivate' or 'activate'
                        }),
                    });
                    const data = await response.json();

                    if (response.ok && data.success) {
                        showStaffActionConfirmation(data.message || `Staff ${actionText}d successfully.`, 'success');
                        fetchStaffMembers(staffCurrentPage, staffItemsPerPage); // Refresh the list
                    } else {
                        showStaffActionConfirmation(data.message || `Failed to ${actionText} staff.`, 'error');
                    }
                } catch (error) {
                    console.error(`Error ${actionText}ing staff:`, error);
                    showStaffActionConfirmation(`Network error. Could not ${actionText} staff.`, 'error');
                }
            }
        }


        document.addEventListener('DOMContentLoaded', function() {
            fetchStaffMembers(staffCurrentPage, staffItemsPerPage);

            document.getElementById('staffPrev')?.addEventListener('click', handleStaffPaginationClick);
            document.getElementById('staffNext')?.addEventListener('click', handleStaffPaginationClick);
            document.getElementById('staffPrevMobile')?.addEventListener('click', handleStaffPaginationClick);
            document.getElementById('staffNextMobile')?.addEventListener('click', handleStaffPaginationClick);

            // Event listener for search (if implemented)
            // const staffSearchInput = document.getElementById('staffSearch');
            // if (staffSearchInput) {
            //     let searchTimeout;
            //     staffSearchInput.addEventListener('input', function() {
            //         clearTimeout(searchTimeout);
            //         searchTimeout = setTimeout(() => {
            //             staffCurrentPage = 1;
            //             fetchStaffMembers(staffCurrentPage, staffItemsPerPage, this.value);
            //         }, 500); // Debounce search
            //     });
            // }
        });

    </script>
</body>
</html>
<?php
if (isset($link)) {
    mysqli_close($link);
}
?>
