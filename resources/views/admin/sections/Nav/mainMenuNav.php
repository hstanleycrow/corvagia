<?php

use App\Core\Route;

$dashboardURL = Route::getUrlFromName('admin.dashboard');
$usersURL     = Route::getUrlFromName('admin.usersList');
$logoutURL    = Route::getUrlFromName('admin.logout');
?>
<nav class="navbar fixed-top navbar-expand-xl bg-body-tertiary">
	<div class="container-fluid">
		<a class="navbar-brand" href="<?= $dashboardURL; ?>"><?= BUSINESS_NAME; ?></a>
		<?php if (isLogged()) : ?>
			<button class="navbar-toggler ms-auto" type="button" data-bs-toggle="collapse"
				data-bs-target="#mainNav" aria-controls="mainNav" aria-expanded="false" aria-label="Toggle navigation">
				<span class="navbar-toggler-icon"></span>
			</button>

			<div class="collapse navbar-collapse" id="mainNav">
				<ul class="navbar-nav me-auto mb-2 mb-lg-0">
					<!-- Configuración (add new admin CRUDs here) -->
					<li class="nav-item dropdown">
						<a class="nav-link dropdown-toggle" href="#" role="button"
							data-bs-toggle="dropdown" aria-expanded="false">Configuración</a>
						<ul class="dropdown-menu">
							<li><a class="dropdown-item" href="<?= $usersURL; ?>">Usuarios</a></li>
						</ul>
					</li>
				</ul>

				<ul class="navbar-nav ms-auto">
					<li class="nav-item">
						<a class="nav-link" href="<?= $logoutURL; ?>">
							<i class="fa-solid fa-right-from-bracket me-1"></i>Cerrar sesión
						</a>
					</li>
				</ul>
			</div>
		<?php endif; ?>
	</div>
</nav>
<div class="clearfix"></div>
