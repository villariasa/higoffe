// Multi-photo upload (Facebook/Instagram-style). Each selected file
// uploads independently to the Cloudflare Worker; successful ones are
// tracked in an array and kept in sync with a hidden JSON input plus a
// thumbnail grid with per-photo remove buttons.
document.querySelectorAll('.image-upload-multi-field').forEach(function (field) {
    const fileInput = field.querySelector('.file-input');
    const urlsInput = field.querySelector('.urls-input');
    const thumbGrid = field.querySelector('.thumb-grid');
    const status = field.querySelector('.status-text');
    const maxMb = parseInt(field.dataset.maxMb, 10) || 5;
    const maxCount = parseInt(field.dataset.maxCount, 10) || 10;

    const formEl = field.closest('form');
    const submitBtn = formEl ? formEl.querySelector('button[type="submit"], input[type="submit"]') : null;

    let urls = [];

    function escapeHtml(s) {
        return String(s ?? '').replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }

    function sync() {
        urlsInput.value = JSON.stringify(urls);
        thumbGrid.innerHTML = urls.map(function (url, i) {
            return '<div class="relative aspect-square overflow-hidden rounded-xl border border-stone-200 bg-stone-50">' +
                '<img src="' + escapeHtml(url) + '" alt="" class="h-full w-full object-cover">' +
                '<button type="button" class="remove-thumb absolute right-1 top-1 flex h-5 w-5 items-center justify-center rounded-full bg-black/60 text-white" data-index="' + i + '" aria-label="Remove photo">' +
                '<svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>' +
                '</button></div>';
        }).join('');
        thumbGrid.querySelectorAll('.remove-thumb').forEach(function (btn) {
            btn.addEventListener('click', function () {
                urls.splice(parseInt(btn.dataset.index, 10), 1);
                sync();
            });
        });
    }

    async function uploadOne(rawFile) {
        const file = window.higoffeOptimizeImage ? await window.higoffeOptimizeImage(rawFile) : rawFile;
        if (file.size > maxMb * 1024 * 1024) {
            if (window.higoffeToast) window.higoffeToast('One photo was skipped — over ' + maxMb + 'MB.', 'warning');
            return null;
        }
        const form = new FormData();
        form.append('file', file);
        form.append('token', field.dataset.token);
        form.append('expiry', field.dataset.expiry);
        form.append('user_id', field.dataset.userId);
        form.append('scope', field.dataset.scope);
        const res = await fetch(field.dataset.workerUrl + '/upload', { method: 'POST', body: form });
        const data = await res.json();
        return data.ok ? data.url : null;
    }

    fileInput.addEventListener('change', async function () {
        const files = Array.from(fileInput.files || []);
        if (!files.length) return;

        const room = maxCount - urls.length;
        if (room <= 0) {
            if (window.higoffeToast) window.higoffeToast('You can add up to ' + maxCount + ' photos.', 'warning');
            fileInput.value = '';
            return;
        }
        const toUpload = files.slice(0, room);
        if (files.length > room && window.higoffeToast) {
            window.higoffeToast('Only added ' + room + ' more — ' + maxCount + ' photo limit.', 'warning');
        }

        if (submitBtn) submitBtn.disabled = true;
        status.textContent = 'Uploading ' + toUpload.length + ' photo(s)...';

        for (const rawFile of toUpload) {
            try {
                const url = await uploadOne(rawFile);
                if (url) { urls.push(url); sync(); }
            } catch (err) { /* this one failed — continue with the rest */ }
        }

        status.textContent = urls.length ? urls.length + ' photo(s) added.' : 'Upload failed — try again.';
        if (submitBtn) submitBtn.disabled = false;
        fileInput.value = '';
    });
});
