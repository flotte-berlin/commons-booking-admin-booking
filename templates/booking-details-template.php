<div style="width: 100%; float: left; margin-top: 5px;">
  <div style="width: 40%; float: left;">
    <label for="booking-start-date"><?= ___( 'FROM', 'commons-booking-admin-booking', 'from') ?>:</label><br>
    <input  style="width: 150px;" type="date" name="date_start" min="<?= $date_min->format('Y-m-d') ?>" value="<?= $date_start->format('Y-m-d') ?>">
  </div>
  <div style="width: 40%; float: left;">
    <label for="booking-end-date"><?= ___( 'UNTIL', 'commons-booking-admin-booking', 'until') ?>:</label><br>
    <input style="width: 150px;" type="date" name="date_end" min="<?= $date_min->format('Y-m-d') ?>" value="<?= $date_end->format('Y-m-d') ?>">
  </div>
</div>
<div style="width: 100%; float: left; margin-top: 5px;">
  <label for="comment"><?= ___( 'WITH_COMMENT', 'commons-booking-admin-booking', 'with comment') ?>:</label><br>
  <input style="width: 100%;" type="text" name="comment" value="<?= $comment ?>">
</div>
<div style="width: 100%; float: left; margin-top: 5px;">
  <input type="checkbox" name="ignore_closed_days" <?= $ignore_closed_days ? 'checked' : ''?>><?= ___( 'IGNORE_CLOSED_DAYS', 'commons-booking-admin-booking', 'ignore closed days of location for booking start/end') ?>
</div>
<?php if($render_ibiur_option): ?>
  <div style="width: 100%; float: left; margin-top: 5px;">
    <input type="checkbox" name="ignore_blocking_item_usage_restriction" <?= $ignore_blocking_item_usage_restriction ? 'checked' : ''?>><?= ___( 'IGNORE_BLOCKING_ITEM_USAGE_RESTRICTION', 'commons-booking-admin-booking', 'ignore intersection with existing item usage restriction (total breakdown) and bookings inside (that were created before restriction)') ?>
  </div>
<?php endif; ?>
<div style="width: 100%; float: left; margin-top: 5px;">
  <input type="checkbox" name="send_mail" <?= $send_mail ? 'checked' : ''?>><?= ___( 'SEND_CONFIRMATION_MAIL', 'commons-booking-admin-booking', 'send confirmation mail') ?>
</div>
