// Updates the dot indicator + "n/total" counter as the user swipes through
// a multi-photo post (pure CSS scroll-snap handles the actual swipe).
(function () {
    function wire() {
        document.querySelectorAll('.post-carousel').forEach(function (car) {
            const track = car.querySelector('.carousel-track');
            if (!track || track.dataset.carouselBound) return;
            track.dataset.carouselBound = '1';
            const dots = car.querySelectorAll('.carousel-dots span');
            const counter = car.querySelector('.carousel-counter');
            const total = dots.length;

            track.addEventListener('scroll', function () {
                const index = Math.max(0, Math.min(total - 1, Math.round(track.scrollLeft / track.clientWidth)));
                dots.forEach(function (d, i) {
                    d.classList.toggle('bg-coffee', i === index);
                    d.classList.toggle('bg-stone-300', i !== index);
                });
                if (counter) counter.textContent = (index + 1) + '/' + total;
            }, { passive: true });
        });
    }
    document.addEventListener('DOMContentLoaded', wire);
    document.addEventListener('turbo:load', wire);
})();
