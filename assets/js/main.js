/**
 * Edu C2C Marketplace - Main JavaScript
 * Supporting South Africa's Informal Economy
 * 
 * Features:
 * - Low data mode optimization
 * - Mobile-first responsive design
 * - Multi-language support (EN/AF/ZU)
 * - Offline functionality
 * - Progressive Web App features
 */

// =============================================
// GLOBAL CONFIGURATION
// =============================================

const EduvosC2C = {
    config: {
        apiBaseUrl: '/api/v1',
        supportPhone: '+27831234567',
        supportWhatsApp: '+27831234567',
        defaultLocation: 'Johannesburg, Gauteng',
        lowDataMode: localStorage.getItem('lowDataMode') === 'true',
        currentLanguage: localStorage.getItem('language') || 'en',
        offlineMode: !navigator.onLine,
        searchRadius: 10, // km
        maxImageSize: 5 * 1024 * 1024, // 5MB
        maxImagesPerListing: 5,
        currency: 'ZAR',
        currencySymbol: 'R'
    },
    
    // Translation strings
    translations: {
        en: {
            search: 'Search',
            category: 'Category',
            location: 'Location',
            price: 'Price',
            contact: 'Contact',
            verified: 'Verified',
            barter: 'Barter Available',
            sold: 'Sold',
            loading: 'Loading...',
            error: 'Something went wrong',
            retry: 'Retry',
            offline: 'You are offline',
            lowData: 'Low Data Mode',
            networkError: 'Network connection issue'
        },
        af: {
            search: 'Soek',
            category: 'Kategorie',
            location: 'Ligging',
            price: 'Prys',
            contact: 'Kontak',
            verified: 'Geverifieer',
            barter: 'Ruil Beskikbaar',
            sold: 'Verkoop',
            loading: 'Laai...',
            error: 'Iets het verkeerd gegaan',
            retry: 'Probeer weer',
            offline: 'Jy is vanlyn',
            lowData: 'Lae Data Modus',
            networkError: 'Netwerk verbinding probleem'
        },
        zu: {
            search: 'Sesha',
            category: 'Uhlobo',
            location: 'Indawo',
            price: 'Intengo',
            contact: 'Xhumana',
            verified: 'Kuqinisekisiwe',
            barter: 'Ukushintshanisa Kuyatholakala',
            sold: 'Kudayisiwe',
            loading: 'Kulayisha...',
            error: 'Kukhona okungahambi kahle',
            retry: 'Zama futhi',
            offline: 'Awukho ku-inthanethi',
            lowData: 'Imodi Yedatha Encane',
            networkError: 'Inkinga yoxhumano lwe-network'
        }
    },

    // State management
    state: {
        currentUser: null,
        cart: [],
        favorites: [],
        recentSearches: [],
        unreadMessages: 0,
        location: null,
        listings: [],
        filters: {}
    }
};

// =============================================
// UTILITY FUNCTIONS
// =============================================

const Utils = {
    // Get translation
    t(key) {
        const lang = EduvosC2C.config.currentLanguage;
        return EduvosC2C.translations[lang]?.[key] || EduvosC2C.translations.en[key] || key;
    },

    // Format currency
    formatCurrency(amount) {
        if (amount === 0) return 'Free';
        return `${EduvosC2C.config.currencySymbol}${parseFloat(amount).toFixed(2)}`;
    },

    // Format date for local context
    formatDate(date) {
        const d = new Date(date);
        const now = new Date();
        const diffTime = Math.abs(now - d);
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
        
        if (diffDays === 1) return 'Yesterday';
        if (diffDays < 7) return `${diffDays} days ago`;
        if (diffDays < 30) return `${Math.ceil(diffDays / 7)} weeks ago`;
        return d.toLocaleDateString();
    },

    // Calculate distance between coordinates
    calculateDistance(lat1, lon1, lat2, lon2) {
        const R = 6371; // Earth's radius in km
        const dLat = (lat2 - lat1) * Math.PI / 180;
        const dLon = (lon2 - lon1) * Math.PI / 180;
        const a = Math.sin(dLat/2) * Math.sin(dLat/2) +
                Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
                Math.sin(dLon/2) * Math.sin(dLon/2);
        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
        return R * c;
    },

    // Compress image for low data mode
    async compressImage(file, maxWidth = 800, quality = 0.8) {
        return new Promise((resolve) => {
            const canvas = document.createElement('canvas');
            const ctx = canvas.getContext('2d');
            const img = new Image();
            
            img.onload = () => {
                const ratio = Math.min(maxWidth / img.width, maxWidth / img.height);
                canvas.width = img.width * ratio;
                canvas.height = img.height * ratio;
                
                ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
                canvas.toBlob(resolve, 'image/jpeg', quality);
            };
            
            img.src = URL.createObjectURL(file);
        });
    },

    // Debounce function for search
    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    },

    // Show toast notification
    showToast(message, type = 'info', duration = 3000) {
        const toast = document.createElement('div');
        toast.className = `toast toast-${type} position-fixed top-0 end-0 m-3`;
        toast.style.zIndex = '9999';
        toast.innerHTML = `
            <div class="toast-header">
                <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'} me-2"></i>
                <strong class="me-auto">Edu C2C</strong>
                <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
            </div>
            <div class="toast-body">${message}</div>
        `;
        
        document.body.appendChild(toast);
        const bsToast = new bootstrap.Toast(toast);
        bsToast.show();
        
        setTimeout(() => {
            toast.remove();
        }, duration + 500);
    },

    // Handle offline status
    updateOfflineStatus() {
        EduvosC2C.config.offlineMode = !navigator.onLine;
        const offlineIndicator = document.getElementById('offlineIndicator');
        
        if (EduvosC2C.config.offlineMode) {
            if (!offlineIndicator) {
                const indicator = document.createElement('div');
                indicator.id = 'offlineIndicator';
                indicator.className = 'alert alert-warning position-fixed top-0 start-50 translate-middle-x';
                indicator.style.zIndex = '9998';
                indicator.innerHTML = `<i class="fas fa-wifi-slash me-2"></i> ${Utils.t('offline')}`;
                document.body.appendChild(indicator);
            }
        } else {
            if (offlineIndicator) {
                offlineIndicator.remove();
            }
        }
    }
};

// =============================================
// LOW DATA MODE FUNCTIONALITY
// =============================================

