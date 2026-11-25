document.querySelectorAll('.password-field').forEach(function (wrap) {
    const input = wrap.querySelector('input');
    const btn = wrap.querySelector('.password-toggle-btn');
    if (!input || !btn) return;

    btn.addEventListener('click', function () {
        const showing = input.type === 'text';
        input.type = showing ? 'password' : 'text';
        btn.querySelector('.eye-show').classList.toggle('hidden', !showing);
        btn.querySelector('.eye-hide').classList.toggle('hidden', showing);
        btn.setAttribute('aria-label', showing ? 'Show password' : 'Hide password');
    });
});
