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

      <?php include( CB_ADMIN_BOOKING_PATH . 'templates/booking-details-template.php' ); ?>

      <input style="float: right; width: 100px;" id="submit-booking" class="button action" value="<?= ___( 'EXECUTE', 'commons-booking-admin-booking', 'make it so') ?>" type="submit">
    </div>

  </form>
</p>

</div>

<div id="booking-serial-confirm-modal" class="hidden">
  <table class="wp-list-tables widefat">
    <thead>
      <tr>
        <th style="width: 30px;"><?= ___('NR', 'commons-booking-admin-booking', 'nr') ?></th>
        <th style="width: 100px;"><?= ___('FROM', 'commons-booking-admin-booking', 'from') ?></th>
        <th style="width: 100px;"><?= ___('UNTIL', 'commons-booking-admin-booking', 'until') ?></th>
        <th><?= ___('ERROR', 'commons-booking-admin-booking', 'error') ?></th>
      </tr>
    </thead>
    <tbody></tbody>
  </table>

  <button id="booking-serial-confirm-button" class="button action" style="margin-top: 10px; float: right;"><?= ___('CONFIRM', 'commons-booking-admin-booking', 'confirm') ?></button>
</div>

<div id="booking-edit-modal" class="hidden">
  <div id="booking-edit-notice-wrapper"></div>
  <table style="width: 100%">
    <thead>
      <tr>
        <th>Artikel</th>
        <th>Nutzer*in</th>
        <th>Standort</th>
        <th>Status</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td id="bem-item"></td>
        <td id="bem-user"></td>
        <td id="bem-location"></td>
        <td id="bem-status"></td>
      </tr>
    </tbody>
  </table>
  <form id="booking-edit-form">
    <input name="booking_id" type="hidden">
    <input name="table_row_index" type="hidden">
    <?php include( CB_ADMIN_BOOKING_PATH . 'templates/booking-details-template.php' ); ?>

    <input type="submit" id="submit-booking-edit" class="button" style="margin-top: 10px; float: right; width: 100px;" value="<?= ___( 'UPDATE', 'commons-booking-admin-booking', 'update') ?>">
  </form>

