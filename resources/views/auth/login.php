<?php $this->layout('Layouts/app', ['title' => $title]) ?>

<div class="row justify-content-center">
    <div class="col-sm-8 col-md-5 col-lg-4">
        <h1 class="h4 mb-4 text-center"><?= $this->e(BUSINESS_NAME) ?></h1>

        <form method="POST" action="/login/" class="card p-4">
            <input type="hidden" name="csrf_token" value="<?= $this->e($csrfToken ?? '') ?>">

            <div class="mb-3">
                <label class="form-label" for="username">Usuario</label>
                <input class="form-control" id="username" name="username" autocomplete="username" autofocus>
            </div>
            <div class="mb-3">
                <label class="form-label" for="password">Clave</label>
                <input type="password" class="form-control" id="password" name="password" autocomplete="current-password">
            </div>
            <button type="submit" class="btn btn-primary w-100">Entrar</button>
        </form>
    </div>
</div>
