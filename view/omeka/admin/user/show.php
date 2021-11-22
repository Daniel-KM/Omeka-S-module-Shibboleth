<?php declare(strict_types=1);
/**
 * @var Omeka_View $this
 * @var User $user
 */
$displayEmail = get_option('shibboleth_display_email');

$userTitle = $user->username;
if ($userTitle != '') {
    $userTitle = ': &quot;' . html_escape($userTitle) . '&quot; ';
} else {
    $userTitle = '';
}
$userTitle = __('User #%s', $user->id) . $userTitle;
echo head(['title' => $userTitle, 'bodyclass' => 'themes']);
echo flash();
?>

<?php /*
<?php if (is_allowed('Users', 'edit')): ?>
<p id="edit-item" class="edit-button"><?php
echo link_to($user, 'edit', __('Edit this User'), array('class'=>'edit')); ?></p>
<?php endif; ?>
*/ ?>

<?php if ($displayEmail): ?>
<h2><?php echo __('Username'); ?></h2>
<p><?php echo html_escape($user->username); ?></p>
<?php endif; ?>
<h2><?php echo __('Display Name'); ?></h2>
<p><?php echo html_escape($user->name); ?></p>
<?php if ($displayEmail): ?>
<h2><?php echo __('Email'); ?></h2>
<p><?php echo html_escape($user->email); ?></p>
<?php endif; ?>
<?php fire_plugin_hook('admin_users_show', ['user' => $user, 'view' => $this]); ?>
<?php echo foot();?>
