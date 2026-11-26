// Lightweight wrapper around Driver.js (loaded via CDN in header.php) for
// the guided product tour. One localStorage flag per tour per user — no
// schema change, no server round trip, consistent with how this app
// already keeps location state client-side only.
window.HigoffeTour = (function () {
    function uid() { return window.HIGOFFE_USER_ID || 0; }
    function storageKey(name) { return 'higoffe_tour_' + uid() + '_' + name; }

    function isDone(name) { return localStorage.getItem(storageKey(name)) === '1'; }
    function markDone(name) { localStorage.setItem(storageKey(name), '1'); }

    // 'all' clears every tour flag for the current user; a specific name
    // clears just that one (used by the "Restart tutorial" buttons).
    function reset(name) {
        const prefix = 'higoffe_tour_' + uid() + '_';
        if (name === 'all') {
            Object.keys(localStorage)
                .filter(function (k) { return k.indexOf(prefix) === 0; })
                .forEach(function (k) { localStorage.removeItem(k); });
        } else {
            localStorage.removeItem(prefix + name);
        }
    }

    // Desktop and mobile render separate DOM for the same nav destination
    // (top navbar vs bottom tab bar) — pick whichever copy is actually
    // visible in the current viewport.
    function visible(selector) {
        const els = document.querySelectorAll(selector);
        for (let i = 0; i < els.length; i++) {
            if (els[i].offsetParent !== null) return els[i];
        }
        return null;
    }

    function run(name, rawSteps, opts) {
        opts = opts || {};
        if (!opts.force && isDone(name)) return;
        if (!window.driver || !window.driver.js || typeof window.driver.js.driver !== 'function') return;

        // Give Tailwind's CDN JIT + images a beat to settle layout before
        // measuring element positions for the spotlight.
        setTimeout(function () {
            const steps = [];
            rawSteps.forEach(function (s) {
                const el = visible(s.selector);
                if (!el) return; // target not on screen at this viewport — skip, don't break the tour
                steps.push({
                    element: el,
                    popover: {
                        title: s.title,
                        description: s.description,
                        side: s.side || 'bottom',
                        align: s.align || 'start'
                    }
                });
            });
            if (!steps.length) return;

            let d;
            d = window.driver.js.driver({
                showProgress: true,
                allowClose: true,
                nextBtnText: 'Next',
                prevBtnText: 'Back',
                doneBtnText: 'Done',
                steps: steps,
                onPopoverRender: function (popover) {
                    try {
                        const skip = document.createElement('button');
                        skip.type = 'button';
                        skip.innerText = 'Skip tutorial';
                        skip.className = 'higoffe-tour-skip';
                        skip.addEventListener('click', function () { d.destroy(); });
                        popover.footerButtons.insertBefore(skip, popover.footerButtons.firstChild);
                    } catch (err) { /* Driver.js internals changed — tour still works without the extra button */ }
                },
                onDestroyed: function () { markDone(name); }
            });
            d.drive();
        }, 400);
    }

    return { run: run, isDone: isDone, markDone: markDone, reset: reset };
})();
