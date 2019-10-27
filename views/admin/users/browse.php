<?php
/**
 * @var Omeka_View $this
 * @var User[] $users
 * @var int $total_results
 */

$displayEmail = get_option('shibboleth_display_email');

$pageTitle = __('Browse Users') . ' ' . __('(%s total)', $total_results);
echo head(array('title' => $pageTitle, 'bodyclass' => 'users'));
echo flash();
?>

<?php /* ?>
<?php if (is_allowed('Users', 'add')): ?>
    <?php echo link_to('users', 'add', __('Add a User'), array('class'=>'small green button')); ?>
<?php endif; ?>
*/ ?>

<?php if (isset($_GET['search'])):?>
<div id='search-filters'>
    <ul>
        <li>
        <?php if ($displayEmail): ?>
        <?php switch ($_GET['search-type']) {
                        case "name":
                            echo __("Name") . ': ';
                        break;
                        case "username":
                            echo __("Username") . ': ';
                        break;
                        case "email":
                            echo __("Email") . ': ';
                        break;
                    }
        else:
            echo __("Name") . ': ';
        endif;
        ?>
        <?php echo html_escape($_GET['search']); ?>
        </li>
    </ul>

</div>
<?php endif; ?>

<form id='search-users' method='GET'>
<button><?php echo __('Search users'); ?></button><input type='text' name='search' aria-label="<?php echo __('Search users'); ?>"/>
<?php if ($displayEmail): ?>
<label><input type='radio' name='search-type' value='username' checked='checked' /><?php echo __('Username'); ?></label>
<label><input type='radio' name='search-type' value='name' /><?php echo __('Display Name'); ?></label>
<label><input type='radio' name='search-type' value='email' /><?php echo __('Email'); ?></label>
<?php else: ?>
<input type="hidden" name='search-type' value='name' />
<?php endif; ?>

</form>

<?php echo pagination_links(); ?>
<table id="users">
    <thead>
        <tr>
        <?php if ($displayEmail): ?>
        <?php $sortLinks = array(
                __('Username') => 'username',
                __('Display Name') => 'name',
                __('Email') => 'email',
                __('Role') => 'role',
                );
        else:
            $sortLinks = array(
                __('Display Name') => 'name',
                __('Role') => 'role',
            );
        endif;
        ?>

        <?php echo browse_sort_links($sortLinks, array('link_tag' => 'th scope="col"', 'list_tag' => '')); ?>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($users as $key => $user): ?>
        <tr class="<?php if (current_user()->id == $user->id) echo 'current-user '; ?><?php if($key%2==1) echo 'even'; else echo 'odd'; ?><?php if(!$user->active): ?> inactive<?php endif; ?>">
            <?php if ($displayEmail): ?>
            <td>
            <?php echo html_escape($user->username); ?> <?php if (!$user->active): ?>(<?php echo __('inactive'); ?>)<?php endif; ?>
<?php /*
            <ul class="action-links group">
                <?php if (is_allowed($user, 'edit')): ?>
                <li><?php echo link_to($user, 'edit', __('Edit'), array('class'=>'edit')); ?></li>
                <?php endif; ?>
                <?php if (is_allowed($user, 'delete')): ?>
                <li><?php echo link_to($user, 'delete-confirm', __('Delete'), array('class'=>'delete-confirm')); ?></li>
                <?php endif; ?>
            </ul>
*/ ?>
            <?php fire_plugin_hook('admin_users_browse_each', array('user' => $user, 'view' => $this)); ?>
           </td>
           <?php endif; ?>
            <td><?php echo html_escape($user->name); ?></td>
            <?php if ($displayEmail): ?>
            <td><?php echo html_escape($user->email); ?></td>
            <?php endif; ?>
            <td><span class="<?php echo html_escape($user->role); ?>"><?php echo html_escape(__(Inflector::humanize($user->role))); ?></span></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php echo pagination_links(); ?>
<?php fire_plugin_hook('admin_users_browse', array('users' => $users, 'view' => $this)); ?>
<?php echo foot();?>
