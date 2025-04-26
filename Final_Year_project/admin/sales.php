<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Dashboard</title>
    <style>
        .chart-container {
            position: relative;
            height: 100%;
            width: 100%;
        }
    </style>
    <?php include "../includes/packages.php" ?>
</head>

<body class="bg-gray-100">
    <?php include "../includes/admin_header.php" ?>
    <!-- Sales Overview Section -->
    <section id="sales-overview" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="flex justify-between items-center mb-8">
            <h2 class="text-2xl font-bold text-gray-900">Sales Overview</h2>
            <div class="flex space-x-3">
                <button id="salesExportPDFBtn"
                    class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-md flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10">
                        </path>
                    </svg>
                    Export PDF
                </button>
            </div>
        </div>

        <!-- Date Range Filter -->
        <div class="bg-white shadow rounded-lg p-4 mb-6">
            <div class="flex flex-col md:flex-row md:items-center gap-4">
                <div class="flex items-center space-x-4">
                    <div>
                        <label for="salesDateRange" class="block text-sm font-medium text-gray-700 mb-1">Date
                            Range</label>
                        <select id="salesDateRange"
                            class="block w-full pl-3 pr-10 py-2 text-base border border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                            <option value="today">Today</option>
                            <option value="yesterday">Yesterday</option>
                            <option value="week">This Week</option>
                            <option value="month" selected>This Month</option>
                            <option value="quarter">This Quarter</option>
                            <option value="year">This Year</option>
                            <option value="custom">Custom Range</option>
                        </select>
                    </div>
                    <div id="salesCustomDateRange" class="hidden flex items-center space-x-2">
                        <div>
                            <label for="salesStartDate"
                                class="block text-sm font-medium text-gray-700 mb-1">From</label>
                            <input type="date" id="salesStartDate"
                                class="block w-full pl-3 pr-10 py-2 text-base border border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                        </div>
                        <div>
                            <label for="salesEndDate" class="block text-sm font-medium text-gray-700 mb-1">To</label>
                            <input type="date" id="salesEndDate"
                                class="block w-full pl-3 pr-10 py-2 text-base border border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                        </div>
                    </div>
                </div>
                <button id="applySalesFiltersBtn"
                    class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-md h-[42px] mt-auto">
                    Apply
                </button>
            </div>
        </div>

        <!-- Key Metrics Cards -->
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4 mb-6">
            <!-- Total Revenue -->
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
                                Total Revenue
                            </dt>
                            <dd class="flex items-baseline">
                                <div class="text-2xl font-semibold text-gray-900" id="totalRevenue">
                                    ₹0
                                </div>
                                <div
                                    class="ml-2 flex items-baseline text-sm font-semibold text-green-600 percentage-change">
                                    +0%
                                </div>
                            </dd>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Total Items Sold -->
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
                                Total Items Sold
                            </dt>
                            <dd class="flex items-baseline">
                                <div class="text-2xl font-semibold text-gray-900" id="totalItemsSold">
                                    0
                                </div>
                                <div
                                    class="ml-2 flex items-baseline text-sm font-semibold text-green-600 percentage-change">
                                    +0%
                                </div>
                            </dd>
                        </div>
                    </div>
                </div>
            </div>

            <!-- New Customers -->
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
                                New Customers
                            </dt>
                            <dd class="flex items-baseline">
                                <div class="text-2xl font-semibold text-gray-900" id="newCustomers">
                                    0
                                </div>
                                <div
                                    class="ml-2 flex items-baseline text-sm font-semibold text-green-600 percentage-change">
                                    +0%
                                </div>
                            </dd>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Repeat Customers -->
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 bg-purple-500 rounded-md p-3">
                            <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dt class="text-sm font-medium text-gray-500 truncate">
                                Repeat Customers
                            </dt>
                            <dd class="flex items-baseline">
                                <div class="text-2xl font-semibold text-gray-900" id="repeatCustomers">
                                    0
                                </div>
                                <div
                                    class="ml-2 flex items-baseline text-sm font-semibold text-green-600 percentage-change">
                                    +0%
                                </div>
                            </dd>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            <!-- Revenue Chart -->
            <div class="bg-white shadow rounded-lg p-4">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-medium text-gray-900">Revenue Trend</h3>
                    <div class="flex space-x-2">
                        <button class="px-3 py-1 text-sm bg-indigo-100 text-indigo-800 rounded-md">Daily</button>
                        <button class="px-3 py-1 text-sm bg-gray-100 text-gray-800 rounded-md">Weekly</button>
                        <button class="px-3 py-1 text-sm bg-gray-100 text-gray-800 rounded-md">Monthly</button>
                    </div>
                </div>
                <div class="h-64">
                    <canvas id="revenueChart"></canvas>
                </div>
            </div>

            <!-- Top Items Chart -->
            <div class="bg-white shadow rounded-lg p-4">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-medium text-gray-900">Top Selling Items</h3>
                    <button class="px-3 py-1 text-sm bg-gray-100 text-gray-800 rounded-md">View All</button>
                </div>
                <div class="h-64">
                    <canvas id="topItemsChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Most Consumed Items Table -->
        <div class="bg-white shadow overflow-hidden rounded-lg mb-6">
            <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                <h3 class="text-lg font-medium text-gray-900">Most Consumed Items</h3>
                <div class="flex space-x-3">
                    <select
                        class="block pl-3 pr-10 py-2 text-base border border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                        <option>All Categories</option>
                        <option>Food</option>
                        <option>Beverage</option>
                        <option>Snack</option>
                        <option>Dessert</option>
                    </select>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Item</th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Category</th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Quantity Sold</th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Total Revenue</th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">%
                                of Sales</th>
                        </tr>
                    </thead>
                    <tbody id="topItemsTableBody" class="bg-white divide-y divide-gray-200">
                        <tr>
                            <td colspan="5" class="px-6 py-4 text-center text-gray-500">Loading items data...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Customer Growth Table -->
        <div class="bg-white shadow overflow-hidden rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Customer Growth</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Date</th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                New Customers</th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Repeat Customers</th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Total Customers</th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Growth Rate</th>
                        </tr>
                    </thead>
                    <tbody id="customerGrowthTableBody" class="bg-white divide-y divide-gray-200">
                        <tr>
                            <td colspan="5" class="px-6 py-4 text-center text-gray-500">Loading customer data...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="bg-white px-6 py-4 border-t border-gray-200 flex items-center justify-between">
                <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                    <div>
                        <p class="text-sm text-gray-700">
                            Showing <span class="font-medium">1</span> to <span class="font-medium">7</span> of <span
                                class="font-medium">30</span> results
                        </p>
                    </div>
                    <div>
                        <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
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

    <script>
        // Enhanced dummy data generator
        function generateDummyData() {
            // Generate daily sales data with realistic patterns
            const generateDailySales = (days = 30) => {
                const data = [];
                let currentTrend = 5000;
                const today = new Date();

                for (let i = days - 1; i >= 0; i--) {
                    const date = new Date(today);
                    date.setDate(date.getDate() - i);
                    const day = date.getDay();

                    // Base sales with trends
                    let revenue = currentTrend * (0.95 + Math.random() * 0.1);

                    // Weekend boost
                    if (day === 0 || day === 6) revenue *= 1.3;

                    // Random events
                    if (Math.random() < 0.1) revenue *= 1.2; // random busy day
                    if (Math.random() < 0.1) revenue *= 0.8; // random slow day

                    data.push({
                        date: date.toISOString().split('T')[0],
                        revenue: Math.round(revenue),
                        transactions: Math.floor(revenue / 250 * (0.8 + Math.random() * 0.4)),
                        newCustomers: Math.floor(2 + Math.random() * 3),
                        repeatCustomers: Math.floor(5 + Math.random() * 6)
                    });

                    // Update trend with slight upward drift
                    currentTrend *= 1.002;
                }
                return data;
            };

            // Top selling items with categories
            const topItems = [
                { id: 1, name: "Masala Chai", category: "beverage", sold: 285, revenue: 4275 },
                { id: 2, name: "Veg Sandwich", category: "food", sold: 192, revenue: 8640 },
                { id: 3, name: "Samosa", category: "snack", sold: 178, revenue: 3560 },
                { id: 4, name: "Coffee", category: "beverage", sold: 156, revenue: 3900 },
                { id: 5, name: "Paneer Tikka", category: "food", sold: 132, revenue: 10560 },
                { id: 6, name: "Gulab Jamun", category: "dessert", sold: 98, revenue: 2940 },
                { id: 7, name: "Burger", category: "food", sold: 87, revenue: 4350 },
                { id: 8, name: "French Fries", category: "snack", sold: 76, revenue: 3040 },
                { id: 9, name: "Cold Drink", category: "beverage", sold: 65, revenue: 1950 },
                { id: 10, name: "Ice Cream", category: "dessert", sold: 54, revenue: 1890 }
            ];

            // Customer growth data (monthly)
            const customerGrowth = Array.from({ length: 12 }, (_, i) => {
                const date = new Date();
                date.setMonth(date.getMonth() - (11 - i));

                const baseNew = 15 + Math.floor(Math.random() * 10);
                const baseRepeat = 30 + Math.floor(Math.random() * 20);
                const growthFactor = 1 + (i * 0.05); // 5% growth each month

                return {
                    date: `${date.getFullYear()}-${(date.getMonth() + 1).toString().padStart(2, '0')}-01`,
                    newCustomers: Math.round(baseNew * growthFactor),
                    repeatCustomers: Math.round(baseRepeat * growthFactor)
                };
            });

            return {
                dailySales: generateDailySales(90), // 3 months of data
                topItems,
                customerGrowth
            };
        }

        // Chart instances
        let revenueChart, topItemsChart;

        // Initialize the dashboard
        document.addEventListener('DOMContentLoaded', function () {
            // Initialize date inputs
            const today = new Date();
            const monthStart = new Date(today.getFullYear(), today.getMonth(), 1);

            document.getElementById('salesStartDate').value = monthStart.toISOString().split('T')[0];
            document.getElementById('salesEndDate').value = today.toISOString().split('T')[0];

            // Initialize charts
            initCharts();

            // Set up event listeners
            setupEventListeners();

            // Load initial data
            updateSalesOverview();
        });

        // Initialize charts
        function initCharts() {
            const chartOptions = {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'top' }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            };

            // Revenue Chart
            const revenueCtx = document.getElementById('revenueChart').getContext('2d');
            revenueChart = new Chart(revenueCtx, {
                type: 'line',
                data: {
                    labels: [],
                    datasets: [{
                        label: 'Daily Revenue',
                        data: [],
                        borderColor: '#4F46E5',
                        backgroundColor: 'rgba(79, 70, 229, 0.1)',
                        tension: 0.3,
                        fill: true
                    }]
                },
                options: chartOptions
            });

            // Top Items Chart
            const topItemsCtx = document.getElementById('topItemsChart').getContext('2d');
            topItemsChart = new Chart(topItemsCtx, {
                type: 'bar',
                data: {
                    labels: [],
                    datasets: [{
                        label: 'Revenue',
                        data: [],
                        backgroundColor: '#10B981'
                    }]
                },
                options: chartOptions
            });
        }

        // Set up event listeners
        function setupEventListeners() {
            // Date range selector
            document.getElementById('salesDateRange').addEventListener('change', function () {
                if (this.value === 'custom') {
                    document.getElementById('salesCustomDateRange').classList.remove('hidden');
                } else {
                    document.getElementById('salesCustomDateRange').classList.add('hidden');
                    setPresetDateRange(this.value);
                }
            });

            // Apply filters button
            document.getElementById('applySalesFiltersBtn').addEventListener('click', updateSalesOverview);

            // Export PDF button
            document.getElementById('salesExportPDFBtn').addEventListener('click', function () {
                alert('PDF export would be generated here in a real implementation');
            });
        }

        // Set preset date ranges
        function setPresetDateRange(range) {
            const today = new Date();
            const startDateInput = document.getElementById('salesStartDate');
            const endDateInput = document.getElementById('salesEndDate');

            switch (range) {
                case 'today':
                    startDateInput.value = endDateInput.value = today.toISOString().split('T')[0];
                    break;
                case 'yesterday':
                    const yesterday = new Date(today);
                    yesterday.setDate(today.getDate() - 1);
                    startDateInput.value = endDateInput.value = yesterday.toISOString().split('T')[0];
                    break;
                case 'week':
                    const startOfWeek = new Date(today);
                    startOfWeek.setDate(today.getDate() - today.getDay());
                    endDateInput.value = today.toISOString().split('T')[0];
                    startDateInput.value = startOfWeek.toISOString().split('T')[0];
                    break;
                case 'month':
                    startDateInput.value = new Date(today.getFullYear(), today.getMonth(), 1)
                        .toISOString().split('T')[0];
                    endDateInput.value = today.toISOString().split('T')[0];
                    break;
                case 'quarter':
                    const quarter = Math.floor(today.getMonth() / 3);
                    startDateInput.value = new Date(today.getFullYear(), quarter * 3, 1)
                        .toISOString().split('T')[0];
                    endDateInput.value = today.toISOString().split('T')[0];
                    break;
                case 'year':
                    startDateInput.value = new Date(today.getFullYear(), 0, 1)
                        .toISOString().split('T')[0];
                    endDateInput.value = today.toISOString().split('T')[0];
                    break;
            }
        }

        // Calculate percentage change
        function calculatePercentageChange(current, previous) {
            if (previous === 0) return '∞%';
            const change = ((current - previous) / previous * 100).toFixed(1);
            return `${change >= 0 ? '+' : ''}${change}%`;
        }

        // Format date for display
        function formatDate(dateStr) {
            const date = new Date(dateStr);
            return date.toLocaleDateString('en-IN', { day: 'numeric', month: 'short' });
        }

        // Format month for display
        function formatMonth(dateStr) {
            const date = new Date(dateStr);
            return date.toLocaleDateString('en-IN', { month: 'short', year: 'numeric' });
        }

        // Update the entire sales overview
        function updateSalesOverview() {
            const dummyData = generateDummyData();
            const startDate = document.getElementById('salesStartDate').value;
            const endDate = document.getElementById('salesEndDate').value;

            // Filter daily sales data for the selected period
            const filteredDailySales = dummyData.dailySales.filter(day =>
                day.date >= startDate && day.date <= endDate
            );

            // Calculate metrics from filtered data
            const totalRevenue = filteredDailySales.reduce((sum, day) => sum + day.revenue, 0);
            const totalItemsSold = filteredDailySales.reduce((sum, day) =>
                sum + Math.round(day.transactions * (2 + Math.random())), 0); // Avg 2-3 items per transaction
            const totalNewCustomers = filteredDailySales.reduce((sum, day) => sum + day.newCustomers, 0);
            const totalRepeatCustomers = filteredDailySales.reduce((sum, day) => sum + day.repeatCustomers, 0);

            // Calculate previous period for comparison
            const prevPeriodData = calculatePreviousPeriodMetrics(dummyData.dailySales, startDate, endDate);

            // Update metric cards
            document.getElementById('totalRevenue').textContent = `₹${totalRevenue.toLocaleString('en-IN')}`;
            document.getElementById('totalItemsSold').textContent = totalItemsSold.toLocaleString('en-IN');
            document.getElementById('newCustomers').textContent = totalNewCustomers;
            document.getElementById('repeatCustomers').textContent = totalRepeatCustomers;

            // Update percentage changes
            const percentageElements = document.querySelectorAll('.percentage-change');
            const percentages = [
                calculatePercentageChange(totalRevenue, prevPeriodData.revenue),
                calculatePercentageChange(totalItemsSold, prevPeriodData.itemsSold),
                calculatePercentageChange(totalNewCustomers, prevPeriodData.newCustomers),
                calculatePercentageChange(totalRepeatCustomers, prevPeriodData.repeatCustomers)
            ];

            percentageElements.forEach((el, index) => {
                el.textContent = percentages[index];
                el.className = 'ml-2 flex items-baseline text-sm font-semibold ' +
                    (percentages[index].startsWith('+') ? 'text-green-600' :
                        percentages[index].startsWith('-') ? 'text-red-600' : 'text-gray-500');
            });

            // Update charts
            updateRevenueChart(filteredDailySales);
            updateTopItemsChart(dummyData.topItems.slice(0, 5));

            // Update tables
            updateTopItemsTable(dummyData.topItems);
            updateCustomerGrowthTable(dummyData.customerGrowth);
        }

        // Calculate metrics for previous period
        function calculatePreviousPeriodMetrics(dailySales, startDate, endDate) {
            const start = new Date(startDate);
            const end = new Date(endDate);
            const diffDays = Math.ceil((end - start) / (1000 * 60 * 60 * 24)) + 1;

            const prevStart = new Date(start);
            prevStart.setDate(start.getDate() - diffDays);

            const prevEnd = new Date(end);
            prevEnd.setDate(end.getDate() - diffDays);

            const prevDailySales = dailySales.filter(day =>
                day.date >= prevStart.toISOString().split('T')[0] &&
                day.date <= prevEnd.toISOString().split('T')[0]
            );

            return {
                revenue: prevDailySales.reduce((sum, day) => sum + day.revenue, 0),
                itemsSold: prevDailySales.reduce((sum, day) => sum + Math.round(day.transactions * (2 + Math.random())), 0),
                newCustomers: prevDailySales.reduce((sum, day) => sum + day.newCustomers, 0),
                repeatCustomers: prevDailySales.reduce((sum, day) => sum + day.repeatCustomers, 0)
            };
        }

        // Update revenue chart
        function updateRevenueChart(dailySales) {
            revenueChart.data.labels = dailySales.map(day => formatDate(day.date));
            revenueChart.data.datasets[0].data = dailySales.map(day => day.revenue);
            revenueChart.update();
        }

        // Update top items chart
        function updateTopItemsChart(topItems) {
            topItemsChart.data.labels = topItems.map(item => item.name);
            topItemsChart.data.datasets[0].data = topItems.map(item => item.revenue);
            topItemsChart.update();
        }

        // Update top items table
        function updateTopItemsTable(topItems) {
            const totalRevenue = topItems.reduce((sum, item) => sum + item.revenue, 0);

            const tableBody = document.getElementById('topItemsTableBody');
            tableBody.innerHTML = topItems.map(item => `
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-medium text-gray-900">${item.name}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-900 capitalize">${item.category}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-900">${item.sold}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-900">₹${item.revenue.toLocaleString('en-IN')}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-900">${((item.revenue / totalRevenue) * 100).toFixed(1)}%</div>
                    </td>
                </tr>
            `).join('');
        }

        // Update customer growth table
        function updateCustomerGrowthTable(customerGrowth) {
            let prevTotal = 0;

            const tableBody = document.getElementById('customerGrowthTableBody');
            tableBody.innerHTML = customerGrowth.map((month, index) => {
                const total = month.newCustomers + month.repeatCustomers;
                let growthRate = 'N/A';

                if (index > 0) {
                    growthRate = calculatePercentageChange(total, prevTotal);
                }
                prevTotal = total;

                return `
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">${formatMonth(month.date)}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">${month.newCustomers}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">${month.repeatCustomers}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">${total}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm ${growthRate.startsWith('+') ? 'text-green-600' :
                        growthRate.startsWith('-') ? 'text-red-600' : 'text-gray-500'}">
                                ${growthRate}
                            </div>
                        </td>
                    </tr>
                `;
            }).join('');
        }
    </script>
</body>

</html>