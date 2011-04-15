<?php use_javascript('jquery-get_charges.js') ?>

<div class="search-charges">
  <h1><?php echo __("calls.charges.heading") ?></h1>
  <form action="<?php echo url_for('get_charges') ?>" method="get">
    <input type="text" name="destination" value="<?php echo $sf_request->getParameter('destination') ?>" id="destination" />
    <input type="submit" value="<?php echo __('calls.charges.get')?>" />
    <img id="loader" src="/images/loader.gif" style="vertical-align: middle; display: none; margin: 0; padding: 0; width: 1em; height: auto" />
    <span id="charges"><?php echo $sf_request->getParameter('charges')?></span>
    <div class="help">
      <?php echo __('calls.charges.help')?>
    </div>
  </form>
</div>