const LowDataMode = {
    init() {
        const toggle = document.getElementById('lowDataToggle');
        if (toggle) {
            toggle.addEventListener('click', this.toggle);
            this.updateUI();
        }
    },

    toggle() {
        EduvosC2C.config.lowDataMode = !EduvosC2C.config.lowDataMode;
        localStorage.setItem('lowDataMode', EduvosC2C.config.lowDataMode);
        LowDataMode.updateUI();
        Utils.showToast(
            `Low data mode ${EduvosC2C.config.lowDataMode ? 'enabled' : 'disabled'}`,
            'info'
        );
    },

    updateUI() {
        const toggle = document.getElementById('lowDataToggle');
        const body = document.body;
        
        if (EduvosC2C.config.lowDataMode) {
            body.classList.add('low-data-mode');
            if (toggle) toggle.classList.add('active');
            this.optimizeImages();
            this.disableAutoplay();
        } else {
            body.classList.remove('low-data-mode');
            if (toggle) toggle.classList.remove('active');
        }
    },

    optimizeImages() {
        const images = document.querySelectorAll('img');
        images.forEach(img => {
            if (!img.dataset.original) {
                img.dataset.original = img.src;
            }
            // Use smaller images in low data mode
            if (img.src.includes('w=1470')) {
                img.src = img.src.replace('w=1470', 'w=400');
            }
        });
    },

    disableAutoplay() {
        const videos = document.querySelectorAll('video[autoplay]');
        videos.forEach(video => {
            video.removeAttribute('autoplay');
            video.pause();
        });
    }
};

// =============================================
// GEOLOCATION FUNCTIONALITY
// =============================================

const LocationService = {
    async getCurrentLocation() {
        return new Promise((resolve, reject) => {
            if (!navigator.geolocation) {
                reject('Geolocation not supported');
                return;
            }

            navigator.geolocation.getCurrentPosition(
                position => {
                    const location = {
                        latitude: position.coords.latitude,
                        longitude: position.coords.longitude,
                        accuracy: position.coords.accuracy
                    };
                    EduvosC2C.state.location = location;
                    resolve(location);
                },
                error => {
                    console.warn('Location access denied:', error);
                    resolve(null);
                },
                { timeout: 10000, maximumAge: 300000 }
            );
        });
    },

    async reverseGeocode(lat, lng) {
        try {
            // In a real app, use a geocoding service
            // For now, return a default location
            return {
                city: 'Johannesburg',
                province: 'Gauteng',
                country: 'South Africa'
            };
        } catch (error) {
            console.error('Reverse geocoding failed:', error);
            return null;
        }
    }
};

// =============================================
// SEARCH FUNCTIONALITY
// =============================================

const SearchManager = {
    init() {
        const searchForm = document.querySelector('.input-group');
        const searchInput = document.querySelector('input[placeholder*="looking for"]');
        
        if (searchInput) {
            searchInput.addEventListener('input', Utils.debounce(this.handleSearch, 300));
            searchInput.addEventListener('keydown', this.handleSearchKeydown);
        }

        // Initialize search suggestions
        this.initSearchSuggestions();
    },

    handleSearch(event) {
        const query = event.target.value.trim();
        if (query.length > 2) {
            SearchManager.showSuggestions(query);
        } else {
            SearchManager.hideSuggestions();
        }
    },

    handleSearchKeydown(event) {
        if (event.key === 'Enter') {
            event.preventDefault();
            const query = event.target.value.trim();
            if (query) {
                SearchManager.performSearch(query);
            }
        }
    },

    async performSearch(query, filters = {}) {
        try {
            // Add to recent searches
            this.addToRecentSearches(query);
            
            // Show loading state
            this.showLoadingState();
            
            const searchParams = new URLSearchParams({
                q: query,
                location: EduvosC2C.state.location?.latitude ? 
                    `${EduvosC2C.state.location.latitude},${EduvosC2C.state.location.longitude}` : 
                    EduvosC2C.config.defaultLocation,
                radius: EduvosC2C.config.searchRadius,
                ...filters
            });

            // Simulate API call
            const results = await this.mockSearchAPI(query, filters);
            this.displaySearchResults(results);
            
        } catch (error) {
            console.error('Search failed:', error);
            Utils.showToast(Utils.t('networkError'), 'error');
        }
    },

    async mockSearchAPI(query, filters) {
        // Simulate API delay
        await new Promise(resolve => setTimeout(resolve, 500));
        
        // Mock search results based on query
        const mockResults = [
            {
                id: 1,
                title: 'Fresh Vegetables',
                price: 85,
                location: 'Johannesburg, 2km away',
                image: 'https://images.unsplash.com/photo-1606787366850-de6330128bfc?w=400',
                verified: true,
                barter: true,
                category: 'Produce'
            },
            {
                id: 2,
                title: 'Handmade Jewelry',
                price: 120,
                location: 'Cape Town, 5km away',
                image: 'https://images.unsplash.com/photo-1560343090-f0409e92791a?w=400',
                verified: false,
                barter: false,
                category: 'Handicrafts'
            }
        ];

        return mockResults.filter(item => 
            item.title.toLowerCase().includes(query.toLowerCase())
        );
    },

    showSuggestions(query) {
        const suggestions = this.generateSuggestions(query);
        // Implementation for showing search suggestions dropdown
        console.log('Suggestions for:', query, suggestions);
    },

    hideSuggestions() {
        const dropdown = document.querySelector('.search-suggestions');
        if (dropdown) {
            dropdown.style.display = 'none';
        }
    },

    generateSuggestions(query) {
        const categories = ['Fresh Produce', 'Handicrafts', 'Clothing', 'Electronics'];
        const recentSearches = EduvosC2C.state.recentSearches;
        
        return [
            ...recentSearches.filter(search => 
                search.toLowerCase().includes(query.toLowerCase())
            ).slice(0, 3),
            ...categories.filter(cat => 
                cat.toLowerCase().includes(query.toLowerCase())
            ).slice(0, 2)
        ];
    },

    addToRecentSearches(query) {
        const recent = EduvosC2C.state.recentSearches;
        const index = recent.indexOf(query);
        
        if (index > -1) {
            recent.splice(index, 1);
        }
        
        recent.unshift(query);
        
        if (recent.length > 10) {
            recent.pop();
        }
        
        localStorage.setItem('recentSearches', JSON.stringify(recent));
    },

    showLoadingState() {
        const container = document.querySelector('.row .col-lg-9, #listingsGrid');
        if (container) {
            container.innerHTML = `
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">${Utils.t('loading')}</span>
                    </div>
                    <p class="mt-3">${Utils.t('loading')}</p>
                </div>
            `;
        }
    },

    displaySearchResults(results) {
        const container = document.querySelector('#listingsGrid');
        if (!container) return;

        if (results.length === 0) {
            container.innerHTML = `
                <div class="col-12">
                    <div class="text-center py-5">
                        <i class="fas fa-search fa-3x text-muted mb-3"></i>
                        <h5>No results found</h5>
                        <p class="text-muted">Try adjusting your search terms or filters</p>
                    </div>
                </div>
            `;
            return;
        }

        container.innerHTML = results.map(item => this.createListingCard(item)).join('');
    },

    createListingCard(listing) {
        return `
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card h-100">
                    <div class="position-relative">
                        <img src="${listing.image}" class="card-img-top" alt="${listing.title}" loading="lazy">
                        ${listing.barter ? '<span class="badge bg-success position-absolute top-0 end-0 m-2">Barter Available</span>' : ''}
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <h5 class="card-title">${listing.title}</h5>
                            <span class="text-primary fw-bold">${Utils.formatCurrency(listing.price)}</span>
                        </div>
                        <p class="card-text text-muted small">${listing.location}</p>
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="badge bg-light text-dark">${listing.category}</span>
                                ${listing.verified ? '<span class="badge badge-verified text-white ms-1"><i class="fas fa-check-circle"></i> Verified</span>' : ''}
                            </div>
                            <small class="text-muted">${Utils.formatDate(new Date())}</small>
                        </div>
                        <a href="product-detail.php?id=${listing.id}" class="stretched-link"></a>
                    </div>
                </div>
            </div>
        `;
    },

    initSearchSuggestions() {
        // Load recent searches from localStorage
        const stored = localStorage.getItem('recentSearches');
        if (stored) {
            EduvosC2C.state.recentSearches = JSON.parse(stored);
        }
    }
};

