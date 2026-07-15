<!DOCTYPE html>
<html lang="es" data-bs-theme="dark">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title><?= $this->e($title ?? BUSINESS_NAME) ?></title>

	<link rel="stylesheet" href="<?= RESORUCES_URL; ?>bootstrap/css/bootstrap.min.css">
	<link rel="stylesheet" href="<?= RESORUCES_URL; ?>sweetalert2/sweetalert2.min.css">
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" crossorigin="anonymous">
	<?php if (!empty($useDataTablesResources)) : ?>
		<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css" />
		<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.dataTables.min.css" />
		<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.dataTables.min.css" />
		<?= $datatable->autoLoadCSSResources(); ?>
	<?php endif; ?>
	<?= $this->section('styles') ?>
	<style>
		body {
			padding-top: 66px;
		}
	</style>
</head>

<body>
	<header>
		<?= $this->insert('admin/sections/Nav/mainMenuNav'); ?>
	</header>
	<main class="container mt-5 pt-3">
		<div class="row mb-2">
			<div class="col-12">
				<h1><?= $this->e($h1 ?? '') ?></h1>
			</div>
		</div>
		<?php \App\Core\FlashMessages::display(); ?>
		<?= $this->section('content'); ?>
	</main>

	<script src="https://code.jquery.com/jquery-3.7.0.min.js" crossorigin="anonymous"></script>
	<script src="<?= RESORUCES_URL; ?>bootstrap/js/bootstrap.bundle.min.js"></script>
	<script src="<?= RESORUCES_URL; ?>sweetalert2/sweetalert2.all.min.js"></script>
	<?php if (!empty($useDataTablesResources)) : ?>
		<?= $datatable->autoLoadJsResources(); ?>
		<?= $datatable->autoLoadDatatableJS(); ?>
		<script>
			$(document).ready(function() {
				$(document).on('click', '.btn-danger', function(e) {
					// Delete buttons in a datatable row ask for confirmation.
					if (!$(this).attr('href')) return;
					e.preventDefault();
					var href = $(this).attr('href');
					Swal.fire({
						title: '¿Estás seguro?',
						text: 'Esta acción no se puede revertir.',
						icon: 'warning',
						showCancelButton: true,
						confirmButtonColor: '#3085d6',
						cancelButtonColor: '#d33',
						confirmButtonText: 'Sí, bórralo',
						cancelButtonText: 'Cancelar'
					}).then((result) => {
						if (result.isConfirmed) {
							window.location.href = href;
						}
					});
				});
			});
		</script>
	<?php endif; ?>

	<?= $this->section('scripts') ?>
</body>

</html>
