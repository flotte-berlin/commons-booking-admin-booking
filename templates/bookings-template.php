<div class="wrap">

<h3><?= ___('CREATE_BOOKING', 'commons-booking-admin-booking', 'Create Booking') ?> </h3>
<p>
<?= ___( 'ADMIN_DESCRIPTION_L1', 'commons-booking-admin-booking', 'Here you can create bookings for other users independently from calendar period in the Commons Booking settings,') ?><br>
<?= ___( 'ADMIN_DESCRIPTION_L2', 'commons-booking-admin-booking', 'i.e. to block certain dates or to plan longer in advance.') ?>
</p>

  <form method="POST">
    <input type="hidden" name="action" value="cb-booking-create">
    <div style ="display: inline-block; min-width: 500px;">
      <div>
        <div style="width: 40%; float: left;">
          <label for="item_id"><?= ___( 'ITEM', 'commons-booking-admin-booking', 'book item') ?> </label><br>
          <select name="item_id">
            <?php foreach ($cb_items as $cb_item): ?>
              <option value="<?= $cb_item->ID ?>" <?= $cb_item->ID == $item_id ? 'selected' : '' ?>><?= $cb_item->post_title ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div style="width: 60%; float: left;">
          <label for="user_id"><?= ___( 'FOR_USER', 'commons-booking-admin-booking', 'for user') ?> </label><br>
          <select name="user_id" placeholder="<?= ___( 'NAME', 'commons-booking-admin-booking', 'name') ?>...">
            <option value=""></option>
            <?php foreach ($users as $user): ?>
              <option value="<?= $user->ID ?>" <?= $user->ID == $user_id ? 'selected' : '' ?>><?= $user->first_name ?> <?= $user->last_name ?> (<?= $user->display_name ?>)</option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div>
        <div style="width: 40%; float: left;">
          <label for="booking-start-date"><?= ___( 'FROM', 'commons-booking-admin-booking', 'from') ?> </label><br>
          <input  style="width: 125px;" type="date" name="date_start" min="<?= $date_min->format('Y-m-d') ?>" value="<?= $date_start->format('Y-m-d') ?>">
        </div>
        <div style="width: 40%; float: left;">
          <label for="booking-end-date"><?= ___( 'UNTIL', 'commons-booking-admin-booking', 'until') ?> </label><br>
          <input style="width: 125px;" type="date" name="date_end" min="<?= $date_min->format('Y-m-d') ?>" value="<?= $date_end->format('Y-m-d') ?>">
        </div>
      </div>
      <div style="width: 100%; float: left; margin-top: 5px;">
        <label for="comment">mit Kommentar (optional)</label><br>
        <input style="width: 100%;" type="text" name="comment" value="<?= $comment ?>">
      </div>
      <div style="width: 100%; float: left; margin-top: 5px;">
        <?= ___( 'BOOK_AND', 'commons-booking-admin-booking', 'and') ?>
        <input type="checkbox" name="send_mail" <?= $send_mail ? 'checked' : ''?>><?= ___( 'SEND_CONFIRMATION_MAIL', 'commons-booking-admin-booking', 'send confirmation mail') ?>
      </div>

      <input style="float: right;" id="cb-codes-export" class="button action" value="<?= ___( 'EXECUTE', 'commons-booking-admin-booking', 'make it so') ?>" type="submit">
    </div>

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
  'width': '100%',
  'display': 'inline-block',
  'vertical-align': 'top',
  'margin-top': '2px'
});

jQuery('.selectize-input').css({
  'padding': '4.5px',
  'border-radius': '0px'
});
</script>
