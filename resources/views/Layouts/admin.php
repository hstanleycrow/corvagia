<!DOCTYPE html>
<html lang="es" data-bs-theme="dark">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title><?= $this->e($title ?? BUSINESS_NAME) ?></title>

	<!-- Vendor assets are pinned CDN builds with SRI. To self-host instead, drop the
	     files under public/ and swap these four tags (see README). -->
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"
		integrity="sha384-9ndCyUaIbzAi2FUVXJi0CjmCapSmO7SnpJef0486qhLnuZ2cdeRhO02iuK6FUUVM" crossorigin="anonymous">
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.27/dist/sweetalert2.min.css"
		integrity="sha384-e9JoBUb50niLuTodlxX3NLZZfrt9fQkX5bihGXOGWD/7QFJoXEH37S2df8UA2ehO" crossorigin="anonymous">
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css"
		integrity="sha384-blOohCVdhjmtROpu8+CfTnUWham9nkX7P7OZQMst+RUnhtoY/9qemFAkIKOYxDI3" crossorigin="anonymous">
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

	<script src="https://code.jquery.com/jquery-3.7.0.min.js"
		integrity="sha384-NXgwF8Kv9SSAr+jemKKcbvQsz+teULH/a5UNJvZc6kP47hZgl62M1vGnw6gHQhb1" crossorigin="anonymous"></script>
	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"
		integrity="sha384-geWF76RCwLtnZ8qwWowPQNguL3RmwHVBC9FhGdlKrxdiJJigb/j/68SIy3Te4Bkz" crossorigin="anonymous"></script>
	<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.27/dist/sweetalert2.all.min.js"
		integrity="sha384-mdoL/5UxiiM5ctOnxLuxKDJy3T8r0cDATSr/QEK/m5xMEgwzfimGt2OK0hjqJp9S" crossorigin="anonymous"></script>
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
