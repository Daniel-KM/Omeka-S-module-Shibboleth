<?php declare(strict_types=1);
/**
 * @var Omeka_View $this
 */
?>

<fieldset id="fieldset-shibboleth-main"><legend><?php echo __('Users management'); ?></legend>
    <div class="field">
        <div class="two columns alpha">
            <?php echo $this->formLabel('shibboleth_display_email', __('Display user email in users pages')); ?>
        </div>
        <div class='inputs five columns omega'>
            <?php echo $this->formCheckbox('shibboleth_display_email', true, ['checked' => (bool) get_option('shibboleth_display_email')]); ?>
        </div>
    </div>
</fieldset>
