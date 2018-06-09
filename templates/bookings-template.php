<div class="wrap">

<h3><?= __('CREATE_BOOKING', 'commons-booking-admin-booking') ?> </h3>
<p>
<?= __( 'ADMIN_DESCRIPTION_L1', 'commons-booking-admin-booking') ?><br>
<?= __( 'ADMIN_DESCRIPTION_L2', 'commons-booking-admin-booking') ?>
</p>

  <form method="POST">
    <input type="hidden" name="action" value="cb-booking-create">
    <label for="item_id"><?= __( 'ARTICLE', 'commons-booking-admin-booking') ?> </label>
    <select name="item_id">
      <?php foreach ($cb_items as $cb_item): ?>
        <option value="<?= $cb_item->ID ?>" <?= $cb_item->ID == $item_id ? 'selected' : '' ?>><?= $cb_item->post_title ?></option>
      <?php endforeach; ?>
    </select>
    <label for="user_id"><?= __( 'FOR_USER', 'commons-booking-admin-booking') ?> </label>
    <select name="user_id" placeholder="<?= __( 'NAME', 'commons-booking-admin-booking') ?>...">
      <option value=""></option>
      <?php foreach ($users as $user): ?>
        <option value="<?= $user->ID ?>" <?= $user->ID == $user_id ? 'selected' : '' ?>><?= $user->first_name ?> <?= $user->last_name ?> (<?= $user->display_name ?>)</option>
      <?php endforeach; ?>
    </select>
    <label for="booking-start-date"><?= __( 'FROM', 'commons-booking-admin-booking') ?> </label>
      <input type="date" name="date_start" min="<?= $date_min->format('Y-m-d') ?>" value="<?= $date_start->format('Y-m-d') ?>">
    <label for="booking-end-date"><?= __( 'UNTIL', 'commons-booking-admin-booking') ?> </label>
    <input type="date" name="date_end" min="<?= $date_min->format('Y-m-d') ?>" value="<?= $date_end->format('Y-m-d') ?>">
    <?= __( 'BOOK_AND', 'commons-booking-admin-booking') ?>
    <input type="checkbox" name="send_mail" <?= $send_mail ? 'checked' : ''?>><?= __( 'SEND_CONFIRMATION_MAIL', 'commons-booking-admin-booking') ?>
    | <input id="cb-codes-export" class="button action" value="<?= __( 'CONFIRM', 'commons-booking-admin-booking') ?>" type="submit">
  </form>
</p>

</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/selectize.js/0.12.4/js/standalone/selectize.js"></script>

<script>
jQuery('head').append('<link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/selectize.js/0.12.4/css/selectize.min.css">');

jQuery('select[name=user_id]').selectize({
    sortField: 'text'
});

jQuery('.selectize-control').css({
  'width': '300px',
  'display': 'inline-block',
  'vertical-align': 'top',
  'margin-top': '2px'
});

jQuery('.selectize-input').css({
  'padding': '4.5px',
  'border-radius': '0px'
});
</script>
