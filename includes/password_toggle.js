document.querySelectorAll('.password-field').forEach(function (wrap) {
    const input = wrap.querySelector('input');
    const btn = wrap.querySelector('.password-toggle-btn');
    if (!input || !btn) return;

    btn.addEventListener('click', function () {
        const showing = input.type === 'text';
        input.type = showing ? 'password' : 'text';
        btn.textContent = showing ? 'Show' : 'Hide';
        btn.setAttribute('aria-label', showing ? 'Show password' : 'Hide password');
    });
});
