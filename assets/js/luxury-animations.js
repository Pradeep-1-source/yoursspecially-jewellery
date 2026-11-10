/**
 * YoursSpeciallyJewellery - Haute Joaillerie Luxury Animation Suite
 * Inspired by High-End European Jewellery Houses
 * (Ken Burns Hero, Editorial Typography Reveals, Golden Sparkles, Staggered Reveals, Parallax)
 */

(function() {
    'use strict';

    const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    document.addEventListener('DOMContentLoaded', () => {
        initPreloader();
        initHeroTypography();
        initHeroSlideWatcher();
        initLuxurySparkles();
        initScrollReveals();
        initParallaxScroll();
        initWishlistMicroInteractions();
    });

    /* ----------------------------------------------------
       1. LUXURY ATELIER PRELOADER
       ---------------------------------------------------- */
    function initPreloader() {
        const preloader = document.getElementById('luxuryLoader');
        if (!preloader) return;

        if (prefersReducedMotion) {
            preloader.style.display = 'none';
            return;
        }

        const isReturning = sessionStorage.getItem('ysj_preloader_seen');
        const minDisplayTime = isReturning ? 250 : 550;
        const startTime = Date.now();

        function dismissPreloader() {
            const elapsed = Date.now() - startTime;
            const remaining = Math.max(0, minDisplayTime - elapsed);

            setTimeout(() => {
                preloader.classList.add('fade-out');
                sessionStorage.setItem('ysj_preloader_seen', '1');
                setTimeout(() => {
                    preloader.style.display = 'none';
                }, 650);
            }, remaining);
        }

        if (document.readyState === 'complete') {
            dismissPreloader();
        } else {
            window.addEventListener('load', dismissPreloader);
            // Safety timeout in case external assets are slow
            setTimeout(dismissPreloader, 1800);
        }
    }

    /* ----------------------------------------------------
       2. HERO EDITORIAL TYPOGRAPHY REVEAL
       ---------------------------------------------------- */
    function initHeroTypography() {
        if (prefersReducedMotion) return;

        const heroTitles = document.querySelectorAll('.hero-title');
        heroTitles.forEach(title => {
            // Guard against double processing
            if (title.getAttribute('data-split-done')) return;

            const originalText = title.textContent.trim();
            title.setAttribute('data-original-title', originalText);

            const words = originalText.split(/\s+/);
            const wrappedHTML = words.map((word, idx) => {
                const delay = 0.22 + (idx * 0.08);
                return `<span class="hero-word-wrap"><span class="hero-word" style="transition-delay: ${delay}s">${word}</span></span>`;
            }).join(' ');

            title.innerHTML = wrappedHTML;
            title.setAttribute('data-split-done', 'true');
        });
    }

    /* ----------------------------------------------------
       3. HERO SLIDE MUTATION WATCHER (KEN BURNS & RESET)
       ---------------------------------------------------- */
    function initHeroSlideWatcher() {
        const slider = document.querySelector('.hero-slider');
        if (!slider) return;

        const slides = slider.querySelectorAll('.hero-slide');
        if (!slides.length) return;

        // Observer to detect when active class changes on slides
        const observer = new MutationObserver(mutations => {
            mutations.forEach(mutation => {
                if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                    const target = mutation.target;
                    if (target.classList.contains('active')) {
                        onSlideActivated(target);
                    }
                }
            });
        });

        slides.forEach(slide => {
            observer.observe(slide, { attributes: true });
        });

        // Trigger for initial active slide
        const initialActive = slider.querySelector('.hero-slide.active');
        if (initialActive) {
            onSlideActivated(initialActive);
        }
    }

    function onSlideActivated(slide) {
        if (prefersReducedMotion) return;

        // Reset and re-trigger word reveal animations
        const words = slide.querySelectorAll('.hero-word');
        words.forEach(word => {
            word.style.transition = 'none';
            word.style.transform = 'translateY(115%)';
            word.style.opacity = '0';
        });

        // Force reflow
        void slide.offsetWidth;

        words.forEach((word, idx) => {
            const delay = 0.22 + (idx * 0.08);
            word.style.transition = `transform 0.9s cubic-bezier(0.16, 1, 0.3, 1) ${delay}s, opacity 0.9s cubic-bezier(0.16, 1, 0.3, 1) ${delay}s`;
            word.style.transform = 'translateY(0)';
            word.style.opacity = '1';
        });
    }

    /* ----------------------------------------------------
       4. LUXURY GOLDEN SPARKLE PARTICLES
       ---------------------------------------------------- */
    function initLuxurySparkles() {
        if (prefersReducedMotion) return;

        const containers = document.querySelectorAll('.hero-sparkle-container, .has-luxury-sparkles');
        if (!containers.length) return;

        const sparkleSymbols = ['✦', '✧', '⋆'];

        containers.forEach(container => {
            // Avoid duplicate sparkles
            if (container.querySelector('.golden-sparkle')) return;

            const isHero = container.classList.contains('hero-sparkle-container');
            const count = isHero ? 9 : 6;

            for (let i = 0; i < count; i++) {
                const sparkle = document.createElement('span');
                sparkle.className = 'golden-sparkle';
                sparkle.setAttribute('aria-hidden', 'true');
                sparkle.textContent = sparkleSymbols[i % sparkleSymbols.length];

                // Random distribution
                const left = 8 + Math.random() * 84;
                const top = 12 + Math.random() * 76;
                const size = 11 + Math.random() * 9;
                const duration = 2.8 + Math.random() * 2.2;
                const delay = Math.random() * 3.5;

                sparkle.style.left = `${left}%`;
                sparkle.style.top = `${top}%`;
                sparkle.style.fontSize = `${size}px`;
                sparkle.style.animationDuration = `${duration}s`;
                sparkle.style.animationDelay = `${delay}s`;

                container.appendChild(sparkle);
            }
        });
    }

    /* ----------------------------------------------------
       5. SCROLL REVEAL & STAGGER ENGINE
       ---------------------------------------------------- */
    function initScrollReveals() {
        if (!('IntersectionObserver' in window)) return;

        // Auto-assign stagger indexes to child cards in grids
        const gridSelectors = [
            '.category-grid',
            '.product-grid',
            '.trust-features-grid',
            '.insta-grid',
            '.footer-grid'
        ];

        gridSelectors.forEach(gridSel => {
            const grids = document.querySelectorAll(gridSel);
            grids.forEach(grid => {
                const children = Array.from(grid.children);
                children.forEach((child, idx) => {
                    child.style.setProperty('--stagger', idx);
                    child.classList.add('reveal-init');
                });
            });
        });

        // Other editorial elements to reveal
        const standaloneSelectors = [
            '.section-eyebrow',
            '.section-title',
            '.section-subtitle',
            '.promo-banner-card',
            '.newsletter-card'
        ];

        standaloneSelectors.forEach(sel => {
            document.querySelectorAll(sel).forEach(el => {
                el.classList.add('reveal-init');
            });
        });

        // Intersection Observer configuration
        const observerOptions = {
            root: null,
            rootMargin: '0px 0px -40px 0px',
            threshold: 0.08
        };

        const revealObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-revealed');
                    // Once revealed, unobserve to free resources
                    observer.unobserve(entry.target);
                }
            });
        }, observerOptions);

        document.querySelectorAll('.reveal-init').forEach(el => {
            revealObserver.observe(el);
        });
    }

    /* ----------------------------------------------------
       6. PARALLAX & LUXURY DEPTH ON SCROLL
       ---------------------------------------------------- */
    function initParallaxScroll() {
        if (prefersReducedMotion) return;
        // Skip on low-power mobile devices for high frame rate
        if (window.innerWidth < 768) return;

        const heroContent = document.querySelector('.hero-content');
        const promoCard = document.querySelector('.promo-banner-card');
        const promoImg = document.querySelector('.promo-image-side img');

        let lastScrollY = window.scrollY;
        let isTicking = false;

        function updateParallax() {
            const scrollY = lastScrollY;
            const winHeight = window.innerHeight;

            // Hero content subtle parallax dampening
            if (heroContent && scrollY < winHeight) {
                const offset = scrollY * 0.22;
                const opacity = Math.max(0, 1 - (scrollY / (winHeight * 0.85)));
                heroContent.style.transform = `translateY(${offset}px)`;
                heroContent.style.opacity = opacity;
            }

            // Promo card subtle image drift
            if (promoCard && promoImg) {
                const rect = promoCard.getBoundingClientRect();
                if (rect.top < winHeight && rect.bottom > 0) {
                    const progress = (winHeight - rect.top) / (winHeight + rect.height);
                    const shift = (progress - 0.5) * 25;
                    promoImg.style.transform = `scale(1.04) translateY(${shift}px)`;
                }
            }

            isTicking = false;
        }

        window.addEventListener('scroll', () => {
            lastScrollY = window.scrollY;
            if (!isTicking) {
                window.requestAnimationFrame(updateParallax);
                isTicking = true;
            }
        }, { passive: true });
    }

    /* ----------------------------------------------------
       7. WISHLIST HEART POP MICRO-INTERACTION
       ---------------------------------------------------- */
    function initWishlistMicroInteractions() {
        document.body.addEventListener('click', (e) => {
            const btn = e.target.closest('.wishlist-toggle-btn');
            if (!btn) return;

            btn.classList.add('active');
            setTimeout(() => {
                btn.classList.remove('active');
            }, 600);
        });
    }

})();

