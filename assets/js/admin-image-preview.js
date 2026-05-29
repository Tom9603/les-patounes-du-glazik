/**
 * Prévisualisation d'images dans tous les formulaires EasyAdmin.
 * - Mode ajout  : affiche un aperçu dès que l'utilisateur choisit un fichier
 * - Mode édition : agrandit l'<img> déjà rendu par VichImageType
 */

(function () {
    // ── Styles injectés une seule fois ─────────────────────────────────────
    const styleId = 'ea-img-preview-styles';
    if (!document.getElementById(styleId)) {
        const style = document.createElement('style');
        style.id = styleId;
        style.textContent = `
            /* Image existante en mode édition (rendu par VichImageType) */
            .vich-image > a > img,
            .vich-file > a > img {
                display: block;
                max-width: 100%;
                max-height: 260px;
                width: auto;
                border-radius: 10px;
                margin: 0.6rem 0 0.5rem;
                object-fit: contain;
                box-shadow: 0 2px 12px rgba(0,0,0,.18);
            }

            /* Aperçu live (mode ajout / remplacement) */
            .ea-live-preview {
                margin: 0.65rem 0 0.4rem;
            }
            .ea-live-preview img {
                display: block;
                max-width: 100%;
                max-height: 260px;
                width: auto;
                border-radius: 10px;
                object-fit: contain;
                box-shadow: 0 2px 12px rgba(0,0,0,.18);
            }
        `;
        document.head.appendChild(style);
    }

    // ── Aperçu live au choix d'un fichier (event delegation) ───────────────
    document.addEventListener('change', function (e) {
        const input = e.target;
        if (input.tagName !== 'INPUT' || input.type !== 'file') return;

        const file = input.files && input.files[0];
        if (!file || !file.type.startsWith('image/')) return;

        // Trouver ou créer le conteneur de prévisualisation
        const wrap = input.closest('.vich-image, .vich-file, .field-image-upload') || input.parentElement;
        let preview = wrap.querySelector('.ea-live-preview');
        if (!preview) {
            preview = document.createElement('div');
            preview.className = 'ea-live-preview';
            const img = document.createElement('img');
            img.alt = '';
            preview.appendChild(img);
            // Insérer après l'input (ou avant le bloc Supprimer si présent)
            input.insertAdjacentElement('afterend', preview);
        }

        const img = preview.querySelector('img');
        const reader = new FileReader();
        reader.onload = function (ev) {
            img.src = ev.target.result;
            img.alt = file.name;
        };
        reader.readAsDataURL(file);
    });
})();
