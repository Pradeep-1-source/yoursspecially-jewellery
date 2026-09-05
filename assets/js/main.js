/**
 * YoursSpeciallyJewellery - Main Client Interactive Engine
 * Handles Hero Banner Carousel, Mobile Navigation, AJAX Cart, 
 * Product Gallery Zoom, and Luxury Micro-Interactions
 */

document.addEventListener('DOMContentLoaded', () => {
    initHeroCarousel();
    initMobileNav();
    initProductGallery();
    initAjaxCart();
    initToasts();
});

/* ----------------------------------------------------
   1. HERO PROMOTIONAL BANNER CAROUSEL
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
   2. MOBILE NAVIGATION DRAWER
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
   3. PRODUCT DETAIL GALLERY & IMAGE ZOOM
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
   4. AJAX CART OPERATIONS
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
   5. LUXURY TOAST NOTIFICATIONS
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
    const bg = type === 'success' ? '#5B1B36' : '#C9654E';
    toast.style.cssText = `
        background: ${bg};
        color: #FFFFFF;
        padding: 14px 22px;
        border-radius: 6px;
        font-size: 0.9rem;
        box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        display: flex;
        align-items: center;
        gap: 12px;
        pointer-events: auto;
        opacity: 0;
        transform: translateY(-15px);
        transition: all 0.35s ease;
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
    }, 4500);
}
