<?php

use App\Components\Buttons\SaveButton;
use App\Components\Buttons\CancelButton;

$this->layout('Layouts/admin', [
    'title'                  => $title,
    'h1'                     => $h1,
    'action'                 => $action,
    'formAction'             => $formAction,
    'record'                 => $record,
    'useDataTablesResources' => false,
]);

if (isset($_SESSION['errors']) && count($_SESSION['errors']) > 0) {
    $errors = $_SESSION['errors'];
    unset($_SESSION['errors']);
}

// Defaults
$id       = 0;
$name     = '';
$username = '';

// Restore from session on validation error
if (!empty($_SESSION['formData'])) {
    $id       = $_SESSION['formData']['id']       ?? 0;
    $name     = $_SESSION['formData']['name']     ?? '';
    $username = $_SESSION['formData']['username'] ?? '';
    unset($_SESSION['formData']);
}

// Populate from DB record (edit mode)
if (!empty($record)) {
    $id       = $record['id'];
    $name     = $record['name'];
    $username = $record['username'];
}
?>

<div class="container mt-5">
    <form method="POST" action="<?= $formAction ?>" class="needs-validation" novalidate>

        <?php if ($action === 'edit') : ?>
            <div class="mb-3">
                <label class="form-label">ID</label>
                <div class="col-sm-4">
                    <input type="text" class="form-control" name="id" value="<?= (int)$id ?>" readonly>
                </div>
            </div>
        <?php endif; ?>

        <!-- Nombre -->
        <div class="mb-3">
            <label for="name" class="form-label">Nombre completo</label>
            <div class="col-sm-6">
                <input type="text"
                    class="form-control <?= !empty($errors['name']) ? 'is-invalid' : '' ?>"
                    name="name" id="name" autocomplete="off"
                    value="<?= htmlspecialchars($name) ?>">
            </div>
            <?php if (!empty($errors['name'])) : ?>
                <div class="text-danger small"><?= $errors['name'] ?></div>
            <?php endif; ?>
        </div>

        <!-- Usuario -->
        <div class="mb-3">
            <label for="username" class="form-label">Nombre de usuario</label>
            <div class="col-sm-6">
                <input type="text"
                    class="form-control <?= !empty($errors['username']) ? 'is-invalid' : '' ?>"
                    name="username" id="username" autocomplete="off"
                    value="<?= htmlspecialchars($username) ?>">
            </div>
            <?php if (!empty($errors['username'])) : ?>
                <div class="text-danger small"><?= $errors['username'] ?></div>
            <?php endif; ?>
        </div>

        <!-- Contraseña -->
        <div class="mb-3">
            <label for="password" class="form-label">
                Contraseña
                <?php if ($action === 'edit') : ?>
                    <small class="text-muted fw-normal">(dejar vacío para no cambiarla)</small>
                <?php endif; ?>
            </label>
            <div class="col-sm-6">
                <input type="password"
                    class="form-control <?= !empty($errors['password']) ? 'is-invalid' : '' ?>"
                    name="password" id="password" autocomplete="new-password">
            </div>
            <?php if (!empty($errors['password'])) : ?>
                <div class="text-danger small"><?= $errors['password'] ?></div>
            <?php endif; ?>
        </div>

        <!-- Activo (ENUM dropdown built in the controller) -->
        <div class="mb-3">
            <label for="active" class="form-label">Estado</label>
            <div class="col-sm-4">
                <?= $activeDropdown ?>
            </div>
            <?php if (!empty($errors['active'])) : ?>
                <div class="text-danger small"><?= $errors['active'] ?></div>
            <?php endif; ?>
        </div>

        <!-- isAdmin (ENUM dropdown built in the controller) -->
        <div class="mb-3">
            <label for="isAdmin" class="form-label">Rol</label>
            <div class="col-sm-4">
                <?= $isAdminDropdown ?>
            </div>
            <?php if (!empty($errors['isAdmin'])) : ?>
                <div class="text-danger small"><?= $errors['isAdmin'] ?></div>
            <?php endif; ?>
        </div>

        <div class="clearfix"></div>
        <div id="buttons" class="mt-4">
            <?= (new SaveButton('Guardar'))->render() ?>
            <?= (new CancelButton('/admin/users/', 'Cancelar'))->render() ?>
        </div>

    </form>
</div>

<?php $this->start('scripts') ?>
<?php $this->stop() ?>
