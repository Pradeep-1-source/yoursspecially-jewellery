<?php
/**
 * YoursSpeciallyJewellery - About Us Page
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';

$pageTitle = 'Our Story & Atelier Craftsmanship | YoursSpeciallyJewellery';
$metaDescription = 'Discover the soul of YoursSpeciallyJewellery. We craft timeless romantic fine jewellery inspired by eternal love and modern feminine grace.';

require_once __DIR__ . '/includes/header.php';
?>

<div class="container section-padding" style="padding-top: 3.5rem;">
    <!-- Brand Story Hero -->
    <div class="text-center" style="max-width: 800px; margin: 0 auto 4rem;">
        <span class="section-eyebrow">The Atelier Heritage</span>
        <h1 style="font-size: clamp(2.2rem, 4vw, 3.2rem); color: var(--primary); margin-bottom: 1.2rem;">
            Sculpting Poetry in Gold & Gems
        </h1>
        <p style="font-size: 1.15rem; color: var(--text-secondary); line-height: 1.8; font-weight: 300;">
            Born from an ardent reverence for love and grace, <strong>YoursSpeciallyJewellery</strong> exists to transform intimate emotions into wearable heirlooms.
        </p>
    </div>

    <!-- Editorial Showcase Section -->
    <div class="promo-banner-card" style="margin-bottom: 5rem;">
        <div class="promo-image-side">
            <img src="<?= BASE_URL ?>assets/images/banner-hero-2.jpg" alt="YoursSpecially Craftsmanship">
        </div>
        <div class="promo-content-side">
            <span class="section-eyebrow">Our Philosophy</span>
            <h2 style="font-size: 2.2rem; color: var(--primary); margin-bottom: 1rem;">The Infinity of Devotion</h2>
            <p style="color: var(--text-secondary); line-height: 1.8; margin-bottom: 1.5rem;">
                Our signature emblem is the sacred infinity loop intertwined with a delicate heart—a perpetual reminder that true affection knows no boundaries of time or distance.
            </p>
            <p style="color: var(--text-secondary); line-height: 1.8;">
                Every necklace, ring, bracelet, and earring in our repertoire is meticulously hand-assembled by generational master jewelers who treat every stone with sacred devotion.
            </p>
        </div>
    </div>

    <!-- Three Pillars of YoursSpecially -->
    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 2.5rem; margin-bottom: 5rem;">
        <div style="background: #fff; border: 1px solid var(--border-subtle); border-radius: var(--radius-md); padding: 2.5rem 2rem; text-align: center; box-shadow: var(--shadow-soft);">
            <div style="width: 56px; height: 56px; border-radius: 50%; background: var(--bg-blush); color: var(--primary); display: flex; align-items: center; justify-content: center; margin: 0 auto 1.2rem;">
                <svg viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"></path></svg>
            </div>
            <h3 style="font-size: 1.25rem; color: var(--primary); margin-bottom: 0.75rem;">Generational Artistry</h3>
            <p style="font-size: 0.9rem; color: var(--text-secondary); line-height: 1.7;">
                Blending age-old Indian kundan and meenakari techniques with contemporary Italian minimalist silhouettes.
            </p>
        </div>

        <div style="background: #fff; border: 1px solid var(--border-subtle); border-radius: var(--radius-md); padding: 2.5rem 2rem; text-align: center; box-shadow: var(--shadow-soft);">
            <div style="width: 56px; height: 56px; border-radius: 50%; background: var(--bg-blush); color: var(--primary); display: flex; align-items: center; justify-content: center; margin: 0 auto 1.2rem;">
                <svg viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path></svg>
            </div>
            <h3 style="font-size: 1.25rem; color: var(--primary); margin-bottom: 0.75rem;">Bespoke Touch</h3>
            <p style="font-size: 0.9rem; color: var(--text-secondary); line-height: 1.7;">
                From bridal trousseaus to anniversary surprises, our stylists work personally with you to create one-of-a-kind treasures.
            </p>
        </div>

        <div style="background: #fff; border: 1px solid var(--border-subtle); border-radius: var(--radius-md); padding: 2.5rem 2rem; text-align: center; box-shadow: var(--shadow-soft);">
            <div style="width: 56px; height: 56px; border-radius: 50%; background: var(--bg-blush); color: var(--primary); display: flex; align-items: center; justify-content: center; margin: 0 auto 1.2rem;">
                <svg viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
            </div>
            <h3 style="font-size: 1.25rem; color: var(--primary); margin-bottom: 0.75rem;">Certified Authenticity</h3>
            <p style="font-size: 0.9rem; color: var(--text-secondary); line-height: 1.7;">
                Stringent multi-tier purity tests ensuring anti-tarnish micro-plating, secure stone settings, and premium luster.
            </p>
        </div>
    </div>

    <!-- Call to Action -->
    <div style="text-align: center; background: var(--bg-blush-light); padding: 4rem 2rem; border-radius: var(--radius-lg); border: 1px solid var(--border-subtle);">
        <h2 style="margin-bottom: 1rem;">Experience the Collection</h2>
        <p style="max-width: 520px; margin: 0 auto 2rem; color: var(--text-secondary);">
            Indulge in pieces sculpted for your most unforgettable milestones.
        </p>
        <a href="<?= BASE_URL ?>products.php" class="btn btn-primary">Browse All Jewellery</a>
    </div>
</div>

<style>
@media (max-width: 860px) {
    div[style*="grid-template-columns: repeat(3, 1fr)"] {
        grid-template-columns: 1fr !important;
    }
}
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
