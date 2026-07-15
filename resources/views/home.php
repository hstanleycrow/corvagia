<?php $this->layout('Layouts/app', ['title' => $title]) ?>

<div class="text-center">
    <h1 class="display-5">Corvagia</h1>
    <p class="lead text-muted">Mini-framework PHP — skeleton</p>

    <div class="card mx-auto mt-4" style="max-width: 420px;">
        <div class="card-body">
            <p class="mb-1">Base de datos:
                <strong><?= $this->e($dbStatus) ?></strong>
            </p>
            <?php if ($userCount !== null) : ?>
                <p class="mb-0">Usuarios en la tabla <code>users</code>:
                    <strong><?= $this->e((string) $userCount) ?></strong>
                </p>
            <?php endif ?>
        </div>
    </div>

    <a href="/login/" class="btn btn-primary mt-4">Ir al login</a>
</div>