// =============================================
// USER INTERFACE ENHANCEMENTS
// =============================================

const UIEnhancements = {
    init() {
        this.initBackToTop();
        this.initImageLazyLoading();
        this.initFormValidation();
        this.initPhoneNumberFormatting();
        this.initOfflineDetection();
        this.initPWAPrompt();
    },

    initBackToTop() {
        const backToTop = document.createElement('button');
        backToTop.className = 'btn btn-primary position-fixed bottom-0 end-0 m-3 rounded-circle';
        backToTop.style.cssText = 'width: 50px; height: 50px; z-index: 1000; display: none;';
        backToTop.innerHTML = '<i class="fas fa-arrow-up"></i>';
        backToTop.title = 'Back to top';
        
        backToTop.addEventListener('click', () => {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
        
        document.body.appendChild(backToTop);
        
        window.addEventListener('scroll', () => {
            if (window.pageYOffset > 300) {
                backToTop.style.display = 'block';
            } else {
                backToTop.style.display = 'none';
            }
        });
    },

    initImageLazyLoading() {
        if ('IntersectionObserver' in window) {
            const imageObserver = new IntersectionObserver((entries, observer) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        img.src = img.dataset.src || img.src;
                        img.classList.remove('lazy');
                        observer.unobserve(img);
                    }
                });
            });

            document.querySelectorAll('img[loading="lazy"]').forEach(img => {
                imageObserver.observe(img);
            });
        }
    },

    initFormValidation() {
        const forms = document.querySelectorAll('form');
        forms.forEach(form => {
            form.addEventListener('submit', function(event) {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            });
        });
    },

    initPhoneNumberFormatting() {
        const phoneInputs = document.querySelectorAll('input[type="tel"]');
        phoneInputs.forEach(input => {
            input.addEventListener('input', function(e) {
                let value = e.target.value.replace(/\D/g, '');
                if (value.startsWith('27')) {
                    value = value.substring(2);
                } else if (value.startsWith('0')) {
                    value = value.substring(1);
                }
                
                // Format as: 083 123 4567
                if (value.length >= 3) {
                    value = value.substring(0, 3) + ' ' + 
                           value.substring(3, 6) + ' ' + 
                           value.substring(6, 10);
                }
                
                e.target.value = value.trim();
            });
        });
    },

    initOfflineDetection() {
        window.addEventListener('online', Utils.updateOfflineStatus);
        window.addEventListener('offline', Utils.updateOfflineStatus);
        Utils.updateOfflineStatus();
    },

    initPWAPrompt() {
        let deferredPrompt;
        
        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            deferredPrompt = e;
            
            // Show install prompt after user interaction
            setTimeout(() => {
                if (deferredPrompt && !localStorage.getItem('pwaPromptShown')) {
                    this.showPWAPrompt(deferredPrompt);
                    localStorage.setItem('pwaPromptShown', 'true');
                }
            }, 30000); // Show after 30 seconds
        });
    },

    showPWAPrompt(deferredPrompt) {
        const modal = document.createElement('div');
        modal.className = 'modal fade';
        modal.innerHTML = `
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Install Edu C2C</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body text-center">
                        <i class="fas fa-mobile-alt fa-3x text-primary mb-3"></i>
                        <p>Install our app for a better experience!</p>
                        <ul class="list-unstyled text-start">
                            <li><i class="fas fa-check text-success me-2"></i> Faster loading</li>
                            <li><i class="fas fa-check text-success me-2"></i> Offline access</li>
                            <li><i class="fas fa-check text-success me-2"></i> Push notifications</li>
                        </ul>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Maybe Later</button>
                        <button type="button" class="btn btn-primary" id="installPWA">Install Now</button>
                    </div>
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
        const bsModal = new bootstrap.Modal(modal);
        bsModal.show();
        
        document.getElementById('installPWA').addEventListener('click', async () => {
            bsModal.hide();
            deferredPrompt.prompt();
            const { outcome } = await deferredPrompt.userChoice;
            console.log(`PWA install prompt outcome: ${outcome}`);
            deferredPrompt = null;
        });
        
        modal.addEventListener('hidden.bs.modal', () => {
            modal.remove();
        });
    }
};

// =============================================
// FAVORITES MANAGEMENT
// =============================================

const FavoritesManager = {
    init() {
        this.loadFavorites();
        this.bindFavoriteButtons();
    },

    loadFavorites() {
        const stored = localStorage.getItem('favorites');
        if (stored) {
            EduvosC2C.state.favorites = JSON.parse(stored);
        }
    },

    bindFavoriteButtons() {
        document.addEventListener('click', (e) => {
            if (e.target.closest('.btn-favorite')) {
                e.preventDefault();
                const btn = e.target.closest('.btn-favorite');
                const listingId = btn.dataset.listingId;
                this.toggleFavorite(listingId, btn);
            }
        });
    },

    toggleFavorite(listingId, button) {
        const favorites = EduvosC2C.state.favorites;
        const index = favorites.indexOf(listingId);
        
        if (index > -1) {
            favorites.splice(index, 1);
            button.innerHTML = '<i class="far fa-heart"></i>';
            button.classList.remove('favorited');
            Utils.showToast('Removed from favorites', 'info');
        } else {
            favorites.push(listingId);
            button.innerHTML = '<i class="fas fa-heart"></i>';
            button.classList.add('favorited');
            Utils.showToast('Added to favorites', 'success');
        }
        
        localStorage.setItem('favorites', JSON.stringify(favorites));
    },

    isFavorite(listingId) {
        return EduvosC2C.state.favorites.includes(listingId);
    }
};

// =============================================
// MESSAGING SYSTEM
// =============================================

const MessagingSystem = {
    init() {
        this.bindMessageButtons();
        this.loadUnreadCount();
    },

    bindMessageButtons() {
        document.addEventListener('click', (e) => {
            if (e.target.closest('.btn-message, .btn-chat')) {
                e.preventDefault();
                const btn = e.target.closest('.btn-message, .btn-chat');
                const sellerId = btn.dataset.sellerId;
                const listingId = btn.dataset.listingId;
                this.openChatModal(sellerId, listingId);
            }
        });
    },

    openChatModal(sellerId, listingId) {
        // Check if user is logged in
        if (!EduvosC2C.state.currentUser) {
            Utils.showToast('Please log in to send messages', 'warning');
            return;
        }

        const modal = document.createElement('div');
        modal.className = 'modal fade';
        modal.innerHTML = `
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Send Message</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Message</label>
                            <textarea class="form-control" rows="4" placeholder="Hi, I'm interested in your item..."></textarea>
                        </div>
                        <div class="d-flex gap-2">
                            <button class="btn btn-success flex-fill">
                                <i class="fab fa-whatsapp me-2"></i> WhatsApp
                            </button>
                            <button class="btn btn-primary flex-fill">
                                <i class="fas fa-phone me-2"></i> Call
                            </button>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-primary">Send Message</button>
                    </div>
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
        const bsModal = new bootstrap.Modal(modal);
        bsModal.show();
        
        modal.addEventListener('hidden.bs.modal', () => {
            modal.remove();
        });
    },

    loadUnreadCount() {
        // Simulate loading unread message count
        EduvosC2C.state.unreadMessages = 3;
        this.updateUnreadBadge();
    },

    updateUnreadBadge() {
        const badges = document.querySelectorAll('.unread-count');
        badges.forEach(badge => {
            if (EduvosC2C.state.unreadMessages > 0) {
                badge.textContent = EduvosC2C.state.unreadMessages;
                badge.style.display = 'inline';
            } else {
                badge.style.display = 'none';
            }
        });
    }
};