</div>

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

  function start_loading($notice_wrapper, $submit_button) {
    $notice_wrapper.html('');

    $submit_button.prop("disabled", true);
    var loading_text = '.';
    $submit_button.val(loading_text);
    loading_interval = setInterval(function() {
      loading_text = $submit_button.val();
      if(loading_text.length < 9) {
        loading_text += '..';
      }
      else {
        loading_text = '.';
      }
      $submit_button.val(loading_text);
    }, 250);

    return loading_interval
  }

  function stop_loading($submit_button, loading) {
    clearInterval(loading.interval);
    $submit_button.val(loading.button_text);
    $submit_button.prop("disabled",false);
  }

  $('input[name="booking_mode"]').change();

  $('#submit-booking').click(function(event) {
    var loading = {};
    setTimeout(function() {
      loading.button_text = $('#submit-booking').val();
      loading.interval = start_loading($('#admin-booking-error-wrapper'), $('#submit-booking'));
    }, 0);

    var booking_mode = $('input[name="booking_mode"]:checked').val();
    //console.log('booking_mode: ', booking_mode);

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
        stop_loading($('#submit-booking'), loading);
        var data = JSON.parse(response);
        //console.log('data: ', data);
        if(data.state == 'booking') {

          if(data.bookings) {
            populate_booking_serial_confirm_modal(data);
            var tb_url = '#TB_inline?&width=800&height=500&inlineId=booking-serial-confirm-modal';
            tb_show('<?= ___('SERIAL_BOOKING_CONFIRM_DIALOG_TITLE', 'commons-booking-admin-booking', 'Confirm Bookings') ?>', tb_url);
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

  var locale = '<?= str_replace('_', '-', get_locale()) ?>';
  function format_date(date_string) {
    var date_format_options = { year: 'numeric', month: '2-digit', day: '2-digit' };
    var date = new Date(Date.parse(date_string));
    return date.toLocaleDateString(locale, date_format_options);
  }

  function populate_booking_serial_confirm_modal(data) {
    //console.log(data);

    var $table = $('#booking-serial-confirm-modal table:first');
    var $tbody = $($table.find('tbody:first'));
    $tbody.html('');

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
    });

    var disabled = bookings_to_confirm == 0;
    $confirm_button.prop('disabled', disabled);
  }

  function load_booking_comment(booking_id, form_id) {
    //load booking comment
    var $comment_input = $('#' + form_id + ' input[name="comment"]')
    $comment_input.val('');
    $comment_input.attr('placeholder', '<?= ___( 'LOADING', 'commons-booking-admin-booking', 'loading...') ?>');
    $comment_input.prop('disabled', true);
    jQuery.ajax({
      url: '<?= get_site_url(null, '', null) . '/wp-admin/admin-ajax.php' ?>',
      type: 'POST',
      dataType: 'JSON',
      data: {action : 'get_booking_comment', booking_id: booking_id},
      error: function(res) {
        console.error('comment error:', res);

      },
      success: function(res) {
        $comment_input.prop('disabled', false);
        $comment_input.attr('placeholder', '');
        $comment_input.val(res.comment);
      }
    });
  }

  var $confirm_button = $('#booking-serial-confirm-button');
  $confirm_button.click(function() {
    $confirm_button.prop('disabled', true);
    var $form = $('#admin-booking-form');
    $form.submit();
  });

  /*** edit booking ***/
  var $table_body = $('table.buchungen tbody').first();

  //add edit button
  if($table_body) {

    var booking_ids = [];

    var $table_body_rows = $table_body.find('tr');

    //collect booking ids
    $table_body_rows.each(function(table_row_index, table_row) {
      var $table_row = $(table_row);

      var status = $table_row.find('td').eq(8).contents().get(0).nodeValue;
      if(status != 'pending') {

        var $edit_button = $('<a id="show-booking-edit" class="button thickbox" style="margin-right: 5px; margin-bottom: 5px; margin-top: 5px; padding-top: 4px; line-height: 18px;" href="#TB_inline?&width=500&height=350&inlineId=booking-edit-modal" title="<?= ___('EDIT_BOOKING', 'commons-booking-admin-booking', 'edit booking') ?>"><span class="dashicons dashicons-edit"></span></a>');
        $table_row.find('td:last').append($edit_button);

        $edit_button.click(function(e) {
          e.preventDefault();
          $('#booking-edit-notice-wrapper').html('');

          //boking data table
          var item = $table_row.find('td').eq(1).contents().get(0).innerText;
          $('#bem-item').html(item);

          var user = $table_row.find('td').eq(5).contents().get(0).innerText;
          $('#bem-user').html(user);
          var location = $table_row.find('td').eq(6).contents().get(0).innerText;
          $('#bem-location').html(location);

          var status = $table_row.find('td').eq(8).contents().get(0).nodeValue;
          $('#bem-status').html(status);

          //form values
          $('#booking-edit-form input[name="table_row_index"]').val(table_row_index);

          var booking_id = $table_row.find('td:first').contents().get(0).nodeValue;
          $('#booking-edit-form input[name="booking_id"]').val(booking_id);

          var date_start = $table_row.find('td').eq(2).contents().get(0).nodeValue;
          var date_end = $table_row.find('td').eq(3).contents().get(0).nodeValue;
          $('#booking-edit-form input[name="date_start"]').val(date_start);
          $('#booking-edit-form input[name="date_start"]').attr('min', date_start);
          $('#booking-edit-form input[name="date_start"]').attr('max', date_end);

          $('#booking-edit-form input[name="date_end"]').val(date_end);
          $('#booking-edit-form input[name="date_end"]').attr('min', date_start);
          $('#booking-edit-form input[name="date_end"]').attr('max', date_end);

          //enable/disable date fields based on booking status
          var past = new Date(Date.parse(date_end)).setHours(23, 59, 59) < new Date();
          if(status == 'canceled' || status == 'blocked' || past) {
            $('#booking-edit-form input[name="date_end"]').prop('disabled', true);
            $('#booking-edit-form input[name="date_start"]').prop('disabled', true);

            $('#booking-edit-form input[name="ignore_closed_days"]').parent().hide();
            $('#booking-edit-form input[name="ignore_blocking_item_usage_restriction"]').parent().hide();
            $('#booking-edit-form input[name="send_mail"]').parent().hide();
          }
          else {
            $('#booking-edit-form input[name="date_end"]').prop('disabled', false);
            $('#booking-edit-form input[name="date_start"]').prop('disabled', false);

            $('#booking-edit-form input[name="ignore_closed_days"]').parent().show();
            $('#booking-edit-form input[name="ignore_blocking_item_usage_restriction"]').parent().show();
            $('#booking-edit-form input[name="send_mail"]').parent().show();
          }

          load_booking_comment(booking_id, 'booking-edit-form');
        });
      }

      //copy booking data to create field
      var $copy_button = $('<button class="button" style="margin-right: 5px; margin-bottom: 5px; margin-top: 5px;" title="<?= ___('APPLY_BOOKING_DATA', 'commons-booking-admin-booking', 'apply booking data') ?>"><span style="line-height: 30px;" class="dashicons dashicons-forms"</button>');
      $table_row.find('td:last').append($copy_button);

      $copy_button.click(function (e) {
        e.preventDefault();

        var item_name = $table_row.find('td').eq(1).contents().get(0).innerText;
        var $item_select = $('#admin-booking-form select[name="item_id"]');
        $item_select.find('option').each(function() {
          var $option = $(this);
          if($option.text() == item_name) {
            $item_select.val($option.val());
          }
        });

        //user
        var $table_cell = $table_row.find('td').eq(5);
        var user_name = $table_cell.contents().get(0).innerText;
        var user_id = $table_cell.find('a')[0].search.split('=')[1];

        var $user_selectize_container = $('#admin-booking-form select[name="user_id"]').siblings('.selectize-control');
        var $user_input = $user_selectize_container.find('input');

        $user_input.val(user_name);
        $user_input.css('width', '80%')
        $('#admin-booking-form select[name="user_id"]').find('option').val(9);
        $('#admin-booking-form select[name="user_id"]').val(9);

        //dates
        var date_end_str = $table_row.find('td').eq(3).contents().get(0).nodeValue;
        var date_end = new Date(Date.parse(date_end_str));
        date_end.setDate(date_end.getDate() + 1);
        date_end_str = date_end.toISOString().split('T')[0];
        $('#admin-booking-form input[name="date_start"]').val(date_end_str);
        $('#admin-booking-form input[name="date_end"]').val(date_end_str);

        //comment
        var booking_id = $table_row.find('td').eq(0).contents().get(0).nodeValue;
        console.log('id: ', booking_id);
        load_booking_comment(booking_id, 'admin-booking-form');
      });

    });

    //update form submit
    $('#submit-booking-edit').click(function(e) {
      e.preventDefault();
      var loading = {};
      setTimeout(function() {
        loading.button_text = $('#submit-booking-edit').val();
        loading.interval = start_loading($('#booking-edit-notice-wrapper'), $('#submit-booking-edit'));
      }, 0);

      var $form = $('#booking-edit-form');
      var payload = {};
      $form.serializeArray().forEach(function(item) {
        payload[item.name] = item.value;
      });
      //console.log('payload:', payload);

      function render_notice(success, message) {
        var $error_wrapper = $('#admin-booking-error-wrapper');
        var notice_class = success ? 'notice-success' : 'notice-error';
        var $notice = $('<div class="notice ' + notice_class + '"><p>' + message + '</p></div>');

        setTimeout(function() {
          $('#booking-edit-notice-wrapper').html($notice);
        }, 0);
      }

      jQuery.ajax({
        url: '<?= get_site_url(null, '', null) . '/wp-admin/admin-ajax.php' ?>',
        type: 'POST',
        dataType: 'JSON',
        data: Object.assign({action : 'cb_admin_booking_edit' }, payload),
        error: function(res) {
          //console.error('booking error:', res);
          render_notice(res.success, res.message);
        },
        success: function(res) {
          //console.log(res);
          var table_row_index = $('#booking-edit-form > input[name="table_row_index"]').val()
          var $table_row = $($table_body.find('tr')[table_row_index]);

          if(res.success) {
            $table_row.find('td').eq(2).contents().get(0).nodeValue = payload.date_start;
            $table_row.find('td').eq(3).contents().get(0).nodeValue = payload.date_end;
          }
          render_notice(res.success, res.message);
        }
      }).always(function() {
        stop_loading($('#submit-booking-edit'), loading);
      });
    });
  }
});

</script>
