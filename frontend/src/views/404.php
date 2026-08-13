<?php ob_start(); ?>
<main class="login-page">
    <section class="login-card">
        <h1>Page not found</h1>
        <a href="<?= route_url('/') ?>">Return home</a>
    </section>
</main>
<?php $content = ob_get_clean(); require __DIR__ . '/layouts/app.php'; ?>
