/**
 * YoursSpeciallyJewellery - Main Client Interactive Engine
 * Handles Hero Banner Carousel, Mobile Navigation, AJAX Cart, 
 * Product Gallery Zoom, Wishlist (localStorage), Scroll Restoration,
 * Buy Now Guest Flow, and Luxury Micro-Interactions
 */

document.addEventListener('DOMContentLoaded', () => {
    initScrollRestoration();
    initHeroCarousel();
    initMobileNav();
    initProductGallery();
    initAjaxCart();
    initWishlist();
    initBuyNow();
    initToasts();
});

/* ----------------------------------------------------
   1. PRODUCT LIST SCROLL RESTORATION
   ---------------------------------------------------- */
function initScrollRestoration() {
    const isListingPage = window.location.pathname.endsWith('products.php') || 
                          window.location.pathname.endsWith('index.php') || 
                          window.location.pathname.endsWith('/');

    if (isListingPage) {
        if ('scrollRestoration' in history) {
            history.scrollRestoration = 'manual';
        }

        const scrollKey = 'ysj_scroll_' + window.location.pathname + window.location.search;
        const savedY = sessionStorage.getItem(scrollKey);

        if (savedY !== null) {
            const targetY = parseInt(savedY, 10);
            // Restore immediately and after images/layout settle
            window.scrollTo(0, targetY);
            requestAnimationFrame(() => {
                window.scrollTo(0, targetY);
            });
            setTimeout(() => {
                window.scrollTo(0, targetY);
            }, 60);
        }

        // Intercept clicks on links pointing to product detail pages
        document.addEventListener('click', (e) => {
            const link = e.target.closest('a');
            if (!link || !link.href) return;

            if (link.href.includes('product.php')) {
                sessionStorage.setItem(scrollKey, window.scrollY.toString());
                sessionStorage.setItem('ysj_last_listing_url', window.location.href);
            }
        });
    }

    // On pageshow (fires on browser Back / Forward buttons)
    window.addEventListener('pageshow', (event) => {
        if (isListingPage) {
            const scrollKey = 'ysj_scroll_' + window.location.pathname + window.location.search;
            const savedY = sessionStorage.getItem(scrollKey);
            if (savedY !== null) {
                window.scrollTo(0, parseInt(savedY, 10));
            }
        }
    });
}

/* ----------------------------------------------------
   2. HERO PROMOTIONAL BANNER CAROUSEL
   ---------------------------------------------------- */
function initHeroCarousel() {
    const slider = document.querySelector('.hero-slider');
    if (!slider) return;

    const slides = slider.querySelectorAll('.hero-slide');
    const dots = document.querySelectorAll('.slider-dot');
    const prevBtn = document.querySelector('.slider-arrow.prev');
    const nextBtn = document.querySelector('.slider-arrow.next');

    if (slides.length <= 1) return;

    let currentIndex = 0;
    let autoSlideTimer = null;
    const intervalTime = 5500; // 5.5s autoplay

    function showSlide(index) {
        slides.forEach((s, i) => {
            s.classList.toggle('active', i === index);
        });
        dots.forEach((d, i) => {
            d.classList.toggle('active', i === index);
        });
        currentIndex = index;
    }

    function nextSlide() {
        const next = (currentIndex + 1) % slides.length;
        showSlide(next);
    }

    function prevSlide() {
        const prev = (currentIndex - 1 + slides.length) % slides.length;
        showSlide(prev);
    }

    function startAutoSlide() {
        stopAutoSlide();
        autoSlideTimer = setInterval(nextSlide, intervalTime);
    }

    function stopAutoSlide() {
        if (autoSlideTimer) clearInterval(autoSlideTimer);
    }

    if (nextBtn) {
        nextBtn.addEventListener('click', () => {
            nextSlide();
            startAutoSlide();
        });
    }

    if (prevBtn) {
        prevBtn.addEventListener('click', () => {
            prevSlide();
            startAutoSlide();
        });
    }

    dots.forEach((dot, idx) => {
        dot.addEventListener('click', () => {
            showSlide(idx);
            startAutoSlide();
        });
    });

    // Pause on mouse enter
    slider.addEventListener('mouseenter', stopAutoSlide);
    slider.addEventListener('mouseleave', startAutoSlide);

    // Touch Swipe on mobile
    let touchStartX = 0;
    let touchEndX = 0;

    slider.addEventListener('touchstart', (e) => {
        touchStartX = e.changedTouches[0].screenX;
        stopAutoSlide();
    }, { passive: true });

    slider.addEventListener('touchend', (e) => {
        touchEndX = e.changedTouches[0].screenX;
        if (touchEndX < touchStartX - 50) {
            nextSlide();
        } else if (touchEndX > touchStartX + 50) {
            prevSlide();
        }
        startAutoSlide();
    }, { passive: true });

    startAutoSlide();
}

/* ----------------------------------------------------
   3. MOBILE NAVIGATION DRAWER
   ---------------------------------------------------- */
