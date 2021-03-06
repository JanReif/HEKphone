  <h2><?php echo __('phone.edit.title') ?></h2>

<?php include_partial('form', array('form' => $form)) ?>
<?php if($form->getDefault('technology') == 'SIP'): ?>
  <h2>Aktionen</h2>
  <ul id="actions">
    <li><?php echo link_to(__('phone.edit.reset.keepPersonalSettings'), 'phone/reset?id=' . $form->getObject()->getId(),
      array('query_string' => 'overwritePersonalSettings=false',
          'confirm' => __('phone.edit.reset.warning.keeping_personal_settings'))) ?> <br />
    </li>
    <li><?php echo link_to(__('phone.edit.reset.overwritePersonalSettings'), 'phone/reset?id=' . $form->getObject()->getId(),
      array('query_string' => 'overwritePersonalSettings=true',
          'confirm' => __('phone.edit.reset.warning.overwrite_personal_settings'))) ?>
    </li>
  </ul>
<?php endif; ?>