// =============================================
// LANGUAGE SWITCHING
// =============================================

const LanguageManager = {
    init() {
        this.bindLanguageButtons();
        this.updateLanguageUI();
    },

    bindLanguageButtons() {
        document.addEventListener('click', (e) => {
            if (e.target.closest('.language-btn')) {
                e.preventDefault();
                const btn = e.target.closest('.language-btn');
                const lang = btn.dataset.lang;
                this.switchLanguage(lang);
            }
        });
    },

    switchLanguage(lang) {
        if (EduvosC2C.translations[lang]) {
            EduvosC2C.config.currentLanguage = lang;
            localStorage.setItem('language', lang);
            this.updateLanguageUI();
            Utils.showToast(`Language changed to ${lang.toUpperCase()}`, 'success');
        }
    },

    updateLanguageUI() {
        // Update all translatable elements
        document.querySelectorAll('[data-translate]').forEach(element => {
            const key = element.dataset.translate;
            element.textContent = Utils.t(key);
        });

        // Update placeholders
        document.querySelectorAll('[data-translate-placeholder]').forEach(element => {
            const key = element.dataset.translatePlaceholder;
            element.placeholder = Utils.t(key);
        });

        // Update document language attribute
        document.documentElement.lang = EduvosC2C.config.currentLanguage;
    }
};

// =============================================
// FORM HANDLING
// =============================================