function initMobileNav() {
    const toggleBtn = document.querySelector('.mobile-menu-btn');
    const drawer = document.querySelector('.mobile-nav-drawer');
    const backdrop = document.querySelector('.drawer-backdrop');
    const closeBtn = document.querySelector('.drawer-close-btn');

    if (!toggleBtn || !drawer) return;

    function openDrawer() {
        drawer.classList.add('open');
        if (backdrop) backdrop.classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    function closeDrawer() {
        drawer.classList.remove('open');
        if (backdrop) backdrop.classList.remove('active');
        document.body.style.overflow = '';
    }

    toggleBtn.addEventListener('click', openDrawer);
    if (backdrop) backdrop.addEventListener('click', closeDrawer);
    if (closeBtn) closeBtn.addEventListener('click', closeDrawer);
}

/* ----------------------------------------------------
   4. PRODUCT DETAIL GALLERY & IMAGE ZOOM
   ---------------------------------------------------- */
function initProductGallery() {
    const mainImg = document.getElementById('mainGalleryImg');
    const thumbs = document.querySelectorAll('.thumb-item');

    if (!mainImg || !thumbs.length) return;

    thumbs.forEach(thumb => {
        thumb.addEventListener('click', function() {
            thumbs.forEach(t => t.classList.remove('active'));
            this.classList.add('active');
            const targetSrc = this.getAttribute('data-src') || this.querySelector('img').src;
            mainImg.src = targetSrc;
        });
    });
}

/* ----------------------------------------------------
   5. AJAX CART OPERATIONS
   ---------------------------------------------------- */
function initAjaxCart() {
    // Add to cart buttons (Product cards and detail page)
    document.body.addEventListener('click', function(e) {
        const btn = e.target.closest('.ajax-add-to-cart');
        if (!btn) return;

        e.preventDefault();
        const productId = btn.dataset.productId;
        const qtyInput = document.querySelector('#productQuantity');
        const quantity = qtyInput ? parseInt(qtyInput.value) || 1 : 1;

        const originalText = btn.innerHTML;
        btn.innerHTML = '<span>Adding...</span>';
        btn.disabled = true;

        const formData = new FormData();
        formData.append('action', 'add');
        formData.append('product_id', productId);
        formData.append('quantity', quantity);

        fetch('api/cart.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            btn.innerHTML = originalText;
            btn.disabled = false;

            if (data.success) {
                showToast('success', data.message || 'Added to your luxury shopping bag!');
                updateCartBadge(data.cart_count);
            } else {
                showToast('error', data.message || 'Unable to add item. Please try again.');
            }
        })
        .catch(err => {
            console.error(err);
            btn.innerHTML = originalText;
            btn.disabled = false;
            showToast('error', 'Network error while adding to bag.');
        });
    });
}

function updateCartBadge(count) {
    const badges = document.querySelectorAll('.cart-count-badge');
    badges.forEach(b => {
        b.textContent = count;
        b.style.display = count > 0 ? 'flex' : 'none';
    });
}

/* ----------------------------------------------------
   6. BUY NOW INSTANT CHECKOUT FLOW
   ---------------------------------------------------- */
window.buyNow = function(productId, quantity = 1) {
    const qtyInput = document.querySelector('#productQuantity');
    const finalQty = qtyInput ? parseInt(qtyInput.value) || 1 : quantity;

    const formData = new FormData();
    formData.append('action', 'add');
    formData.append('product_id', productId);
    formData.append('quantity', finalQty);

    fetch('api/cart.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            window.location.href = 'checkout.php';
        } else {
            showToast('error', data.message || 'Unable to proceed to checkout.');
        }
    })
    .catch(err => {
        console.error(err);
        showToast('error', 'Network error. Please try again.');
    });
};

function initBuyNow() {
    document.body.addEventListener('click', function(e) {
        const btn = e.target.closest('.btn-buy-now');
        if (!btn) return;

        e.preventDefault();
        const productId = btn.dataset.productId;
        if (productId) {
            window.buyNow(productId, 1);
        }
    });
}

/* ----------------------------------------------------
   7. WISHLIST ENGINE (LOCALSTORAGE)
   ---------------------------------------------------- */
const WISHLIST_KEY = 'ysj_wishlist';

function getWishlist() {
    try {
        const stored = localStorage.getItem(WISHLIST_KEY);
        return stored ? JSON.parse(stored) : [];
    } catch (e) {
        console.error('Error reading wishlist', e);
        return [];
    }
}

function saveWishlist(items) {
    try {
        localStorage.setItem(WISHLIST_KEY, JSON.stringify(items));
        updateWishlistBadges();
        syncWishlistButtons();
    } catch (e) {
        console.error('Error saving wishlist', e);
    }
}

function isInWishlist(productId) {
    const list = getWishlist();
    return list.some(item => String(item.id) === String(productId));
}

function toggleWishlist(product) {
    let list = getWishlist();
    const index = list.findIndex(item => String(item.id) === String(product.id));

    if (index > -1) {
        list.splice(index, 1);
        saveWishlist(list);
        showToast('info', 'Removed from your Wishlist.');
        return false;
    } else {
        list.push(product);
        saveWishlist(list);
        showToast('success', 'Saved to your Wishlist!');
        return true;
    }
}

