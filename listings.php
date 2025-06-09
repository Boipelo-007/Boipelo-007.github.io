product-detail.html<!DOCTYPE html>/sw.js
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Browse Listings - Edu C2C Marketplace</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-4Q6Gf2aSP4eDXB8Miphtr37CMZZQ5oXLH2yaXMJ2w8e2ZtHTl7GptT4jmndRuHDT" crossorigin="anonymous">
    <!-- Font Awesome -->
    <script src="https://kit.fontawesome.com/4d21d6d70f.js" crossorigin="anonymous"></script>
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .loading-spinner {
            display: none;
            text-align: center;
            padding: 2rem;
        }
        
        .no-results {
            text-align: center;
            padding: 3rem;
            color: #6c757d;
        }
        
        .filter-active {
            background-color: #e3f2fd !important;
            border-color: #2196f3 !important;
        }
        
        .list-view .col-md-6,
        .list-view .col-lg-4 {
            width: 100% !important;
            flex: 0 0 100% !important;
            max-width: 100% !important;
        }
        
        .list-view .card {
            flex-direction: row !important;
        }
        
        .list-view .card-img-top {
            width: 200px !important;
            height: 150px !important;
            object-fit: cover;
            border-radius: 0.375rem 0 0 0.375rem !important;
        }
        
        .list-view .card-body {
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        
        .badge-verified {
            background: #4a6bff;
        }
        
        .listing-skeleton {
            animation: pulse 1.5s ease-in-out infinite;
        }
        
        .listing-skeleton .skeleton-img {
            background: #e2e8f0;
            height: 200px;
            border-radius: 8px 8px 0 0;
        }
        
        .listing-skeleton .skeleton-text {
            background: #e2e8f0;
            height: 20px;
            border-radius: 4px;
            margin: 10px 0;
        }
        
        .listing-skeleton .skeleton-text.short {
            width: 60%;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-handshake me-2"></i>Edu C2C
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="listings.php">Browse</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="sell.html">Sell</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="about.html">About</a>
                    </li>
                </ul>
                <div class="d-flex">
                    <a href="login.html" class="btn btn-outline-primary me-2">Login</a>
                    <a href="register.html" class="btn btn-primary">Register</a>
                    <button id="lowDataToggle" class="btn btn-sm btn-outline-secondary ms-2" title="Toggle Low Data Mode">
                        <i class="fas fa-network-wired"></i>
                    </button>
                </div>
            </div>
        </div>
    </nav>

    <!-- Search Bar -->
    <section class="bg-light py-4">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="input-group">
                        <input type="text" class="form-control form-control-lg" placeholder="Search for products..." id="searchInput">
                        <select class="form-select" style="max-width: 200px;" id="locationFilter">
                            <option value="">All Locations</option>
                            <option value="Johannesburg">Johannesburg</option>
                            <option value="Cape Town">Cape Town</option>
                            <option value="Durban">Durban</option>
                            <option value="Pretoria">Pretoria</option>
                        </select>
                        <button class="btn btn-primary" type="button" id="searchButton">
                            <i class="fas fa-search"></i> Search
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Main Content -->
    <main class="container my-5">
        <div class="row">
            <!-- Filters Sidebar -->
            <div class="col-lg-3 mb-4">
                <div class="card">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Filters</h5>
                        <button class="btn btn-sm btn-outline-secondary" id="clearFilters">Clear All</button>
                    </div>
                    <div class="card-body">
                        <!-- Categories -->
                        <div class="mb-4">
                            <h6 class="mb-3">Categories</h6>
                            <div id="categoryFilters">
                                <!-- Categories will be loaded dynamically -->
                                <div class="text-center">
                                    <div class="spinner-border spinner-border-sm" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Price Range -->
                        <div class="mb-4">
                            <h6 class="mb-3">Price Range</h6>
                            <div class="d-flex justify-content-between mb-2">
                                <span>R0</span>
                                <span>R5000+</span>
                            </div>
                            <input type="range" class="form-range" min="0" max="5000" step="50" id="priceRange" value="5000">
                            <div class="d-flex justify-content-between mt-2">
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text">R</span>
                                    <input type="number" class="form-control" placeholder="Min" id="minPrice" min="0">
                                </div>
                                <span class="mx-2 align-self-center">-</span>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text">R</span>
                                    <input type="number" class="form-control" placeholder="Max" id="maxPrice" min="0">
                                </div>
                            </div>
                        </div>
                        
                        <!-- Condition -->
                        <div class="mb-4">
                            <h6 class="mb-3">Condition</h6>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="condition-new" value="new">
                                <label class="form-check-label" for="condition-new">New</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="condition-like-new" value="like_new">
                                <label class="form-check-label" for="condition-like-new">Like New</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="condition-good" value="good">
                                <label class="form-check-label" for="condition-good">Good</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="condition-fair" value="fair">
                                <label class="form-check-label" for="condition-fair">Fair</label>
                            </div>
                        </div>
                        
                        <!-- Seller Type -->
                        <div class="mb-4">
                            <h6 class="mb-3">Seller Type</h6>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="seller-verified">
                                <label class="form-check-label" for="seller-verified">Verified Sellers Only</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="barter-available">
                                <label class="form-check-label" for="barter-available">Barter Available</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="offers-accepted">
                                <label class="form-check-label" for="offers-accepted">Accepts Offers</label>
                            </div>
                        </div>
                        
                        <button class="btn btn-primary w-100" id="applyFilters">Apply Filters</button>
                    </div>
                </div>
            </div>
            
            <!-- Listings -->
            <div class="col-lg-9">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2 class="mb-1">All Listings</h2>
                        <small class="text-muted" id="resultsCount">Loading...</small>
                    </div>
                    <div class="d-flex gap-2">
                        <div class="dropdown">
                            <button class="btn btn-outline-secondary dropdown-toggle" type="button" id="sortDropdown" data-bs-toggle="dropdown">
                                Sort by: Newest
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="#" data-sort="newest">Newest</a></li>
                                <li><a class="dropdown-item" href="#" data-sort="oldest">Oldest</a></li>
                                <li><a class="dropdown-item" href="#" data-sort="price_low">Price: Low to High</a></li>
                                <li><a class="dropdown-item" href="#" data-sort="price_high">Price: High to Low</a></li>
                                <li><a class="dropdown-item" href="#" data-sort="distance">Distance</a></li>
                                <li><a class="dropdown-item" href="#" data-sort="popular">Most Popular</a></li>
                            </ul>
                        </div>
                        <div class="btn-group" role="group">
                            <button type="button" class="btn btn-outline-secondary active" id="gridView">
                                <i class="fas fa-th"></i>
                            </button>
                            <button type="button" class="btn btn-outline-secondary" id="listView">
                                <i class="fas fa-list"></i>
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Loading Spinner -->
                <div class="loading-spinner" id="loadingSpinner">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading listings...</span>
                    </div>
                    <p class="mt-2">Loading listings...</p>
                </div>
                
                <!-- Listings Grid -->
                <div class="row" id="listingsGrid">
                    <!-- Listings will be loaded dynamically -->
                </div>
                
                <!-- No Results -->
                <div class="no-results d-none" id="noResults">
                    <i class="fas fa-search fa-3x text-muted mb-3"></i>
                    <h5>No listings found</h5>
                    <p class="text-muted">Try adjusting your search criteria or filters</p>
                    <button class="btn btn-primary" id="clearAllFilters">Clear All Filters</button>
                </div>
                
                <!-- Pagination -->
                <nav aria-label="Listings pagination" id="paginationContainer" class="d-none">
                    <ul class="pagination justify-content-center" id="pagination">
                        <!-- Pagination will be generated dynamically -->
                    </ul>
                </nav>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-md-3 mb-4">
                    <h5 class="mb-3">Edu C2C</h5>
                    <p>Empowering South Africa's informal economy through technology.</p>
                    <div class="social-icons">
                        <a href="#" class="text-white me-2"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="text-white me-2"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="text-white me-2"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="text-white"><i class="fab fa-whatsapp"></i></a>
                    </div>
                </div>
                <div class="col-md-3 mb-4">
                    <h5 class="mb-3">Quick Links</h5>
                    <ul class="list-unstyled">
                        <li><a href="index.php" class="text-white">Home</a></li>
                        <li><a href="listings.php" class="text-white">Browse Listings</a></li>
                        <li><a href="sell.html" class="text-white">Sell Products</a></li>
                        <li><a href="about.html" class="text-white">How It Works</a></li>
                        <li><a href="contact.html" class="text-white">Contact Us</a></li>
                    </ul>
                </div>
                <div class="col-md-3 mb-4">
                    <h5 class="mb-3">Categories</h5>
                    <ul class="list-unstyled">
                        <li><a href="listings.php?category=produce" class="text-white">Fresh Produce</a></li>
                        <li><a href="listings.php?category=handicrafts" class="text-white">Handicrafts</a></li>
                        <li><a href="listings.php?category=clothing" class="text-white">Clothing</a></li>
                        <li><a href="listings.php?category=electronics" class="text-white">Electronics</a></li>
                        <li><a href="listings.php?category=barter" class="text-white">Barter Offers</a></li>
                    </ul>
                </div>
                <div class="col-md-3 mb-4">
                    <h5 class="mb-3">Support</h5>
                    <ul class="list-unstyled">
                        <li><a href="#" class="text-white">Help Center</a></li>
                        <li><a href="#" class="text-white">Safety Tips</a></li>
                        <li><a href="#" class="text-white">Digital Literacy</a></li>
                        <li><a href="#" class="text-white">Payment Options</a></li>
                        <li><a href="#" class="text-white">Community Guidelines</a></li>
                    </ul>
                </div>
            </div>
            <hr class="my-4 bg-light">
            <div class="row">
                <div class="col-md-6">
                    <p class="small mb-0">© 2025 Edu C2C Platform. All rights reserved.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <a href="#" class="text-white small me-3">Privacy Policy</a>
                    <a href="#" class="text-white small me-3">Terms of Service</a>
                    <a href="#" class="text-white small">Accessibility</a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js" integrity="sha384-j1CDi7MgGQ12Z7Qab0qlWQ/Qqz24Gc6BM0thvEMVjHnfYGF0rmFCozFSxQBxwHKO" crossorigin="anonymous"></script>
    <!-- Custom JS -->
    <script src="assets/js/main.js"></script>
    <script>
        // Listings Page Functionality
        class ListingsManager {
            constructor() {
                this.currentPage = 1;
                this.pageSize = 12;
                this.currentSort = 'newest';
                this.currentFilters = {
                    search: '',
                    category: [],
                    location: '',
                    minPrice: '',
                    maxPrice: '',
                    condition: [],
                    verified: false,
                    barter: false,
                    offers: false
                };
                this.isLoading = false;
                this.viewMode = 'grid';
                this.categories = [];
                
                this.init();
            }
            
            init() {
                this.loadCategories();
                this.bindEvents();
                this.loadFromURL();
                this.loadListings();
            }
            
            bindEvents() {
                // Search functionality
                document.getElementById('searchButton').addEventListener('click', () => this.handleSearch());
                document.getElementById('searchInput').addEventListener('keypress', (e) => {
                    if (e.key === 'Enter') this.handleSearch();
                });
                
                // View toggle
                document.getElementById('gridView').addEventListener('click', () => this.setViewMode('grid'));
                document.getElementById('listView').addEventListener('click', () => this.setViewMode('list'));
                
                // Sort dropdown
                document.querySelectorAll('[data-sort]').forEach(item => {
                    item.addEventListener('click', (e) => {
                        e.preventDefault();
                        this.setSort(e.target.dataset.sort);
                    });
                });
                
                // Filter controls
                document.getElementById('applyFilters').addEventListener('click', () => this.applyFilters());
                document.getElementById('clearFilters').addEventListener('click', () => this.clearFilters());
                document.getElementById('clearAllFilters').addEventListener('click', () => this.clearFilters());
                
                // Price range
                document.getElementById('priceRange').addEventListener('input', (e) => {
                    document.getElementById('maxPrice').value = e.target.value;
                });
                
                // Location filter
                document.getElementById('locationFilter').addEventListener('change', (e) => {
                    this.currentFilters.location = e.target.value;
                    this.loadListings();
                });
            }
            
            async loadCategories() {
                try {
                    const response = await fetch('server/get_categories.php');
                    const result = await response.json();
                    
                    if (result.success) {
                        this.categories = result.data;
                        this.renderCategoryFilters();
                    }
                } catch (error) {
                    console.error('Failed to load categories:', error);
                    this.renderCategoryFilters([]); // Render with fallback categories
                }
            }
            
            renderCategoryFilters(fallbackCategories = null) {
                const container = document.getElementById('categoryFilters');
                const categories = fallbackCategories || this.categories || [
                    {category_id: 1, category_name: 'Fresh Produce', category_slug: 'produce'},
                    {category_id: 2, category_name: 'Handicrafts', category_slug: 'handicrafts'},
                    {category_id: 3, category_name: 'Clothing', category_slug: 'clothing'},
                    {category_id: 4, category_name: 'Electronics', category_slug: 'electronics'},
                    {category_id: 5, category_name: 'Home Goods', category_slug: 'home'},
                    {category_id: 6, category_name: 'Barter Offers', category_slug: 'barter'}
                ];
                
                container.innerHTML = categories.map(category => `
                    <div class="form-check">
                        <input class="form-check-input category-filter" type="checkbox" 
                               id="category-${category.category_slug}" 
                               value="${category.category_id}">
                        <label class="form-check-label" for="category-${category.category_slug}">
                            ${category.category_name}
                        </label>
                    </div>
                `).join('');
                
                // Bind category filter events
                document.querySelectorAll('.category-filter').forEach(checkbox => {
                    checkbox.addEventListener('change', () => this.updateCategoryFilters());
                });
            }
            
            updateCategoryFilters() {
                const checkedCategories = Array.from(document.querySelectorAll('.category-filter:checked'))
                    .map(cb => cb.value);
                this.currentFilters.category = checkedCategories;
            }
            
            loadFromURL() {
                const urlParams = new URLSearchParams(window.location.search);
                
                // Load category from URL
                const categoryParam = urlParams.get('category');
                if (categoryParam) {
                    setTimeout(() => {
                        const categoryCheckbox = document.getElementById(`category-${categoryParam}`);
                        if (categoryCheckbox) {
                            categoryCheckbox.checked = true;
                            this.updateCategoryFilters();
                        }
                    }, 100);
                }
                
                // Load search term
                const searchParam = urlParams.get('search');
                if (searchParam) {
                    document.getElementById('searchInput').value = searchParam;
                    this.currentFilters.search = searchParam;
                }
            }
            
            handleSearch() {
                const searchTerm = document.getElementById('searchInput').value.trim();
                this.currentFilters.search = searchTerm;
                this.currentPage = 1;
                this.loadListings();
            }
            
            setViewMode(mode) {
                this.viewMode = mode;
                
                // Update button states
                document.getElementById('gridView').classList.toggle('active', mode === 'grid');
                document.getElementById('listView').classList.toggle('active', mode === 'list');
                
                // Update grid class
                const grid = document.getElementById('listingsGrid');
                grid.classList.toggle('list-view', mode === 'list');
            }
            
            setSort(sortType) {
                this.currentSort = sortType;
                
                // Update dropdown text
                const sortTexts = {
                    newest: 'Newest',
                    oldest: 'Oldest',
                    price_low: 'Price: Low to High',
                    price_high: 'Price: High to Low',
                    distance: 'Distance',
                    popular: 'Most Popular'
                };
                
                document.getElementById('sortDropdown').textContent = `Sort by: ${sortTexts[sortType]}`;
                this.loadListings();
            }
            
            applyFilters() {
                // Gather all filter values
                this.currentFilters.minPrice = document.getElementById('minPrice').value;
                this.currentFilters.maxPrice = document.getElementById('maxPrice').value;
                
                this.currentFilters.condition = Array.from(document.querySelectorAll('input[id^="condition-"]:checked'))
                    .map(cb => cb.value);
                
                this.currentFilters.verified = document.getElementById('seller-verified').checked;
                this.currentFilters.barter = document.getElementById('barter-available').checked;
                this.currentFilters.offers = document.getElementById('offers-accepted').checked;
                
                this.currentPage = 1;
                this.loadListings();
            }
            
            clearFilters() {
                // Reset all filter inputs
                document.querySelectorAll('.form-check-input').forEach(cb => cb.checked = false);
                document.getElementById('minPrice').value = '';
                document.getElementById('maxPrice').value = '';
                document.getElementById('priceRange').value = 5000;
                document.getElementById('searchInput').value = '';
                document.getElementById('locationFilter').value = '';
                
                // Reset filter object
                this.currentFilters = {
                    search: '',
                    category: [],
                    location: '',
                    minPrice: '',
                    maxPrice: '',
                    condition: [],
                    verified: false,
                    barter: false,
                    offers: false
                };
                
                this.currentPage = 1;
                this.loadListings();
            }
            
            async loadListings() {
                if (this.isLoading) return;
                
                this.isLoading = true;
                this.showLoading();
                
                try {
                    const params = new URLSearchParams({
                        page: this.currentPage,
                        limit: this.pageSize,
                        sort: this.currentSort,
                        ...this.currentFilters,
                        category: this.currentFilters.category.join(','),
                        condition: this.currentFilters.condition.join(',')
                    });
                    
                    const response = await fetch(`server/get_listings.php?${params}`);
                    const result = await response.json();
                    
                    if (result.success) {
                        this.renderListings(result.data.listings);
                        this.renderPagination(result.data.pagination);
                        this.updateResultsCount(result.data.total);
                    } else {
                        throw new Error(result.error || 'Failed to load listings');
                    }
                } catch (error) {
                    console.error('Failed to load listings:', error);
                    this.showError();
                } finally {
                    this.isLoading = false;
                    this.hideLoading();
                }
            }
            
            showLoading() {
                document.getElementById('loadingSpinner').style.display = 'block';
                document.getElementById('listingsGrid').style.display = 'none';
                document.getElementById('noResults').classList.add('d-none');
                document.getElementById('paginationContainer').classList.add('d-none');
            }
            
            hideLoading() {
                document.getElementById('loadingSpinner').style.display = 'none';
                document.getElementById('listingsGrid').style.display = 'flex';
            }
            
            renderListings(listings) {
                const container = document.getElementById('listingsGrid');
                
                if (!listings || listings.length === 0) {
                    container.innerHTML = '';
                    document.getElementById('noResults').classList.remove('d-none');
                    return;
                }
                
                document.getElementById('noResults').classList.add('d-none');
                
                container.innerHTML = listings.map(listing => this.createListingCard(listing)).join('');
            }
            
            createListingCard(listing) {
                const verifiedBadge = listing.seller_verified ? 
                    '<span class="badge badge-verified text-white ms-1"><i class="fas fa-check-circle"></i> Verified</span>' : '';
                
                const barterBadge = listing.allow_barter ? 
                    '<span class="badge bg-success position-absolute top-0 end-0 m-2">Barter Available</span>' : '';
                
                const price = listing.price == 0 ? 'Free' : `R${parseFloat(listing.price).toFixed(0)}`;
                
                return `
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card h-100">
                            <div class="position-relative">
                                <img src="${listing.image_url || this.getDefaultImage(listing.category_slug)}" 
                                     class="card-img-top" alt="${listing.title}" 
                                     style="height: 200px; object-fit: cover;"
                                     loading="lazy">
                                ${barterBadge}
                            </div>
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <h5 class="card-title">${listing.title}</h5>
                                    <span class="text-primary fw-bold">${price}</span>
                                </div>
                                <p class="card-text text-muted small">${listing.location} • ${listing.distance}</p>
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <span class="badge bg-light text-dark">${listing.category_name}</span>
                                        ${verifiedBadge}
                                    </div>
                                    <small class="text-muted">${listing.time_ago}</small>
                                </div>
                                <a href="product-detail.php?id=${listing.listing_id}" class="stretched-link"></a>
                            </div>
                        </div>
                    </div>
                `;
            }
            
            getDefaultImage(categorySlug) {
                const defaultImages = {
                    'produce': 'https://images.unsplash.com/photo-1606787366850-de6330128bfc?w=400',
                    'handicrafts': 'https://images.unsplash.com/photo-1560343090-f0409e92791a?w=400',
                    'clothing': 'https://images.unsplash.com/photo-1523275335684-37898b6baf30?w=400',
                    'electronics': 'https://images.unsplash.com/photo-1526170375885-4d8ecf77b99f?w=400',
                    'home': 'https://images.unsplash.com/photo-1556909114-f6e7ad7d3136?w=400',
                    'default': 'https://images.unsplash.com/photo-1551232864-3f0890e580d9?w=400'
                };
                
                return defaultImages[categorySlug] || defaultImages['default'];
            }
            
            renderPagination(pagination) {
                if (!pagination || pagination.totalPages <= 1) {
                    document.getElementById('paginationContainer').classList.add('d-none');
                    return;
                }
                
                document.getElementById('paginationContainer').classList.remove('d-none');
                const container = document.getElementById('pagination');
                
                let paginationHTML = '';
                
                // Previous button
                const prevDisabled = pagination.currentPage <= 1 ? 'disabled' : '';
                paginationHTML += `
                    <li class="page-item ${prevDisabled}">
                        <a class="page-link" href="#" data-page="${pagination.currentPage - 1}" ${prevDisabled ? 'tabindex="-1"' : ''}>
                            Previous
                        </a>
                    </li>
                `;
                
                // Page numbers
                const startPage = Math.max(1, pagination.currentPage - 2);
                const endPage = Math.min(pagination.totalPages, pagination.currentPage + 2);
                
                if (startPage > 1) {
                    paginationHTML += `<li class="page-item"><a class="page-link" href="#" data-page="1">1</a></li>`;
                    if (startPage > 2) {
                        paginationHTML += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
                    }
                }
                
                for (let i = startPage; i <= endPage; i++) {
                    const active = i === pagination.currentPage ? 'active' : '';
                    paginationHTML += `
                        <li class="page-item ${active}">
                            <a class="page-link" href="#" data-page="${i}">${i}</a>
                        </li>
                    `;
                }
                
                if (endPage < pagination.totalPages) {
                    if (endPage < pagination.totalPages - 1) {
                        paginationHTML += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
                    }
                    paginationHTML += `<li class="page-item"><a class="page-link" href="#" data-page="${pagination.totalPages}">${pagination.totalPages}</a></li>`;
                }
                
                // Next button
                const nextDisabled = pagination.currentPage >= pagination.totalPages ? 'disabled' : '';
                paginationHTML += `
                    <li class="page-item ${nextDisabled}">
                        <a class="page-link" href="#" data-page="${pagination.currentPage + 1}">Next</a>
                    </li>
                `;
                
                container.innerHTML = paginationHTML;
                
                // Bind pagination events
                container.querySelectorAll('a[data-page]').forEach(link => {
                    link.addEventListener('click', (e) => {
                        e.preventDefault();
                        if (!e.target.closest('.disabled')) {
                            this.currentPage = parseInt(e.target.dataset.page);
                            this.loadListings();
                        }
                    });
                });
            }
            
            updateResultsCount(total) {
                const start = ((this.currentPage - 1) * this.pageSize) + 1;
                const end = Math.min(this.currentPage * this.pageSize, total);
                
                document.getElementById('resultsCount').textContent = 
                    `Showing ${start}-${end} of ${total} listings`;
            }
            
            showError() {
                const container = document.getElementById('listingsGrid');
                container.innerHTML = `
                    <div class="col-12">
                        <div class="text-center py-5">
                            <i class="fas fa-exclamation-triangle fa-3x text-warning mb-3"></i>
                            <h5>Unable to Load Listings</h5>
                            <p class="text-muted">Please check your internet connection and try again.</p>
                            <button class="btn btn-primary" onclick="listingsManager.loadListings()">Try Again</button>
                        </div>
                    </div>
                `;
            }
        }
        
        // Initialize listings manager when page loads
        let listingsManager;
        document.addEventListener('DOMContentLoaded', function() {
            listingsManager = new ListingsManager();
        });
    </script>
</body>
</html>