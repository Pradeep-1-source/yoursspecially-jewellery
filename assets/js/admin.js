/**
 * YoursSpeciallyJewellery - Admin Interactive Engine
 */

document.addEventListener('DOMContentLoaded', () => {
    initImagePreviews();
    initSlugGenerators();
    initDeleteConfirmations();
});

function initImagePreviews() {
    const fileInputs = document.querySelectorAll('.image-upload-input');
    fileInputs.forEach(input => {
        input.addEventListener('change', function() {
            const previewTargetId = this.dataset.preview;
            const previewEl = document.getElementById(previewTargetId);
            if (previewEl && this.files && this.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    previewEl.innerHTML = `<img src="${e.target.result}" alt="Preview" style="width:100%; height:100%; object-fit:cover;">`;
                };
                reader.readAsDataURL(this.files[0]);
            }
        });
    });
}

function initSlugGenerators() {
    const nameInput = document.querySelector('.auto-slug-source');
    const slugInput = document.querySelector('.auto-slug-target');

    if (nameInput && slugInput) {
        nameInput.addEventListener('input', function() {
            if (!slugInput.dataset.manualEdited) {
                slugInput.value = this.value
                    .toLowerCase()
                    .replace(/[^\w\s-]/g, '')
                    .replace(/[\s_-]+/g, '-')
                    .replace(/^-+|-+$/g, '');
            }
        });

        slugInput.addEventListener('input', function() {
            this.dataset.manualEdited = "true";
        });
    }
}

function initDeleteConfirmations() {
    const deleteButtons = document.querySelectorAll('.confirm-delete');
    deleteButtons.forEach(btn => {
        btn.addEventListener('click', function(e) {
            const msg = this.dataset.confirm || 'Are you certain you wish to delete this item? This action cannot be reversed.';
            if (!confirm(msg)) {
                e.preventDefault();
            }
        });
    });
}
