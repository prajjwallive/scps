// admin/js/sales.js - Sales Dashboard Frontend JavaScript Logic

// Helper function to show confirmation message (reused pattern)
const adminConfirmation = document.getElementById('admin-confirmation');
function showAdminConfirmation(message = 'Success!', color = 'green') {
    if (!adminConfirmation) return;
    adminConfirmation.textContent = message;
    // Removed dark mode classes from here, assuming a consistent light theme
    adminConfirmation.className = `fixed top-5 left-1/2 -translate-x-1/2 text-white p-4 rounded-md shadow-lg z-50 opacity-0 hidden transition-all duration-500 ease-in-out transform bg-${color}-500 block`; // Reset classes
    setTimeout(() => { adminConfirmation.classList.add('opacity-100'); }, 10);
    setTimeout(() => {
        adminConfirmation.classList.remove('opacity-100');
        setTimeout(() => { adminConfirmation.classList.remove('block'); adminConfirmation.classList.add('hidden'); }, 500);
    }, 3000); // Show for 3 seconds
}

// Removed isDarkMode() function as we are now targeting a light theme directly
// function isDarkMode() {
//     return document.documentElement.classList.contains('dark');
// }

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

 // Helper to format date and time (for tables)
 function formatDateTime(dateTimeStr) {
      if (!dateTimeStr) return 'N/A';
     try {
         const date = new Date(dateTimeStr);
         if (isNaN(date.getTime())) { return 'Invalid Date'; }
         const options = { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit', hour12: true };
         return date.toLocaleDateString('en-IN', options);
     } catch (e) {
         console.error("Error formatting date/time:", dateTimeStr, e);
         return 'Invalid Date';
     }
 }

 // Helper to format date only (for charts period)
 function formatDateOnly(dateStr) {
     if (!dateStr) return 'N/A';
     try {
         const date = new Date(dateStr);
          if (isNaN(date.getTime())) { return 'Invalid Date'; }
         const options = { year: 'numeric', month: 'short', day: 'numeric' };
         return date.toLocaleDateString('en-IN', options);
     } catch (e) {
         console.error("Error formatting date:", dateStr, e);
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


// Chart Instances
let revenueItemsChart, topProductsChart;

// Variables to hold fetched data for charts and tables
let salesOverviewData = []; // For Revenue & Items Sold Trend Chart
let topProductsChartData = []; // For Top Selling Products Chart
let topProductsTableData = []; // For Top Selling Items Table
let customerGrowthData = []; // For Customer Activity Table
let salesSummaryData = { total_revenue: 0, total_items_sold: 0, total_transactions: 0, total_customers: 0 }; // For Summary Cards

// Pagination state for Customer Growth Table
let customerGrowthCurrentPage = 1;
let customerGrowthItemsPerPage = 10; // Default items per page


// --- Date Range Handling ---
const salesDateRangeSelect = document.getElementById('salesDateRange');
const salesCategoryFilterSelect = document.getElementById('salesCategoryFilter'); // For charts and summary
const topItemsTableCategoryFilterSelect = document.getElementById('topItemsTableCategoryFilter'); // For table
const customDateRangeContainer = document.getElementById('customDateRangeContainer');
const customStartDateInput = document.getElementById('customStartDate');
const customEndDateInput = document.getElementById('customEndDate');
const applyCustomRangeBtn = document.getElementById('applyCustomRangeBtn');


// Set initial date range dropdown value based on default fetch (This Month)
function setInitialDateRange() {
     if (salesDateRangeSelect) {
         salesDateRangeSelect.value = 'this_month';
     }
}

// Get selected date range based on dropdown value
function getSelectedDateRange() {
    const today = new Date();
    const options = { timeZone: 'Asia/Kathmandu' }; // Ensure correct timezone
    let startDate = null;
    let endDate = today.toLocaleDateString('en-CA', options).replace(/\//g, '-'); // Default end date is today

    const selectedRange = salesDateRangeSelect ? salesDateRangeSelect.value : 'this_month';

    switch (selectedRange) {
        case 'today':
            startDate = today.toLocaleDateString('en-CA', options).replace(/\//g, '-');
            break;
        case 'this_week':
             const firstDayOfWeek = new Date(today);
             firstDayOfWeek.setDate(today.getDate() - today.getDay()); // Sunday as first day
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
            startDate = null; // Indicate no start date filter
            endDate = null; // Indicate no end date filter
            break;
        case 'custom': // Handle custom range
            startDate = customStartDateInput.value;
            endDate = customEndDateInput.value;
            // Basic validation for custom dates
            if (!startDate || !endDate || new Date(startDate) > new Date(endDate)) {
                showAdminConfirmation('Please select valid start and end dates for custom range.', 'orange');
                // Fallback to default if custom dates are invalid
                const defaultFirstDayOfMonth = new Date(today.getFullYear(), today.getMonth(), 1);
                startDate = defaultFirstDayOfMonth.toLocaleDateString('en-CA', options).replace(/\//g, '-');
                endDate = today.toLocaleDateString('en-CA', options).replace(/\//g, '-');
                salesDateRangeSelect.value = 'this_month'; // Reset dropdown
                customDateRangeContainer.classList.add('hidden'); // Hide custom inputs
            }
            break;
        default:
            // Default to this month if value is unexpected
             const defaultFirstDayOfMonth = new Date(today.getFullYear(), today.getMonth(), 1);
             startDate = defaultFirstDayOfMonth.toLocaleDateString('en-CA', options).replace(/\//g, '-');
             endDate = today.toLocaleDateString('en-CA', options).replace(/\//g, '-');
            break;
    }
    return { startDate, endDate };
}

 // Get selected category filter value
 function getSelectedCategory(filterElementId) {
      const selectElement = document.getElementById(filterElementId);
      return selectElement ? selectElement.value : 'all'; // Default to 'all'
 }

if (salesDateRangeSelect) {
    // FIX: Corrected typo from salesDateSelect to salesDateRangeSelect
    salesDateRangeSelect.addEventListener('change', function() {
        if (this.value === 'custom') {
            customDateRangeContainer.classList.remove('hidden');
            
            // Set default dates for custom range (last 7 days) if inputs are empty
            if (!customStartDateInput.value || !customEndDateInput.value) {
                const endDate = new Date();
                const startDate = new Date();
                startDate.setDate(endDate.getDate() - 7);
                
                customStartDateInput.valueAsDate = startDate;
                customEndDateInput.valueAsDate = endDate;
            }
        } else {
            customDateRangeContainer.classList.add('hidden');
            console.log('Date range changed to predefined: ' + this.value + '. Calling fetchAllSalesData().'); // DEBUG
            fetchAllSalesData(); // Fetch data immediately for predefined ranges
        }
    });
}

// Event listener for the "Apply Custom Range" button
if (applyCustomRangeBtn) {
    applyCustomRangeBtn.addEventListener('click', function() {
        console.log('Apply Custom Range button clicked. Calling fetchAllSalesData().'); // DEBUG
        fetchAllSalesData();
    });
}

// Initialize charts (call this AFTER data is loaded and stored)
 function initCharts() { // Removed parameters, uses global/scoped data
     // Destroy existing charts if they exist to prevent duplicates
    if (revenueItemsChart) revenueItemsChart.destroy();
    if (topProductsChart) topProductsChart.destroy();

     const chartBaseOptions = {
         responsive: true,
         maintainAspectRatio: false,
          plugins: {
              legend: {
                  position: 'top',
                   labels: {
                        color: '#4a5568' // Changed to gray-700 for light theme
                    }
               },
               tooltip: { // Tooltip colors for light theme
                   backgroundColor: '#fff',
                   titleColor: '#4a5568', // Changed to gray-700
                   bodyColor: '#4a5568', // Changed to gray-700
                   borderColor: '#e5e7eb',
                   borderWidth: 1
               }
          },
           scales: { // Base scale options for light theme
               y: {
                   beginAtZero: true,
                    ticks: { color: '#4a5568' }, // Changed to gray-700
                    grid: { color: '#e2e8f0' } // Changed to gray-200 for light theme
               },
                x: {
                   ticks: { color: '#4a5568' }, // Changed to gray-700
                    grid: { color: '#e2e8f0' } // Changed to gray-200 for light theme
                }
           }
     };


    // Revenue & Items Sold Trend Chart
    const revenueItemsCtx = document.getElementById('revenueItemsChart');
    if (revenueItemsCtx) {
         let chartContainer = revenueItemsCtx.parentElement;
         // Remove any existing messages before drawing chart
         if(chartContainer) chartContainer.querySelectorAll('.chart-message').forEach(msgEl => msgEl.remove());

         if (salesOverviewData && salesOverviewData.length > 0) {
              if (revenueItemsCtx) revenueItemsCtx.style.display = 'block'; // Show canvas

              const revenueItemsChartOptions = JSON.parse(JSON.stringify(chartBaseOptions));
              revenueItemsChartOptions.scales.y.title = { display: true, text: 'Sales (₹)', color: '#4a5568' }; // Changed to gray-700
              revenueItemsChartOptions.scales.y1 = { // Secondary Y-axis for Items Sold
                  beginAtZero: true,
                  position: 'right',
                  grid: { drawOnChartArea: false, color: '#e2e8f0' }, // Changed to gray-200
                  title: { display: true, text: 'Items Sold', color: '#4a5568' } // Changed to gray-700
              };

             // --- Chart Optimization: Styling for Revenue & Items Sold Trend ---
             revenueItemsChart = new Chart(revenueItemsCtx, {
                 type: 'line',
                 data: {
                     labels: salesOverviewData.map(item => `${item.period}`), // Use stored data
                     datasets: [{
                         label: 'Total Sales',
                         data: salesOverviewData.map(item => item.revenue ?? 0), // Use stored data
                          backgroundColor: 'rgba(79, 70, 229, 0.2)', // Light theme indigo fill
                          borderColor: 'rgba(79, 70, 229, 1)', // Light theme indigo border
                         borderWidth: 2, // Thicker line
                         tension: 0.3, // Smoother curve
                         fill: true,
                         pointRadius: 5, // Add points
                         pointBackgroundColor: 'rgba(79, 70, 229, 1)', // Light theme indigo
                         pointBorderColor: '#ffffff', // White border for points
                         pointHoverRadius: 7,
                          yAxisID: 'y'
                     }, {
                         label: 'Items Sold',
                         data: salesOverviewData.map(item => item.items_sold ?? 0), // Use stored data
                          backgroundColor: 'rgba(16, 185, 129, 0.2)', // Light theme green fill
                          borderColor: 'rgba(16, 185, 129, 1)', // Light theme green border
                         borderWidth: 2, // Thicker line
                         tension: 0.3, // Smoother curve
                         fill: true,
                          pointRadius: 5, // Add points
                         pointBackgroundColor: 'rgba(16, 185, 129, 1)', // Light theme green
                         pointBorderColor: '#ffffff', // White border for points
                         pointHoverRadius: 7,
                         yAxisID: 'y1'
                     }]
                 },
                 options: revenueItemsChartOptions
             });
             // --- End Optimization ---

         } else {
             // No data found, display message
              if (revenueItemsCtx) revenueItemsCtx.style.display = 'none'; // Hide canvas
              const noDataMessage = document.createElement('p');
              noDataMessage.textContent = 'No revenue or items sold data found for this period.';
              noDataMessage.classList.add('chart-message'); // Removed text-gray-700, handled by CSS
              if(chartContainer) chartContainer.appendChild(noDataMessage);
         }
    }


    // Top Selling Products Chart
    const topProductsCtx = document.getElementById('topProductsChart');
     if (topProductsCtx) {
         let chartContainer = topProductsCtx.parentElement;
          // Remove any existing messages before drawing chart
         if(chartContainer) chartContainer.querySelectorAll('.chart-message').forEach(msgEl => msgEl.remove());


          if (topProductsChartData && topProductsChartData.length > 0) {
               if (topProductsCtx) topProductsCtx.style.display = 'block'; // Show canvas

               const topProductsChartOptions = JSON.parse(JSON.stringify(chartBaseOptions));
               topProductsChartOptions.plugins.legend.display = false; // Hide legend for bar chart
               topProductsChartOptions.scales.y.title = { display: true, text: 'Revenue (₹)', color: '#4a5568' }; // Changed to gray-700

               // --- Chart Optimization: Styling for Top Selling Products Chart ---
               topProductsChartOptions.scales.x = { // Apply to X-axis for bar charts
                    ticks: { color: '#4a5568' }, // Changed to gray-700
                    grid: { color: '#e2e8f0' }, // Changed to gray-200
                    categoryPercentage: 0.6, // Adjusts the width of the bars relative to the space available
                    barPercentage: 0.8 // Adjusts the width of the bars relative to the categoryPercentage
               };

               topProductsChart = new Chart(topProductsCtx, {
                   type: 'bar',
                   data: {
                       labels: topProductsChartData.map(item => htmlspecialchars(item.name)), // Use stored data
                       datasets: [{
                           label: 'Revenue',
                           data: topProductsChartData.map(item => item.total_revenue ?? 0), // Use stored data
                           backgroundColor: [
                                'rgba(79, 70, 229, 0.8)', // Light theme colors
                                'rgba(16, 185, 129, 0.8)',
                                'rgba(59, 130, 246, 0.8)',
                                'rgba(245, 158, 11, 0.8)',
                                'rgba(239, 68, 68, 0.8)'
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
                   options: topProductsChartOptions
               });
               // --- End Optimization ---
           } else {
               // No data found, display message
                if (topProductsCtx) topProductsCtx.style.display = 'none'; // Hide canvas
               const noDataMessage = document.createElement('p');
               noDataMessage.textContent = 'No top selling items found for this period/category.';
               noDataMessage.classList.add('chart-message'); // Removed text-gray-700, handled by CSS
               if(chartContainer) chartContainer.appendChild(noDataMessage);
           }
         }
}

// --- Fetch Data Functions (Using APIs) ---

 // Fetch Sales Summary Cards
 async function fetchSalesSummary(startDate, endDate) {
     const queryParams = new URLSearchParams();
     if (startDate) queryParams.append('startDate', startDate);
     if (endDate) queryParams.append('endDate', endDate);
     // Add category filter to sales summary fetch
     const category = getSelectedCategory('salesCategoryFilter');
     if (category !== 'all') queryParams.append('category', category);


     try {
         const response = await fetch(`./api/fetch_sales_summary.php?${queryParams.toString()}`);
         const data = await response.json();

         if (response.ok && data.success) {
             salesSummaryData = data.summary || { total_revenue: 0, total_items_sold: 0, total_transactions: 0, total_customers: 0 };
             updateSalesSummaryCards(); // Update the cards with fetched data
             showAdminConfirmation(data.message || 'Sales summary fetched.', 'green');
         } else {
             console.error('fetchSalesSummary: Backend reported error or no data:', data.message);
              salesSummaryData = { total_revenue: 0, total_items_sold: 0, total_transactions: 0, total_customers: 0 }; // Clear data on error
             updateSalesSummaryCards(); // Update with zeros on error
             showAdminConfirmation(data.message || 'Failed to fetch sales summary.', 'red');
         }
     } catch (error) {
         console.error('fetchSalesSummary: Fetch Error:', error);
          salesSummaryData = { total_revenue: 0, total_items_sold: 0, total_transactions: 0, total_customers: 0 }; // Clear data on error
         updateSalesSummaryCards(); // Update with zeros on error
         showAdminConfirmation('Error fetching sales summary!', 'red');
     }
 }

 // Update Sales Summary Cards (using stored data)
 function updateSalesSummaryCards() {
      const totalRevenueCardEl = document.getElementById('totalRevenueCard');
      const totalItemsSoldCardEl = document.getElementById('totalItemsSoldCard');
      const totalTransactionsCardEl = document.getElementById('totalTransactionsCard');
      const totalCustomersCardEl = document.getElementById('totalCustomersCard');

      if (totalRevenueCardEl) totalRevenueCardEl.textContent = `₹${formatCurrency(salesSummaryData.total_revenue)}`;
      if (totalItemsSoldCardEl) totalItemsSoldCardEl.textContent = formatNumber(salesSummaryData.total_items_sold);
      if (totalTransactionsCardEl) totalTransactionsCardEl.textContent = formatNumber(salesSummaryData.total_transactions);
      if (totalCustomersCardEl) totalCustomersCardEl.textContent = formatNumber(salesSummaryData.total_customers);
 }


 // Fetch Revenue Trend (for Chart)
 async function fetchRevenueTrend(startDate, endDate, granularity = 'monthly') {
      const chartContainer = document.getElementById('revenueItemsChart').parentElement;
      if (!chartContainer) { console.error('Revenue Items Chart container not found!'); return; }

      let chartCanvas = document.getElementById('revenueItemsChart');
      if (!chartCanvas) {
          chartContainer.innerHTML = '<canvas id="revenueItemsChart"></canvas>';
          chartCanvas = document.getElementById('revenueItemsChart');
      }

      chartContainer.querySelectorAll('.chart-message').forEach(msgEl => msgEl.remove());
      const loadingMessage = document.createElement('p');
      loadingMessage.textContent = 'Loading Sales Trend...';
      loadingMessage.classList.add('chart-message'); // Removed text-gray-700, handled by CSS
      chartContainer.appendChild(loadingMessage);
      if(chartCanvas) chartCanvas.style.display = 'none';


     const queryParams = new URLSearchParams();
     if (startDate) queryParams.append('startDate', startDate);
     if (endDate) queryParams.append('endDate', endDate);
     queryParams.append('granularity', granularity);
     // Add category filter to revenue trend fetch
     const category = getSelectedCategory('salesCategoryFilter');
     if (category !== 'all') queryParams.append('category', category);


     try {
         const response = await fetch(`./api/fetch_revenue_trend.php?${queryParams.toString()}`);
         if (loadingMessage) loadingMessage.remove();

         if (!response.ok) {
               console.error('fetchRevenueTrend: HTTP Error:', response.status, response.statusText);
               showAdminConfirmation(`HTTP Error fetching revenue trend: ${response.status} ${response.statusText}`, 'red');
                salesOverviewData = []; // Clear data on error
                initCharts(); // Re-initialize charts with empty data
                if (chartContainer) {
                    let canvas = document.getElementById('revenueItemsChart');
                    if(canvas) canvas.style.display = 'none';
                     const errorMessage = document.createElement('p');
                     errorMessage.textContent = `Network Error loading trend data: ${response.status} ${response.statusText}`;
                     errorMessage.classList.add('chart-message', 'error');
                     chartContainer.appendChild(errorMessage);
                }
                return;
          }

          const data = await response.json();

         if (data.success && data.trend && data.trend.length > 0) {
               salesOverviewData = data.trend; // Store data
               initCharts(); // Re-initialize charts with new data
               showAdminConfirmation(data.message || 'Revenue trend data fetched successfully.', 'green');
          } else {
              console.warn('fetchRevenueTrend: Backend reported no revenue trend data:', data.message);
               salesOverviewData = []; // Store empty data
               initCharts(); // Re-initialize charts with empty data
               showAdminConfirmation(data.message || 'No revenue trend data found for this period.', 'orange');
          }
     } catch (error) {
         console.error('fetchRevenueTrend: Fetch Error:', error);
         showAdminConfirmation('Error fetching revenue trend!', 'red');
          salesOverviewData = []; // Clear data on error
          initCharts(); // Re-initialize charts with empty data
           if (chartContainer) {
               let canvas = document.getElementById('revenueItemsChart');
               if(canvas) canvas.style.display = 'none';
                const errorMessage = document.createElement('p');
                errorMessage.textContent = 'Network Error loading trend data.';
                errorMessage.classList.add('chart-message', 'error');
                chartContainer.appendChild(errorMessage);
           }
     }
 }


 // Fetch Top Selling Items (for Chart AND Table)
  async function fetchTopSellingItems(startDate, endDate, category = 'all') {
       console.log('fetchTopSellingItems called with:', { startDate, endDate, category }); // DEBUG: Log parameters
       const chartContainer = document.getElementById('topProductsChart').parentElement;
       const tableBody = document.getElementById('topItemsTableBody'); // Get the table body element

        if (!chartContainer || !tableBody) {
            console.error('Top Items Chart container or Table Body not found!');
            return;
        }

        // Handle loading states for both chart and table
        let chartCanvas = document.getElementById('topProductsChart');
        if (!chartCanvas) {
             chartContainer.innerHTML = '<canvas id="topProductsChart"></canvas>';
             chartCanvas = document.getElementById('topProductsChart');
        }
         chartContainer.querySelectorAll('.chart-message').forEach(msgEl => msgEl.remove()); // Corrected msgL to msgEl
         const chartLoadingMessage = document.createElement('p');
         chartLoadingMessage.textContent = 'Loading Top Products Chart...';
         chartLoadingMessage.classList.add('chart-message'); // Removed text-gray-700, handled by CSS
         chartContainer.appendChild(chartLoadingMessage);
         if(chartCanvas) chartCanvas.style.display = 'none';

         // Table loading state
         tableBody.innerHTML = `<tr><td colspan="5" class="px-6 py-4 whitespace-nowrap text-sm text-center table-message">Loading top selling items...</td></tr>`; // Removed text-gray-700, handled by CSS


      const queryParams = new URLSearchParams();
      if (startDate) queryParams.append('startDate', startDate);
      if (endDate) queryParams.append('endDate', endDate);
      if (category) queryParams.append('category', category);

      const apiUrl = `./api/fetch_top_selling_items.php?${queryParams.toString()}`;
      console.log('Fetching Top Selling Items from URL:', apiUrl); // DEBUG: Log the full API URL


      try {
          const response = await fetch(apiUrl); // Use the constructed URL
          // DEBUG: Log the raw response status and text
          console.log('Top Selling Items API Response Status:', response.status, response.statusText);
          // FIX: Corrected variable name from 'loadingMessage' to 'chartLoadingMessage'
          if (chartLoadingMessage) chartLoadingMessage.remove();


          if (!response.ok) {
               console.error('fetchTopSellingItems: HTTP Error:', response.status, response.statusText);
               showAdminConfirmation(`HTTP Error fetching top selling items: ${response.status} ${response.statusText}`, 'red');
               topProductsChartData = []; // Clear data on error
               topProductsTableData = []; // Clear data on error
               initCharts(); // Re-initialize charts with empty data
               updateTopItemsTable([]); // Update table with empty data
                // Add error message to chart container
                if (chartContainer) {
                    let canvas = document.getElementById('topProductsChart');
                    if(canvas) canvas.style.display = 'none';
                     const errorMessage = document.createElement('p');
                     errorMessage.textContent = `Network Error loading top items: ${response.status} ${response.statusText}`;
                     errorMessage.classList.add('chart-message', 'error');
                     chartContainer.appendChild(errorMessage);
                }
                // Add error message to table
                tableBody.innerHTML = `<tr><td colspan="5" class="px-6 py-4 whitespace-nowrap text-sm text-red-600 text-center table-message">Error loading top selling items.</td></tr>`;

                return;
          }

          const data = await response.json();
          // DEBUG: Log the parsed JSON data
          console.log('Top Selling Items API Response Data:', data);


          if (data.success && data.top_items && data.top_items.length > 0) {
               console.log('fetchTopSellingItems: Data found. Updating UI.');
               topProductsChartData = data.top_items; // Store data for chart
               topProductsTableData = data.top_items; // Store data for table
               initCharts(); // Re-initialize charts with new data
               updateTopItemsTable(topProductsTableData); // Update the table
               showAdminConfirmation(data.message || 'Top selling items data fetched successfully.', 'green');

          } else {
              console.warn('fetchTopSellingItems: Backend reported no top selling items for this filter:', data.message);
              topProductsChartData = []; // Store empty data for chart
              topProductsTableData = []; // Store empty data for table
              initCharts(); // Re-initialize charts with empty data
              updateTopItemsTable([]); // Update the table with empty data
              showAdminConfirmation(data.message || 'No top selling items found for this period/category.', 'orange');
          }
       } catch (error) {
          console.error('fetchTopSellingItems: Fetch Error:', error);
          showAdminConfirmation('Error fetching top selling items!', 'red');
          topProductsChartData = []; // Clear data on error
          topProductsTableData = []; // Clear data on error
          initCharts(); // Re-initialize charts with empty data
          updateTopItemsTable([]); // Update table with empty data
           // Add error message to chart container
           if (chartContainer) {
               let canvas = document.getElementById('topProductsChart');
               if(canvas) canvas.style.display = 'none';
                const errorMessage = document.createElement('p');
                errorMessage.textContent = 'Network Error loading top items.';
                errorMessage.classList.add('chart-message', 'error');
                chartContainer.appendChild(errorMessage);
           }
           // Add error message to table
           tableBody.innerHTML = `<tr><td colspan="5" class="px-6 py-4 whitespace-nowrap text-sm text-red-600 text-center table-message">Error loading top selling items.</td></tr>`;
      }
 }

 // Fetch All Categories (for filters)
 async function fetchCategories() {
     try {
         const response = await fetch('./api/fetch_categories.php'); // Assuming you have this API
         const data = await response.json();

         if (response.ok && data.success && data.categories && data.categories.length > 0) {
             console.log('Fetched categories data structure:', data.categories); // IMPORTANT: Debugging line
             updateCategoryFilters(data.categories); // Update both dropdowns
         } else {
             console.warn('fetchCategories: No categories found or error:', data.message);
             // Keep default "All Categories" option
         }
     } catch (error) {
         console.error('fetchCategories: Fetch Error:', error);
         // Keep default "All Categories" option
     }
 }

 // Update Category Filter Dropdowns
 function updateCategoryFilters(categories) {
      const salesCategoryFilter = document.getElementById('salesCategoryFilter');
      const topItemsTableCategoryFilter = document.getElementById('topItemsTableCategoryFilter');

      // Helper to update a single select element and preserve selection
      const populateAndSelect = (selectElement) => {
          if (!selectElement) return;

          const currentSelectedValue = selectElement.value; // Capture current selection BEFORE clearing

          selectElement.innerHTML = ''; // Clear existing options

          const allOption = document.createElement('option');
          allOption.value = 'all';
          allOption.textContent = 'All Categories';
          selectElement.appendChild(allOption);

          // Restore 'All Categories' selection if it was previously selected
          if (currentSelectedValue === 'all') {
              allOption.selected = true;
          }

          categories.forEach(cat => {
              // Be robust: check if category is an object with 'name' or just a string
              const categoryValue = typeof cat === 'object' && cat !== null && cat.name ? cat.name : String(cat);
              const categoryText = htmlspecialchars(categoryValue);

              const option = document.createElement('option');
              option.value = categoryValue;
              option.textContent = categoryText;

              if (categoryValue === currentSelectedValue) {
                  option.selected = true;
              }
              selectElement.appendChild(option);
          });
      };

      populateAndSelect(salesCategoryFilter);
      populateAndSelect(topItemsTableCategoryFilter);
 }


 // --- Update Table Functions ---

 // Update Top Selling Items Table (using stored data)
 function updateTopItemsTable(items) { // Takes the array of items directly
     const tableBody = document.getElementById('topItemsTableBody');
     if (!tableBody) {
         console.error('Top Items Table Body not found for update!');
         return;
     }

     tableBody.innerHTML = ''; // Clear existing rows

     if (items && items.length > 0) {
         // Calculate total revenue across all top items for percentage calculation
         // FIX: Corrected syntax error in reduce function - missing closing parenthesis for arrow function
         const totalRevenueTopItems = items.reduce((sum, item) => sum + (item.total_revenue ?? 0), 0);

         items.forEach(item => {
             // Calculate percentage of total revenue for this item
             const percentageOfSales = totalRevenueTopItems > 0 ? ((item.total_revenue ?? 0) / totalRevenueTopItems) * 100 : 0;

             const row = `
                 <tr>
                     <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                         ${htmlspecialchars(item.name || 'N/A')}
                     </td>
                     <td class="px-6 py-4 whitespace-nowrap text-sm"> ${htmlspecialchars(item.category || 'N/A')}
                     </td>
                     <td class="px-6 py-4 whitespace-nowrap text-sm"> ${formatNumber(item.total_quantity_sold || 0)} </td>
                     <td class="px-6 py-4 whitespace-nowrap text-sm"> ₹${formatCurrency(item.total_revenue || 0)}
                     </td>
                     <td class="px-6 py-4 whitespace-nowrap text-sm"> ${percentageOfSales.toFixed(1)} %
                     </td>
                 </tr>
             `;
             tableBody.innerHTML += row;
         });
     } else {
         tableBody.innerHTML = `
             <tr>
                 <td colspan="5" class="px-6 py-4 whitespace-nowrap text-sm text-center table-message">No top selling items found for this period/category.</td> </tr>
         `;
     }
 }


 // Fetch Customer Growth Data (for Table)
 let customerGrowthTotalRecords = 0; // Variable to store total records for pagination

 async function fetchCustomerGrowth(startDate, endDate, page, itemsPerPage) {
     const tableBody = document.getElementById('customerGrowthTableBody');
     if (!tableBody) { console.error('Customer Growth Table Body not found!'); return; }

      // Table loading state
      tableBody.innerHTML = `<tr><td colspan="5" class="px-6 py-4 whitespace-nowrap text-sm text-center table-message">Loading customer activity...</td></tr>`; // Removed text-gray-700, handled by CSS


     const queryParams = new URLSearchParams();
     if (startDate) queryParams.append('startDate', startDate);
     if (endDate) queryParams.append('endDate', endDate);
     queryParams.append('page', page);
     queryParams.append('itemsPerPage', itemsPerPage);

     try {
         const response = await fetch(`./api/fetch_customer_growth.php?${queryParams.toString()}`);
         const data = await response.json();

         if (response.ok && data.success && data.growth_data && data.growth_data.length > 0) {
             customerGrowthData = data.growth_data; // Store data
             customerGrowthTotalRecords = data.total_records || 0; // Store total records
             updateCustomerGrowthTable(customerGrowthData); // Update the table
             updateCustomerGrowthPagination(); // Update pagination controls
             showAdminConfirmation(data.message || 'Customer growth data fetched.', 'green');
         } else {
             console.warn('fetchCustomerGrowth: Backend reported no data:', data.message);
             customerGrowthData = []; // Store empty data
             customerGrowthTotalRecords = 0; // Reset total records
             updateCustomerGrowthTable([]); // Update table with empty data
             updateCustomerGrowthPagination(); // Update pagination controls
             showAdminConfirmation(data.message || 'No customer growth data found.', 'orange');
         }
     } catch (error) {
         console.error('fetchCustomerGrowth: Fetch Error:', error);
         showAdminConfirmation('Error fetching customer growth data!', 'red');
         customerGrowthData = []; // Clear data on error
         customerGrowthTotalRecords = 0; // Reset total records
         updateCustomerGrowthTable([]); // Update table with empty data
         updateCustomerGrowthPagination(); // Update pagination controls
          tableBody.innerHTML = `<tr><td colspan="5" class="px-6 py-4 whitespace-nowrap text-sm text-red-600 text-center table-message">Error loading customer activity.</td></tr>`;
     }
 }

 // Update Customer Growth Table (using stored data)
 function updateCustomerGrowthTable(growthData) { // Takes the array of growth data
      const tableBody = document.getElementById('customerGrowthTableBody');
      if (!tableBody) { console.error('Customer Growth Table Body not found for update!'); return; }

      tableBody.innerHTML = ''; // Clear existing rows

      if (growthData && growthData.length > 0) {
          growthData.forEach(entry => {
               const row = `
                   <tr>
                       <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                           ${htmlspecialchars(entry.period || 'N/A')} </td>
                       <td class="px-6 py-4 whitespace-nowrap text-sm"> ${formatNumber(entry.new_customers || 0)} </td>
                       <td class="px-6 py-4 whitespace-nowrap text-sm"> ${formatNumber(entry.repeat_customers || 0)} </td>
                       <td class="px-6 py-4 whitespace-nowrap text-sm"> ${formatNumber(entry.total_customers_cumulative || 0)} </td>
                       <td class="px-6 py-4 whitespace-nowrap text-sm"> ${htmlspecialchars(entry.growth_rate || '--')} </td>
                   </tr>
               `;
               tableBody.innerHTML += row;
           });
       } else {
            tableBody.innerHTML = `
                <tr>
                    <td colspan="5" class="px-6 py-4 whitespace-nowrap text-sm text-center table-message">No customer activity found for this period.</td> </tr>
            `;
       }
 }

 // Update Customer Growth Pagination Controls
 function updateCustomerGrowthPagination() {
     const totalRecords = customerGrowthTotalRecords;
     const itemsPerPage = customerGrowthItemsPerPage;
     const currentPage = customerGrowthCurrentPage;
     const totalPages = Math.ceil(totalRecords / itemsPerPage);

     const showingFromEl = document.getElementById('customerGrowthShowingFrom');
     const showingToEl = document.getElementById('customerGrowthShowingTo');
     const totalRecordsEl = document.getElementById('customerGrowthTotalRecords');
     const prevBtn = document.getElementById('customerGrowthPrev');
     const nextBtn = document.getElementById('customerGrowthNext');
     const prevMobileBtn = document.getElementById('customerGrowthPrevMobile');
     const nextMobileBtn = document.getElementById('customerGrowthNextMobile');
     const pageNumbersSpan = document.getElementById('customerGrowthPageNumbers');

     if (totalRecordsEl) totalRecordsEl.textContent = formatNumber(totalRecords);

     if (totalRecords === 0) {
         if (showingFromEl) showingFromEl.textContent = '0';
         if (showingToEl) showingToEl.textContent = '0';
     } else {
         const showingFrom = (currentPage - 1) * itemsPerPage + 1;
         const showingTo = Math.min(currentPage * itemsPerPage, totalRecords);
         if (showingFromEl) showingFromEl.textContent = formatNumber(showingFrom);
         if (showingToEl) showingToEl.textContent = formatNumber(showingTo);
     }

     // Enable/disable Prev/Next buttons
     if (prevBtn) prevBtn.disabled = currentPage <= 1;
     if (nextBtn) nextBtn.disabled = currentPage >= totalPages;
     if (prevMobileBtn) prevMobileBtn.disabled = currentPage <= 1;
     if (nextMobileBtn) nextMobileBtn.disabled = currentPage >= totalPages;

     // Update page numbers display (simple version)
     if (pageNumbersSpan) {
         if (totalPages <= 1) {
             pageNumbersSpan.textContent = '1'; // Or hide if only one page
         } else {
             pageNumbersSpan.textContent = `Page ${formatNumber(currentPage)} of ${formatNumber(totalPages)}`;
         }
     }
 }

 // Handle Customer Growth Pagination Button Clicks
 function handleCustomerGrowthPaginationClick(event) {
     const targetId = event.target.id;
     const { startDate, endDate } = getSelectedDateRange();
     const trendStartDate = salesDateRangeSelect.value === 'all_time' ? null : startDate;
     const trendEndDate = salesDateRangeSelect.value === 'all_time' ? null : endDate;

     if (targetId === 'customerGrowthPrev' || targetId === 'customerGrowthPrevMobile') {
         if (customerGrowthCurrentPage > 1) {
             customerGrowthCurrentPage--;
             fetchCustomerGrowth(trendStartDate, trendEndDate, customerGrowthCurrentPage, customerGrowthItemsPerPage);
         }
     } else if (targetId === 'customerGrowthNext' || targetId === 'customerGrowthNextMobile') {
         const totalPages = Math.ceil(customerGrowthTotalRecords / customerGrowthItemsPerPage);
         if (customerGrowthCurrentPage < totalPages) {
             customerGrowthCurrentPage++;
             fetchCustomerGrowth(trendStartDate, trendEndDate, customerGrowthCurrentPage, customerGrowthItemsPerPage);
         }
     }
 }


 // --- Initial Data Fetch and Event Listeners ---

 // Function to fetch all necessary data for the sales page
 function fetchAllSalesData() {
     console.log('fetchAllSalesData called.'); // DEBUG
     const { startDate, endDate } = getSelectedDateRange();
     const category = getSelectedCategory('salesCategoryFilter'); // Category for charts and summary
     console.log('fetchAllSalesData - Date Range:', { startDate, endDate }, 'Category:', category); // DEBUG

     // Fetch data for Sales Summary Cards
     fetchSalesSummary(startDate, endDate);

     // Fetch data for Revenue & Items Sold Trend Chart (Granularity is handled inside the function)
     fetchRevenueTrend(startDate, endDate, 'monthly'); // Default to monthly

     // Fetch data for Top Selling Items Chart AND Table
     fetchTopSellingItems(startDate, endDate, category);

     // Fetch data for Customer Growth Table (Pagination is handled inside the function)
     customerGrowthCurrentPage = 1; // Reset pagination on date/category change
     fetchCustomerGrowth(startDate, endDate, customerGrowthCurrentPage, customerGrowthItemsPerPage);

     // fetchCategories(); // REMOVED: This is now called only once on DOMContentLoaded
 }


 // Setup Event Listeners for filters and pagination
 function setupEventListeners() {
     // Date Range filter change
     if (salesDateRangeSelect) {
         // The event listener is already set up to call fetchAllSalesData()
         // when the value changes, and it also handles showing/hiding custom inputs.
     }

     // Category filter change for Charts/Summary
      if (salesCategoryFilterSelect) {
          salesCategoryFilterSelect.addEventListener('change', function() {
              console.log('salesCategoryFilterSelect changed to:', this.value); // DEBUG
              const { startDate, endDate } = getSelectedDateRange();
              const category = this.value; // Get the selected category from THIS dropdown

              // Synchronize the other category filter dropdown
              if (topItemsTableCategoryFilterSelect) {
                  topItemsTableCategoryFilterSelect.value = category;
                  console.log('Synchronized topItemsTableCategoryFilterSelect to:', topItemsTableCategoryFilterSelect.value); // DEBUG
              }
              
              // Call fetchAllSalesData to update all relevant sections with the new category
              fetchAllSalesData();
          });
      }

     // Category filter change for Top Items Table
      if (topItemsTableCategoryFilterSelect) {
          topItemsTableCategoryFilterSelect.addEventListener('change', function() {
              console.log('topItemsTableCategoryFilterSelect changed to:', this.value); // DEBUG
              const { startDate, endDate } = getSelectedDateRange();
              const topItemsCategory = getSelectedCategory('topItemsTableCategoryFilter');
              // Only refetch top items data when this specific filter changes
              fetchTopSellingItems(startDate, endDate, topItemsCategory);
          });
      }


     // Customer Growth Pagination buttons
     const customerGrowthPrevBtn = document.getElementById('customerGrowthPrev');
     const customerGrowthNextBtn = document.getElementById('customerGrowthNext');
     const customerGrowthPrevMobileBtn = document.getElementById('customerGrowthPrevMobile');
     const customerGrowthNextMobileBtn = document.getElementById('customerGrowthNextMobile');
     const customerGrowthItemsPerPageSelect = document.getElementById('customerGrowthItemsPerPageSelect'); // Assuming you add this select


     if(customerGrowthPrevBtn) customerGrowthPrevBtn.addEventListener('click', handleCustomerGrowthPaginationClick);
     if(customerGrowthNextBtn) customerGrowthNextBtn.addEventListener('click', handleCustomerGrowthPaginationClick);
     if(customerGrowthPrevMobileBtn) customerGrowthPrevMobileBtn.addEventListener('click', handleCustomerGrowthPaginationClick);
     if(customerGrowthNextMobileBtn) customerGrowthNextMobileBtn.addEventListener('click', handleCustomerGrowthPaginationClick);

      // Items per page selector event listener (assuming you add this select)
      if(customerGrowthItemsPerPageSelect) {
          customerGrowthItemsPerPageSelect.addEventListener('change', function() {
              customerGrowthItemsPerPage = parseInt(this.value, 10); // Update items per page state
              customerGrowthCurrentPage = 1; // Reset to first page
              const { startDate, endDate } = getSelectedDateRange();
              const trendStartDate = salesDateRangeSelect.value === 'all_time' ? null : startDate;
              const trendEndDate = salesDateRangeSelect.value === 'all_time' ? null : endDate;
               fetchCustomerGrowth(trendStartDate, trendEndDate, customerGrowthCurrentPage, customerGrowthItemsPerPage); // Fetch data with new settings
          });
      }

      // Add event listeners for Revenue Trend granularity buttons
      document.querySelectorAll('button[data-granularity]').forEach(button => {
          button.addEventListener('click', function() {
              const granularity = this.dataset.granularity;
              // Update button active state visually (optional)
               document.querySelectorAll('button[data-granularity]').forEach(btn => {
                   // Removed dark mode classes from here
                   btn.classList.remove('bg-indigo-100', 'text-indigo-800');
                   btn.classList.add('bg-gray-100', 'text-gray-800', 'hover:bg-gray-200');
               });
               this.classList.add('bg-indigo-100', 'text-indigo-800');
               this.classList.remove('bg-gray-100', 'text-gray-800', 'hover:bg-gray-200');

              const { startDate, endDate } = getSelectedDateRange();
              fetchRevenueTrend(startDate, endDate, granularity); // Fetch trend with selected granularity
          });
      });


 }


 document.addEventListener('DOMContentLoaded', async function () { // Made async to await fetchCategories
     setInitialDateRange(); // Set the default date range dropdown value
     initCharts(); // Initialize charts with empty data initially
     setupEventListeners(); // Setup event listeners

     // Call fetchCategories FIRST to populate the dropdowns and ensure they are stable
     await fetchCategories(); // Await this call

     // Then fetch all initial sales data
     fetchAllSalesData();
 });
