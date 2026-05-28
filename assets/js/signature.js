import SignaturePad from 'signature_pad';

document.addEventListener('DOMContentLoaded', () => {
    const canvas = document.getElementById('signature-canvas');
    if (!canvas) return;

    const pad = new SignaturePad(canvas, {
        backgroundColor: 'rgb(255, 255, 255)',
        penColor: '#1a1a2e',
        minWidth: 1,
        maxWidth: 3,
    });

    function resizeCanvas() {
        const ratio = Math.max(window.devicePixelRatio || 1, 1);
        const w = canvas.offsetWidth;
        const h = canvas.offsetHeight;
        canvas.width  = w * ratio;
        canvas.height = h * ratio;
        canvas.getContext('2d').scale(ratio, ratio);
        pad.clear();
    }

    window.addEventListener('resize', resizeCanvas);
    resizeCanvas();

    document.getElementById('btn-clear')?.addEventListener('click', () => pad.clear());

    document.getElementById('signature-form')?.addEventListener('submit', (e) => {
        if (pad.isEmpty()) {
            e.preventDefault();
            alert('Veuillez signer avant de valider.');
            return;
        }
        document.getElementById('signature-data').value = pad.toDataURL('image/png');
    });
});
