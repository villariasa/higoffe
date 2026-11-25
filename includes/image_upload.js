document.querySelectorAll('.image-upload-field').forEach(function (field) {
    const fileInput = field.querySelector('.file-input');
    const urlInput = field.querySelector('.url-input');
    const preview = field.querySelector('.preview-img');
    const status = field.querySelector('.status-text');

    fileInput.addEventListener('change', async function () {
        const file = fileInput.files[0];
        if (!file) return;

        // UX-only pre-check; the Worker enforces the real per-scope limit.
        const maxMb = parseInt(field.dataset.maxMb, 10) || 5;
        if (file.size > maxMb * 1024 * 1024) {
            status.textContent = 'File too large — max ' + maxMb + 'MB for this photo.';
            fileInput.value = '';
            return;
        }

        status.textContent = 'Uploading...';

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
        }
    });
});