const FormHandler = {
    init() {
        this.bindForms();
        this.initImageUpload();
        this.initLocationAutocomplete();
    },

    bindForms() {
        // Login form
        const loginForm = document.getElementById('loginForm');
        if (loginForm) {
            loginForm.addEventListener('submit', this.handleLogin);
        }

        // Registration form
        const registerForm = document.getElementById('registerForm');
        if (registerForm) {
            registerForm.addEventListener('submit', this.handleRegister);
        }

        // Listing form
        const listingForm = document.getElementById('listingForm');
        if (listingForm) {
            listingForm.addEventListener('submit', this.handleCreateListing);
        }

        // Contact form
        const contactForm = document.getElementById('contactForm');
        if (contactForm) {
            contactForm.addEventListener('submit', this.handleContact);
        }
    },

    async handleLogin(event) {
        event.preventDefault();
        const formData = new FormData(event.target);
        const data = Object.fromEntries(formData);

        try {
            // Show loading state
            const submitBtn = event.target.querySelector('button[type="submit"]');
            const originalText = submitBtn.textContent;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Signing in...';

            // Simulate API call
            await new Promise(resolve => setTimeout(resolve, 1500));
            
            // Mock successful login
            EduvosC2C.state.currentUser = {
                id: 1,
                name: 'John Doe',
                email: data.loginEmail,
                verified: true,
                type: 'seller'
            };

            localStorage.setItem('currentUser', JSON.stringify(EduvosC2C.state.currentUser));
            Utils.showToast('Welcome back!', 'success');
            
            // Redirect to dashboard or previous page
            setTimeout(() => {
                window.location.href = 'index.php';
            }, 1000);

        } catch (error) {
            Utils.showToast('Login failed. Please try again.', 'error');
        } finally {
            const submitBtn = event.target.querySelector('button[type="submit"]');
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
        }
    },

    async handleRegister(event) {
        event.preventDefault();
        const formData = new FormData(event.target);
        const data = Object.fromEntries(formData);

        // Validate passwords match
        if (data.registerPassword !== data.registerConfirm) {
            Utils.showToast('Passwords do not match', 'error');
            return;
        }

        try {
            const submitBtn = event.target.querySelector('button[type="submit"]');
            const originalText = submitBtn.textContent;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Creating account...';

            // Simulate API call
            await new Promise(resolve => setTimeout(resolve, 2000));
            
            Utils.showToast('Account created successfully! Please verify your phone number.', 'success');
            
            setTimeout(() => {
                window.location.href = 'login.html';
            }, 2000);

        } catch (error) {
            Utils.showToast('Registration failed. Please try again.', 'error');
        } finally {
            const submitBtn = event.target.querySelector('button[type="submit"]');
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
        }
    },

    async handleCreateListing(event) {
        event.preventDefault();
        const formData = new FormData(event.target);
        const data = Object.fromEntries(formData);

        try {
            const submitBtn = event.target.querySelector('button[type="submit"]');
            const originalText = submitBtn.textContent;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Publishing...';

            // Validate required fields
            if (!data.itemTitle || !data.itemDescription || !data.itemPrice) {
                Utils.showToast('Please fill in all required fields', 'error');
                return;
            }

            // Process images if any
            const images = await this.processUploadedImages();
            
            // Simulate API call
            await new Promise(resolve => setTimeout(resolve, 1500));
            
            Utils.showToast('Listing published successfully!', 'success');
            
            setTimeout(() => {
                window.location.href = 'listings.php';
            }, 1000);

        } catch (error) {
            Utils.showToast('Failed to publish listing. Please try again.', 'error');
        } finally {
            const submitBtn = event.target.querySelector('button[type="submit"]');
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
        }
    },

    async handleContact(event) {
        event.preventDefault();
        const formData = new FormData(event.target);
        const data = Object.fromEntries(formData);

        try {
            const submitBtn = event.target.querySelector('button[type="submit"]');
            const originalText = submitBtn.textContent;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Sending...';

            // Simulate API call
            await new Promise(resolve => setTimeout(resolve, 1000));
            
            Utils.showToast('Message sent successfully! We\'ll get back to you soon.', 'success');
            event.target.reset();

        } catch (error) {
            Utils.showToast('Failed to send message. Please try again.', 'error');
        } finally {
            const submitBtn = event.target.querySelector('button[type="submit"]');
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
        }
    },

    initImageUpload() {
        const dropzone = document.querySelector('.dropzone');
        const fileInput = document.createElement('input');
        fileInput.type = 'file';
        fileInput.multiple = true;
        fileInput.accept = 'image/*';
        fileInput.style.display = 'none';

        if (dropzone) {
            const button = dropzone.querySelector('button');
            if (button) {
                button.addEventListener('click', () => fileInput.click());
            }

            // Drag and drop functionality
            dropzone.addEventListener('dragover', (e) => {
                e.preventDefault();
                dropzone.classList.add('dragover');
            });

            dropzone.addEventListener('dragleave', () => {
                dropzone.classList.remove('dragover');
            });

            dropzone.addEventListener('drop', (e) => {
                e.preventDefault();
                dropzone.classList.remove('dragover');
                this.handleImageFiles(e.dataTransfer.files);
            });

            fileInput.addEventListener('change', (e) => {
                this.handleImageFiles(e.target.files);
            });
        }
    },

    async handleImageFiles(files) {
        const photoPreviews = document.getElementById('photoPreviews');
        if (!photoPreviews) return;

        const validFiles = Array.from(files).filter(file => {
            if (!file.type.startsWith('image/')) {
                Utils.showToast(`${file.name} is not an image file`, 'error');
                return false;
            }
            if (file.size > EduvosC2C.config.maxImageSize) {
                Utils.showToast(`${file.name} is too large (max 5MB)`, 'error');
                return false;
            }
            return true;
        });

        if (validFiles.length > EduvosC2C.config.maxImagesPerListing) {
            Utils.showToast(`Maximum ${EduvosC2C.config.maxImagesPerListing} images allowed`, 'error');
            return;
        }

        photoPreviews.innerHTML = '';

        for (let i = 0; i < validFiles.length; i++) {
            const file = validFiles[i];
            let processedFile = file;

            // Compress image if in low data mode
            if (EduvosC2C.config.lowDataMode) {
                processedFile = await Utils.compressImage(file);
            }

            const preview = await this.createImagePreview(processedFile, i);
            photoPreviews.appendChild(preview);
        }
    },

    async createImagePreview(file, index) {
        return new Promise((resolve) => {
            const reader = new FileReader();
            reader.onload = (e) => {
                const col = document.createElement('div');
                col.className = 'col-4';
                col.innerHTML = `
                    <div class="position-relative">
                        <img src="${e.target.result}" class="img-thumbnail w-100" alt="Preview ${index + 1}">
                        <button type="button" class="btn btn-sm btn-danger position-absolute top-0 end-0 m-1" onclick="this.parentElement.parentElement.remove()">
                            <i class="fas fa-times"></i>
                        </button>
                        ${index === 0 ? '<span class="badge bg-primary position-absolute bottom-0 start-0 m-1">Main</span>' : ''}
                    </div>
                `;
                resolve(col);
            };
            reader.readAsDataURL(file);
        });
    },

    async processUploadedImages() {
        const previews = document.querySelectorAll('#photoPreviews img');
        const images = [];

        for (const img of previews) {
            // In a real app, upload to server and get URL
            images.push({
                url: img.src,
                isPrimary: img.parentElement.querySelector('.badge') !== null
            });
        }

        return images;
    },

    initLocationAutocomplete() {
        const locationInputs = document.querySelectorAll('input[placeholder*="location"], select[id*="location"]');
        
        locationInputs.forEach(input => {
            if (input.tagName === 'INPUT') {
                input.addEventListener('input', Utils.debounce(this.handleLocationSearch, 300));
            }
        });
    },

    handleLocationSearch(event) {
        const query = event.target.value;
        if (query.length > 2) {
            // Implement location autocomplete
            console.log('Location search:', query);
        }
    }
};

// =============================================
// ANALYTICS & TRACKING
// =============================================

const Analytics = {
    init() {
        this.trackPageView();
        this.bindTrackingEvents();
    },

    trackPageView() {
        const page = window.location.pathname;
        console.log('Page view:', page);
        
        // In a real app, send to analytics service
        this.sendEvent('page_view', {
            page: page,
            timestamp: new Date().toISOString(),
            user_agent: navigator.userAgent,
            language: EduvosC2C.config.currentLanguage
        });
    },

    bindTrackingEvents() {
        // Track search events
        document.addEventListener('search', (e) => {
            this.sendEvent('search', {
                query: e.detail.query,
                filters: e.detail.filters
            });
        });

        // Track listing views
        document.addEventListener('click', (e) => {
            const listingLink = e.target.closest('a[href*="product-detail"]');
            if (listingLink) {
                const url = new URL(listingLink.href);
                const listingId = url.searchParams.get('id');
                this.sendEvent('listing_view', { listing_id: listingId });
            }
        });

        // Track button clicks
        document.addEventListener('click', (e) => {
            const button = e.target.closest('button, .btn');
            if (button && button.dataset.track) {
                this.sendEvent('button_click', {
                    action: button.dataset.track,
                    location: window.location.pathname
                });
            }
        });
    },

    sendEvent(event, data) {
        // In a real app, send to analytics service (Google Analytics, Mixpanel, etc.)
        console.log('Analytics event:', event, data);
        
        // Store locally for offline sync
        const events = JSON.parse(localStorage.getItem('analyticsEvents') || '[]');
        events.push({
            event,
            data,
            timestamp: new Date().toISOString()
        });
        
        // Keep only last 100 events
        if (events.length > 100) {
            events.splice(0, events.length - 100);
        }
        
        localStorage.setItem('analyticsEvents', JSON.stringify(events));
    }
};

