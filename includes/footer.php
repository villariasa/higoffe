    </main>
</div><!-- /content column -->
</div><!-- /shell -->
<?php if (empty($hide_bottom_nav)) require_once __DIR__ . '/bottom_nav.php'; ?>
<?php if ($is_app_user): ?>
    <script>
    // Main "shell" tour — the app-wide orientation walkthrough. Fires once
    // ever per account (localStorage-gated in tour.js) on whichever page
    // the user happens to land on first, since this runs from the one
    // footer every app page shares.
    //
    // tour.js is loaded with `defer`, so it only runs AFTER the whole page
    // has finished parsing — this script tag (plain, inline, in the body)
    // would otherwise execute first and crash on window.HigoffeTour being
    // undefined. Deferring the call itself to DOMContentLoaded (which
    // always fires after every deferred script has run) fixes that.
    function higoffeRunShellTour() {
        if (!window.HigoffeTour) return;
        var role = <?= json_encode($current_role) ?>;
        var steps = [
            { selector: '[data-tour="nav-home"]', title: 'Home', description: role === 'seller' ? "This is your seller dashboard — new inquiries, active chats, and today's stats." : 'Your feed — posts from cafés and people you follow.' }
        ];
        if (role === 'customer') {
            steps.push({ selector: '[data-tour="nav-explore"]', title: 'Explore cafés', description: 'Discover nearby cafés and browse menus.' });
        } else {
            steps.push({ selector: '[data-tour="nav-feed"]', title: 'Social feed', description: 'See what customers and other cafés are posting.' });
        }
        steps.push({ selector: '[data-tour="nav-post"]', title: 'Share a post', description: 'Post a photo — tag a café or a menu item, or check in.' });
        if (role === 'customer') {
            steps.push({ selector: '[data-tour="nav-inbox"]', title: 'Inbox', description: 'Chat with cafés here once you message one.' });
        } else {
            steps.push({ selector: '[data-tour="nav-menu"]', title: 'Menu', description: 'Add products, sizes and add-ons customers can order.' });
        }
        steps.push({ selector: '[data-tour="nav-orders"]', title: 'Orders', description: role === 'seller' ? 'Track every order from Pending to Completed.' : 'Your order history and live order tracking.' });
        steps.push({ selector: '.location-chip', title: 'Set your location', description: 'Share your location to sort cafés by real distance.' });
        steps.push({ selector: '[data-tour="notif-bell"]', title: 'Notifications', description: 'Likes, comments, reviews and new chats show up here.' });
        steps.push({ selector: '[data-tour="nav-profile"]', title: 'Profile', description: 'Your profile, bio, and app settings — including restarting this tour.' });

        window.HigoffeTour.run('shell', steps);
    }
    document.addEventListener('DOMContentLoaded', higoffeRunShellTour);
    document.addEventListener('turbo:load', higoffeRunShellTour);
    </script>
<?php endif; ?>
</body>
</html>
