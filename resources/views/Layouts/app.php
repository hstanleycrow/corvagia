<!DOCTYPE html>
<html lang="es" data-bs-theme="dark">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $this->e($title ?? 'Corvagia') ?></title>
    <!-- Public front end: its own vendor tags, independent from the admin layout.
         Swap these for Tailwind or anything else without touching the admin panel. -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"
        integrity="sha384-9ndCyUaIbzAi2FUVXJi0CjmCapSmO7SnpJef0486qhLnuZ2cdeRhO02iuK6FUUVM" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.27/dist/sweetalert2.min.css"
        integrity="sha384-e9JoBUb50niLuTodlxX3NLZZfrt9fQkX5bihGXOGWD/7QFJoXEH37S2df8UA2ehO" crossorigin="anonymous">
</head>

<body>
    <main class="container py-5">
        <?php \App\Core\FlashMessages::display(); ?>
        <?= $this->section('content') ?>
    </main>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-geWF76RCwLtnZ8qwWowPQNguL3RmwHVBC9FhGdlKrxdiJJigb/j/68SIy3Te4Bkz" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.27/dist/sweetalert2.all.min.js"
        integrity="sha384-mdoL/5UxiiM5ctOnxLuxKDJy3T8r0cDATSr/QEK/m5xMEgwzfimGt2OK0hjqJp9S" crossorigin="anonymous"></script>
</body>

</html>
