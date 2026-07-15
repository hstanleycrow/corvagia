<!DOCTYPE html>
<html lang="es" data-bs-theme="dark">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $this->e($title ?? 'Corvagia') ?></title>
    <link rel="stylesheet" href="<?= RESORUCES_URL ?>bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= RESORUCES_URL ?>sweetalert2/sweetalert2.min.css">
</head>

<body>
    <main class="container py-5">
        <?php \App\Core\FlashMessages::display(); ?>
        <?= $this->section('content') ?>
    </main>
    <script src="<?= RESORUCES_URL ?>bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="<?= RESORUCES_URL ?>sweetalert2/sweetalert2.all.min.js"></script>
</body>

</html>