function updateWishlistBadges() {
    const list = getWishlist();
    const count = list.length;

    const badges = document.querySelectorAll('.wishlist-count-badge');
    badges.forEach(b => {
        b.textContent = count;
        b.style.display = count > 0 ? 'flex' : 'none';
    });

    const textCounters = document.querySelectorAll('.wishlist-count-text');
    textCounters.forEach(t => {
        t.textContent = count;
    });
}

function syncWishlistButtons() {
    const buttons = document.querySelectorAll('.wishlist-toggle-btn');
    buttons.forEach(btn => {
        const pid = btn.dataset.productId;
        if (pid && isInWishlist(pid)) {
            btn.classList.add('active');
            btn.setAttribute('aria-label', 'Remove from Wishlist');
            btn.setAttribute('title', 'Remove from Wishlist');
        } else {
            btn.classList.remove('active');
            btn.setAttribute('aria-label', 'Add to Wishlist');
            btn.setAttribute('title', 'Add to Wishlist');
        }
    });
}

function initWishlist() {
    updateWishlistBadges();
    syncWishlistButtons();

    // Delegate click on wishlist toggle buttons
    document.body.addEventListener('click', function(e) {
        const btn = e.target.closest('.wishlist-toggle-btn');
        if (!btn) return;

        e.preventDefault();
        e.stopPropagation();

        const product = {
            id: btn.dataset.productId,
            name: btn.dataset.name || '',
            slug: btn.dataset.slug || '',
            price: btn.dataset.price || '0',
            sale_price: btn.dataset.salePrice || '',
            image: btn.dataset.image || '',
            sku: btn.dataset.sku || '',
            in_stock: btn.dataset.inStock !== '0'
        };

        const added = toggleWishlist(product);

        // If on wishlist.php, re-render wishlist page
        if (typeof window.renderWishlistPage === 'function') {
            window.renderWishlistPage();
        }
    });

    // Listen for storage events across tabs
    window.addEventListener('storage', (e) => {
        if (e.key === WISHLIST_KEY) {
            updateWishlistBadges();
            syncWishlistButtons();
            if (typeof window.renderWishlistPage === 'function') {
                window.renderWishlistPage();
            }
        }
    });
}

/* ----------------------------------------------------
   8. QUANTITY CONTROLLER (STRICT STOCK CAP & NO STOCK COUNT)
   ---------------------------------------------------- */
window.updateQty = function(delta) {
    const input = document.getElementById('productQuantity');
    if (!input) return;

    let currentVal = parseInt(input.value) || 1;
    const maxLimit = input.getAttribute('max') ? parseInt(input.getAttribute('max'), 10) : 999;
    const plusBtn = input.parentElement ? input.parentElement.querySelector('.qty-btn:last-child') : null;

    let newVal = currentVal + delta;
    if (newVal < 1) newVal = 1;

    if (newVal >= maxLimit) {
        newVal = maxLimit;
        if (plusBtn) {
            plusBtn.disabled = true;
            plusBtn.style.opacity = '0.4';
            plusBtn.style.cursor = 'not-allowed';
        }
    } else {
        if (plusBtn) {
            plusBtn.disabled = false;
            plusBtn.style.opacity = '1';
            plusBtn.style.cursor = 'pointer';
        }
    }

    input.value = newVal;
};

/* ----------------------------------------------------
   9. LUXURY TOAST NOTIFICATIONS
   ---------------------------------------------------- */
function showToast(type, message) {
    let container = document.querySelector('.toast-container');
    if (!container) {
        container = document.createElement('div');
        container.className = 'toast-container';
        container.style.cssText = `
            position: fixed;
            top: 24px;
            right: 24px;
            z-index: 9999;
            display: flex;
            flex-direction: column;
            gap: 10px;
            pointer-events: none;
        `;
        document.body.appendChild(container);
    }

    const toast = document.createElement('div');
    toast.className = `luxury-toast ${type}`;
    const bg = type === 'success' ? '#5B1B36' : (type === 'info' ? '#3F1024' : '#C9654E');
    toast.style.cssText = `
        background: ${bg};
        color: #FFFFFF;
        padding: 14px 20px;
        border-radius: 6px;
        font-size: 0.9rem;
        box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        display: flex;
        align-items: center;
        gap: 12px;
        pointer-events: auto;
        opacity: 0;
        transform: translateY(-15px);
        transition: all 0.35s cubic-bezier(0.16, 1, 0.3, 1);
        max-width: 360px;
        border-left: 4px solid #C5A059;
    `;

    toast.innerHTML = `
        <span style="flex-grow: 1;">${message}</span>
        <button style="background:none; border:none; color:#fff; cursor:pointer; font-size:18px; line-height:1;" onclick="this.parentElement.remove()">&times;</button>
    `;

    container.appendChild(toast);

    setTimeout(() => {
        toast.style.opacity = '1';
        toast.style.transform = 'translateY(0)';
    }, 10);

    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateY(-15px)';
        setTimeout(() => toast.remove(), 400);
    }, 4000);
}
