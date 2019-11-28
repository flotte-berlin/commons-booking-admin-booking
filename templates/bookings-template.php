<div class="wrap">

<h3><?= ___('CREATE_BOOKING', 'commons-booking-admin-booking', 'Create Booking') ?> </h3>
<p>
<?= ___( 'ADMIN_DESCRIPTION_L1', 'commons-booking-admin-booking', 'Here you can create bookings for other users independently from calendar period in the Commons Booking settings,') ?><br>
<?= ___( 'ADMIN_DESCRIPTION_L2', 'commons-booking-admin-booking', 'i.e. to block certain dates or to plan longer in advance.') ?>
</p>

  <div id="admin-booking-error-wrapper"></div>

  <form id="admin-booking-form" method="POST">
    <input type="hidden" name="action" value="cb-booking-create">
    <div style ="display: inline-block; width: 600px;">
      <div>
        <div style="width: 40%; float: left;">
          <label for="booking_mode"><?= ___( 'BOOKING_MODE', 'commons-booking-admin-booking', 'booking mode') ?>:</label>
        </div>
        <div style="width: 60%; float: left;">
          <input type="radio" name="booking_mode" value="1"checked><label for="booking_mode"><?= ___( 'BOOKING_MODE_SINGLE', 'commons-booking-admin-booking', 'single booking') ?></label>
          <input type="radio" name="booking_mode" value="2" style="margin-left: 20px;"><label for="booking_mode"><?= ___( 'BOOKING_MODE_SERIAL', 'commons-booking-admin-booking', 'serial booking') ?></label>
        </div>
      </div>
      <div style="width: 100%; float: left; margin-top: 5px;">
        <div style="width: 40%; float: left;">
          <label for="item_id"><?= ___( 'ITEM', 'commons-booking-admin-booking', 'book item') ?>:</label><br>
          <select name="item_id">
            <?php foreach ($cb_items as $cb_item): ?>
              <option value="<?= $cb_item->ID ?>" <?= $cb_item->ID == $item_id ? 'selected' : '' ?>><?= $cb_item->post_title ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div style="width: 60%; float: left;">
          <label for="user_id"><?= ___( 'FOR_USER', 'commons-booking-admin-booking', 'for user') ?>:</label><br>
          <select name="user_id" placeholder="<?= ___( 'NAME', 'commons-booking-admin-booking', 'name') ?>...">
          </select>
        </div>
      </div>
      <div id="weekdays-wrapper" style="width: 100%; float: left; margin-top: 5px; display: none;">
        <div style="width: 40%; float: left;">
          <label for="weekdays"><?= ___( 'WEEKDAYS', 'commons-booking-admin-booking', 'weekdays') ?>:</label>
        </div>
        <div style="width: 60%; float: left;">
          <div style="display: inline-block; text-align: center;"><?= ___( 'MONDAY', 'commons-booking-admin-booking', 'mon') ?><br><input type="checkbox" name="weekdays[]" value="1"></div>
          <div style="display: inline-block; text-align: center;"><?= ___( 'TUESDAY', 'commons-booking-admin-booking', 'tue') ?><br><input type="checkbox" name="weekdays[]" value="2"></div>
          <div style="display: inline-block; text-align: center;"><?= ___( 'WEDNESDAY', 'commons-booking-admin-booking', 'wed') ?><br><input type="checkbox" name="weekdays[]" value="3"></div>
          <div style="display: inline-block; text-align: center;"><?= ___( 'THURSDAY', 'commons-booking-admin-booking', 'thu') ?><br><input type="checkbox" name="weekdays[]" value="4"></div>
          <div style="display: inline-block; text-align: center;"><?= ___( 'FRIDAY', 'commons-booking-admin-booking', 'fri') ?><br><input type="checkbox" name="weekdays[]" value="5"></div>
          <div style="display: inline-block; text-align: center;"><?= ___( 'SATURDAY', 'commons-booking-admin-booking', 'sat') ?><br><input type="checkbox" name="weekdays[]" value="6"></div>
          <div style="display: inline-block; text-align: center;"><?= ___( 'SUNDAY', 'commons-booking-admin-booking', 'sun') ?><br><input type="checkbox" name="weekdays[]" value="7"></div>
        </div>
      </div>
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
      <div style="width: 100%; float: left; margin-top: 5px;">
        <input type="checkbox" name="send_mail" <?= $send_mail ? 'checked' : ''?>><?= ___( 'SEND_CONFIRMATION_MAIL', 'commons-booking-admin-booking', 'send confirmation mail') ?>
      </div>

      <input style="float: right; width: 100px;" id="submit-booking" class="button action" value="<?= ___( 'EXECUTE', 'commons-booking-admin-booking', 'make it so') ?>" type="submit">
    </div>

  </form>
