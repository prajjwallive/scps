<?php
// admin/product.php - Admin Product Management (Frontend)

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
// though the current data fetching is via AJAX to Workspace_products.php)
// Path: From admin/ UP to root (../) THEN into includes/
require_once '../includes/db_connection.php'; // Make sure this path is correct

// Get admin username and role from session for header (optional, for header display)
$admin_username = $_SESSION['admin_username'] ?? 'Admin';
$admin_role = $_SESSION['admin_role'] ?? 'N/A';

// Include necessary packages (like CSS, Flowbite, Tailwind)
// Path from admin/ UP to root (../) then includes/
include '../includes/packages.php'; // Ensure this path is correct

// Include Admin Header HTML
// Ensure admin_header.php exists in includes/ folder
// Path: From admin/ UP to root (../) THEN into includes/admin_header.php
include '../includes/admin_header.php'; // Ensure this path is correct

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Products</title>
     <style>
         /* Add any custom styles needed here */
         body {
            font-family: 'Inter', sans-serif;
            background-color: #f8f8f8; /* Very light gray for body */
            color: #1a202c; /* Dark gray for text */
        }
         .modal-active {
             overflow: hidden; /* Prevent scrolling when modal is open */
         }
         /* Ensure table container allows horizontal scrolling on small screens */
         @media (max-width: 768px) { /* Applies to screens smaller than md breakpoint (768px) */
             .overflow-x-auto-md {
                 overflow-x: auto;
             }
         }
         /* Modal specific adjustments for small screens */
         #productModal > div { /* The inner div (white box) that contains the modal content */
            max-height: 95vh; /* Limit modal height to 95% of viewport height */
            margin: 2.5vh auto; /* Center with a small vertical margin */
            display: flex; /* Use flexbox for header, body, footer layout */
            flex-direction: column; /* Stack children vertically */
            /* Do NOT put overflow-y: auto here, as it would scroll the whole modal including header/footer */
         }
         /* Ensure the form area within the modal is scrollable */
         #productModal .modal-scroll-content {
             flex-grow: 1; /* Allows this div to grow and take available space */
             overflow-y: auto; /* This makes the content within this div scrollable */
             padding: 1.5rem; /* Equivalent to px-6 py-4, applied here */
             min-height: 0; /* Critical for flex items with overflow */
         }
         /* Remove default padding from form as it's now on the modal-scroll-content wrapper */
         #productForm {
             padding: 0;
         }
     </style>
</head>

