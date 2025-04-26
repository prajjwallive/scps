<!DOCTYPE html>
<lang="en">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Document</title>
        <?php include "../includes/packages.php" ?>
    </head>

    <body>
        <?php include "../includes/admin_header.php" ?>
        <!-- Transaction Management Section -->
        <section id="transactions" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="flex justify-between items-center mb-8">
                <h2 class="text-2xl font-bold text-gray-900">Transaction Management</h2>
                <div class="flex space-x-3">
                    <button id="exportPDFBtn"
                        class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-md flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10">
                            </path>
                        </svg>
                        Export PDF
                    </button>
                    <button id="exportExcelBtn"
                        class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                            </path>
                        </svg>
                        Export Excel
                    </button>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white shadow rounded-lg p-4 mb-6">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <div class="flex items-center space-x-4">
                        <div>
                            <label for="dateRange" class="block text-sm font-medium text-gray-700 mb-1">Date
                                Range</label>
                            <select id="dateRange"
                                class="block w-full pl-3 pr-10 py-2 text-base border border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                                <option value="today">Today</option>
                                <option value="yesterday">Yesterday</option>
                                <option value="week">This Week</option>
                                <option value="month">This Month</option>
                                <option value="quarter">This Quarter</option>
                                <option value="year">This Year</option>
                                <option value="custom">Custom Range</option>
                            </select>
                        </div>
                        <div id="customDateRange" class="hidden flex items-center space-x-2">
                            <div>
                                <label for="startDate" class="block text-sm font-medium text-gray-700 mb-1">From</label>
                                <input type="date" id="startDate"
                                    class="block w-full pl-3 pr-10 py-2 text-base border border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                            </div>
                            <div>
                                <label for="endDate" class="block text-sm font-medium text-gray-700 mb-1">To</label>
                                <input type="date" id="endDate"
                                    class="block w-full pl-3 pr-10 py-2 text-base border border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center space-x-4">
                        <div>
                            <label for="transactionType"
                                class="block text-sm font-medium text-gray-700 mb-1">Type</label>
                            <select id="transactionType"
                                class="block w-full pl-3 pr-10 py-2 text-base border border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                                <option value="all">All Types</option>
                                <option value="sale">Sales</option>
                                <option value="refund">Refunds</option>
                                <option value="void">Voids</option>
                            </select>
                        </div>
                        <div>
                            <label for="paymentMethod"
                                class="block text-sm font-medium text-gray-700 mb-1">Payment</label>
                            <select id="paymentMethod"
                                class="block w-full pl-3 pr-10 py-2 text-base border border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                                <option value="all">All Methods</option>
                                <option value="cash">Cash</option>
                                <option value="card">Card</option>
                                <option value="upi">UPI</option>
                                <option value="wallet">Wallet</option>
                            </select>
                        </div>
                        <button id="applyFiltersBtn"
                            class="mt-5 bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-md">
                            Apply
                        </button>
                        <button id="resetFiltersBtn"
                            class="mt-5 bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded-md">
                            Reset
                        </button>
                    </div>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4 mb-6">
                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="px-4 py-5 sm:p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 bg-indigo-500 rounded-md p-3">
                                <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dt class="text-sm font-medium text-gray-500 truncate">
                                    Total Transactions
                                </dt>
                                <dd class="flex items-baseline">
                                    <div class="text-2xl font-semibold text-gray-900" id="totalTransactions">
                                        124
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
                                <dt class="text-sm font-medium text-gray-500 truncate">
                                    Total Sales
                                </dt>
                                <dd class="flex items-baseline">
                                    <div class="text-2xl font-semibold text-gray-900" id="totalSales">
                                        ₹5,678
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
                                <dt class="text-sm font-medium text-gray-500 truncate">
                                    Avg. Order Value
                                </dt>
                                <dd class="flex items-baseline">
                                    <div class="text-2xl font-semibold text-gray-900" id="avgOrderValue">
                                        ₹125.45
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
                                        d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z" />
                                </svg>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dt class="text-sm font-medium text-gray-500 truncate">
                                    Refunds
                                </dt>
                                <dd class="flex items-baseline">
                                    <div class="text-2xl font-semibold text-gray-900" id="totalRefunds">
                                        ₹320
                                    </div>
                                </dd>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Transactions Listing -->
            <div class="bg-white shadow overflow-hidden rounded-lg">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Transaction ID</th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Date & Time</th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Items</th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Amount</th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Payment</th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Status</th>
                                <th scope="col"
                                    class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Actions</th>
                            </tr>
                        </thead>
                        <tbody id="transactionsTableBody" class="bg-white divide-y divide-gray-200">
                            <!-- Transactions will be loaded here -->
                            <tr class="text-center py-4">
                                <td colspan="7" class="px-6 py-4 text-gray-500">Loading transactions...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="bg-white px-6 py-4 border-t border-gray-200 flex items-center justify-between">
                    <div class="flex-1 flex justify-between sm:hidden">
                        <button
                            class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                            Previous
                        </button>
                        <button
                            class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                            Next
                        </button>
                    </div>
                    <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                        <div>
                            <p class="text-sm text-gray-700">
                                Showing <span class="font-medium">1</span> to <span class="font-medium">10</span> of
                                <span class="font-medium">20</span> results
                            </p>
                        </div>
                        <div>
                            <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px"
                                aria-label="Pagination">
                                <button
                                    class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                    <span class="sr-only">Previous</span>
                                    <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                                        fill="currentColor" aria-hidden="true">
                                        <path fill-rule="evenodd"
                                            d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z"
                                            clip-rule="evenodd" />
                                    </svg>
                                </button>
                                <button aria-current="page"
                                    class="z-10 bg-indigo-50 border-indigo-500 text-indigo-600 relative inline-flex items-center px-4 py-2 border text-sm font-medium">
                                    1
                                </button>
                                <button
                                    class="bg-white border-gray-300 text-gray-500 hover:bg-gray-50 relative inline-flex items-center px-4 py-2 border text-sm font-medium">
                                    2
                                </button>
                                <button
                                    class="bg-white border-gray-300 text-gray-500 hover:bg-gray-50 relative inline-flex items-center px-4 py-2 border text-sm font-medium">
                                    3
                                </button>
                                <button
                                    class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                    <span class="sr-only">Next</span>
                                    <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                                        fill="currentColor" aria-hidden="true">
                                        <path fill-rule="evenodd"
                                            d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z"
                                            clip-rule="evenodd" />
                                    </svg>
                                </button>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </body>
    <script>
        // Enhanced dummy data for a restaurant/cafe scenario
        const menuItems = [
            { id: 1, name: "Veg Sandwich", price: 45, category: "food" },
            { id: 2, name: "Cheese Sandwich", price: 55, category: "food" },
            { id: 3, name: "Masala Chai", price: 15, category: "beverage" },
            { id: 4, name: "Coffee", price: 25, category: "beverage" },
            { id: 5, name: "Samosa", price: 20, category: "snack" },
            { id: 6, name: "Gulab Jamun", price: 30, category: "dessert" },
            { id: 7, name: "Pasta", price: 65, category: "food" },
            { id: 8, name: "Burger", price: 50, category: "food" },
            { id: 9, name: "French Fries", price: 40, category: "snack" },
            { id: 10, name: "Cold Drink", price: 30, category: "beverage" },
            { id: 11, name: "Paneer Tikka", price: 80, category: "food" },
            { id: 12, name: "Ice Cream", price: 35, category: "dessert" }
        ];

        const customers = [
            { id: 1, name: "Rahul Sharma", phone: "9876543210", email: "rahul@example.com" },
            { id: 2, name: "Priya Patel", phone: "8765432109", email: "priya@example.com" },
            { id: 3, name: "Amit Singh", phone: "7654321098", email: "amit@example.com" },
            { id: 4, name: "Neha Gupta", phone: "6543210987", email: "neha@example.com" },
            { id: 5, name: "Vikram Joshi", phone: "9432109876", email: "vikram@example.com" },
            { id: 6, name: "Ananya Reddy", phone: "8321098765", email: "ananya@example.com" },
            { id: 7, name: "Karthik Nair", phone: "7210987654", email: "karthik@example.com" }
        ];

        // Generate more realistic transactions with proper dates and amounts
        function generateDummyTransactions() {
            const txns = [];
            const statuses = ['completed', 'completed', 'completed', 'completed', 'completed', 'refunded', 'voided'];
            const paymentMethods = ['cash', 'card', 'upi', 'wallet'];

            // Generate transactions for the last 30 days
            for (let i = 0; i < 50; i++) {
                const daysAgo = Math.floor(Math.random() * 30);
                const txnDate = new Date();
                txnDate.setDate(txnDate.getDate() - daysAgo);

                // Random time between 8AM and 10PM
                const hours = 8 + Math.floor(Math.random() * 14);
                const minutes = Math.floor(Math.random() * 60);
                txnDate.setHours(hours, minutes, 0, 0);

                // Random customer
                const customer = customers[Math.floor(Math.random() * customers.length)];

                // Random items (1-4 items per transaction)
                const itemCount = 1 + Math.floor(Math.random() * 4);
                const items = [];
                for (let j = 0; j < itemCount; j++) {
                    const menuItem = menuItems[Math.floor(Math.random() * menuItems.length)];
                    const quantity = 1 + Math.floor(Math.random() * 3); // 1-3 quantity
                    items.push({
                        id: menuItem.id,
                        name: menuItem.name,
                        price: menuItem.price,
                        quantity: quantity,
                        category: menuItem.category
                    });
                }

                // Random status and payment method
                const status = statuses[Math.floor(Math.random() * statuses.length)];
                const paymentMethod = paymentMethods[Math.floor(Math.random() * paymentMethods.length)];

                txns.push({
                    id: `TXN-${txnDate.getFullYear()}-${(1000 + i).toString().substring(1)}`,
                    date: txnDate.toISOString(),
                    items: items,
                    paymentMethod: paymentMethod,
                    status: status,
                    customer: customer.name,
                    customerId: customer.id,
                    notes: status === 'refunded' ? 'Customer requested refund' :
                        status === 'voided' ? 'Order cancelled before preparation' : ''
                });
            }

            return txns;
        }

        let transactions = generateDummyTransactions();

        // DOM Elements (same as before)
        const dateRange = document.getElementById('dateRange');
        const customDateRange = document.getElementById('customDateRange');
        const startDate = document.getElementById('startDate');
        const endDate = document.getElementById('endDate');
        const transactionType = document.getElementById('transactionType');
        const paymentMethod = document.getElementById('paymentMethod');
        const applyFiltersBtn = document.getElementById('applyFiltersBtn');
        const resetFiltersBtn = document.getElementById('resetFiltersBtn');
        const transactionsTableBody = document.getElementById('transactionsTableBody');
        const exportPDFBtn = document.getElementById('exportPDFBtn');
        const exportExcelBtn = document.getElementById('exportExcelBtn');
        const totalTransactions = document.getElementById('totalTransactions');
        const totalSales = document.getElementById('totalSales');
        const avgOrderValue = document.getElementById('avgOrderValue');
        const totalRefunds = document.getElementById('totalRefunds');

        // Initialize date inputs with today's date
        const today = new Date().toISOString().split('T')[0];
        startDate.value = today;
        endDate.value = today;

        // Event Listeners (same as before)
        dateRange.addEventListener('change', function () {
            if (this.value === 'custom') {
                customDateRange.classList.remove('hidden');
            } else {
                customDateRange.classList.add('hidden');
                // Set dates based on selection
                const now = new Date();
                let start, end = today;

                switch (this.value) {
                    case 'today':
                        start = today;
                        break;
                    case 'yesterday':
                        const yesterday = new Date(now);
                        yesterday.setDate(yesterday.getDate() - 1);
                        start = yesterday.toISOString().split('T')[0];
                        end = start;
                        break;
                    case 'week':
                        const weekStart = new Date(now);
                        weekStart.setDate(weekStart.getDate() - weekStart.getDay());
                        start = weekStart.toISOString().split('T')[0];
                        break;
                    case 'month':
                        start = new Date(now.getFullYear(), now.getMonth(), 1).toISOString().split('T')[0];
                        break;
                    case 'quarter':
                        const quarter = Math.floor(now.getMonth() / 3);
                        start = new Date(now.getFullYear(), quarter * 3, 1).toISOString().split('T')[0];
                        break;
                    case 'year':
                        start = new Date(now.getFullYear(), 0, 1).toISOString().split('T')[0];
                        break;
                }

                if (start) startDate.value = start;
                if (end) endDate.value = end;
            }
        });

        applyFiltersBtn.addEventListener('click', filterTransactions);
        resetFiltersBtn.addEventListener('click', resetFilters);
        exportPDFBtn.addEventListener('click', exportToPDF);
        exportExcelBtn.addEventListener('click', exportToExcel);

        // Filter transactions (enhanced with more filters)
        function filterTransactions() {
            const type = transactionType.value;
            const payment = paymentMethod.value;
            const start = startDate.value;
            const end = endDate.value;

            const filtered = transactions.filter(txn => {
                // Filter by date
                const txnDate = txn.date.split('T')[0];
                if (txnDate < start || txnDate > end) return false;

                // Filter by type
                if (type !== 'all') {
                    if (type === 'sale' && txn.status !== 'completed') return false;
                    if (type === 'refund' && txn.status !== 'refunded') return false;
                    if (type === 'void' && txn.status !== 'voided') return false;
                }

                // Filter by payment method
                if (payment !== 'all' && txn.paymentMethod !== payment) return false;

                return true;
            });

            renderTransactions(filtered);
            updateStats(filtered);
        }

        // Reset filters (same as before)
        function resetFilters() {
            dateRange.value = 'today';
            customDateRange.classList.add('hidden');
            startDate.value = today;
            endDate.value = today;
            transactionType.value = 'all';
            paymentMethod.value = 'all';
            filterTransactions();
        }

        // Render transactions in table (enhanced with more details)
        function renderTransactions(filteredTransactions = null) {
            const txnsToRender = filteredTransactions || transactions;

            if (txnsToRender.length === 0) {
                transactionsTableBody.innerHTML = `
        <tr class="text-center py-4">
          <td colspan="7" class="px-6 py-4 text-gray-500">No transactions found matching your criteria</td>
        </tr>
      `;
                return;
            }

            transactionsTableBody.innerHTML = txnsToRender.map(txn => `
      <tr>
        <td class="px-6 py-4 whitespace-nowrap">
          <div class="text-sm font-medium text-gray-900">${txn.id}</div>
          <div class="text-xs text-gray-500">${txn.customer}</div>
        </td>
        <td class="px-6 py-4 whitespace-nowrap">
          <div class="text-sm text-gray-900">${formatDate(txn.date)}</div>
          <div class="text-xs text-gray-500">${formatTime(txn.date)}</div>
        </td>
        <td class="px-6 py-4">
          <div class="text-sm text-gray-900 max-w-xs truncate">
            ${txn.items.map(item => `${item.quantity}x ${item.name}`).join(', ')}
          </div>
          <div class="text-xs text-gray-500">${txn.items.length} item(s)</div>
        </td>
        <td class="px-6 py-4 whitespace-nowrap">
          <div class="text-sm font-medium text-gray-900">₹${calculateTotal(txn.items).toFixed(2)}</div>
        </td>
        <td class="px-6 py-4 whitespace-nowrap">
          <div class="flex items-center">
            <span class="capitalize ${getPaymentMethodClass(txn.paymentMethod)} px-2 py-1 rounded-full text-xs">
              ${txn.paymentMethod}
            </span>
          </div>
        </td>
        <td class="px-6 py-4 whitespace-nowrap">
          <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${getStatusClass(txn.status)}">
            ${txn.status.charAt(0).toUpperCase() + txn.status.slice(1)}
          </span>
        </td>
        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
          <button onclick="viewTransactionDetails('${txn.id}')" class="text-indigo-600 hover:text-indigo-900 mr-3">View</button>
          ${txn.status === 'completed' ? `<button onclick="refundTransaction('${txn.id}')" class="text-red-600 hover:text-red-900">Refund</button>` : ''}
        </td>
      </tr>
    `).join('');
        }

        // Update stats cards (enhanced with more metrics)
        function updateStats(filteredTransactions) {
            const completedTxns = filteredTransactions.filter(t => t.status === 'completed');
            const refundedTxns = filteredTransactions.filter(t => t.status === 'refunded');
            const voidedTxns = filteredTransactions.filter(t => t.status === 'voided');

            const totalAmount = completedTxns.reduce((sum, txn) => sum + calculateTotal(txn.items), 0);
            const refundAmount = refundedTxns.reduce((sum, txn) => sum + calculateTotal(txn.items), 0);

            totalTransactions.textContent = filteredTransactions.length;
            totalSales.textContent = `₹${totalAmount.toLocaleString('en-IN')}`;
            avgOrderValue.textContent = completedTxns.length > 0 ?
                `₹${(totalAmount / completedTxns.length).toFixed(2)}` : '₹0.00';
            totalRefunds.textContent = `₹${refundAmount.toLocaleString('en-IN')}`;
        }

        // Helper functions (enhanced with more utilities)
        function calculateTotal(items) {
            return items.reduce((sum, item) => sum + (item.price * item.quantity), 0);
        }

        function formatDate(dateTimeStr) {
            const date = new Date(dateTimeStr);
            return date.toLocaleDateString('en-IN', { day: 'numeric', month: 'short', year: 'numeric' });
        }

        function formatTime(dateTimeStr) {
            const date = new Date(dateTimeStr);
            return date.toLocaleTimeString('en-IN', { hour: '2-digit', minute: '2-digit', hour12: true });
        }

        function getStatusClass(status) {
            switch (status) {
                case 'completed': return 'bg-green-100 text-green-800';
                case 'refunded': return 'bg-blue-100 text-blue-800';
                case 'voided': return 'bg-red-100 text-red-800';
                default: return 'bg-gray-100 text-gray-800';
            }
        }

        function getPaymentMethodClass(method) {
            switch (method) {
                case 'cash': return 'bg-yellow-100 text-yellow-800';
                case 'card': return 'bg-purple-100 text-purple-800';
                case 'upi': return 'bg-blue-100 text-blue-800';
                case 'wallet': return 'bg-green-100 text-green-800';
                default: return 'bg-gray-100 text-gray-800';
            }
        }

        // Export functions (enhanced with more realistic simulation)
        function exportToPDF() {
            // Simulate PDF generation delay
            exportPDFBtn.disabled = true;
            exportPDFBtn.innerHTML = `<svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
    </svg> Generating PDF...`;

            setTimeout(() => {
                exportPDFBtn.disabled = false;
                exportPDFBtn.innerHTML = `<svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10"></path>
      </svg> Export PDF`;

                // In a real app, this would download the PDF
                alert('PDF report generated successfully!\n\nIn a real application, this would download a PDF file containing:\n- Transaction list\n- Summary statistics\n- Date range\n- Filter criteria');
            }, 1500);
        }

        function exportToExcel() {
            // Simulate Excel generation delay
            exportExcelBtn.disabled = true;
            exportExcelBtn.innerHTML = `<svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
    </svg> Generating Excel...`;

            setTimeout(() => {
                exportExcelBtn.disabled = false;
                exportExcelBtn.innerHTML = `<svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
      </svg> Export Excel`;

                // In a real app, this would download the Excel file
                alert('Excel report generated successfully!\n\nIn a real application, this would download an Excel file containing:\n- Detailed transaction data\n- Itemized lists\n- Calculated totals\n- Filtered results');
            }, 1500);
        }

        // Transaction actions (enhanced with more details)
        window.viewTransactionDetails = function (id) {
            const txn = transactions.find(t => t.id === id);
            if (txn) {
                const customer = customers.find(c => c.id === txn.customerId) || {};
                const itemsList = txn.items.map(item =>
                    `${item.quantity} x ${item.name} @ ₹${item.price.toFixed(2)} = ₹${(item.price * item.quantity).toFixed(2)}`
                ).join('\n');

                const total = calculateTotal(txn.items);
                const gst = total * 0.05; // Assuming 5% GST
                const grandTotal = total + gst;

                alert(`TRANSACTION DETAILS\n\n` +
                    `ID: ${txn.id}\n` +
                    `Date: ${formatDate(txn.date)} at ${formatTime(txn.date)}\n` +
                    `Status: ${txn.status.charAt(0).toUpperCase() + txn.status.slice(1)}\n\n` +
                    `CUSTOMER INFORMATION\n` +
                    `Name: ${txn.customer}\n` +
                    `Phone: ${customer.phone || 'N/A'}\n` +
                    `Email: ${customer.email || 'N/A'}\n\n` +
                    `ORDER ITEMS\n${itemsList}\n\n` +
                    `SUBTOTAL: ₹${total.toFixed(2)}\n` +
                    `GST (5%): ₹${gst.toFixed(2)}\n` +
                    `TOTAL: ₹${grandTotal.toFixed(2)}\n\n` +
                    `PAYMENT METHOD: ${txn.paymentMethod.toUpperCase()}\n` +
                    `NOTES: ${txn.notes || 'No additional notes'}`);
            }
        };

        window.refundTransaction = function (id) {
            const txn = transactions.find(t => t.id === id);
            if (!txn) return;

            const refundAmount = calculateTotal(txn.items);
            const confirmMsg = `Are you sure you want to refund this transaction?\n\n` +
                `Transaction ID: ${txn.id}\n` +
                `Customer: ${txn.customer}\n` +
                `Amount: ₹${refundAmount.toFixed(2)}\n\n` +
                `Reason for refund:`;

            const reason = prompt(confirmMsg, txn.notes || 'Customer requested refund');
            if (reason !== null) {
                txn.status = 'refunded';
                txn.notes = reason || 'Refund processed';
                filterTransactions();
                alert(`Transaction ${id} has been refunded successfully.`);
            }
        };

        // Initialize
        filterTransactions();
    </script>

    </html>