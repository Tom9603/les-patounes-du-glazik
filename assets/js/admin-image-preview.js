/**
 * Prévisualisation d'images dans tous les formulaires EasyAdmin.
 * Gère :
 *  - VichImageType (champ imageFile, VichUploaderBundle)
 *  - ImageField natif EasyAdmin (Article à la une, etc.)
 */
document.addEventListener('DOMContentLoaded', () => {
    // --- Prévisualisation à la sélection d'un fichier ---
    function attachPreview(input) {
        // Trouver ou créer le conteneur de preview
        let wrap = input.closest('.vich-image, .field-image-upload, .ea-vich-image') || input.parentElement;

        let preview = wrap.querySelector('.ea-img-preview');
        if (!preview) {
            preview = document.createElement('div');
            preview.className = 'ea-img-preview';
            preview.style.cssText = 'margin:0.6rem 0 0.5rem;';

            const img = document.createElement('img');
            img.className = 'ea-img-preview-img';
            img.style.cssText = [
                'max-width:100%',
                'max-height:220px',
                'border-radius:8px',
                'object-fit:contain',
                'display:block',
                'box-shadow:0 2px 12px rgba(0,0,0,.18)',
            ].join(';');
            preview.appendChild(img);
            wrap.insertBefore(preview, input);
        }

        const img = preview.querySelector('img');

        // Afficher l'image existante (mode édition) : cherche un lien <a> dans le wrap
        if (!img.src || img.src === window.location.href) {
            const link = wrap.querySelector('a[href]');
            if (link) {
                const href = link.getAttribute('href');
                // Exclure les liens d'action EasyAdmin
                if (/\.(jpg|jpeg|png|gif|webp|svg|avif)(\?.*)?$/i.test(href) || href.startsWith('/uploads/')) {
                    img.src = href;
                    img.alt = 'Aperçu actuel';
                }
            }
        }

        input.addEventListener('change', () => {
            const file = input.files?.[0];
            if (!file || !file.type.startsWith('image/')) return;

            const reader = new FileReader();
            reader.onload = (e) => {
                img.src = e.target.result;
                img.alt = file.name;
            };
            reader.readAsDataURL(file);
        });
    }

    // Attacher sur tous les inputs file de type image présents au chargement
    document.querySelectorAll('input[type="file"]').forEach((input) => {
        const accept = (input.getAttribute('accept') || '').toLowerCase();
        if (accept.includes('image') || accept === '' || /\.(jpg|jpeg|png|gif|webp)/i.test(accept)) {
            attachPreview(input);
        }
    });

    // Observer les nouveaux champs ajoutés dynamiquement (champs collection EA)
    const observer = new MutationObserver((mutations) => {
        mutations.forEach((m) => {
            m.addedNodes.forEach((node) => {
                if (node.nodeType !== 1) return;
                node.querySelectorAll?.('input[type="file"]').forEach((input) => {
                    attachPreview(input);
                });
            });
        });
    });
    observer.observe(document.body, { childList: true, subtree: true });
});
