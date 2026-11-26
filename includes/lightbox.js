// Facebook-style full-screen image viewer. Any <img class="lightbox-img">
// opens uncropped (object-contain) on click — thumbnails elsewhere keep
// whatever crop they already use. One shared overlay, built lazily.
(function () {
    function overlay() {
        let ov = document.getElementById('higoffe-lightbox');
        if (ov) return ov;
        ov = document.createElement('div');
        ov.id = 'higoffe-lightbox';
        ov.className = 'fixed inset-0 z-[120] hidden items-center justify-center bg-black/90 p-4';
        ov.innerHTML =
            '<button type="button" id="higoffe-lightbox-close" aria-label="Close" ' +
            'class="absolute right-4 top-4 flex h-10 w-10 items-center justify-center rounded-full bg-white/10 text-white hover:bg-white/20">' +
            '<svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">' +
            '<path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg></button>' +
            '<img id="higoffe-lightbox-img" src="" alt="" class="max-h-full max-w-full rounded-lg object-contain">';
        document.body.appendChild(ov);
        ov.addEventListener('click', function (e) { if (e.target === ov) close(); });
        ov.querySelector('#higoffe-lightbox-close').addEventListener('click', close);
        return ov;
    }

    function open(src) {
        const ov = overlay();
        ov.querySelector('#higoffe-lightbox-img').src = src;
        ov.classList.remove('hidden');
        ov.classList.add('flex');
        document.body.style.overflow = 'hidden';
    }

    function close() {
        const ov = document.getElementById('higoffe-lightbox');
        if (!ov) return;
        ov.classList.add('hidden');
        ov.classList.remove('flex');
        ov.querySelector('#higoffe-lightbox-img').src = '';
        document.body.style.overflow = '';
    }

    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') close(); });

    // Delegated — works for images rendered after the fact (chat polling,
    // AJAX-loaded order fragments) without re-binding anything.
    document.addEventListener('click', function (e) {
        const img = e.target.closest('.lightbox-img');
        if (!img) return;
        e.preventDefault();
        open(img.dataset.full || img.src);
    }, true);
})();
