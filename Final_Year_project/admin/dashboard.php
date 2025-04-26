<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <?php include "../includes/packages.php" ?>
</head>

<body class="bg-gray-100 font-sans">
    <!-- Header -->
    <?php include '../includes/admin_header.php' ?>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Stats Cards -->
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4 mb-8">
            <!-- Total Sales -->
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
                                Total Sales
                            </dt>
                            <dd class="flex items-baseline">
                                <div class="text-2xl font-semibold text-gray-900">
                                    $24,567
                                </div>
                                <div class="ml-2 flex items-baseline text-sm font-semibold text-green-600">
                                    <svg class="self-center flex-shrink-0 h-5 w-5 text-green-500" fill="currentColor"
                                        viewBox="0 0 20 20" aria-hidden="true">
                                        <path fill-rule="evenodd"
                                            d="M5.293 9.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 7.414V15a1 1 0 11-2 0v-7.586l-2.293 2.293a1 1 0 01-1.414 0z"
                                            clip-rule="evenodd" />
                                    </svg>
                                    <span class="sr-only">
                                        Increased by
                                    </span>
                                    12%
                                </div>
                            </dd>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Items Sold -->
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
                                Items Sold
                            </dt>
                            <dd class="flex items-baseline">
                                <div class="text-2xl font-semibold text-gray-900">
                                    1,234
                                </div>
                                <div class="ml-2 flex items-baseline text-sm font-semibold text-green-600">
                                    <svg class="self-center flex-shrink-0 h-5 w-5 text-green-500" fill="currentColor"
                                        viewBox="0 0 20 20" aria-hidden="true">
                                        <path fill-rule="evenodd"
                                            d="M5.293 9.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 7.414V15a1 1 0 11-2 0v-7.586l-2.293 2.293a1 1 0 01-1.414 0z"
                                            clip-rule="evenodd" />
                                    </svg>
                                    <span class="sr-only">
                                        Increased by
                                    </span>
                                    8.2%
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
                                <div class="text-2xl font-semibold text-gray-900">
                                    56
                                </div>
                                <div class="ml-2 flex items-baseline text-sm font-semibold text-green-600">
                                    <svg class="self-center flex-shrink-0 h-5 w-5 text-green-500" fill="currentColor"
                                        viewBox="0 0 20 20" aria-hidden="true">
                                        <path fill-rule="evenodd"
                                            d="M5.293 9.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 7.414V15a1 1 0 11-2 0v-7.586l-2.293 2.293a1 1 0 01-1.414 0z"
                                            clip-rule="evenodd" />
                                    </svg>
                                    <span class="sr-only">
                                        Increased by
                                    </span>
                                    3.4%
                                </div>
                            </dd>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Avg. Order Value -->
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
                            <dt class="text-sm font-medium text-gray-500 truncate">
                                Avg. Order Value
                            </dt>
                            <dd class="flex items-baseline">
                                <div class="text-2xl font-semibold text-gray-900">
                                    $89.34
                                </div>
                                <div class="ml-2 flex items-baseline text-sm font-semibold text-red-600">
                                    <svg class="self-center flex-shrink-0 h-5 w-5 text-red-500" fill="currentColor"
                                        viewBox="0 0 20 20" aria-hidden="true">
                                        <path fill-rule="evenodd"
                                            d="M14.707 10.293a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 111.414-1.414L9 12.586V5a1 1 0 012 0v7.586l2.293-2.293a1 1 0 011.414 0z"
                                            clip-rule="evenodd" />
                                    </svg>
                                    <span class="sr-only">
                                        Decreased by
                                    </span>
                                    2.6%
                                </div>
                            </dd>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Section -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
            <!-- Sales Chart -->
            <div class="bg-white shadow rounded-lg p-6">
                <h2 class="text-lg font-medium text-gray-900 mb-4">Sales Overview</h2>
                <div class="h-80">
                    <canvas id="salesChart"></canvas>
                </div>
            </div>

            <!-- Top Products Chart -->
            <div class="bg-white shadow rounded-lg p-6">
                <h2 class="text-lg font-medium text-gray-900 mb-4">Top Selling Products</h2>
                <div class="h-80">
                    <canvas id="productsChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Recent Orders & Activity Feed -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Recent Orders -->
            <div class="bg-white shadow rounded-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-900">Recent Orders</h2>
                </div>
                <div class="divide-y divide-gray-200">
                    <div class="px-6 py-4">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <div class="ml-4">
                                    <div class="text-sm font-medium text-gray-900">#ORD-12345</div>
                                    <div class="text-sm text-gray-500">3 items • $124.99</div>
                                </div>
                            </div>
                            <div class="text-sm text-gray-500">2 hours ago</div>
                        </div>
                    </div>
                    <div class="px-6 py-4">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <div class="ml-4">
                                    <div class="text-sm font-medium text-gray-900">#ORD-12344</div>
                                    <div class="text-sm text-gray-500">1 item • $59.99</div>
                                </div>
                            </div>
                            <div class="text-sm text-gray-500">4 hours ago</div>
                        </div>
                    </div>
                    <div class="px-6 py-4">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <div class="ml-4">
                                    <div class="text-sm font-medium text-gray-900">#ORD-12343</div>
                                    <div class="text-sm text-gray-500">5 items • $289.50</div>
                                </div>
                            </div>
                            <div class="text-sm text-gray-500">6 hours ago</div>
                        </div>
                    </div>
                    <div class="px-6 py-4">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <div class="ml-4">
                                    <div class="text-sm font-medium text-gray-900">#ORD-12342</div>
                                    <div class="text-sm text-gray-500">2 items • $78.00</div>
                                </div>
                            </div>
                            <div class="text-sm text-gray-500">Yesterday</div>
                        </div>
                    </div>
                    <div class="px-6 py-4">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <div class="ml-4">
                                    <div class="text-sm font-medium text-gray-900">#ORD-12341</div>
                                    <div class="text-sm text-gray-500">1 item • $45.99</div>
                                </div>
                            </div>
                            <div class="text-sm text-gray-500">Yesterday</div>
                        </div>
                    </div>
                </div>
                <div class="px-6 py-4 border-t border-gray-200">
                    <a href="#" class="text-sm font-medium text-indigo-600 hover:text-indigo-500">View all orders</a>
                </div>
            </div>

            <!-- Activity Feed -->
            <div class="bg-white shadow rounded-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-900">Activity Feed</h2>
                </div>
                <div class="divide-y divide-gray-200">
                    <div class="px-6 py-4">
                        <div class="flex space-x-3">
                            <div class="flex-shrink-0">
                                <img class="h-8 w-8 rounded-full"
                                    src="https://images.unsplash.com/photo-1519244703995-f4e0f30006d5?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=facearea&facepad=2&w=256&h=256&q=80"
                                    alt="">
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm text-gray-800"><span class="font-medium text-gray-900">Michael
                                        Foster</span> placed a new order for $124.99</p>
                                <p class="text-sm text-gray-500">2 hours ago</p>
                            </div>
                        </div>
                    </div>
                    <div class="px-6 py-4">
                        <div class="flex space-x-3">
                            <div class="flex-shrink-0">
                                <img class="h-8 w-8 rounded-full"
                                    src="https://images.unsplash.com/photo-1506794778202-cad84cf45f1d?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=facearea&facepad=2&w=256&h=256&q=80"
                                    alt="">
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm text-gray-800"><span class="font-medium text-gray-900">Dries
                                        Vincent</span> completed payment for order #ORD-12344</p>
                                <p class="text-sm text-gray-500">4 hours ago</p>
                            </div>
                        </div>
                    </div>
                    <div class="px-6 py-4">
                        <div class="flex space-x-3">
                            <div class="flex-shrink-0">
                                <img class="h-8 w-8 rounded-full"
                                    src="https://images.unsplash.com/photo-1517841905240-472988babdf9?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=facearea&facepad=2&w=256&h=256&q=80"
                                    alt="">
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm text-gray-800"><span class="font-medium text-gray-900">Lindsay
                                        Walton</span> created a new account</p>
                                <p class="text-sm text-gray-500">6 hours ago</p>
                            </div>
                        </div>
                    </div>
                    <div class="px-6 py-4">
                        <div class="flex space-x-3">
                            <div class="flex-shrink-0">
                                <img class="h-8 w-8 rounded-full"
                                    src="https://images.unsplash.com/photo-1531427186611-ecfd6d936c79?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=facearea&facepad=2&w=256&h=256&q=80"
                                    alt="">
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm text-gray-800"><span class="font-medium text-gray-900">Courtney
                                        Henry</span> requested a refund for order #ORD-12340</p>
                                <p class="text-sm text-gray-500">Yesterday</p>
                            </div>
                        </div>
                    </div>
                    <div class="px-6 py-4">
                        <div class="flex space-x-3">
                            <div class="flex-shrink-0">
                                <img class="h-8 w-8 rounded-full"
                                    src="https://images.unsplash.com/photo-1438761681033-6461ffad8d80?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=facearea&facepad=2&w=256&h=256&q=80"
                                    alt="">
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm text-gray-800"><span class="font-medium text-gray-900">Leslie
                                        Alexander</span> left a review for "Premium Headphones"</p>
                                <p class="text-sm text-gray-500">Yesterday</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="px-6 py-4 border-t border-gray-200">
                    <a href="#" class="text-sm font-medium text-indigo-600 hover:text-indigo-500">View all activity</a>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-white border-t border-gray-200 mt-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <p class="text-center text-sm text-gray-500">&copy; 2023 Your Company. All rights reserved.</p>
        </div>
    </footer>

    <script>
        // Sales Chart
        const salesCtx = document.getElementById('salesChart').getContext('2d');
        const salesChart = new Chart(salesCtx, {
            type: 'line',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                datasets: [{
                    label: 'Total Sales',
                    data: [15000, 18000, 17000, 19000, 21000, 23000, 24000, 22000, 25000, 27000, 28000, 30000],
                    backgroundColor: 'rgba(79, 70, 229, 0.05)',
                    borderColor: 'rgba(79, 70, 229, 1)',
                    borderWidth: 2,
                    tension: 0.1,
                    fill: true
                }, {
                    label: 'Items Sold',
                    data: [800, 950, 900, 1000, 1100, 1200, 1250, 1150, 1300, 1400, 1450, 1500],
                    backgroundColor: 'rgba(16, 185, 129, 0.05)',
                    borderColor: 'rgba(16, 185, 129, 1)',
                    borderWidth: 2,
                    tension: 0.1,
                    fill: true,
                    yAxisID: 'y1'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Sales ($)'
                        }
                    },
                    y1: {
                        beginAtZero: true,
                        position: 'right',
                        grid: {
                            drawOnChartArea: false,
                        },
                        title: {
                            display: true,
                            text: 'Items Sold'
                        }
                    }
                }
            }
        });

        // Products Chart
        const productsCtx = document.getElementById('productsChart').getContext('2d');
        const productsChart = new Chart(productsCtx, {
            type: 'bar',
            data: {
                labels: ['Premium Headphones', 'Wireless Earbuds', 'Smart Watch', 'Bluetooth Speaker', 'Fitness Tracker'],
                datasets: [{
                    label: 'Units Sold',
                    data: [450, 380, 290, 210, 180],
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
                        'rgba(239, 68, 68, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            afterLabel: function (context) {
                                const dataset = context.dataset;
                                const total = dataset.data.reduce((previousValue, currentValue) => previousValue + currentValue);
                                const currentValue = dataset.data[context.dataIndex];
                                const percentage = Math.floor((currentValue / total) * 100);
                                return `Market share: ${percentage}%`;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Units Sold'
                        }
                    }
                }
            }
        });
    </script>
</body>

</html>