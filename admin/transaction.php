<?php
// admin/transaction.php - Admin Transaction Management (Frontend Page)

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

// Include database connection (Needed here for potential future server-side rendering or data checks,
// although the current data fetching is via AJAX)
// Path: From admin/ UP to root (../) THEN into includes/
require_once '../includes/db_connection.php'; // Make sure this path is correct and uses $link

// Get admin username and role from session for header (optional, for header display)
$admin_username = $_SESSION['admin_username'] ?? 'Admin';
$admin_role = $_SESSION['admin_role'] ?? 'N/A'; // Get the logged-in admin's role

// Include necessary packages (like CSS, Flowbite, Tailwind) - Path from admin/ UP to root (../) then includes/
include '../includes/packages.php'; // Ensure this path is correct

// Include Admin Header HTML - ENSURE THIS LINE APPEARS ONLY ONCE IN THIS FILE
// Ensure admin_header.php exists in includes/ folder
// Path: From admin/ UP to root (../) THEN into includes/admin_header.php
include '../includes/admin_header.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaction Management - Admin</title>
    <style>
        /* Add some basic styling for the table and pagination */
        body {
            font-family: 'Inter', sans-serif;
        }
        .container {
            padding: 2rem;
        }
        .table-container {
            background-color: #ffffff;
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
            overflow-x: auto; /* Ensures table is scrollable on small screens */
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
            white-space: nowrap; /* Prevents text wrapping in cells */
        }
        th {
            background-color: #f8f8f8;
            font-weight: 600;
            color: #4a5568;
        }
        tbody tr:hover {
            background-color: #f0f4f8;
        }
        .pagination-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 0;
            font-size: 0.875rem;
            color: #4a5568;
        }
        .pagination-buttons button {
            background-color: #ffffff;
            border: 1px solid #e2e8f0;
            padding: 0.5rem 1rem;
            margin: 0 0.25rem;
            border-radius: 0.375rem;
            cursor: pointer;
            transition: background-color 0.2s, border-color 0.2s;
        }
        .pagination-buttons button:hover:not(:disabled) {
            background-color: #f0f4f8;
            border-color: #cbd5e0;
        }
        .pagination-buttons button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        .pagination-buttons button.active {
            background-color: #3b82f6; /* Blue 500 */
            color: white;
            border-color: #3b82f6;
        }
        /* Re-added modal base styles for proper display */
        .modal {
            display: none; /* Hidden by default */
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.4);
            align-items: center;
            justify-content: center;
        }
        .modal.flex { /* When 'flex' class is added by JS, it will be displayed */
            display: flex !important; /* Use !important to ensure it overrides 'display: none' */
        }

        .modal-content-wrapper { /* New wrapper for modal content for consistent styling */
            background-color: #fefefe;
            border-radius: 8px;
            position: relative;
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
            border-radius: 12px;
            overflow: hidden;
            animation: fadeIn 0.3s ease-out;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .close-button {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            position: absolute;
            top: 10px;
            right: 20px;
            cursor: pointer;
            transition: color 0.2s;
        }
        .close-button:hover,
        .close-button:focus {
            color: #333;
        }
        .transaction-items-list {
            margin-top: 1rem;
            border-top: 1px solid #e2e8f0;
            padding-top: 1rem;
        }
        .transaction-items-list div {
            display: flex;
            align-items: center;
            padding: 0.5rem 0;
            border-bottom: 1px dashed #edf2f7;
        }
        .transaction-items-list div:last-child {
            border-bottom: none;
        }
        .food-item-image {
            width: 60px; /* Further increased size */
            height: 60px; /* Further increased size */
            border-radius: 8px; /* Rounded corners for image */
            object-fit: cover;
            margin-right: 15px; /* Space between image and text */
            border: 1px solid #e2e8f0;
            flex-shrink: 0; /* Prevent image from shrinking */
        }
        .badge-pending {
            background-color: #fffbeb;
            color: #d97706;
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            font-size: 0.75rem;
            font-weight: 500;
        }
        /* Adjusted colors for better contrast */
        .badge-success {
            background-color: #d1fae5; /* Lighter green background */
            color: #065f46; /* Darker green text */
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            font-size: 0.75rem;
            font-weight: 500;
        }

        /* Table status badges */
        .status-badge {
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            font-size: 0.75rem;
            font-weight: 500;
        }
        .status-badge.success {
            background-color: #d1fae5;
            color: #065f46;
        }
        .status-badge.pending {
            background-color: #fffbeb;
            color: #d97706;
        }

        /* Fix for dark text in modal */
        .modal-content-wrapper .text-gray-900 {
            color: #111827 !important; /* Force dark text in modal */
        }

        /* Styles for new action buttons */
        .action-buttons-group {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap; /* Allow wrapping on smaller screens */
        }
        .action-buttons-group button {
            padding: 8px 16px;
            border-radius: 6px;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.2s, transform 0.1s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .action-buttons-group button:hover:not(:disabled) {
            transform: translateY(-1px);
        }
        .action-buttons-group button:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        .export-btn {
            background-color: #10b981; /* Green */
            color: white;
            border: none;
        }
        .export-btn:hover {
            background-color: #059669; /* Darker green */
        }
        .delete-selected-btn {
            background-color: #ef4444; /* Red */
            color: white;
            border: none;
        }
        .delete-selected-btn:hover {
            background-color: #dc2626; /* Darker red */
        }
    </style>
</head>
<body class="bg-gray-100 text-gray-900 font-sans">
    <main class="container mx-auto mt-6">
        <h2 class="text-3xl font-bold text-gray-800 mb-6">Transaction Management</h2>

        <div class="mb-4 flex flex-wrap gap-4 justify-between items-center">
            <input type="text" id="searchInput" placeholder="Search by Student Name or NFC ID..."
                   class="p-2 border border-gray-300 rounded-md shadow-sm flex-grow max-w-sm">
            <select id="statusFilter" class="p-2 border border-gray-300 rounded-md shadow-sm">
                <option value="">All Statuses</option>
                <option value="success">Success</option>
                <option value="pending">Pending</option>
            </select>
            <input type="date" id="startDate" class="p-2 border border-gray-300 rounded-md shadow-sm">
            <input type="date" id="endDate" class="p-2 border border-gray-300 rounded-md shadow-sm">
            <button id="applyFiltersBtn" class="bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-blue-600 transition-colors">Apply Filters</button>
            <button id="resetFiltersBtn" class="bg-gray-300 text-gray-800 px-4 py-2 rounded-md hover:bg-gray-400 transition-colors">Reset Filters</button>
        </div>

        <div class="action-buttons-group">
            <button id="exportPdfBtn" class="export-btn">
                <svg class="w-5 h-5 mr-1" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M5.5 17a4.5 4.5 0 01-1.44-8.765 4.5 4.5 0 018.302-3.046 3.5 3.5 0 01.844 6.705L15 10l-2 3h2l2-3h-2.5a4.5 4.5 0 01-4.5 4.5z" clip-rule="evenodd"></path></svg>
                Export PDF
            </button>
            <button id="exportExcelBtn" class="export-btn">
                <svg class="w-5 h-5 mr-1" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M5.5 17a4.5 4.5 0 01-1.44-8.765 4.5 4.5 0 018.302-3.046 3.5 3.5 0 01.844 6.705L15 10l-2 3h2l2-3h-2.5a4.5 4.5 0 01-4.5 4.5z" clip-rule="evenodd"></path></svg>
                Export Excel
            </button>
            <?php if ($admin_role === 'super_administrator' || $admin_role === 'administrator'): // Changed condition ?>
            <button id="deleteSelectedBtn" class="delete-selected-btn" disabled>
                <svg class="w-5 h-5 mr-1" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 011-1h4a1 1 0 110 2H8a1 1 0 01-1-1zm-2 4a1 1 0 011-1h4a1 1 0 110 2H6a1 1 0 01-1-1z" clip-rule="evenodd"></path></svg>
                Delete Selected
            </button>
            <?php endif; ?>
        </div>


        <div class="table-container">
            <table class="min-w-full divide-y divide-gray-200">
                <thead>
                    <tr>
                        <th>
                            <input type="checkbox" id="selectAllTransactions" class="h-4 w-4 text-indigo-600 border-gray-300 rounded">
                        </th>
                        <th>Transaction ID</th>
                        <th>Student Name</th>
                        <th>NFC ID</th>
                        <th>Total Amount</th>
                        <th>Timestamp</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="transactionTableBody" class="bg-white divide-y divide-gray-200">
                    <tr>
                        <td colspan="8" class="text-center py-4 text-gray-500">Loading transactions...</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="pagination-container">
            <div id="paginationInfo">Showing 0 to 0 of 0 results</div>
            <div class="pagination-buttons">
                <button id="prevPageBtn" disabled>Previous</button>
                <div id="pageNumbers" class="inline-flex space-x-1">
                    </div>
                <button id="nextPageBtn" disabled>Next</button>
            </div>
        </div>
    </main>

    <div id="transactionDetailsModal" tabindex="-1" aria-hidden="true" class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full">
        <div class="relative p-4 w-full max-w-md max-h-full">
            <div class="relative bg-white rounded-lg shadow dark:bg-gray-700 modal-content-wrapper">
                <div class="flex items-center justify-between p-4 md:p-5 border-b rounded-t dark:border-gray-600">
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white">
                        Transaction Details #<span id="modalTxnId"></span>
                    </h3>
                    <button type="button" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center dark:hover:bg-gray-600 dark:hover:text-white close-button" data-modal-hide="transactionDetailsModal">
                        <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 6 6-6M7 7l6 6-6-6Z"/>
                        </svg>
                        <span class="sr-only">Close modal</span>
                    </button>
                </div>
                <div class="p-4 md:p-5 space-y-4 text-gray-900"> <div class="space-y-2 mb-4">
                        <p><strong>Student Name:</strong> <span id="modalStudentName" class="font-medium"></span></p>
                        <p><strong>NFC ID:</strong> <span id="modalNFCId" class="font-medium"></span></p>
                        <p><strong>Total Amount:</strong> <span id="modalTotalAmount" class="font-medium"></span></p>
                        <p><strong>Timestamp:</strong> <span id="modalTimestamp" class="font-medium"></span></p>
                        <p><strong>Status:</strong> <span id="modalStatus" class="font-medium"></span></p>
                    </div>
                    <h4 class="font-semibold text-lg mt-6 mb-2 text-gray-800">Items:</h4>
                    <div id="modalItems" class="transaction-items-list">
                        <p class="text-gray-500 dark:text-gray-400">Loading items...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <script>
        // DOM Elements
        const transactionTableBody = document.getElementById('transactionTableBody');
        const searchInput = document.getElementById('searchInput');
        const statusFilter = document.getElementById('statusFilter');
        const startDateInput = document.getElementById('startDate');
        const endDateInput = document.getElementById('endDate');
        const applyFiltersBtn = document.getElementById('applyFiltersBtn');
        const resetFiltersBtn = document.getElementById('resetFiltersBtn');

        const prevPageBtn = document.getElementById('prevPageBtn');
        const nextPageBtn = document.getElementById('nextPageBtn');
        const pageNumbersContainer = document.getElementById('pageNumbers');
        const paginationInfo = document.getElementById('paginationInfo');

        const transactionDetailsModal = document.getElementById('transactionDetailsModal');
        const modalTxnId = document.getElementById('modalTxnId');
        const modalStudentName = document.getElementById('modalStudentName');
        const modalNFCId = document.getElementById('modalNFCId');
        const modalTotalAmount = document.getElementById('modalTotalAmount');
        const modalTimestamp = document.getElementById('modalTimestamp');
        const modalStatus = document.getElementById('modalStatus');
        const modalItems = document.getElementById('modalItems');

        // New action buttons
        const exportPdfBtn = document.getElementById('exportPdfBtn');
        const exportExcelBtn = document.getElementById('exportExcelBtn');
        const deleteSelectedBtn = document.getElementById('deleteSelectedBtn'); // This button might not exist if not super_admin

        // Checkbox elements
        const selectAllTransactionsCheckbox = document.getElementById('selectAllTransactions');


        // Pagination variables
        let currentPage = 1;
        const itemsPerPage = 20; // Set to 20 elements per page
        let totalPages = 1;
        let totalTransactions = 0;

        // PHP variable for admin role
        const currentAdminRole = <?php echo json_encode($admin_role); ?>;


        // Function to fetch and display transactions
        async function fetchAndDisplayTransactions() {
            transactionTableBody.innerHTML = '<tr><td colspan="8" class="text-center py-4 text-gray-500">Loading transactions...</td></tr>'; // Updated colspan
            setPaginationButtonsState(true); // Disable buttons during fetch
            uncheckAllTransactionCheckboxes(); // Uncheck checkboxes on new data load

            const searchTerm = searchInput.value.trim();
            const status = statusFilter.value;
            const startDate = startDateInput.value;
            const endDate = endDateInput.value;

            let url = `./api/fetch_transactions.php?page=${currentPage}&limit=${itemsPerPage}`;
            if (searchTerm) url += `&search=${encodeURIComponent(searchTerm)}`;
            if (status) url += `&status=${encodeURIComponent(status)}`;
            if (startDate) url += `&start_date=${encodeURIComponent(startDate)}`;
            if (endDate) url += `&end_date=${encodeURIComponent(endDate)}`;

            try {
                const response = await fetch(url);
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                const data = await response.json();

                if (data.success) {
                    renderTransactionTable(data.transactions);
                    totalTransactions = data.total_transactions;
                    totalPages = data.total_pages;
                    renderPaginationControls(data.current_page, data.total_pages, data.total_transactions);
                } else {
                    transactionTableBody.innerHTML = `<tr><td colspan="8" class="text-center py-4 text-red-500">${data.message || 'Failed to load transactions.'}</td></tr>`; // Updated colspan
                    totalTransactions = 0;
                    totalPages = 1;
                    renderPaginationControls(1, 1, 0);
                }
            } catch (error) {
                console.error('Error fetching transactions:', error);
                transactionTableBody.innerHTML = `<tr><td colspan="8" class="text-center py-4 text-red-500">Error loading transactions. Please try again.</td></tr>`; // Updated colspan
                totalTransactions = 0;
                totalPages = 1;
                renderPaginationControls(1, 1, 0);
            } finally {
                setPaginationButtonsState(false); // Re-enable buttons after fetch
            }
        }

        function renderTransactionTable(transactions) {
            if (transactions.length === 0) {
                transactionTableBody.innerHTML = '<tr><td colspan="8" class="text-center py-4 text-gray-500">No transactions found.</td></tr>'; // Updated colspan
                return;
            }

            let html = '';
            transactions.forEach(txn => {
                html += `
                    <tr data-txn-id="${txn.txn_id}">
                        <td>
                            <input type="checkbox" class="transaction-checkbox h-4 w-4 text-indigo-600 border-gray-300 rounded" data-txn-id="${txn.txn_id}">
                        </td>
                        <td>${txn.txn_id}</td>
                        <td>${txn.student_name || 'N/A'}</td>
                        <td>${txn.nfc_id || 'N/A'}</td>
                        <td>NPR ${parseFloat(txn.total_amount).toFixed(2)}</td>
                        <td>${new Date(txn.transaction_time).toLocaleString()}</td>
                        <td><span class="status-badge ${txn.status}">${txn.status}</span></td>
                        <td>
                            <button class="view-details-btn bg-blue-500 text-white px-3 py-1 rounded-md text-sm hover:bg-blue-600 transition-colors"
                                    data-txn-id="${txn.txn_id}">View Details</button>
                        </td>
                    </tr>
                `;
            });
            transactionTableBody.innerHTML = html;
            attachViewDetailsListeners();
            attachCheckboxListeners(); // Attach listeners for new checkboxes
        }

        function renderPaginationControls(currentPage, totalPages, totalTransactions) {
            // Update info text
            const startItem = (currentPage - 1) * itemsPerPage + 1;
            const endItem = Math.min(currentPage * itemsPerPage, totalTransactions);
            paginationInfo.textContent = `Showing ${totalTransactions === 0 ? 0 : startItem} to ${endItem} of ${totalTransactions} results`;

            // Render page number buttons
            pageNumbersContainer.innerHTML = '';
            const maxPageButtons = 5; // Show a maximum of 5 page buttons

            let startPage = Math.max(1, currentPage - Math.floor(maxPageButtons / 2));
            let endPage = Math.min(totalPages, startPage + maxPageButtons - 1);

            // Adjust startPage if we hit the end
            if (endPage - startPage + 1 < maxPageButtons) {
                startPage = Math.max(1, endPage - maxPageButtons + 1);
            }

            if (startPage > 1) {
                pageNumbersContainer.innerHTML += `<button class="page-number-btn" data-page="1">1</button>`;
                if (startPage > 2) {
                    pageNumbersContainer.innerHTML += `<span>...</span>`;
                }
            }

            for (let i = startPage; i <= endPage; i++) {
                const activeClass = i === currentPage ? ' active' : '';
                pageNumbersContainer.innerHTML += `<button class="page-number-btn${activeClass}" data-page="${i}">${i}</button>`;
            }

            if (endPage < totalPages) {
                if (endPage < totalPages - 1) {
                    pageNumbersContainer.innerHTML += `<span>...</span>`;
                }
                pageNumbersContainer.innerHTML += `<button class="page-number-btn" data-page="${totalPages}">${totalPages}</button>`;
            }

            // Set disabled state for Prev/Next buttons
            prevPageBtn.disabled = currentPage === 1;
            nextPageBtn.disabled = currentPage === totalPages;

            attachPageNumberListeners();
        }

        function setPaginationButtonsState(disabled) {
            prevPageBtn.disabled = disabled;
            nextPageBtn.disabled = disabled;
            pageNumbersContainer.querySelectorAll('button').forEach(btn => btn.disabled = disabled);
        }

        function attachPageNumberListeners() {
            document.querySelectorAll('.page-number-btn').forEach(button => {
                button.removeEventListener('click', handlePageClick); // Prevent multiple listeners
                button.addEventListener('click', handlePageClick);
            });
        }

        function handlePageClick(event) {
            const page = parseInt(event.target.dataset.page);
            if (page && page !== currentPage) {
                currentPage = page;
                fetchAndDisplayTransactions();
            }
        }

        // Event Listeners for pagination buttons
        prevPageBtn.addEventListener('click', () => {
            if (currentPage > 1) {
                currentPage--;
                fetchAndDisplayTransactions();
            }
        });

        nextPageBtn.addEventListener('click', () => {
            if (currentPage < totalPages) {
                currentPage++;
                fetchAndDisplayTransactions();
            }
        });

        // Event Listeners for Filters
        applyFiltersBtn.addEventListener('click', () => {
            currentPage = 1; // Reset to first page on filter change
            fetchAndDisplayTransactions();
        });

        resetFiltersBtn.addEventListener('click', () => {
            searchInput.value = '';
            statusFilter.value = '';
            startDateInput.value = '';
            endDateInput.value = '';
            currentPage = 1;
            fetchAndDisplayTransactions();
        });

        // Function to fetch and display transaction details in modal
        async function fetchAndDisplayTransactionDetails(txnId) {
            try {
                const response = await fetch(`./api/fetch_transaction_details.php?txn_id=${encodeURIComponent(txnId)}`);
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                const data = await response.json();

                if (data.success) {
                    const txn = data.transaction;
                    modalTxnId.textContent = txn.txn_id;
                    modalStudentName.textContent = txn.student_name || 'N/A';
                    modalNFCId.textContent = txn.nfc_id || 'N/A';
                    modalTotalAmount.textContent = `NPR ${parseFloat(txn.total_amount).toFixed(2)}`; // Added NPR prefix
                    modalTimestamp.textContent = new Date(txn.transaction_time).toLocaleString();
                    modalStatus.textContent = txn.status;
                    // Apply appropriate badge class based on status
                    modalStatus.className = txn.status === 'success' ? 'badge-success' : 'badge-pending';

                    modalItems.innerHTML = '';
                    if (txn.items && txn.items.length > 0) {
                        txn.items.forEach(item => {
                            const itemDiv = document.createElement('div');
                            // Fallback image in case image_path is empty or broken
                            // Note: The path `../images/` assumes your images folder is one level up from `admin/`
                            const imageUrl = item.image_path && item.image_path !== '' ? `../${item.image_path}` : 'https://placehold.co/60x60/cccccc/000000?text=Food';
                            itemDiv.innerHTML = `
                                <img src="${imageUrl}" class="food-item-image" alt="${item.food_name}" onerror="this.onerror=null;this.src='https://placehold.co/60x60/cccccc/000000?text=Food';">
                                <span>${item.food_name} (x${item.quantity}) - NPR ${parseFloat(item.unit_price).toFixed(2)} each</span>
                            `; // Image placed before text
                            modalItems.appendChild(itemDiv);
                        });
                    } else {
                        modalItems.innerHTML = '<p class="text-gray-500">No items found for this transaction.</p>';
                    }

                    // Show the modal by removing 'hidden' and adding 'flex'
                    transactionDetailsModal.classList.remove('hidden');
                    transactionDetailsModal.classList.add('flex');
                } else {
                    // Using a custom message box instead of alert()
                    const message = data.message || 'Failed to load transaction details.';
                    displayMessageBox(message, 'error');
                }
            } catch (error) {
                console.error('Error fetching transaction details:', error);
                // Using a custom message box instead of alert()
                displayMessageBox('Error loading transaction details. Please try again.', 'error');
            }
        }

        function attachViewDetailsListeners() {
            document.querySelectorAll('.view-details-btn').forEach(button => {
                button.removeEventListener('click', handleViewDetailsClick); // Prevent multiple listeners
                button.addEventListener('click', handleViewDetailsClick);
            });
        }

        function handleViewDetailsClick(event) {
            const txnId = event.target.dataset.txnId;
            if (txnId) {
                fetchAndDisplayTransactionDetails(txnId);
            }
        }

        // --- Modal Close Buttons ---
        document.querySelectorAll('[data-modal-hide="transactionDetailsModal"]').forEach(button => {
            button.addEventListener('click', function() {
                // Hide the modal by adding 'hidden' and removing 'flex'
                transactionDetailsModal.classList.add('hidden');
                transactionDetailsModal.classList.remove('flex');
            });
        });

        // Custom Message Box (instead of alert)
        function displayMessageBox(message, type = 'info') {
            let messageBox = document.getElementById('customMessageBox');
            if (!messageBox) {
                messageBox = document.createElement('div');
                messageBox.id = 'customMessageBox';
                messageBox.style.cssText = `
                    position: fixed;
                    top: 20px;
                    left: 50%;
                    transform: translateX(-50%);
                    padding: 15px 25px;
                    border-radius: 8px;
                    font-weight: bold;
                    z-index: 10000;
                    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                    display: none; /* Initially hidden */
                    opacity: 0;
                    transition: opacity 0.3s ease-in-out;
                `;
                document.body.appendChild(messageBox);
            }

            // Set colors based on type
            if (type === 'success') {
                messageBox.style.backgroundColor = '#d4edda';
                messageBox.style.color = '#155724';
                messageBox.style.borderColor = '#c3e6cb';
            } else if (type === 'error') {
                messageBox.style.backgroundColor = '#f8d7da';
                messageBox.style.color = '#721c24';
                messageBox.style.borderColor = '#f5c6cb';
            } else { // Default info
                messageBox.style.backgroundColor = '#e2e3e5';
                messageBox.style.color = '#383d41';
                messageBox.style.borderColor = '#d6d8db';
            }

            messageBox.textContent = message;
            messageBox.style.display = 'block';
            setTimeout(() => {
                messageBox.style.opacity = 1;
            }, 10); // Small delay to allow display:block to take effect

            setTimeout(() => {
                messageBox.style.opacity = 0;
                messageBox.addEventListener('transitionend', function handler() {
                    messageBox.style.display = 'none';
                    messageBox.removeEventListener('transitionend', handler);
                });
            }, 3000); // Hide after 3 seconds
        }

        // --- New Export and Bulk Delete Functions ---

        function getSelectedTransactionIds() {
            const checkboxes = document.querySelectorAll('.transaction-checkbox:checked');
            const selectedIds = [];
            checkboxes.forEach(checkbox => {
                selectedIds.push(checkbox.dataset.txnId);
            });
            return selectedIds;
        }

        function updateDeleteSelectedButtonState() {
            // Only update if the button exists (i.e., if currentAdminRole is super_administrator or administrator)
            if (deleteSelectedBtn) {
                const selectedCount = getSelectedTransactionIds().length;
                deleteSelectedBtn.disabled = selectedCount === 0;
            }
        }

        function uncheckAllTransactionCheckboxes() {
            document.querySelectorAll('.transaction-checkbox').forEach(checkbox => {
                checkbox.checked = false;
            });
            if (selectAllTransactionsCheckbox) {
                selectAllTransactionsCheckbox.checked = false;
            }
            updateDeleteSelectedButtonState();
        }

        function attachCheckboxListeners() {
            // Attach listener for individual transaction checkboxes
            document.querySelectorAll('.transaction-checkbox').forEach(checkbox => {
                checkbox.removeEventListener('change', updateDeleteSelectedButtonState); // Prevent multiple listeners
                checkbox.addEventListener('change', updateDeleteSelectedButtonState);
            });

            // Attach listener for "Select All" checkbox
            if (selectAllTransactionsCheckbox) {
                selectAllTransactionsCheckbox.removeEventListener('change', handleSelectAllChange); // Prevent multiple listeners
                selectAllTransactionsCheckbox.addEventListener('change', handleSelectAllChange);
            }
        }

        function handleSelectAllChange() {
            const isChecked = selectAllTransactionsCheckbox.checked;
            document.querySelectorAll('.transaction-checkbox').forEach(checkbox => {
                checkbox.checked = isChecked;
            });
            updateDeleteSelectedButtonState();
        }

        async function deleteSelectedTransactions() {
            const selectedIds = getSelectedTransactionIds();
            if (selectedIds.length === 0) {
                displayMessageBox('No transactions selected for deletion.', 'warning');
                return;
            }

            if (!confirm(`Are you sure you want to delete ${selectedIds.length} selected transaction(s)? This action cannot be undone.`)) {
                return; // User cancelled
            }

            try {
                // This will call the new backend script: admin/api/delete_transactions_bulk.php
                const response = await fetch('api/delete_transactions_bulk.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ transaction_ids: selectedIds })
                });

                if (!response.ok) {
                    throw new Error(`HTTP error! Status: ${response.status}`);
                }

                const result = await response.json();

                if (result.success) {
                    displayMessageBox(result.message || 'Selected transactions deleted successfully!', 'success');
                    fetchAndDisplayTransactions(); // Refresh the list
                } else {
                    displayMessageBox(result.message || 'Failed to delete selected transactions.', 'error');
                }
            } catch (error) {
                console.error('Error deleting selected transactions:', error);
                displayMessageBox(`Network error: Could not delete selected transactions. ${error.message}`, 'error');
            }
        }

        function exportTransactionsAsPdf() {
            // Construct URL with current filters if needed
            const searchTerm = searchInput.value.trim();
            const status = statusFilter.value;
            const startDate = startDateInput.value;
            const endDate = endDateInput.value;

            const queryParams = new URLSearchParams();
            if (searchTerm) queryParams.append('search', searchTerm);
            if (status) queryParams.append('status', status);
            if (startDate) queryParams.append('start_date', startDate);
            if (endDate) queryParams.append('end_date', endDate);

            // This will call the new backend script: admin/api/export_transactions_pdf.php
            window.location.href = `api/export_transactions_pdf.php?${queryParams.toString()}`;
            displayMessageBox('Generating PDF export...', 'info');
        }

        function exportTransactionsAsExcel() {
            // Construct URL with current filters if needed
            const searchTerm = searchInput.value.trim();
            const status = statusFilter.value;
            const startDate = startDateInput.value;
            const endDate = endDateInput.value;

            const queryParams = new URLSearchParams();
            if (searchTerm) queryParams.append('search', searchTerm);
            if (status) queryParams.append('status', status);
            if (startDate) queryParams.append('start_date', startDate);
            if (endDate) queryParams.append('end_date', endDate);

            // This will call the new backend script: admin/api/export_transactions_excel.php
            window.location.href = `api/export_transactions_excel.php?${queryParams.toString()}`;
            displayMessageBox('Generating Excel export...', 'info');
        }


        // Initial fetch on page load
        document.addEventListener('DOMContentLoaded', function() {
            fetchAndDisplayTransactions();

            // Event Listeners for pagination buttons are already defined above
            // Event Listeners for Filters are already defined above

            // New Action Button Event Listeners
            if (exportPdfBtn) exportPdfBtn.addEventListener('click', exportTransactionsAsPdf);
            if (exportExcelBtn) exportExcelBtn.addEventListener('click', exportTransactionsAsExcel);
            // Check if deleteSelectedBtn exists before adding listener (it only exists for super_admin/admin)
            if (deleteSelectedBtn) deleteSelectedBtn.addEventListener('click', deleteSelectedTransactions);

            // Initial state for delete selected button and checkbox listeners
            updateDeleteSelectedButtonState();
            attachCheckboxListeners();
        });

    </script>

</body>
</html>
<?php
// Close the database connection at the end of the script
if (isset($link)) {
    mysqli_close($link);
}
?>