// =============================================
// PERFORMANCE MONITORING
// =============================================

const PerformanceMonitor = {
    init() {
        this.measurePageLoad();
        this.monitorNetworkSpeed();
        this.detectSlowOperations();
    },

    measurePageLoad() {
        window.addEventListener('load', () => {
            const perfData = performance.getEntriesByType('navigation')[0];
            const loadTime = perfData.loadEventEnd - perfData.fetchStart;
            
            console.log('Page load time:', loadTime + 'ms');
            
            if (loadTime > 5000) { // Slow page load
                this.suggestOptimizations();
            }
        });
    },

    monitorNetworkSpeed() {
        if ('connection' in navigator) {
            const connection = navigator.connection;
            const isSlowConnection = connection.effectiveType === 'slow-2g' || 
                                   connection.effectiveType === '2g';
            
            if (isSlowConnection && !EduvosC2C.config.lowDataMode) {
                this.suggestLowDataMode();
            }
        }
    },

    detectSlowOperations() {
        const originalFetch = window.fetch;
        window.fetch = async (...args) => {
            const start = performance.now();
            const result = await originalFetch(...args);
            const duration = performance.now() - start;
            
            if (duration > 3000) { // Slow API call
                console.warn('Slow API call detected:', args[0], duration + 'ms');
                Utils.showToast('Network is slow. Consider enabling low data mode.', 'warning');
            }
            
            return result;
        };
    },

    suggestOptimizations() {
        if (!localStorage.getItem('optimizationSuggested')) {
            Utils.showToast('Your connection seems slow. Try low data mode for better performance.', 'info', 5000);
            localStorage.setItem('optimizationSuggested', 'true');
        }
    },

    suggestLowDataMode() {
        if (!localStorage.getItem('lowDataSuggested')) {
            const modal = document.createElement('div');
            modal.className = 'modal fade';
            modal.innerHTML = `
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Slow Connection Detected</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <p>We've detected a slow internet connection. Would you like to enable low data mode for a better experience?</p>
                            <ul class="list-unstyled">
                                <li><i class="fas fa-check text-success me-2"></i> Smaller images</li>
                                <li><i class="fas fa-check text-success me-2"></i> Reduced data usage</li>
                                <li><i class="fas fa-check text-success me-2"></i> Faster loading</li>
                            </ul>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">No Thanks</button>
                            <button type="button" class="btn btn-primary" id="enableLowData">Enable Low Data Mode</button>
                        </div>
                    </div>
                </div>
            `;
            
            document.body.appendChild(modal);
            const bsModal = new bootstrap.Modal(modal);
            bsModal.show();
            
            document.getElementById('enableLowData').addEventListener('click', () => {
                LowDataMode.toggle();
                bsModal.hide();
            });
            
            modal.addEventListener('hidden.bs.modal', () => {
                modal.remove();
            });
            
            localStorage.setItem('lowDataSuggested', 'true');
        }
    }
};

// =============================================
// INITIALIZATION
// =============================================

document.addEventListener('DOMContentLoaded', function() {
    console.log('Edu C2C Marketplace - Initializing...');
    
    // Initialize all modules
    LowDataMode.init();
    SearchManager.init();
    UIEnhancements.init();
    FavoritesManager.init();
    MessagingSystem.init();
    LanguageManager.init();
    FormHandler.init();
    Analytics.init();
    PerformanceMonitor.init();
    
    // Get user location
    LocationService.getCurrentLocation().then(location => {
        if (location) {
            console.log('User location obtained:', location);
        }
    });
    
    // Load current user if exists
    const storedUser = localStorage.getItem('currentUser');
    if (storedUser) {
        EduvosC2C.state.currentUser = JSON.parse(storedUser);
        console.log('User logged in:', EduvosC2C.state.currentUser);
    }
    
    // Service Worker registration for PWA
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('/sw.js')
            .then(registration => {
                console.log('SW registered:', registration);
            })
            .catch(error => {
                console.log('SW registration failed:', error);
            });
    }
    
    console.log('Edu C2C Marketplace - Ready!');
});

// =============================================
// GLOBAL ERROR HANDLING
// =============================================

window.addEventListener('error', (event) => {
    console.error('Global error:', event.error);
    
    // Send error to monitoring service
    Analytics.sendEvent('javascript_error', {
        message: event.error.message,
        stack: event.error.stack,
        filename: event.filename,
        lineno: event.lineno,
        colno: event.colno
    });
});

window.addEventListener('unhandledrejection', (event) => {
    console.error('Unhandled promise rejection:', event.reason);
    
    Analytics.sendEvent('promise_rejection', {
        reason: event.reason.toString()
    });
});

// =============================================
// EXPORT FOR TESTING
// =============================================

if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        EduvosC2C,
        Utils,
        LowDataMode,
        SearchManager,
        LocationService,
        FavoritesManager,
        MessagingSystem,
        FormHandler,
        Analytics
    };
}

/**
 * Enhanced Listings Page Integration
 * Extends the main.js functionality for the listings page
 */


if (typeof EduvosC2C !== 'undefined') {
    EduvosC2C.listings = {
        manager: null,
        config: {
            apiEndpoint: 'server/get_listings.php',
            categoriesEndpoint: 'server/get_categories.php',
            searchEndpoint: 'server/search_listings.php',
            defaultPageSize: 12,
            maxPageSize: 50
        }
    };
}

// Enhanced Listings Manager that integrates with existing functionality
/**
 * Fixed Listings Manager JavaScript
 * Handles listings display with proper error handling and JSON parsing
 */

class ListingsManager {
    constructor() {
        this.currentPage = 1;
        this.pageSize = 12;
        this.isLoading = false;
        this.filters = {
            search: '',
            category: '',
            location: '',
            minPrice: '',
            maxPrice: '',
            condition: '',
            verified: false,
            barter: false,
            offers: false,
            sort: 'newest'
        };
        
        this.init();
    }
    
    init() {
        this.bindEvents();
        this.loadListings();
        this.loadFilters();
    }
    
