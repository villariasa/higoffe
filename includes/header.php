<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$current_role = $_SESSION['role'] ?? null;

// Pages can set these BEFORE including header.php:
//   $page_title — renders a sub-page top bar (back arrow + title) instead
//                 of the main logo bar. $back_url controls the arrow target.
//   $no_shell   — skip top bar entirely (rarely needed).
$page_title = $page_title ?? null;
$back_url = $back_url ?? '/index.php';
$no_shell = $no_shell ?? false;
$is_admin = $current_role === 'admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Higoffe</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
    tailwind.config = {
        theme: {
            extend: {
                colors: {
                    cream:  '#faf7f2',
                    coffee: { light: '#8a6a4f', DEFAULT: '#5c4330', dark: '#3e2d1f' },
                    accent: '#e0701a'
                }
            }
        }
    };
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/nprogress/0.2.0/nprogress.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/nprogress/0.2.0/nprogress.min.js"></script>
    <style>
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
        .line-clamp-1 { display: -webkit-box; -webkit-line-clamp: 1; -webkit-box-orient: vertical; overflow: hidden; }
        /* NProgress recolored to the app accent; hide its spinner */
        #nprogress .bar { background: #e0701a; height: 3px; }
        #nprogress .peg { box-shadow: 0 0 10px #e0701a, 0 0 5px #e0701a; }
        #nprogress .spinner { display: none; }
    </style>
</head>
<body class="bg-stone-200 text-stone-800">
<script>
// Top progress bar on every navigation: link clicks, form submits, and
// back/forward. Finishes as soon as the next page starts rendering.
NProgress.configure({ showSpinner: false, trickleSpeed: 120 });
window.addEventListener('beforeunload', function () { NProgress.start(); });
window.addEventListener('pageshow', function () { NProgress.done(); });
document.addEventListener('DOMContentLoaded', function () { NProgress.done(); });
</script>

<div class="relative mx-auto min-h-screen w-full <?= $is_admin ? 'max-w-5xl bg-cream' : 'max-w-md bg-cream shadow-xl' ?> <?= $current_role && !$is_admin ? 'pb-20' : '' ?>">

<?php if (!$no_shell): ?>
    <?php if ($page_title !== null): ?>
        <!-- Sub-page top bar: back + title -->
        <header class="sticky top-0 z-40 flex items-center gap-2 border-b border-stone-200 bg-cream/95 px-2 py-2.5 backdrop-blur">
            <a href="<?= htmlspecialchars($back_url, ENT_QUOTES, 'UTF-8') ?>" aria-label="Back" class="flex h-9 w-9 items-center justify-center rounded-full active:bg-stone-200">
                <svg class="h-5 w-5 text-stone-700" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5"/></svg>
            </a>
            <h1 class="truncate text-[15px] font-bold text-stone-900"><?= htmlspecialchars($page_title, ENT_QUOTES, 'UTF-8') ?></h1>
        </header>
    <?php else: ?>
        <!-- Main top bar: brand + location + notifications -->
        <header class="sticky top-0 z-40 border-b border-stone-200 bg-cream/95 px-4 py-2.5 backdrop-blur">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="text-lg font-black tracking-tight text-coffee">Higoffe<span class="text-accent">.</span></span>
                </div>
                <div class="flex items-center gap-1">
                    <?php if ($current_role === 'customer' || $current_role === 'seller'): ?>
                        <span class="flex items-center gap-1 rounded-full bg-white px-2.5 py-1 text-[11px] font-semibold text-stone-600 border border-stone-200">
                            <svg class="h-3 w-3 text-accent" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2a7 7 0 0 0-7 7c0 5.25 7 13 7 13s7-7.75 7-13a7 7 0 0 0-7-7Zm0 9.5A2.5 2.5 0 1 1 12 6.5a2.5 2.5 0 0 1 0 5Z"/></svg>
                            Cebu City
                        </span>
                    <?php endif; ?>
                    <?php if ($current_role): ?>
                        <a href="/auth/logout.php" aria-label="Log out" class="flex h-9 w-9 items-center justify-center rounded-full active:bg-stone-200">
                            <svg class="h-5 w-5 text-stone-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9"/></svg>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </header>
    <?php endif; ?>
<?php endif; ?>

<main>
