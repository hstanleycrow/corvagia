<?php

$this->layout('Layouts/admin', [
    'title'                  => $title,
    'h1'                     => $h1,
    'datatable'              => $datatable,
    'useDataTablesResources' => true,
]);
?>
<?= $datatable->setTableId('users')->render(); ?>

<?php $this->start('scripts') ?>
<?php $this->stop() ?>