    bindEvents() {
        // Search form
        const searchForm = document.getElementById('searchForm');
        if (searchForm) {
            searchForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.handleSearch();
            });
        }
        
        // Filter form
        const filterForm = document.getElementById('filterForm');
        if (filterForm) {
            filterForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.handleFilter();
            });
        }
        
        // Sort dropdown
        const sortSelect = document.getElementById('sortSelect');
        if (sortSelect) {
            sortSelect.addEventListener('change', (e) => {
                this.filters.sort = e.target.value;
                this.currentPage = 1;
                this.loadListings();
            });
        }
        
        // Clear filters button
        const clearFiltersBtn = document.getElementById('clearFilters');
        if (clearFiltersBtn) {
            clearFiltersBtn.addEventListener('click', () => {
                this.clearFilters();
            });
        }
    }
    
    async loadListings() {
        if (this.isLoading) return;
        
        this.isLoading = true;
        this.showLoading();
        
        try {
            const params = new URLSearchParams({
                page: this.currentPage,
                limit: this.pageSize,
                ...this.filters
            });
            
            // Remove empty filters
            for (const [key, value] of params.entries()) {
                if (!value || value === 'false') {
                    params.delete(key);
                }
            }
            
            console.log('Loading listings with params:', params.toString());
            
            const response = await fetch(`server/get_listings.php?${params.toString()}`, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                }
            });
            
            // Check if response is ok
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            // Get response text first to debug JSON parsing issues
            const responseText = await response.text();
            console.log('Raw response:', responseText);
            
            // Check if response is empty
            if (!responseText.trim()) {
                throw new Error('Empty response received from server');
            }
            
            // Try to parse JSON
            let data;
            try {
                data = JSON.parse(responseText);
            } catch (parseError) {
                console.error('JSON Parse Error:', parseError);
                console.error('Response text:', responseText);
                throw new Error('Invalid JSON response from server');
            }
            
            console.log('Parsed data:', data);
            
            if (data.success) {
                this.renderListings(data.data.listings);
                this.renderPagination(data.data.pagination);
                this.updateResultsCount(data.data.total);
            } else {
                throw new Error(data.error || 'Failed to load listings');
            }
            
        } catch (error) {
            console.error('Error loading listings:', error);
            this.showError(error.message);
        } finally {
            this.isLoading = false;
            this.hideLoading();
        }
    }
    
    renderListings(listings) {
        const container = document.getElementById('listingsGrid');
        if (!container) {
            console.error('Listings container not found');
            return;
        }
        
        if (!listings || listings.length === 0) {
            container.innerHTML = `
                <div class="col-12">
                    <div class="text-center py-5">
                        <i class="fas fa-search fa-3x text-muted mb-3"></i>
                        <h5>No listings found</h5>
                        <p class="text-muted">Try adjusting your search criteria or browse all listings.</p>
                        <button class="btn btn-primary" onclick="window.listingsManager?.clearFilters()">
                            Clear Filters
                        </button>
                    </div>
                </div>
            `;
            return;
        }
        
        const listingsHTML = listings.map(listing => `
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card h-100 listing-card">
                    <div class="position-relative">
                        <img src="${this.escapeHtml(listing.image_url || '/assets/images/placeholder.jpg')}" 
                             class="card-img-top" alt="${this.escapeHtml(listing.title)}"
                             style="height: 250px; object-fit: cover;"
                             onerror="this.src='/assets/images/placeholder.jpg'">
                        ${listing.is_featured ? '<span class="badge bg-warning position-absolute top-0 start-0 m-2"><i class="fas fa-star me-1"></i>Featured</span>' : ''}
                        ${listing.seller_verified ? '<span class="badge bg-success position-absolute top-0 end-0 m-2"><i class="fas fa-check-circle me-1"></i>Verified</span>' : ''}
                    </div>
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title">${this.escapeHtml(listing.title)}</h5>
                        <p class="card-text text-muted flex-grow-1">${this.escapeHtml(listing.description)}</p>
                        <div class="mb-2">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="h5 text-primary mb-0">${this.escapeHtml(listing.price)}</span>
                                ${listing.allow_barter ? '<span class="badge bg-info"><i class="fas fa-exchange-alt me-1"></i>Barter</span>' : ''}
                            </div>
                        </div>
                        <div class="mb-2">
                            <small class="text-muted">
                                <i class="fas fa-map-marker-alt me-1"></i>${this.escapeHtml(listing.location)}
                            </small>
                        </div>
                        <div class="mb-3">
                            <small class="text-muted">
                                <i class="fas fa-clock me-1"></i>${this.escapeHtml(listing.time_ago)}
                                <span class="mx-2">•</span>
                                <i class="fas fa-eye me-1"></i>${listing.views_count} views
                            </small>
                        </div>
                        <div class="d-flex gap-2">
                            <a href="${listing.url}" class="btn btn-outline-primary flex-grow-1">
                                <i class="fas fa-eye me-1"></i>View Details
                            </a>
                            <button class="btn btn-outline-secondary" onclick="toggleFavorite(${listing.listing_id})" title="Add to Favorites">
                                <i class="fas fa-heart"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `).join('');
        
        container.innerHTML = listingsHTML;
    }
    
    renderPagination(pagination) {
        const container = document.getElementById('paginationContainer');
        if (!container || !pagination) return;
        
        if (pagination.totalPages <= 1) {
            container.innerHTML = '';
            return;
        }
        
        let paginationHTML = '<nav aria-label="Listings pagination"><ul class="pagination justify-content-center">';
        
        // Previous button
        const prevDisabled = pagination.currentPage <= 1 ? 'disabled' : '';
        paginationHTML += `
            <li class="page-item ${prevDisabled}">
                <a class="page-link" href="#" data-page="${pagination.currentPage - 1}" ${prevDisabled ? 'tabindex="-1"' : ''}>
                    <i class="fas fa-chevron-left"></i> Previous
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
            const isActive = i === pagination.currentPage ? 'active' : '';
            paginationHTML += `
                <li class="page-item ${isActive}">
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
                <a class="page-link" href="#" data-page="${pagination.currentPage + 1}" ${nextDisabled ? 'tabindex="-1"' : ''}>
                    Next <i class="fas fa-chevron-right"></i>
                </a>
            </li>
        `;
        
        paginationHTML += '</ul></nav>';
        container.innerHTML = paginationHTML;
        
        // Bind pagination events
        container.querySelectorAll('a[data-page]').forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                if (!e.target.closest('.disabled')) {
                    this.currentPage = parseInt(e.target.dataset.page);
                    this.loadListings();
                    
                    // Scroll to top of listings
                    document.getElementById('listingsGrid').scrollIntoView({ 
                        behavior: 'smooth', 
                        block: 'start' 
                    });
                }
            });
        });
    }
    
    updateResultsCount(total) {
        const resultsCount = document.getElementById('resultsCount');
        if (!resultsCount) return;
        
        const start = ((this.currentPage - 1) * this.pageSize) + 1;
        const end = Math.min(this.currentPage * this.pageSize, total);
        
        if (total === 0) {
            resultsCount.textContent = 'No listings found';
        } else if (total === 1) {
            resultsCount.textContent = '1 listing found';
        } else {
            resultsCount.textContent = `Showing ${start}-${end} of ${total.toLocaleString()} listings`;
        }
    }
    
    handleSearch() {
        const searchInput = document.getElementById('searchInput');
        if (searchInput) {
            this.filters.search = searchInput.value.trim();
            this.currentPage = 1;
            this.loadListings();
        }
    }
    
    handleFilter() {
        // Get filter values from form
        const categorySelect = document.getElementById('categoryFilter');
        const locationInput = document.getElementById('locationFilter');
        const minPriceInput = document.getElementById('minPrice');
        const maxPriceInput = document.getElementById('maxPrice');
        const conditionSelect = document.getElementById('conditionFilter');
        const verifiedCheck = document.getElementById('verifiedOnly');
        const barterCheck = document.getElementById('barterOnly');
        const offersCheck = document.getElementById('offersOnly');
        
        if (categorySelect) this.filters.category = categorySelect.value;
        if (locationInput) this.filters.location = locationInput.value.trim();
        if (minPriceInput) this.filters.minPrice = minPriceInput.value;
        if (maxPriceInput) this.filters.maxPrice = maxPriceInput.value;
        if (conditionSelect) this.filters.condition = conditionSelect.value;
        if (verifiedCheck) this.filters.verified = verifiedCheck.checked;
        if (barterCheck) this.filters.barter = barterCheck.checked;
        if (offersCheck) this.filters.offers = offersCheck.checked;
        
        this.currentPage = 1;
        this.loadListings();
    }
    
    clearFilters() {
        // Reset filters
        this.filters = {
            search: '',
            category: '',
            location: '',
            minPrice: '',
            maxPrice: '',
            condition: '',
            verified: false,
            barter: false,
            offers: false,
            sort: 'newest'
        };
        
        // Reset form elements
        const searchInput = document.getElementById('searchInput');
        const categorySelect = document.getElementById('categoryFilter');
        const locationInput = document.getElementById('locationFilter');
        const minPriceInput = document.getElementById('minPrice');
        const maxPriceInput = document.getElementById('maxPrice');
        const conditionSelect = document.getElementById('conditionFilter');
        const verifiedCheck = document.getElementById('verifiedOnly');
        const barterCheck = document.getElementById('barterOnly');
        const offersCheck = document.getElementById('offersOnly');
        const sortSelect = document.getElementById('sortSelect');
        
        if (searchInput) searchInput.value = '';
        if (categorySelect) categorySelect.value = '';
        if (locationInput) locationInput.value = '';
        if (minPriceInput) minPriceInput.value = '';
        if (maxPriceInput) maxPriceInput.value = '';
        if (conditionSelect) conditionSelect.value = '';
        if (verifiedCheck) verifiedCheck.checked = false;
        if (barterCheck) barterCheck.checked = false;
        if (offersCheck) offersCheck.checked = false;
        if (sortSelect) sortSelect.value = 'newest';
        
        this.currentPage = 1;
        this.loadListings();
    }
    
    async loadFilters() {
        try {
            // Load categories for filter dropdown
            const categoriesResponse = await fetch('server/get_categories.php');
            if (categoriesResponse.ok) {
                const categoriesData = await categoriesResponse.json();
                if (categoriesData.success) {
                    this.populateCategoryFilter(categoriesData.data);
                }
            }
        } catch (error) {
            console.error('Error loading filters:', error);
        }
    }
    
    populateCategoryFilter(categories) {
        const categorySelect = document.getElementById('categoryFilter');
        if (!categorySelect || !categories) return;
        
        // Clear existing options except the first one
        while (categorySelect.children.length > 1) {
            categorySelect.removeChild(categorySelect.lastChild);
        }
        
        // Add category options
        categories.forEach(category => {
            const option = document.createElement('option');
            option.value = category.category_slug;
            option.textContent = `${category.category_name} (${category.listing_count})`;
            categorySelect.appendChild(option);
        });
    }
    
    showLoading() {
        const container = document.getElementById('listingsGrid');
        if (container) {
            container.innerHTML = `
                <div class="col-12">
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-3 text-muted">Loading listings...</p>
                    </div>
                </div>
            `;
        }
        
        // Disable controls
        const controls = document.querySelectorAll('#searchForm button, #filterForm button, #sortSelect');
        controls.forEach(control => control.disabled = true);
    }
    
    hideLoading() {
        // Re-enable controls
        const controls = document.querySelectorAll('#searchForm button, #filterForm button, #sortSelect');
        controls.forEach(control => control.disabled = false);
    }
    
    showError(message = 'Unable to load listings') {
        const container = document.getElementById('listingsGrid');
        if (!container) return;
        
        container.innerHTML = `
            <div class="col-12">
                <div class="alert alert-danger text-center" role="alert">
                    <i class="fas fa-exclamation-triangle fa-2x mb-3"></i>
                    <h5 class="alert-heading">Error Loading Listings</h5>
                    <p class="mb-3">${this.escapeHtml(message)}</p>
                    <button class="btn btn-primary" onclick="window.listingsManager?.loadListings()">
                        <i class="fas fa-redo me-2"></i>Try Again
                    </button>
                </div>
            </div>
        `;
        
        // Update results count
        const resultsCount = document.getElementById('resultsCount');
        if (resultsCount) {
            resultsCount.textContent = 'Error loading listings';
        }
    }
    
    escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    // Method to refresh listings (can be called externally)
    refresh() {
        this.loadListings();
    }
}

// Global functions for favorite and other interactions
function toggleFavorite(listingId) {
    // Check if user is logged in
    if (!window.currentUser) {
        window.location.href = 'login.php';
        return;
    }
    
    // In a real app, this would make an AJAX call to toggle favorite status
    console.log('Toggle favorite for listing:', listingId);
    
    // Show temporary feedback
    const button = event.target.closest('button');
    if (button) {
        const icon = button.querySelector('i');
        if (icon.classList.contains('fas')) {
            icon.classList.remove('fas');
            icon.classList.add('far');
            button.setAttribute('title', 'Add to Favorites');
        } else {
            icon.classList.remove('far');
            icon.classList.add('fas');
            button.setAttribute('title', 'Remove from Favorites');
        }
    }
}

// Initialize listings manager when page loads
let listingsManager;
document.addEventListener('DOMContentLoaded', function() {
    listingsManager = new ListingsManager();
    
    // Make it globally available for debugging
    window.listingsManager = listingsManager;
    
    // Set up current user from session if available
    if (typeof currentUserId !== 'undefined') {
        window.currentUser = { id: currentUserId };
    }
});

// Error handling for uncaught promises
window.addEventListener('unhandledrejection', function(event) {
    console.error('Unhandled promise rejection:', event.reason);
    event.preventDefault();
});

// Export for use in other scripts
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { EnhancedListingsManager };
}