</p>

</div>

<div id="cb-admin-booking-serial-confirm-dialog" class="hidden"></div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/selectize.js/0.12.4/js/standalone/selectize.js"></script>

<script>
jQuery('head').append('<link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/selectize.js/0.12.4/css/selectize.min.css">');

var $selectize = jQuery('select[name=user_id]').selectize({
    valueField: 'id',
    labelField: 'name',
    score: function() { return function() { return 1; }; }, //to keep search query, see https://stackoverflow.com/a/35920145
    load: function(query, callback) {
      var select = $selectize[0].selectize;
      if (!query.length || query.length < 3) return callback();
      jQuery.ajax({
        url: '<?= get_site_url(null, '', null) . '/wp-admin/admin-ajax.php' ?>',
        type: 'POST',
        dataType: 'JSON',
        data: { action : 'cb_admin_booking_user_search' , q: query},
        error: function() {
          callback();
        },
        success: function(res) {
          if(res.length == 0) {
            select.close();
          }

          select.clearOptions();
          callback(res);
          console.log(res);
        }
      });
    }
});

jQuery('.selectize-input').append('<span class="dashicons dashicons-image-rotate"></span>');

</script>

<script>

jQuery(document).ready(function ($) {

  function render_notice(success, message) {
    var $error_wrapper = $('#admin-booking-error-wrapper');
    var notice_class = success ? 'notice-success' : 'notice-error';
    var $error = $('<div class="notice ' + notice_class + '"><p>' + message + '</p></div>');

    setTimeout(function() {
      $error_wrapper.html($error);
    }, 0);
  }

  if(booking_result != undefined) {
    console.log(booking_result);

    render_notice(booking_result.success, booking_result.message)
  }

  $('input[name="booking_mode"]').change(function() {
    var booking_mode = $('input[name="booking_mode"]:checked').val();

    if(booking_mode == 2) {
      $('#weekdays-wrapper').show();
    }
    else {
      $('#weekdays-wrapper').hide();
    }
  })

  var button_text = $('#submit-booking').val();
  var loading_interval;

  function start_loading() {
    $('#admin-booking-error-wrapper').html('');

    $('#submit-booking').prop("disabled",true);
    var loading_text = '.';
    $('#submit-booking').val(loading_text);
    loading_interval = setInterval(function() {
      loading_text = $('#submit-booking').val();
      if(loading_text.length < 9) {
        loading_text += '..';
      }
      else {
        loading_text = '.';
      }
      $('#submit-booking').val(loading_text);
    }, 250);
  }

  function stop_loading() {
    clearInterval(loading_interval);
    $('#submit-booking').val(button_text);
    $('#submit-booking').prop("disabled",false);
  }

  $('input[name="booking_mode"]').change();

  $('#submit-booking').click(function(event) {
    start_loading();

    var booking_mode = $('input[name="booking_mode"]:checked').val();

    if(booking_mode == 2) {
      event.preventDefault();

      var $form = $('#admin-booking-form');
      var url = "<?= get_site_url(null, '', null) . '/wp-admin/admin-ajax.php' ?>";
      var payload = {
        weekdays: []
      };
      $form.serializeArray().forEach(function(item) {
        if(item.name == 'weekdays[]') {
          payload.weekdays.push(item.value);
        }
        else {
          payload[item.name] = item.value;
        }

      });
      payload.action  ='cb_admin_booking_serial';

      jQuery.post(url, payload, function(response) {
        stop_loading();
        var data = JSON.parse(response);
        console.log('data: ', data);
        if(data.state == 'booking') {

          if(data.bookings) {
            var $dialog = $('#cb-admin-booking-serial-confirm-dialog');
            $dialog.data(data);
            $dialog.dialog('open');
          }
          else {
            render_notice(false, data.message);
          }

        }
        else if(data.state == 'validation') {
          //handle input errors
          render_notice(false, data.message);
        }

      });

    }
  });

  //helper div for correct positioning of dialogs
  var $overlay = $('<div id="positioning-overlay" style="position: fixed; top: 0; left: 0; bottom: 0; right: 0; display: none;"></div>');
  $('body').append($overlay);

  var locale = '<?= str_replace('_', '-', get_locale()) ?>';
  function format_date(date_string) {
    var date_format_options = { year: 'numeric', month: '2-digit', day: '2-digit' };
    var date = new Date(Date.parse(date_string));
    return date.toLocaleDateString(locale, date_format_options);
  }

  var $dialog = $('#cb-admin-booking-serial-confirm-dialog');
  $dialog.dialog({
    title: '<?= ___('SERIAL_BOOKING_CONFIRM_DIALOG_TITLE', 'commons-booking-admin-booking', 'Confirm Bookings') ?>',
    dialogClass: 'wp-dialog',
    autoOpen: false,
    draggable: false,
    width: 800,
    modal: true,
    resizable: false,
    closeOnEscape: true,
    position: {
      my: "top",
      at: "top+10%",
      of: '#positioning-overlay'
    },
    open: function (event) {
      // close dialog by clicking the overlay behind it
      $('.ui-widget-overlay').bind('click', function(){
        $dialog.dialog('close');
      })

      // hide close button, because of a styling issue
      $(".ui-dialog-titlebar-close").hide();

      var data = $(this).data();
      console.log(data);

      var $table = $(
        '<table class="wp-list-tables widefat"><thead><tr>' +
          '<th style="width: 30px;"><?= ___('NR', 'commons-booking-admin-booking', 'nr') ?></th>' +
          '<th style="width: 100px;"><?= ___('FROM', 'commons-booking-admin-booking', 'from') ?></th>' +
          '<th style="width: 100px;"><?= ___('UNTIL', 'commons-booking-admin-booking', 'until') ?></th>' +
          '<th><?= ___('ERROR', 'commons-booking-admin-booking', 'error') ?></th>' +
        '</tr></head></table>'
      );
      var $tbody = $('<tbody></tbody>');

      var bookings_to_confirm = 0;
      data.bookings.forEach(function(booking, index) {
        var color = booking.result.success == true ? 'rgb(50, 55, 60)' : '#a00';
        if(booking.result.success) {
          bookings_to_confirm++;
        }
        var $row = $('<tr></tr>');
        var style = 'style="color: ' + color + ';"';
        var count = index + 1;
        $row.append('<td ' + style + '>' + count + '</td>')
        $row.append('<td ' + style + '>' + format_date(booking.date_start) + '</td>');
        $row.append('<td ' + style + '>' + format_date(booking.date_end) + '</td>');
        var message = booking.result.message == null ? '-' : booking.result.message;
        $row.append('<td '+ style + '">' + message + '</td>');
        $tbody.append($row);
      })
      $table.append($tbody);

      $dialog.html($table);

      var disabled = bookings_to_confirm == 0 ? ' disabled' : ''
      var $confirm_button = $('<button class="button action" style="margin-top: 10px; float: right;"' + disabled + '><?= ___('CONFIRM', 'commons-booking-admin-booking', 'confirm') ?></button>');

      $confirm_button.click(function() {
        $confirm_button.prop('disabled', true);
        var $form = $('#admin-booking-form');
        $form.submit();
      });

      $dialog.append($confirm_button);

    },
    close: function() {
      $overlay.hide();
    },
    create: function () {

    },
  });
});


</script>
