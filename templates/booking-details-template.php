<div style="width: 100%; float: left; margin-top: 5px;">
  <div style="width: 40%; float: left;">
    <label for="booking-start-date"><?= ___( 'FROM', 'commons-booking-admin-booking', 'from') ?>:</label><br>
    <input  style="width: 150px;" type="date" name="date_start" min="<?= $date_min->format('Y-m-d') ?>" value="<?= $date_start->format('Y-m-d') ?>">
  </div>
  <div style="width: 40%; float: left;">
    <label for="booking-end-date"><?= ___( 'UNTIL', 'commons-booking-admin-booking', 'until') ?>:</label><br>
    <input style="width: 150px;" type="date" name="date_end" min="<?= $date_min->format('Y-m-d') ?>" value="<?= $date_end->format('Y-m-d') ?>">
  </div>
  <?php if($include_gantt_chart_button): ?>
    <div style="float: right;">
      <label>Buchungsübersicht:</label><br>
      <div style="float: right;">
        <?php
          $chart_date_end = (new DateTime())->setTimestamp(strtotime($date_min->format('Y-m-d').'+ 2 months'));
          $chart_item_id = isset($item_id) ? $item_id : (isset($cb_items[0]) ? $cb_items[0]->ID : '');
          echo do_shortcode('[cb_bookings_gantt_chart item_id="' . $chart_item_id .'?>" date_start="' . $date_min->format('Y-m-d') . '" date_end="' . $chart_date_end->format('Y-m-d') . '"]');
        ?>
      </div>
    </div>
  <?php endif; ?>

</div>
<div style="width: 100%; float: left; margin-top: 5px;">
  <label for="comment"><?= ___( 'WITH_COMMENT', 'commons-booking-admin-booking', 'with comment') ?>:</label><br>
  <input style="width: 100%;" type="text" name="comment" value="<?= $comment ?>">
</div>
<div style="width: 100%; float: left; margin-top: 5px;">
  <input type="checkbox" name="ignore_closed_days" <?= $ignore_closed_days ? 'checked' : ''?>><?= ___( 'IGNORE_CLOSED_DAYS', 'commons-booking-admin-booking', 'ignore closed days of location for booking start/end') ?>
</div>
<?php if($render_ibiur_options): ?>
  <div style="width: 100%; float: left; margin-top: 5px;">
    <input type="checkbox" name="usage_during_restriction" <?= $usage_during_restriction ? 'checked' : ''?>><?= ___( 'USAGE_DURING_RESTRICTION', 'commons-booking-admin-booking', 'ignore intersection with existing item usage restriction (total breakdown) and bookings inside (that were created before restriction)') ?>
  </div>
  <div style="width: 100%; float: left; margin-top: 5px;">
    <input type="checkbox" name="exempt_from_limit" <?= $exempt_from_limit ? 'checked' : ''?>><?= ___( 'EXEMPT_FROM_LIMIT', 'commons-booking-admin-booking', 'usage will be ignored in statistics') ?>
  </div>
<?php endif; ?>

<div style="width: 100%; float: left; margin-top: 5px;">
  <input type="checkbox" name="send_mail" <?= $send_mail ? 'checked' : ''?>><?= ___( 'SEND_CONFIRMATION_MAIL', 'commons-booking-admin-booking', 'send confirmation mail') ?>
</div>

<?php if($include_gantt_chart_button): ?>
  <script>
    jQuery( document ).ready(function($) {
      <?php
        $item_ids = [];
        foreach ($cb_items as $cb_item) {
          $item_ids[] = $cb_item->ID;
        }
      ?>
      var nonces = <?= json_encode(CB_Bookings_Gantt_Chart_Shortcode::create_item_chart_nonces($item_ids)); ?>

      $('.cb-booking-gantt-chart-button').click((ev) => {
        ev.preventDefault();
      });

      $('select[name="item_id"]').first().change(function() {
        console.log('change item to: ', $(this).val());

        $('.cb-booking-gantt-chart-button').attr('data-item_id', $(this).val());
        $('.cb-booking-gantt-chart-button').attr('data-nonce', nonces[$(this).val()]);

        $('.cb-bookings-gantt-chart-close').click();
      });
    });
  </script>
<?php endif; ?>
