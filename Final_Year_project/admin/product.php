<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products</title>
    <?php include "../includes/packages.php" ?>
    <?php include "../includes/admin_header.php" ?>
</head>

<body>
    <!-- Product Management Section -->
    <section id="products" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="flex justify-between items-center mb-8">
            <h2 class="text-2xl font-bold text-gray-900">Product Management</h2>
            <button id="addProductBtn"
                class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-md flex items-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                </svg>
                Add Product
            </button>
        </div>

        <!-- Product Add/Edit Modal (Hidden by default) -->
        <div id="productModal"
            class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg shadow-xl w-full max-w-md">
                <div class="flex justify-between items-center border-b px-6 py-4">
                    <h3 class="text-lg font-semibold text-gray-900" id="modalTitle">Add New Product</h3>
                    <button id="closeModalBtn" class="text-gray-400 hover:text-gray-500">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                <form id="productForm" class="px-6 py-4">
                    <input type="hidden" id="productId">
                    <div class="mb-4">
                        <label for="productName" class="block text-sm font-medium text-gray-700 mb-1">Product
                            Name</label>
                        <input type="text" id="productName"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"
                            required>
                    </div>
                    <div class="mb-4">
                        <label for="productPrice" class="block text-sm font-medium text-gray-700 mb-1">Price (₹)</label>
                        <input type="number" id="productPrice" min="0" step="0.01"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"
                            required>
                    </div>
                    <div class="mb-4">
                        <label for="productCategory"
                            class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                        <select id="productCategory"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            <option value="food">Food</option>
                            <option value="beverage">Beverage</option>
                            <option value="snack">Snack</option>
                            <option value="dessert">Dessert</option>
                        </select>
                    </div>
                    <div class="mb-4">
                        <label for="productDescription"
                            class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                        <textarea id="productDescription" rows="3"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"></textarea>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Product Image</label>
                        <div class="mt-1 flex items-center">
                            <span class="inline-block h-20 w-20 rounded-md overflow-hidden bg-gray-100">
                                <img id="productImagePreview" src="https://via.placeholder.com/80" alt="Product preview"
                                    class="h-full w-full object-cover">
                            </span>
                            <label for="productImage"
                                class="ml-5 bg-white py-2 px-3 border border-gray-300 rounded-md shadow-sm text-sm leading-4 font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 cursor-pointer">
                                Change
                                <input id="productImage" type="file" class="sr-only" accept="image/*">
                            </label>
                        </div>
                    </div>
                    <div class="flex items-center mb-4">
                        <input id="productAvailable" type="checkbox"
                            class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                        <label for="productAvailable" class="ml-2 block text-sm text-gray-700">Available for
                            sale</label>
                    </div>
                </form>
                <div class="bg-gray-50 px-6 py-4 flex justify-end border-t">
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

        <!-- Products Filter/Search -->
        <div class="bg-white shadow rounded-lg p-4 mb-6">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div class="relative flex-1">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z"
                                clip-rule="evenodd"></path>
                        </svg>
                    </div>
                    <input id="productSearch" type="text"
                        class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                        placeholder="Search products...">
                </div>
                <div class="flex items-center space-x-4">
                    <select id="categoryFilter"
                        class="block w-full pl-3 pr-10 py-2 text-base border border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                        <option value="all">All Categories</option>
                        <option value="food">Food</option>
                        <option value="beverage">Beverage</option>
                        <option value="snack">Snack</option>
                        <option value="dessert">Dessert</option>
                    </select>
                    <select id="availabilityFilter"
                        class="block w-full pl-3 pr-10 py-2 text-base border border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                        <option value="all">All Status</option>
                        <option value="available">Available</option>
                        <option value="unavailable">Unavailable</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Products Listing -->
        <div class="bg-white shadow overflow-hidden rounded-lg">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Product</th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Category</th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Price</th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Status</th>
                            <th scope="col"
                                class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Actions</th>
                        </tr>
                    </thead>
                    <tbody id="productsTableBody" class="bg-white divide-y divide-gray-200">
                        <!-- Products will be loaded here -->
                        <tr class="text-center py-4">
                            <td colspan="5" class="px-6 py-4 text-gray-500">Loading products...</td>
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
                            Showing <span class="font-medium">1</span> to <span class="font-medium">10</span> of <span
                                class="font-medium">20</span> results
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
        // Sample product data (replace with your actual data from backend)
        let products = [
            {
                id: 1,
                name: "Veg Sandwich",
                price: 45,
                category: "food",
                description: "Fresh vegetable sandwich with chutney",
                image: "https://images.unsplash.com/photo-1528735602780-2552fd46c7af?ixlib=rb-1.2.1&auto=format&fit=crop&w=500&q=80",
                available: true
            },
            {
                id: 2,
                name: "Masala Chai",
                price: 15,
                category: "beverage",
                description: "Hot spicy Indian tea",
                image: "https://images.unsplash.com/photo-1564890369478-c89ca6d9cde9?ixlib=rb-1.2.1&auto=format&fit=crop&w=500&q=80",
                available: true
            },
            {
                id: 3,
                name: "Samosa",
                price: 20,
                category: "snack",
                description: "Crispy potato stuffed snack",
                image: "https://images.unsplash.com/photo-1589302168068-964664d93dc0?ixlib=rb-1.2.1&auto=format&fit=crop&w=500&q=80",
                available: false
            },
            {
                id: 4,
                name: "Gulab Jamun",
                price: 30,
                category: "dessert",
                description: "Sweet fried milk balls in sugar syrup",
                image: "https://images.unsplash.com/photo-1606041008023-472dfb5e530f?ixlib=rb-1.2.1&auto=format&fit=crop&w=500&q=80",
                available: true
            }
        ];

        // DOM Elements
        const productModal = document.getElementById('productModal');
        const addProductBtn = document.getElementById('addProductBtn');
        const closeModalBtn = document.getElementById('closeModalBtn');
        const cancelModalBtn = document.getElementById('cancelModalBtn');
        const saveProductBtn = document.getElementById('saveProductBtn');
        const productForm = document.getElementById('productForm');
        const productsTableBody = document.getElementById('productsTableBody');
        const productSearch = document.getElementById('productSearch');
        const categoryFilter = document.getElementById('categoryFilter');
        const availabilityFilter = document.getElementById('availabilityFilter');
        const productImage = document.getElementById('productImage');
        const productImagePreview = document.getElementById('productImagePreview');

        // Modal functions
        function openModal(product = null) {
            if (product) {
                document.getElementById('modalTitle').textContent = 'Edit Product';
                document.getElementById('productId').value = product.id;
                document.getElementById('productName').value = product.name;
                document.getElementById('productPrice').value = product.price;
                document.getElementById('productCategory').value = product.category;
                document.getElementById('productDescription').value = product.description;
                document.getElementById('productAvailable').checked = product.available;
                productImagePreview.src = product.image;
            } else {
                document.getElementById('modalTitle').textContent = 'Add New Product';
                productForm.reset();
                productImagePreview.src = "https://via.placeholder.com/80";
            }
            productModal.classList.remove('hidden');
        }

        function closeModal() {
            productModal.classList.add('hidden');
        }

        // Event Listeners
        addProductBtn.addEventListener('click', () => openModal());
        closeModalBtn.addEventListener('click', closeModal);
        cancelModalBtn.addEventListener('click', closeModal);

        productImage.addEventListener('change', function (e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function (event) {
                    productImagePreview.src = event.target.result;
                };
                reader.readAsDataURL(file);
            }
        });

        saveProductBtn.addEventListener('click', function () {
            const productId = document.getElementById('productId').value;
            const productData = {
                name: document.getElementById('productName').value,
                price: parseFloat(document.getElementById('productPrice').value),
                category: document.getElementById('productCategory').value,
                description: document.getElementById('productDescription').value,
                available: document.getElementById('productAvailable').checked,
                image: productImagePreview.src
            };

            if (productId) {
                // Update existing product
                const index = products.findIndex(p => p.id == productId);
                if (index !== -1) {
                    products[index] = { ...products[index], ...productData };
                }
            } else {
                // Add new product
                productData.id = products.length > 0 ? Math.max(...products.map(p => p.id)) + 1 : 1;
                products.push(productData);
            }

            renderProducts();
            closeModal();
        });

        // Render products in table
        function renderProducts(filteredProducts = null) {
            const productsToRender = filteredProducts || products;

            if (productsToRender.length === 0) {
                productsTableBody.innerHTML = `
        <tr class="text-center py-4">
          <td colspan="5" class="px-6 py-4 text-gray-500">No products found</td>
        </tr>
      `;
                return;
            }

            productsTableBody.innerHTML = productsToRender.map(product => `
      <tr>
        <td class="px-6 py-4 whitespace-nowrap">
          <div class="flex items-center">
            <div class="flex-shrink-0 h-10 w-10">
              <img class="h-10 w-10 rounded-full object-cover" src="${product.image}" alt="${product.name}">
            </div>
            <div class="ml-4">
              <div class="text-sm font-medium text-gray-900">${product.name}</div>
              <div class="text-sm text-gray-500 truncate max-w-xs">${product.description}</div>
            </div>
          </div>
        </td>
        <td class="px-6 py-4 whitespace-nowrap">
          <div class="text-sm text-gray-900 capitalize">${product.category}</div>
        </td>
        <td class="px-6 py-4 whitespace-nowrap">
          <div class="text-sm text-gray-900">₹${product.price.toFixed(2)}</div>
        </td>
        <td class="px-6 py-4 whitespace-nowrap">
          <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${product.available ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}">
            ${product.available ? 'Available' : 'Unavailable'}
          </span>
        </td>
        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
          <button onclick="openModal(${JSON.stringify(product).replace(/"/g, '&quot;')})" class="text-indigo-600 hover:text-indigo-900 mr-3">Edit</button>
          <button onclick="deleteProduct(${product.id})" class="text-red-600 hover:text-red-900">Delete</button>
        </td>
      </tr>
    `).join('');
        }

        // Delete product
        window.deleteProduct = function (id) {
            if (confirm('Are you sure you want to delete this product?')) {
                products = products.filter(product => product.id !== id);
                renderProducts();
            }
        };

        // Filter products
        function filterProducts() {
            const searchTerm = productSearch.value.toLowerCase();
            const category = categoryFilter.value;
            const availability = availabilityFilter.value;

            const filtered = products.filter(product => {
                const matchesSearch = product.name.toLowerCase().includes(searchTerm) ||
                    product.description.toLowerCase().includes(searchTerm);
                const matchesCategory = category === 'all' || product.category === category;
                const matchesAvailability = availability === 'all' ||
                    (availability === 'available' && product.available) ||
                    (availability === 'unavailable' && !product.available);

                return matchesSearch && matchesCategory && matchesAvailability;
            });

            renderProducts(filtered);
        }

        // Initialize
        productSearch.addEventListener('input', filterProducts);
        categoryFilter.addEventListener('change', filterProducts);
        availabilityFilter.addEventListener('change', filterProducts);
        renderProducts();
    </script>
</body>

</html>