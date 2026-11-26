// Crop-then-upload modal — shared singleton, reused by every
// .image-upload-field on the page (avatar, café logo/cover, product
// photos, post photos). Cropper.js is loaded via CDN in header.php.
// Payment-proof fields opt out via data-no-crop (it's evidence, not a
// framed photo) and upload the original straight away.
(function () {
    let modal = null;
    let cropper = null;
    let pendingResolve = null;
    let currentRawFile = null; // fallback: if cropping fails for any reason, upload this unmodified rather than blocking the user

    function buildModal() {
        modal = document.createElement('div');
        modal.id = 'higoffe-crop-modal';
        modal.className = 'fixed inset-0 z-[115] hidden items-center justify-center bg-black/80 p-4';
        modal.innerHTML =
            '<div class="flex max-h-full w-full max-w-md flex-col overflow-hidden rounded-2xl bg-white">' +
            '  <div class="max-h-[60vh] overflow-hidden bg-stone-900"><img id="higoffe-crop-img" src="" alt="" style="max-width:100%;display:block"></div>' +
            '  <div class="flex items-center justify-end gap-2 p-3">' +
            '    <button type="button" id="higoffe-crop-cancel" class="rounded-xl px-4 py-2 text-sm font-bold text-stone-500 hover:bg-stone-100">Cancel</button>' +
            '    <button type="button" id="higoffe-crop-use" class="rounded-xl bg-accent px-4 py-2 text-sm font-bold text-white hover:opacity-90">Use photo</button>' +
            '  </div>' +
            '</div>';
        document.body.appendChild(modal);

        modal.querySelector('#higoffe-crop-cancel').addEventListener('click', function () { closeModal(null); });
        modal.querySelector('#higoffe-crop-use').addEventListener('click', function () {
            // No working cropper (failed to init, still loading, etc.) —
            // never block the upload over a cosmetic crop step; just use
            // the photo as picked.
            if (!cropper) { closeModal(currentRawFile); return; }
            try {
                cropper.getCroppedCanvas({ imageSmoothingQuality: 'high' }).toBlob(function (blob) {
                    closeModal(blob || currentRawFile);
                }, 'image/jpeg', 0.92);
            } catch (err) {
                closeModal(currentRawFile);
            }
        });
    }

    function closeModal(result) {
        if (cropper) { try { cropper.destroy(); } catch (e) {} cropper = null; }
        if (modal) { modal.classList.add('hidden'); modal.classList.remove('flex'); }
        const img = document.getElementById('higoffe-crop-img');
        if (img && img.src && img.src.startsWith('blob:')) URL.revokeObjectURL(img.src);
        const resolve = pendingResolve;
        pendingResolve = null;
        currentRawFile = null;
        if (resolve) resolve(result);
    }

    // Returns a Promise<Blob|File|null> — null means the user cancelled
    // outright; otherwise either a cropped Blob or (on any failure) the
    // original file, so a broken crop step never blocks an upload.
    function openCropper(file, aspect) {
        if (!modal) buildModal();
        return new Promise(function (resolve) {
            pendingResolve = resolve;
            currentRawFile = file;
            const img = document.getElementById('higoffe-crop-img');

            // Attach onload BEFORE setting src — assigning src first risks
            // the load firing before the handler is attached, silently
            // leaving `cropper` unset forever.
            img.onload = function () {
                try {
                    if (cropper) cropper.destroy();
                    cropper = new Cropper(img, {
                        aspectRatio: isFinite(aspect) ? aspect : NaN, // NaN = free-form, Cropper.js's own default
                        viewMode: 1,
                        autoCropArea: 1,
                        background: false
                    });
                } catch (err) {
                    cropper = null; // "Use photo" will fall back to the original file
                }
            };
            img.onerror = function () {
                cropper = null;
            };
            img.src = URL.createObjectURL(file);

            modal.classList.remove('hidden');
            modal.classList.add('flex');
        });
    }

    document.querySelectorAll('.image-upload-field').forEach(function (field) {
        const fileInput = field.querySelector('.file-input');
        const urlInput = field.querySelector('.url-input');
        const preview = field.querySelector('.preview-img');
        const status = field.querySelector('.status-text');

        const formEl = field.closest('form');
        const submitBtn = formEl ? formEl.querySelector('button[type="submit"], input[type="submit"]') : null;
        const aspectAttr = field.dataset.cropAspect;
        const aspect = aspectAttr ? parseFloat(aspectAttr) : NaN;
        const skipCrop = field.dataset.noCrop === '1' || typeof window.Cropper === 'undefined';

        fileInput.addEventListener('change', async function () {
            const rawFile = fileInput.files[0];
            if (!rawFile) return;

            let fileToUpload = rawFile;
            if (!skipCrop && rawFile.type && rawFile.type.startsWith('image/') && rawFile.type !== 'image/gif') {
                const result = await openCropper(rawFile, aspect);
                if (!result) { fileInput.value = ''; return; } // user cancelled
                // A plain Blob means cropping actually happened; the raw
                // File passes straight through (fallback path) — only
                // relabel as .jpg when it's really a cropped JPEG blob.
                if (result instanceof File) {
                    fileToUpload = result;
                } else {
                    const baseName = (rawFile.name || 'photo').replace(/\.[^.]+$/, '');
                    fileToUpload = new File([result], baseName + '.jpg', { type: 'image/jpeg' });
                }
            }

            if (submitBtn) submitBtn.disabled = true;
            status.textContent = 'Optimizing & uploading...';
            const file = window.higoffeOptimizeImage
                ? await window.higoffeOptimizeImage(fileToUpload)
                : fileToUpload;

            // UX-only pre-check; the Worker enforces the real per-scope limit.
            const maxMb = parseInt(field.dataset.maxMb, 10) || 5;
            if (file.size > maxMb * 1024 * 1024) {
                status.textContent = 'File too large — max ' + maxMb + 'MB for this photo.';
                fileInput.value = '';
                if (submitBtn) submitBtn.disabled = false;
                return;
            }

            const form = new FormData();
            form.append('file', file);
            form.append('token', field.dataset.token);
            form.append('expiry', field.dataset.expiry);
            form.append('user_id', field.dataset.userId);
            form.append('scope', field.dataset.scope);

            try {
                const res = await fetch(field.dataset.workerUrl + '/upload', { method: 'POST', body: form });
                const data = await res.json();
                if (data.ok) {
                    urlInput.value = data.url;
                    preview.src = data.url;
                    preview.classList.remove('hidden');
                    status.textContent = 'Uploaded.';
                } else {
                    status.textContent = data.error || 'Upload failed.';
                }
            } catch (err) {
                status.textContent = 'Upload failed — check your connection.';
            } finally {
                if (submitBtn) submitBtn.disabled = false;
            }
        });
    });
})();
