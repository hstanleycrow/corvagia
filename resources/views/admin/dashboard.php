<?php $this->layout('Layouts/admin', ['title' => $title, 'h1' => 'Dashboard']) ?>

<div class="alert alert-success">
    Área protegida de ejemplo. Llegaste aquí porque el middleware <code>auth</code> encontró una sesión activa.
</div>
<a href="<?= App\Core\Route::getUrlFromName('admin.usersList') ?>" class="btn btn-primary">
    <i class="fa-solid fa-users me-1"></i>Administrar usuarios
</a>
