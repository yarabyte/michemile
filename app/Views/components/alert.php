<?php
$type = $type ?? 'ok';
$message = $message ?? '';
if ($message === '') {
    return;
}
$class = $type === 'error' ? 'ui-alert ui-alert-error' : 'ui-alert ui-alert-ok';
?>
<div class="<?= e($class) ?>" role="status"><?= e($message) ?></div>