<body class="bg-gray-100 font-sans">

    <div id="admin-notification" class="fixed top-5 left-1/2 -translate-x-1/2 p-4 rounded-md shadow-lg z-50 opacity-0 hidden transition-all duration-500 ease-in-out transform">
        <span id="notification-message"></span>
    </div>


    <section id="products" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        <div class="flex justify-between items-center mb-8">
            <h2 class="text-2xl font-bold text-gray-900">Product Management</h2>
            <button id="addProductBtn"
                class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-md flex items-center focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                </svg>
                Add Product
            </button>
        </div>

        <div id="productModal"
            class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg shadow-xl w-full max-w-md">
                <div class="flex justify-between items-center border-b border-gray-200 px-6 py-4">
                    <h3 class="text-lg font-semibold text-gray-900" id="modalTitle">Add New Product</h3>
                    <button type="button" id="closeModalBtn" class="text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                <!-- This new div will be the scrollable part of the modal body -->
                <div class="modal-scroll-content">
                    <form id="productForm" enctype="multipart/form-data"> <input type="hidden" id="productId" name="productId" value=""> <div class="mb-4">
                            <label for="productName" class="block text-sm font-medium text-gray-700 mb-1">Product Name</label>
                            <input type="text" id="productName" name="productName"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                required>
                        </div>
                        <div class="mb-4">
                            <label for="productPrice" class="block text-sm font-medium text-gray-700 mb-1">Price (₹)</label>
                            <input type="number" id="productPrice" name="productPrice" min="0" step="0.01"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                required>
                        </div>
                        <div class="mb-4">
                            <label for="productCategory"
                                class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                            <select id="productCategory" name="productCategory"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                <option value="">Select Category</option>
                                <option value="Veg">Veg</option>
                                <option value="Non-Veg">Non-Veg</option>
                                <option value="Beverage">Beverage</option>
                                <option value="Snack">Snack</option>
                                <option value="Dessert">Dessert</option>
                            </select>
                        </div>
                        <div class="mb-4">
                            <label for="productDescription"
                                class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                            <textarea id="productDescription" name="productDescription" rows="5"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 resize-y"></textarea>
                        </div>
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1" for="productImage">Product Image</label>
                            <input class="block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 focus:outline-none" aria-describedby="product_image_help" id="productImage" name="productImage" type="file" accept="image/*">
                            <p class="mt-1 text-sm text-gray-500" id="product_image_help">JPG, PNG, or GIF (Recommended: Square image).</p>
                             <img id="imagePreview" src="#" alt="Image Preview" class="mt-2 hidden w-24 h-24 object-cover rounded">
                        </div>
                        <div class="flex items-center mb-4">
                            <input id="productAvailable" name="productAvailable" type="checkbox"
                                class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                            <label for="productAvailable" class="ml-2 block text-sm text-gray-700">Available for sale</label>
                        </div>
                    </form>
                </div>
                <div class="bg-gray-50 px-6 py-4 flex justify-end border-t border-gray-200">
                    <button type="button" id="cancelModalBtn"
                        class="mr-3 px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Cancel
                    </button>
                    <button type="button" id="saveProductBtn"
                        class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Save Product
                    </button>
                </div>
            </div>
        </div>
        <div class="bg-white shadow overflow-hidden rounded-lg">
            <!-- Added a wrapper div with overflow-x-auto for smaller screens -->
            <div class="overflow-x-auto overflow-x-auto-md">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"> Product ID</th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"> Product Name</th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"> Description</th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"> Category</th>
                             <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"> Price</th>
                             <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"> Image</th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"> Status</th>
                            <th scope="col"
                                class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider"> Actions</th>
                        </tr>
                    </thead>
                    <tbody id="productsTableBody" class="bg-white divide-y divide-gray-200">
                        <tr class="text-center py-4">
                            <td colspan="8" class="px-6 py-4 text-gray-500">Loading products...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
             <div class="bg-white px-6 py-4 border-t border-gray-200 flex items-center justify-between">
                  <div class="flex-1 flex justify-between sm:hidden">
                      <button id="prevPageMobile" class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                          Previous
                      </button>
                      <button id="nextPageMobile" class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                          Next
                      </button>
                  </div>
                  <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                      <div>
                          <p class="text-sm text-gray-700">
                              Showing
                              <span class="font-medium" id="pagination-start">0</span>
                              to
                              <span class="font-medium" id="pagination-end">0</span>
                              of
                              <span class="font-medium" id="pagination-total">0</span>
                              results
                          </p>
                      </div>
                      <div>
                          <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                               <button id="prevPage" class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                   <span class="sr-only">Previous</span>
                                   <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                                       fill="currentColor" aria-hidden="true">
                                       <path fill-rule="evenodd"
                                           d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z"
                                           clip-rule="evenodd" />
                                   </svg>
                               </button>
                               <span id="pagination-numbers" class="flex"></span>
                               <button id="nextPage"
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

    <?php // include "../includes/footer.php"; // Include footer if you have one ?>

    <script>
        // --- DOM Element References ---
        const productModal = document.getElementById('productModal');
        const addProductBtn = document.getElementById('addProductBtn'); // Button to open modal
        const closeModalBtn = document.getElementById('closeModalBtn'); // Close button in modal header
        const cancelModalBtn = document.getElementById('cancelModalBtn'); // Cancel button in modal footer
        const productForm = document.getElementById('productForm'); // The form itself
        const modalTitle = document.getElementById('modalTitle'); // Modal title element
        const saveProductBtn = document.getElementById('saveProductBtn'); // Save button in the modal footer
        const productsTableBody = document.getElementById('productsTableBody'); // Table body to display products

        // Form input fields - Make sure these match the IDs and Names in your HTML
        const productIdInput = document.getElementById('productId'); // Hidden ID field for editing
        const productNameInput = document.getElementById('productName');
        const productPriceInput = document.getElementById('productPrice');
        const productCategoryInput = document.getElementById('productCategory');
        const productDescriptionInput = document.getElementById('productDescription');
        const productImageInput = document.getElementById('productImage'); // File input for image
        const productAvailableInput = document.getElementById('productAvailable'); // Checkbox
        const imagePreview = document.getElementById('imagePreview'); // Image preview element

        // Notification element
        const adminNotification = document.getElementById('admin-notification');
        const notificationMessage = document.getElementById('notification-message');

        // Pagination elements
        const paginationStartSpan = document.getElementById('pagination-start');
        const paginationEndSpan = document.getElementById('pagination-end');
        const paginationTotalSpan = document.getElementById('pagination-total');
        const paginationNumbersContainer = document.getElementById('pagination-numbers');
        const prevPageBtn = document.getElementById('prevPage');
        const nextPageBtn = document.getElementById('nextPage');
        const prevPageMobileBtn = document.getElementById('prevPageMobile');
        const nextPageMobileBtn = document.getElementById('nextPageMobile');

        // --- Pagination Variables ---
        let currentPage = 1;
        const itemsPerPage = 10; // Number of products per page
        let totalProducts = 0; // Initialize totalProducts to 0


        // --- Modal Functions ---
        function openModal(isEditing = false) {
            productModal.classList.remove('hidden');
            document.body.classList.add('modal-active'); // Add class to body to prevent scrolling

            if (isEditing) {
                modalTitle.textContent = 'Edit Product';
                saveProductBtn.textContent = 'Save Changes';
                // TODO: Logic to load existing product data for editing goes here
            } else {
                modalTitle.textContent = 'Add New Product';
                saveProductBtn.textContent = 'Save Product';
                productForm.reset(); // Clear form fields for a new product
                productIdInput.value = ''; // Ensure hidden ID is empty for new product
                imagePreview.classList.add('hidden'); // Hide preview for new product
                imagePreview.src = '#'; // Clear preview source
                 // Clear the file input explicitly (reset() might not always work for files)
                 if (productImageInput) productImageInput.value = '';
            }
        }

        function closeModal() {
            productModal.classList.add('hidden');
            document.body.classList.remove('modal-active'); // Remove class from body
            productForm.reset(); // Reset the form when closed
            productIdInput.value = ''; // Ensure hidden ID is empty on close
            imagePreview.classList.add('hidden'); // Hide preview
            imagePreview.src = '#'; // Clear preview source
             if (productImageInput) productImageInput.value = ''; // Clear file input
        }

        // --- Notification Function ---
        function showNotification(message, type = 'success') {
            notificationMessage.textContent = message;
            // Reset classes first
            adminNotification.className = 'fixed top-5 left-1/2 -translate-x-1/2 p-4 rounded-md shadow-lg z-50 opacity-0 hidden transition-all duration-500 ease-in-out transform';

            if (type === 'success') {
                adminNotification.classList.add('bg-green-500', 'text-white');
            } else if (type === 'error') {
                 adminNotification.classList.add('bg-red-500', 'text-white');
            } else { // Default/Info
                 adminNotification.classList.add('bg-blue-500', 'text-white');
            }

            adminNotification.classList.remove('hidden');
            // Use a small delay to ensure the transition plays
            setTimeout(() => {
                adminNotification.classList.remove('opacity-0', 'transform');
                adminNotification.classList.add('opacity-100');
            }, 50);

            // Hide after 3 seconds (adjust as needed)
            setTimeout(() => {
                adminNotification.classList.remove('opacity-100');
                adminNotification.classList.add('opacity-0');
                // Hide completely after transition finishes
                setTimeout(() => {
                    adminNotification.classList.add('hidden', 'transform');
                }, 500); // Match transition duration
            }, 3000);
        }


        // --- Function to Fetch and Display Products ---
        function fetchAndDisplayProducts() {
            // Show loading message
            productsTableBody.innerHTML = '<tr><td colspan="8" class="text-center py-4 text-gray-500">Loading products...</td></tr>';

            // Construct query parameters for pagination
            const queryParams = new URLSearchParams({
                page: currentPage,
                limit: itemsPerPage
            });

            // Use the Fetch API to call the backend script
            // IMPORTANT: Make sure the filename here matches the actual file name on your server
            // that contains the PHP code to fetch products (likely Workspace_products.php)
            fetch(`Workspace_products.php?${queryParams.toString()}`) // <-- ENSURE THIS FILENAME IS CORRECT!
                .then(response => {
                    if (!response.ok) {
                         // If response is not 2xx, throw an error including status and response text
                         return response.text().then(text => { throw new Error(`HTTP error! Status: ${response.status}, Response: ${text}`); });
                    }
                    return response.json();
                })
                .then(data => {
                    console.log("Data received from backend:", data); // Log the received data for debugging
                    // Clear loading message or existing rows
                    productsTableBody.innerHTML = '';

                    if (data.success) {
                        // Ensure total_records is a number, default to 0 if not provided or invalid
                        totalProducts = typeof data.total_records === 'number' ? data.total_records : 0; // Changed from data.total_results to data.total_records
                        renderPaginationControls(data.total_pages, data.current_page, totalProducts); // Pass totalProducts

                        if (data.products && data.products.length > 0) {
                            data.products.forEach(product => {
                                const row = document.createElement('tr');
                                row.classList.add('bg-white', 'border-b', 'hover:bg-gray-50');

                                // Ensure column order and data access matches your SELECT query and database
                                // Assuming your query selects: food_id, name, description, price, category, image_path, is_available
                                row.innerHTML = `
                                    <td class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap">
                                        ${htmlspecialchars(product.food_id)}
                                    </td>
                                    <td class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap">
                                         ${htmlspecialchars(product.name)}
                                    </td>
                                    <td class="px-6 py-4 text-gray-700">
                                        ${htmlspecialchars(product.description || 'No description')}
                                    </td>
                                     <td class="px-6 py-4 text-gray-700">
                                        ${htmlspecialchars(product.category || 'Uncategorized')}
                                    </td>
                                    <td class="px-6 py-4 text-gray-700">
                                        Rs. ${htmlspecialchars(parseFloat(product.price).toFixed(2))}
                                    </td>
                                    <td class="px-6 py-4">
                                        <img src="${htmlspecialchars('../' + product.image_path)}" class="w-12 h-12 object-cover rounded" alt="${htmlspecialchars(product.name)} Image" onerror="this.onerror=null;this.src='https://placehold.co/48x48/cccccc/000000?text=No+Image';"> </td>
                                     <td class="px-6 py-4">
                                        <span class="px-2.5 py-0.5 rounded text-xs font-medium ${product.is_available == 1 ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}">
                                            ${product.is_available == 1 ? 'Available' : 'Unavailable'}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-right whitespace-nowrap">
                                        <a href="#" class="font-medium text-blue-600 hover:underline mr-3 edit-product-btn" data-id="${htmlspecialchars(product.food_id)}">Edit</a>
                                        <a href="#" class="font-medium text-red-600 hover:underline delete-product-btn" data-id="${htmlspecialchars(product.food_id)}">Delete</a>
                                    </td>
                                `;
                                productsTableBody.appendChild(row);
                            });
                            // Add event listeners to the new Edit/Delete buttons via delegation
                            addActionListenerToButtons();

                        } else {
                             // Display message if no products are returned but success is true
                             productsTableBody.innerHTML = `<tr><td colspan="8" class="text-center py-4 text-gray-500">${htmlspecialchars(data.message || 'No products found.')}</td></tr>`;
                        }
                    } else {
                         // Display error message from the backend response
                         productsTableBody.innerHTML = `<tr><td colspan="8" class="text-center py-4 text-red-600">Error: ${htmlspecialchars(data.message || 'Failed to fetch products.')}</td></tr>`;
                         console.error('Backend Error:', data.message);
                         // If unauthorized, redirect to login
                         if(data.message && data.message.includes('Unauthorized')) {
                             showNotification('Session expired or unauthorized. Redirecting to login.', 'error');
                              setTimeout(() => { window.location.href = 'login.php'; }, 2000);
                         }
                    }
                })
                .catch(error => {
                    // Handle network errors or JSON parsing errors
                    productsTableBody.innerHTML = `<tr><td colspan="8" class="text-center py-4 text-red-600">Network Error: ${htmlspecialchars(error.message)}</td></tr>`;
                    console.error('Fetch error:', error);
                    // Ensure pagination display is reset on error
                    renderPaginationControls(0, 1, 0); // Reset pagination display on error
                });
        }

        // Helper function for HTML escaping (good for preventing XSS)
        function htmlspecialchars(str) {
            if (typeof str !== 'string' && str !== null && str !== undefined) { str = String(str); }
            else if (str === null || str === undefined) { return ''; }
            const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
            return str.replace(/[&<>"']/g, function(m) { return map[m]; });
        }


        // --- Pagination Controls Rendering ---
        function renderPaginationControls(totalPages, currentPageNum, totalResults) {
            currentPage = currentPageNum; // Update global current page
            totalProducts = totalResults; // Update global total products

            // Calculate start and end indices for display
            // If totalResults is 0, start and end should both be 0.
            // Otherwise, calculate based on current page and items per page.
            const startIndex = totalResults > 0 ? ((currentPage - 1) * itemsPerPage + 1) : 0;
            // The endIndex should not exceed totalResults
            const endIndex = totalResults > 0 ? Math.min(currentPage * itemsPerPage, totalResults) : 0;

            paginationStartSpan.textContent = startIndex;
            paginationEndSpan.textContent = endIndex;
            paginationTotalSpan.textContent = totalResults;

            // Clear previous page number buttons
            paginationNumbersContainer.innerHTML = '';

            if (totalPages > 1) {
                for (let i = 1; i <= totalPages; i++) {
                    const pageButton = document.createElement('button');
                    pageButton.textContent = i;
                    pageButton.classList.add('relative', 'inline-flex', 'items-center', 'px-4', 'py-2', 'border', 'text-sm', 'font-medium');
                    if (i === currentPage) {
                        pageButton.classList.add('z-10', 'bg-indigo-50', 'border-indigo-500', 'text-indigo-600');
                    } else {
                        pageButton.classList.add('bg-white', 'border-gray-300', 'text-gray-500', 'hover:bg-gray-50');
                    }
                    pageButton.addEventListener('click', () => {
                        currentPage = i; // Update current page before fetching
                        fetchAndDisplayProducts();
                    });
                    paginationNumbersContainer.appendChild(pageButton);
                }
            }

            // Enable/disable Prev/Next buttons
            prevPageBtn.disabled = currentPage === 1;
            prevPageBtn.classList.toggle('opacity-50', currentPage === 1);
            prevPageBtn.classList.toggle('cursor-not-allowed', currentPage === 1);

            nextPageBtn.disabled = currentPage === totalPages || totalPages === 0;
            nextPageBtn.classList.toggle('opacity-50', currentPage === totalPages || totalPages === 0);
            nextPageBtn.classList.toggle('cursor-not-allowed', currentPage === totalPages || totalPages === 0);

            // Mobile buttons
            prevPageMobileBtn.disabled = currentPage === 1;
            prevPageMobileBtn.classList.toggle('opacity-50', currentPage === 1);
            prevPageMobileBtn.classList.toggle('cursor-not-allowed', currentPage === 1);

            nextPageMobileBtn.disabled = currentPage === totalPages || totalPages === 0;
            nextPageMobileBtn.classList.toggle('opacity-50', currentPage === totalPages || totalPages === 0);
            nextPageMobileBtn.classList.toggle('cursor-not-allowed', currentPage === totalPages || totalPages === 0);
        }


        // --- Function to Handle Form Submission (Add/Edit Product) ---
        async function handleProductFormSubmit(event) {
            event.preventDefault(); // Prevent the default form submission

            const form = productForm;
            const formData = new FormData(form); // Use FormData to easily get form data, especially for files

            // Determine if we are adding or editing based on the hidden product ID field
            const isEditing = !!productIdInput.value; // Check if productIdInput has a value

            // Add the productId to the formData if editing (though we are only implementing Add for now)
            if (isEditing) {
                 formData.append('productId', productIdInput.value);
            }

            // Disable the save button and show loading state
            saveProductBtn.disabled = true;
            saveProductBtn.textContent = isEditing ? 'Saving Changes...' : 'Adding Product...';

            try {
                 // Send the form data using fetch API
                 // IMPORTANT: This calls add_product.php for adding (we'll implement edit_product.php later)
                 // Make sure add_product.php exists and is correct.
                const response = await fetch('add_product.php', { // Call add_product.php for adding
                    method: 'POST',
                    body: formData // FormData handles setting the correct headers (like Content-Type: multipart/form-data for files)
                });

                // Check if the response is OK (status is 2xx)
                if (!response.ok) {
                     // If response is not 2xx, throw an error including status and response text
                     return response.text().then(text => { throw new Error(`HTTP error! Status: ${response.status}, Response: ${text}`); });
                }

                const result = await response.json(); // Parse the JSON response

                if (result.success) {
                     showNotification(result.message, 'success'); // Show success message
                     closeModal(); // Close the modal
                     fetchAndDisplayProducts(); // Refresh the product list

                } else {
                     showNotification(result.message, 'error'); // Show error message from backend
                     console.error('Backend Error:', result.message);
                      // If unauthorized error from backend, redirect to login
                      if(result.message && result.message.includes('Unauthorized')) {
                           setTimeout(() => { window.location.href = 'login.php'; }, 2000);
                      }
                }

            } catch (error) {
                // Handle network errors or JSON parsing errors
                showNotification(`Operation failed: ${error.message}`, 'error');
                console.error('Fetch Error:', error);
            } finally {
                // Re-enable the save button and reset text
                saveProductBtn.disabled = false;
                saveProductBtn.textContent = isEditing ? 'Save Changes' : 'Save Product';
            }
        }

        // --- Action Buttons (Edit/Delete) Event Delegation Handler ---
         // This handles clicks on Edit/Delete buttons dynamically added to the table
        // --- Action Buttons (Edit/Delete) Event Delegation Handler ---
        function handleActionClick(event) {
             const target = event.target; // The element that was clicked

             // Check if the clicked element or its parent is an Edit button
             if (target.classList.contains('edit-product-btn')) {
                 event.preventDefault(); // Prevent default link behavior
                 const productId = target.dataset.id; // Get the product ID from data-id attribute
                 console.log('Edit button clicked for product ID:', productId);

                 // --- Fetch single product data and populate modal ---
                 fetch(`Workspace_single_product.php?food_id=${productId}`) // Call the new backend script
                     .then(response => {
                         if (!response.ok) {
                              return response.text().then(text => { throw new Error(`HTTP error! Status: ${response.status}, Response: ${text}`); });
                         }
                         return response.json();
                     })
                     .then(data => {
                         if (data.success && data.product) {
                             const product = data.product;

                             // Populate the modal form fields with fetched data
                             productIdInput.value = product.food_id; // Set the hidden ID field
                             productNameInput.value = product.name;
                             productPriceInput.value = parseFloat(product.price).toFixed(2); // Format price
                             productCategoryInput.value = product.category;
                             productDescriptionInput.value = product.description || ''; // Handle null description
                             // For image, if you have a URL or path, you can set the preview
                             // If you're using a file input, you can't programmatically set its value for security reasons.
                             // You might display the current image path next to the file input.
                             // For now, let's just handle the preview if a path exists
                             if (product.image_path) {
                                 imagePreview.src = '../' + product.image_path; // Assuming path is relative to root/images/
                                 imagePreview.classList.remove('hidden');
                             } else {
                                 imagePreview.classList.add('hidden');
                                 imagePreview.src = '#';
                             }

                             // Set the checkbox based on is_available (1 or 0)
                             productAvailableInput.checked = product.is_available == 1;

                             // Open the modal in edit mode
                             openModal(true);

                         } else {
                             showNotification(data.message || 'Failed to fetch product details.', 'error');
                             console.error('Backend Error fetching single product:', data.message);
                         }
                     })
                     .catch(error => {
                         showNotification(`Error fetching product details: ${error.message}`, 'error');
                         console.error('Fetch error fetching single product:', error);
                     });

             }
             // Check if the clicked element or its parent is a Delete button
             else if (target.classList.contains('delete-product-btn')) {
                 // ... (your existing delete logic here) ...
                  event.preventDefault(); // Prevent default link behavior
                  const productId = target.dataset.id;
                  console.log('Delete button clicked for product ID:', productId);

                   // Show confirmation dialog
                   if (confirm('Are you sure you want to delete this product?')) {
                       // User confirmed deletion
                       console.log('User confirmed deletion for product ID:', productId);
                       deleteProduct(productId); // Call a new function to handle deletion
                   } else {
                       // User cancelled deletion
                       console.log('User cancelled deletion for product ID:', productId);
                   }
             }
         }

         // --- Function to Handle Deletion (Add this new function) ---
         // This function sends the AJAX request to delete_product.php
         async function deleteProduct(productId) {
             try {
                  // Send the delete request
                  const response = await fetch('delete_product.php', {
                      method: 'POST', // Using POST as per your delete_product.php
                      headers: {
                          'Content-Type': 'application/x-www-form-urlencoded', // Standard for POSTing simple key-value pairs
                      },
                      body: `food_id=${productId}` // Send the product ID in the request body
                  });

                  if (!response.ok) {
                       return response.text().then(text => { throw new Error(`HTTP error! Status: ${response.status}, Response: ${text}`); });
                  }

                  const result = await response.json();

                  if (result.success) {
                      showNotification(result.message, 'success'); // Show success message
                      fetchAndDisplayProducts(); // Refresh the product list

                  } else {
                      showNotification(result.message || 'Failed to delete product.', 'error');
                      console.error('Backend Error deleting product:', result.message);
                  }

             } catch (error) {
                 showNotification(`Error deleting product: ${error.message}`, 'error');
                 console.error('Fetch error deleting product:', error);
             }
         }

        // --- Set up Action Button Event Listeners (using Delegation) ---
        // Adds a single event listener to the table body and determines
        // which action button was clicked based on event.target.
        // This function should be called once after the productsTableBody element exists.
         function addActionListenerToButtons() {
             // Ensure the productsTableBody element exists
              if (productsTableBody) {
                  // Add the event listener if it hasn't been added before
                  // Using a data attribute to prevent adding multiple listeners
                   if (!productsTableBody.dataset.listenersAdded) {
                       productsTableBody.addEventListener('click', handleActionClick);
                       productsTableBody.dataset.listenersAdded = 'true'; // Mark as added
                   }
              }
          }


        // --- Event Listeners (Runs when the DOM is fully loaded) ---
        document.addEventListener('DOMContentLoaded', function() {
            // Fetch and display products when the page loads
            fetchAndDisplayProducts(); // Call the function to load products

            // --- Modal Button Event Listeners ---
            addProductBtn.addEventListener('click', () => openModal(false)); // Open modal for adding
            closeModalBtn.addEventListener('click', closeModal); // Close modal using X button
            cancelModalBtn.addEventListener('click', closeModal); // Close modal using Cancel button

            // --- Form Submission Handling ---
            // Listen for the click on the Save Product button
            saveProductBtn.addEventListener('click', () => {
                // Programmatically trigger the form's submit event
                const submitEvent = new Event('submit', { cancelable: true });
                productForm.dispatchEvent(submitEvent);
            });

            // Listen for the actual form submit event (triggered by the button click or pressing Enter if possible)
            productForm.addEventListener('submit', handleProductFormSubmit);

            // --- Image Preview Listener ---
            // Listen for changes on the file input to show a preview
            if (productImageInput) { // Check if the element exists
                 productImageInput.addEventListener('change', function(event) {
                     const file = event.target.files[0]; // Get the selected file

                     if (file) {
                         const reader = new FileReader(); // Create a FileReader object

                         reader.onload = function(e) {
                             // When the file is read, set the image source and show the preview
                             imagePreview.src = e.target.result;
                             imagePreview.classList.remove('hidden');
                         }
                         reader.readAsDataURL(file); // Read the file as a data URL (base64 string)
                     } else {
                         // If no file is selected, hide and clear the preview
                         imagePreview.classList.add('hidden');
                         imagePreview.src = '#';
                     }
                 });
            }

            // --- Pagination Button Event Listeners ---
            prevPageBtn.addEventListener('click', () => {
                if (currentPage > 1) {
                    currentPage--;
                    fetchAndDisplayProducts();
                }
            });
            nextPageBtn.addEventListener('click', () => {
                const totalPossiblePages = Math.ceil(totalProducts / itemsPerPage);
                if (currentPage < totalPossiblePages) {
                    currentPage++;
                    fetchAndDisplayProducts();
                }
            });
            prevPageMobileBtn.addEventListener('click', () => {
                if (currentPage > 1) {
                    currentPage--;
                    fetchAndDisplayProducts();
                }
            });
            nextPageMobileBtn.addEventListener('click', () => {
                const totalPossiblePages = Math.ceil(totalProducts / itemsPerPage);
                if (currentPage < totalPossiblePages) {
                    currentPage++;
                    fetchAndDisplayProducts();
                }
            });


            // --- Initial Setup for Action Buttons ---
            // Set up the event listener delegation for the Edit/Delete buttons
             addActionListenerToButtons(); // Call this once when the DOM is ready

        });

        // Note: No closing PHP tag here is intentional
    </script>

</body>

</html>
<?php
// Close the database connection at the end of the script
if (isset($link)) {
    mysqli_close($link);
}
?>
