<?php

class CB_Admin_Booking_Admin {

  const NONCE_KEY = 'cb_admin_booking';

  /**
  * when the plugin is activated, add columns to bookings table in db, if they don't exist yet
  */
  function activate() {
    global $wpdb;

    $table_name = $wpdb->prefix . 'cb_bookings';

    if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name) {

      if(!$this->table_column_exists($table_name, 'exempt_from_limit')) {
        $sql = "ALTER TABLE " . $table_name .
        " ADD exempt_from_limit int(1)";

        $wpdb->query($sql);
      }

      if(!$this->table_column_exists($table_name, 'usage_during_restriction')) {
        $sql = "ALTER TABLE " . $table_name .
        " ADD usage_during_restriction int(1)";

        $wpdb->query($sql);
      }
    }
  }

  function table_column_exists( $table_name, $column_name ) {
  	global $wpdb;
  	$column = $wpdb->get_results( $wpdb->prepare(
  		"SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = %s ",
  		DB_NAME, $table_name, $column_name
  	) );
  	if ( ! empty( $column ) ) {
  		return true;
  	}
  	return false;
  }

  /**
  * loads booking creation functionality on booking admin page of Commons Booking plugin
  */
  function load_bookings_creation($async = false) {

    //load translation
    load_plugin_textdomain( 'commons-booking-admin-booking', false, CB_ADMIN_LANG_PATH );

    wp_enqueue_script( 'jquery-ui-dialog' );
    wp_enqueue_style( 'wp-jquery-ui-dialog' );
    wp_enqueue_style('cb_admin_booking_css', CB_ADMIN_BOOKING_ASSETS_URL . 'css/style.css');

    //get all items
    $item_posts_args = array(
      'numberposts' => -1,
      'post_type'   => 'cb_items',
      'orderby'    => 'post_title',
      'order' => 'ASC'
    );
    $this->cb_items = get_posts( $item_posts_args );

    $this->valid_cb_item_ids = array();
    foreach ($this->cb_items as $cb_item) {
      $this->valid_cb_item_ids[] = $cb_item->ID;
    }

    if(!$async) {
      $this->render_booking_creation();
    }

  }

  function check_nonce() {
    $actions_to_check = [
      'cb_admin_booking_serial',
      'cb_admin_booking_user_search',
      'cb_admin_booking_edit',
      'get_booking_comment',
      'get_booking_special_fields'
    ];

    if( defined('DOING_AJAX') && DOING_AJAX) { //&& current_user_can('manage_options')

      //is it one of the actions to check
      if(in_array($_POST['action'], $actions_to_check)) {

        //check nonce
        if(isset($_POST['nonce'])) {
          $nonce = sanitize_text_field($_POST['nonce']);

          if(!wp_verify_nonce($nonce, self::NONCE_KEY)) {
            wp_send_json_error([], 403);
            return wp_die();
          }
        }
        else {
          wp_send_json_error([], 403);
          return wp_die();
        }
      }

    }
  }

  function validate_booking_create_form_input() {

    //validation
    $data = array();
    $errors = array();

    $data['date_start_valid'] = isset($_POST['date_start']) && strlen($_REQUEST['date_start']) > 0 ? new DateTime($_POST['date_start']) : null;
    $data['date_end_valid'] = isset($_POST['date_end']) && strlen($_REQUEST['date_end']) > 0 ? new DateTime($_POST['date_end']) : null;
    $data['item_id'] = (int) $_POST['item_id'];
    $data['user_id'] = (int) $_POST['user_id'];
    $data['send_mail'] = isset($_POST['send_mail']) ? true : false;
    $data['comment'] = sanitize_text_field($_POST['comment']);
    $data['ignore_closed_days'] = isset($_POST['ignore_closed_days']) ? true : false;
    $data['usage_during_restriction'] = isset($_POST['usage_during_restriction']) ? true : false;
    $data['exempt_from_limit'] = isset($_POST['exempt_from_limit']) ? true : false;
    $data['booking_mode'] = isset($_POST['booking_mode']) && in_array($_POST['booking_mode'], [1,2]) ? (int) $_POST['booking_mode'] : null;
    $data['weekdays'] = [];

    if(!isset($data['booking_mode'])) {
      $errors[] = ___('BOOKING_MODE_INVALID', 'commons-booking-admin-booking', 'invalid booking mode');
    }
    else {
      if($data['booking_mode'] == 2) {
        if(isset($_POST['weekdays'])) {
          foreach ($_POST['weekdays'] as $weekday) {
            if($weekday >= 1 && $weekday <= 7) {
              $data['weekdays'][] = (int) $weekday;
            }
          }
          sort($data['weekdays']);
          if(count($data['weekdays']) != count($_POST['weekdays'])) {
            $errors[] = ___('WEEKDAYS_INVALID', 'commons-booking-admin-booking', 'invalid weekdays');
          }
        }
        else {
          $errors[] = ___('WEEKDAYS_MISSING', 'commons-booking-admin-booking', 'missing weekdays');
        }
      }
    }

    if(!in_array($data['item_id'], $this->valid_cb_item_ids)) {
      $errors[] = ___('ITEM_INVALID', 'commons-booking-admin-booking', 'invalid item');
    }

    if(get_userdata($data['user_id']) === false) {
      $errors[] = ___('USER_INVALID', 'commons-booking-admin-booking', 'invalid user');
    }
    else {
      $user = get_user_by('id', $data['user_id']);

      if($user) {
        $data['user_name'] = $user->first_name . ' ' . $user->last_name . ' (' . $user->display_name . ')';
      }
    }

    if(!$data['date_start_valid']) {
      $errors[] = ___('START_DATE_INVALID', 'commons-booking-admin-booking', 'invalid start date');
    }
    else {
      $data['date_start'] = $_REQUEST['date_start'];
    }

    if(!$data['date_end_valid']) {
      $errors[] = ___('END_DATE_INVALID', 'commons-booking-admin-booking', 'invalid end date');
    }
    else {
      $data['date_end'] = $_REQUEST['date_end'];
    }

    if(strlen($data['comment']) == 0) {
      $errors[] = ___('NO_COMMENT', 'commons-booking-admin-booking', 'the comment is missing');
    }

    return array('data' => $data, 'errors' => $errors);

  }

  function check_booking_creation($cb_booking, $date_start, $date_end, $item_id, $user_id, $ignore_closed_days, $usage_during_restriction, $ignore_bookings_by_id = []) {

    //bookings not allowed for blocking user
    if(cb_admin_booking\is_plugin_active('commons-booking-item-usage-restriction.php')) {
      $blocking_user_id = get_option('cb_item_restriction_blocking_user_id', null);
      if($user_id == $blocking_user_id) {
        $user = get_user_by('id', $user_id);
        $booking_result = [
          'success' => false,
          'message' => sprintf(___('BOOKING_FOR_USER_NOT_ALLOWED', 'commons-booking-admin-booking', 'For user %s is booking not allowed.'), $user->display_name)
        ];

        return $booking_result;
      }
    }

    //check if location (timeframe) exists
    $location_id = $cb_booking->get_booking_location_id($date_start, $date_end, $item_id);
    $booking_result = [
      'success' => false,
      'message' => null
    ];

    if($location_id) {

      //check if bookings that exist in wanted period
      $conflict_bookings = $this->fetch_bookings_in_period($date_start, $date_end, $item_id);

      //remove bookings with ids that should be ignored
      if(count($ignore_bookings_by_id) > 0) {
        $filtered_conflict_bookings = [];
        foreach ($conflict_bookings as $conflict_booking) {
          if(!in_array($conflict_booking->id, $ignore_bookings_by_id)) {
            $filtered_conflict_bookings[] = $conflict_booking;
          }

        }
        $conflict_bookings = $filtered_conflict_bookings;
      }

      if(!$ignore_closed_days) {
        $closed_days = get_post_meta( $location_id, 'commons-booking_location_closeddays', TRUE  );
        $date_start_valid = true;
        $date_end_valid = true;

        $date_start_valid = $this->validate_day($date_start, $closed_days);
        $date_end_valid = $this->validate_day($date_end, $closed_days);

        //include special days (non-regular closed days & holidays)
        if(cb_admin_booking\is_plugin_active('commons-booking-special-days.php') && method_exists('CB_Special_Days','get_locations_special_closed_days')) {
          if($date_start_valid) {
            $locations_special_closed_days = CB_Special_Days::get_locations_special_closed_days($location_id, strtotime($date_start), strtotime($date_start));
            $date_start_valid = count($locations_special_closed_days) == 0;
          }

          if($date_end_valid) {
            $locations_special_closed_days = CB_Special_Days::get_locations_special_closed_days($location_id, strtotime($date_end), strtotime($date_end));
            $date_end_valid = count($locations_special_closed_days) == 0;
          }

        }

      }

      if($ignore_closed_days || $date_start_valid && $date_end_valid) {
        $conflict_bookings_count = count($conflict_bookings);

        if(cb_admin_booking\is_plugin_active('commons-booking-item-usage-restriction.php') && $usage_during_restriction) {
          $blocking_user_id = get_option('cb_item_restriction_blocking_user_id', null);
          if($blocking_user_id) {
            $conflict_bookings_count = $this->check_conflict_bookings_in_item_usage_restriction($blocking_user_id, $conflict_bookings, $date_start, $date_end);
          }
        }

        if($conflict_bookings_count == 0) {
          $booking_result['success'] = true;
          return $booking_result;
        }
        else {
          $booking_result['message'] = ___('ALREADY_BOOKING_IN_PERIOD', 'commons-booking-admin-booking', 'There is already a booking existing for the given item in the wanted period.');
          return $booking_result;
        }
      }
      else {

        $dates = !$date_start_valid ? date("d.m.Y", strtotime($date_start))  : '';
        if($date_start != $date_end) {
          $dates .= !$date_start_valid && !$date_end_valid ? ', ' : '';
          $dates .= !$date_end_valid ? date("d.m.Y", strtotime($date_end)) : '';
        }

        $booking_result['message'] = sprintf(___('NO_BOOKING_FOR_CLOSED_DAYS', 'commons-booking-admin-booking', 'Start and end date must not fall on a day where the location is closed. (%s)'), $dates);
        return $booking_result;
      }

    }
    else {
      $booking_result['message'] = ___('NO_TIMEFRAME_AVAILABLE', 'commons-booking-admin-booking', 'For the wanted booking no timeframe is existing yet - you have to create one first.');
      return $booking_result;
    }
  }

  function check_conflict_bookings_in_item_usage_restriction($blocking_user_id, $conflict_bookings, $date_start, $date_end, $max_day_column_weight = 0) {
    error_reporting(E_ALL);
    $conflict_bookings_count = 0;

    $duration_length = $this->date_difference($date_start, $date_end) + 1;
    $day_column = [];
    $matrix = [];
    $day_column_weights = [];
    $day_booking_deadline = [];

    $day_column = array_pad($day_column , count($conflict_bookings) , 0);
    $matrix = array_pad($matrix , $duration_length , $day_column);
    $day_column_weights = array_pad($day_column_weights , $duration_length , 0);
    $day_booking_deadline = array_pad($day_booking_deadline , $duration_length , null);

    foreach($conflict_bookings as $booking_index => $conflict_booking) {
      $date_time = new DateTime($date_start);
      $date_time->setTime(12, 0, 0);

      $booking_date_time_start = new DateTime($conflict_booking->date_start);
      $booking_date_time_start->setTime(0, 0, 0);
      $booking_date_time_end = new DateTime($conflict_booking->date_end);
      $booking_date_time_end->setTime(23, 59, 59);

      //first step: consider only blocking bookings
      for($d = 0; $d < $duration_length; $d++) {
        if($date_time > $booking_date_time_start && $date_time < $booking_date_time_end) {
          if($conflict_booking->user_id == $blocking_user_id) {
            $day_booking_deadline[$d] = new DateTime($conflict_booking->booking_time);
            $matrix[$d][$booking_index] = -1; //weight
          }
        }
        $date_time->modify('+1 day');
      }

      //second step: consider other bookings
      $date_time = new DateTime($date_start);
      $date_time->setTime(12, 0, 0);
      for($d = 0; $d < $duration_length; $d++) {
        if($date_time > $booking_date_time_start && $date_time < $booking_date_time_end) {
          if($conflict_booking->user_id != $blocking_user_id) {
            //booking was created after a parallel blocking booking
            if($day_booking_deadline[$d] && new DateTime($conflict_booking->booking_time) > $day_booking_deadline[$d]) {
              $weight = 2;
            }
            else {
              $weight = 1;
            }
            $matrix[$d][$booking_index] = $weight;
          }
        }
        $date_time->modify('+1 day');
      }

    }

    //sum up all weights of a column
    foreach($matrix as $column_index => $day_column) {
      foreach ($day_column as $weight) {
        $day_column_weights[$column_index] += $weight;
      }
    }

    //sum up overall weights of all columns (only if > 0)
    foreach($day_column_weights as $day_column_weight) {
      if($day_column_weight > $max_day_column_weight) {
        $conflict_bookings_count++;
      }
    }

    //error_log('$matrix: ' . json_encode($matrix));
    //error_log('$day_booking_deadline: ' . json_encode($day_booking_deadline));
    //error_log('$day_column_weights: ' . json_encode($day_column_weights));
    //error_log('$conflict_bookings_count: ' . json_encode($conflict_bookings_count));

    return $conflict_bookings_count;
  }

  function date_difference($date_1 , $date_2 , $differenceFormat = '%a' )
  {
      $datetime1 = date_create($date_1);
      $datetime2 = date_create($date_2);

      $interval = date_diff($datetime1, $datetime2);

      return $interval->format($differenceFormat);
  }

  /**
  * check start/end date for logical error
  **/
  function check_dates_start_end($date_start, $date_end, $booking_data = null) {

    $booking_result = [
      'success' => true,
      'message' => ''
    ];

    if( strtotime($date_start) > strtotime($date_end)) {
      $booking_result['success'] = false;
      $booking_result['message'] = ___('START_DATE_AFTER_END_DATE', 'commons-booking-admin-booking', 'end date must be after start date');
    }

    if($booking_data) {
      if(new DateTime($date_end) > new DateTime($booking_data['date_end'])) {
        $booking_result['success'] = false;
        $booking_result['message'] = ___('END_DATE_NOT_ALLOWED', 'commons-booking-admin-booking', 'end date not allowed');
      }

      if(new DateTime($date_start) < new DateTime($booking_data['date_start'])) {
        $booking_result['success'] = false;
        $booking_result['message'] = ___('START_DATE_NOT_ALLOWED', 'commons-booking-admin-booking', 'start date not allowed');
      }
    }

    return $booking_result;
  }

  function parse_validation_errors_and_respond($validation_result) {
    if(count($validation_result['errors']) > 0) {
      $error_list = str_replace(',', ', ', implode(",", $validation_result['errors']));
      $booking_result = [
        'success' => false,
        'message' => ___('INPUT_ERRORS_OCCURED', 'commons-booking-admin-booking', 'There are input erros in the request') . ': ' . $error_list
      ];
      $booking_result['state'] = 'validation';

      echo json_encode($booking_result);
      return wp_die();
    }
  }

  function handle_serial_booking_check() {
    $this->load_bookings_creation(true);

    $validation_result = $this->validate_booking_create_form_input();
    $data = $validation_result['data'];

    $this->parse_validation_errors_and_respond($validation_result);

    $result = $this->check_dates_start_end($data['date_start'], $data['date_end']);

    if(!$result['success']) {
      $result['state'] = 'validation';
      echo json_encode($result, JSON_UNESCAPED_UNICODE);
      return wp_die();
    }

    $result = $this->handle_booking_create_form_submit($data, true);
    $result['state'] = 'booking';
    echo json_encode($result, JSON_UNESCAPED_UNICODE);
    return wp_die();
  }

  /**
  * handle submit of booking creation form
  */
  function handle_booking_create_form_submit($data, $test = false) {
    $date_start = $data['date_start'];
    $date_end = $data['date_end'];
    $item_id = $data['item_id'];
    $user_id = $data['user_id'];
    $send_mail = $data['send_mail'];
    $comment = $data['comment'];
    $ignore_closed_days = $data['ignore_closed_days'];
    $usage_during_restriction = $data['usage_during_restriction'];
    $exempt_from_limit = $data['exempt_from_limit'];

    $cb_booking = new CB_Booking();

    //single booking
    if($data['booking_mode'] == 1) {
      $booking_result = $this->check_dates_start_end($date_start, $date_end);

      if($booking_result['success'] == false) {
        return $booking_result;
      }

      //logical booking precheck
      $booking_result = $this->check_booking_creation($cb_booking, $date_start, $date_end, $item_id, $user_id, $ignore_closed_days, $usage_during_restriction);

      if($booking_result['success'] == true) {
        $location_id = $cb_booking->get_booking_location_id($date_start, $date_end, $item_id);

        if($usage_during_restriction) {
          $blocking_user_id = get_option('cb_item_restriction_blocking_user_id', null);
          $blocking_bookings = $this->fetch_bookings_in_period($date_start, $date_end, $item_id, $blocking_user_id);

          $usage_during_restriction = count($blocking_bookings) > 0 ? true : false;
        }

        $booking_id = $this->create_booking($date_start, $date_end, $item_id, $user_id, 'confirmed', $location_id, $send_mail, $comment, $usage_during_restriction, $exempt_from_limit);

        if($booking_id) {
          $booking_result['message'] = ___('BOOKING_CREATED', 'commons-booking-admin-booking', 'The booking was created successfully.');
          $booking_result['message'] .= $send_mail ? ' Eine Bestätigungsmail wurde versandt.' : '';
        }
        else {
          $booking_result['success'] = false;
          $booking_result['message'] = ___('BOOKING_CREATION_ERROR', 'commons-booking-admin-booking', 'An error occured while creating the booking.');
        }

        return $booking_result;
      }
      else {
        return $booking_result;
      }
    }
    //serial booking: create multiple single bookings
    else {
      $booking_patterns = [];
      //iterate provided weekdays and merge booking days
      $booking_pattern = null;
      foreach ($data['weekdays'] as $weekday) {
        if(!isset($booking_pattern)) {
          $booking_pattern = [
            'start' => $weekday,
            'end' => $weekday
          ];
        }
        else {
          if($weekday > $booking_pattern['end'] + 1) {
            $booking_patterns[] = $booking_pattern;
            $booking_pattern = [
              'start' => $weekday,
              'end' => $weekday
            ];
          }
          else {
            $booking_pattern['end'] = $weekday;
          }
        }
      }

      $booking_patterns[] = $booking_pattern;
      $bookings = [];

      $period_start = new DateTime($date_start);
      $period_end = new DateTime($date_end);
      $period_end->modify( '+1 day' ); //include last day

      $period = new DatePeriod(
         $period_start,
         new DateInterval('P1D'),
         $period_end
      );

      $booking = null;
      foreach ($period as $date) {

        $weekday = date('w', $date->getTimestamp());
        if($weekday == 0) {
          $weekday = 7;
        }
        //echo $date->format('Y-m-d') . ': ' . $weekday . ', ';

        foreach ($booking_patterns as $booking_pattern) {
          if(!isset($booking)) {
            if($weekday == $booking_pattern['start']) {
              $booking = [
                'date_start' => $date->format('Y-m-d')
              ];
            }
          }

          if(isset($booking)){
            if($weekday == $booking_pattern['end']) {
              $booking['date_end'] = $date->format('Y-m-d');
              $bookings[] = $booking;
              $booking = null;
            }
          }
        }
      }

      $booking_result = [
        'bookings' => []
      ];

      if(count($bookings) > 0) {
        $location_id = $cb_booking->get_booking_location_id($date_start, $date_end, $item_id);
        $booking_result['success'] = true;

        $booking_count = 0;
        foreach ($bookings as $booking) {

          //check, if booking is possible - never ignore blocking item usage restriction
          $booking_check_result = $this->check_booking_creation($cb_booking, $booking['date_start'], $booking['date_end'], $item_id, $user_id, $ignore_closed_days, false);
          $booking['result'] = $booking_check_result;

          if($test) {
            $booking_result['bookings'][] = $booking;
          }
          else {
            if($booking_check_result['success']) {
              //create booking
              $booking_id = $this->create_booking($booking['date_start'], $booking['date_end'], $item_id, $user_id, 'confirmed', $location_id, $send_mail, $comment);

              if($booking_id) {
                $booking_count++;
              }
            }
          }
        }

        if(!$test) {
          $booking_result['message'] = sprintf(___('BOOKINGS_CREATED', 'commons-booking-admin-booking', 'Successfully created %i booking(s).'), $booking_count);
        }

      }
      else {
        $booking_result = [
          'success' => false,
          'message' => ___('NO_BOOKINGS', 'commons-booking-admin-booking', 'There are no bookings to create with given input.')
        ];
      }

      return $booking_result;
    }

  }

  function render_booking_result_message($booking_result, $send_mail) {
    echo '<script>';
    echo 'booking_result = ' . json_encode($booking_result, JSON_UNESCAPED_UNICODE);
    echo '</script>';
  }

  /**
  * handle submission and rendering of booking creation form
  */
  function render_booking_creation() {

    $booking_result = null;

    echo '<script>';
    echo 'var booking_result = null';
    echo '</script>';

    if(isset($_POST['action']))  {
      if($_POST['action'] == 'cb-booking-create') {

        $validation_result = $this->validate_booking_create_form_input();

        if(count($validation_result['errors']) > 0) {
          $error_list = str_replace(',', ', ', implode(",", $validation_result['errors']));
          $booking_result = [
            'success' => false,
            'message' => ___('INPUT_ERRORS_OCCURED', 'commons-booking-admin-booking', 'There are input erros in the request') . ': ' . $error_list
          ];

        }
        else {
          $booking_result = $this->handle_booking_create_form_submit($validation_result['data']);

        }

        $this->render_booking_result_message($booking_result, $validation_result['data']['send_mail']);
      }
    }

    //get a location
    $location_posts_args = array(
      'numberposts' => 1,
      'post_type'   => 'cb_locations'
    );

    $location = get_posts( $location_posts_args );

    if(cb_admin_booking\is_plugin_active('commons-booking-item-usage-restriction.php')) {
      $blocking_user_id = get_option('cb_item_restriction_blocking_user_id', null);
    }
    else {
      $blocking_user_id = null;
    }

    //check if there are at least one location and item created
    if(count($location) > 0 && count($this->cb_items) > 0) {

      //prefill form
      $data = isset($validation_result) ? $validation_result['data'] : array();
      //var_dump($data);

      $date_min = new DateTime();
      $date_start = !$booking_result['success'] && isset($data['date_start_valid']) ? $data['date_start_valid'] : new DateTime();
      $date_end = !$booking_result['success'] && isset($data['date_end_valid']) ? $data['date_end_valid'] : new DateTime();

      $user_id = !$booking_result['success'] && isset($data['user_id']) ? $data['user_id'] : null;
      $user_name = !$booking_result['success'] && isset($data['user_name']) ? $data['user_name'] : null;
      $item_id = !$booking_result['success'] && isset($data['item_id']) ? $data['item_id'] : null;

      $cb_items = $this->cb_items;

      $comment = !$booking_result['success'] && isset($data['comment']) ? $data['comment'] : null;

      $ignore_closed_days = !$booking_result['success'] && isset($data['ignore_closed_days']) ? $data['ignore_closed_days'] : null;

      if(!$booking_result) {
        $send_mail = true;
      }
      else {
        $send_mail = !$booking_result['success'] && isset($data['send_mail']) ? $data['send_mail'] : null;
      }

      if(cb_admin_booking\is_plugin_active('commons-booking-item-usage-restriction.php')) {
        $render_ibiur_options = true;
        $usage_during_restriction = !$booking_result['success'] && isset($data['usage_during_restriction']) ? $data['usage_during_restriction'] : null;
        $exempt_from_limit = !$booking_result['success'] && isset($data['exempt_from_limit']) ? $data['exempt_from_limit'] : null;
      }
      else {
        $render_ibiur_options = false;
      }

      $nonce = wp_create_nonce(self::NONCE_KEY);

      add_thickbox();
      include_once( CB_ADMIN_BOOKING_PATH . 'templates/bookings-template.php' );
    }
    else {
      echo '<h3>' . ___('CREATE_BOOKING', 'commons-booking-admin-booking', 'Create Booking') . '</h3>';
      echo '<p>' . ___('NO_LOCATIONS_AND_ITEMS', 'commons-booking-admin-booking', 'There have to be locations and items to create bookings.') . '</p>';
    }

  }

  /**
  * validate date by checking if it falls on a closing day
  */
  function validate_day($date, $closed_days) {

    $weekday = date( "N", strtotime( $date ));

    if ( is_array ( $closed_days ) && in_array( $weekday, $closed_days ) ) {
      return false;
    }
    else {
      return true;
    }
  }

  /**
  * create a booking with given properties
  */
  function create_booking($date_start, $date_end, $item_id, $user_id, $status, $location_id, $send_mail, $comment, $usage_during_restriction = null, $exempt_from_limit = null) {

    $cb_booking = new CB_Booking();

    //set wanted user
    $cb_booking->user_id = $user_id;

    //create booking (pending)
    $cb_booking->hash = $cb_booking->create_hash();
    $booking_id = $cb_booking->create_booking( $date_start, $date_end, $item_id);

    if($booking_id) {

      if(strlen($comment) > 0) {
        $this->save_comment($booking_id, $comment);
      }

      $this->save_special_fields($booking_id, $usage_during_restriction, $exempt_from_limit);

      //set status - (default is pending - it will be deleted by Commons Booking cronjob, if it's not confirmed)
      //set_booking_status is a private method, it has to be made accessible first
      $method = new ReflectionMethod('CB_Booking', 'set_booking_status');
      $method->setAccessible(true);
      $method->invoke($cb_booking, $booking_id, $status);

      if($send_mail) {
        $this->send_mail($cb_booking, $booking_id);
      }
    }

    return $booking_id;

  }

  function send_mail($cb_booking, $booking_id) {
    $cb_booking->booking = $cb_booking->get_booking($booking_id);

    //prepare CB_Booking instance to send email
    $cb_booking->item_id = $cb_booking->booking['item_id'];
    $cb_booking->location_id = $cb_booking->booking['location_id'];
    $cb_booking->date_start = $cb_booking->booking['date_start'];
    $cb_booking->date_end = $cb_booking->booking['date_end'];
    $cb_booking->user_id = $cb_booking->booking['user_id'];
    $cb_booking->hash = $cb_booking->booking['hash'];

    $cb_booking->email_messages = $cb_booking->settings->get_settings( 'mail' );

    $this->set_booking_vars($cb_booking);

    $cb_booking->send_mail($cb_booking->user['email']);
  }

  function get_booking_comment() {

    $booking_id = (int) $_POST['booking_id'];

    $comment = $this->load_comment($booking_id);

    if($comment) {
      echo json_encode(['comment' => $comment]);
    }
    else {
      echo json_encode([]);
    }
    wp_die();
  }

  function load_comment($booking_id) {

    global $wpdb;
    //get comment of booking
    $table_name = $wpdb->prefix . 'cb_bookings';
    $select_statement = "SELECT comment FROM $table_name WHERE id = %d";
    $prepared_statement = $wpdb->prepare($select_statement, $booking_id);

    $row = $wpdb->get_row($prepared_statement);

    return $row->comment;
  }

  function save_comment($booking_id, $comment) {
    global $wpdb;

    $table_name = $wpdb->prefix . 'cb_bookings';

    $wpdb->update(
      $table_name,
      array(
        'comment' => $comment,	// string
      ),
      array( 'id' => $booking_id ),
      array(
        '%s',	// comment
      ),
      array( '%d' )
    );
  }

  function get_booking_special_fields() {
    $booking_id = (int) $_POST['booking_id'];

    $data = $this->load_special_fields($booking_id);

    if($data) {
      echo json_encode(['exempt_from_limit' => $data->exempt_from_limit, 'usage_during_restriction' => $data->usage_during_restriction]);
    }
    else {
      echo json_encode([]);
    }

    wp_die();
  }

  function load_special_fields($booking_id) {

    global $wpdb;
    //get special_fields of booking
    $table_name = $wpdb->prefix . 'cb_bookings';
    $select_statement = "SELECT exempt_from_limit, usage_during_restriction FROM $table_name WHERE id = %d";
    $prepared_statement = $wpdb->prepare($select_statement, $booking_id);

    $row = $wpdb->get_row($prepared_statement);

    return $row;
  }

  function save_special_fields($booking_id, $usage_during_restriction, $exempt_from_limit) {
    global $wpdb;

    $table_name = $wpdb->prefix . 'cb_bookings';

    $wpdb->update(
    	$table_name,
    	array(
        'usage_during_restriction' => (int) $usage_during_restriction,
    		'exempt_from_limit' => (int) $exempt_from_limit,
    	),
    	array( 'id' => $booking_id ),
    	array(
    		'%d',	// usage_during_restriction
        '%d'	// exempt_from_limit
    	),
    	array( '%d' )
    );
  }

  /**
  * set booking vars on given CB_Booking instance to prepare sending an email
  */
  private function set_booking_vars( $cb_booking ) {

    $cb_booking->item = $cb_booking->data->get_item( $cb_booking->item_id );
    $cb_booking->location = $cb_booking->data->get_location( $cb_booking->location_id );
    $cb_booking->user = $cb_booking->data->get_user( $cb_booking->user_id );

    $b_vars['date_start'] = date_i18n( get_option( 'date_format' ), strtotime($cb_booking->date_start) );
    $b_vars['date_end'] = date_i18n( get_option( 'date_format' ), strtotime($cb_booking->date_end) );
    $b_vars['date_end_timestamp'] = strtotime($cb_booking->date_end);
    $b_vars['item_name'] = get_the_title ($cb_booking->item_id );
    $b_vars['item_thumb'] = get_thumb( $cb_booking->item_id );
    $b_vars['item_content'] =  get_post_meta( $cb_booking->item_id, 'commons-booking_item_descr', TRUE  );
    $b_vars['location_name'] = get_the_title ($cb_booking->location_id );
    $b_vars['location_content'] = '';
    $b_vars['location_address'] = $cb_booking->data->format_adress($cb_booking->location['address']);
    $b_vars['location_thumb'] = get_thumb( $cb_booking->location_id );
    $b_vars['location_contact'] = is_array($cb_booking->location['contact']) ? $cb_booking->location['contact']['string'] : $cb_booking->location['contact']; //due to change after CB 0.9.2.2
    $b_vars['location_openinghours'] = $cb_booking->location['openinghours'];

    $b_vars['page_confirmation'] = $cb_booking->settings->get_settings('pages', 'booking_confirmed_page_select');

    $b_vars['hash'] = $cb_booking->hash;

    $b_vars['site_email'] = '';

    $b_vars['user_name'] = $cb_booking->user['name'];
    $b_vars['user_email'] = $cb_booking->user['email'];

    $b_vars['first_name'] = $cb_booking->user['first_name'];
    $b_vars['last_name'] = $cb_booking->user['last_name'];

    $b_vars['user_address'] = $cb_booking->user['address'];
    $b_vars['user_phone'] = $cb_booking->user['phone'];

    $b_vars['code'] = $cb_booking->get_code( $cb_booking->booking['code_id'] );
    $b_vars['url'] = add_query_arg( 'booking', $cb_booking->hash, get_permalink($b_vars['page_confirmation']) );

    $cb_booking->b_vars = $b_vars;

  }

  function handle_user_search() {
    $search_term = sanitize_text_field(stripslashes($_POST['q']));

    $wp_user_query = new WP_User_Query( array (
        'order'      => 'ASC',
        'orderby'    => 'display_name',
        'search'     => '*' . esc_attr($search_term) . '*',
        'search_columns' => [
          'user_nicename'
        ]
    ));

    $users = $wp_user_query->get_results();

    $search_array = explode(' ', $search_term);
    if(count($search_array) == 1) {
      $first_name = $search_term;
      $last_name = $search_term;
      $relation = 'OR';
    }
    else if(count($search_array) == 2) {
      $first_name = $search_array[0];
      $last_name = $search_array[1];
      $relation = 'AND';
    }
    else if(count($search_array) > 2) {
      array_pop($search_array);
      $first_name = implode(' ', $search_array);
      $last_name = $search_array[count($search_array) - 1];
      $relation = 'AND';
    }

    $wp_user_query = new WP_User_Query( array (
        'meta_query' => array(
          'relation' => $relation,
          array(
              'key'     => 'first_name',
              'value'   => $first_name,
              'compare' => 'LIKE'
          ),
          array(
              'key'     => 'last_name',
              'value'   => $last_name,
              'compare' => 'LIKE'
          )
        )
    ));

    $users2 = $wp_user_query->get_results();

    $totalusers = array_unique(array_merge($users, $users2), SORT_REGULAR);

    $result = [];

    $blocking_user_id = get_option('cb_item_restriction_blocking_user_id', null);

    foreach ($totalusers as $user) {
      if($blocking_user_id != $user->ID) {
        $result[] = [
          "id" => $user->ID,
          "name" => $user->first_name . ' ' . $user->last_name . ' (' . $user->display_name . ')',
        ];
      }
    }

    echo json_encode($result);
    wp_die();
  }

  /**
  * fetches bookings in period determined by start and end date from db for given item
  */
  function fetch_bookings_in_period($date_start, $date_end, $item_id, $user_id = null) {
    global $wpdb;

    //get bookings data
    $table_name = $wpdb->prefix . 'cb_bookings';
    $select_statement = "SELECT * FROM $table_name WHERE item_id = %d ".
                        "AND ((date_start BETWEEN '".$date_start."' ".
                        "AND '".$date_end."') ".
                        "OR (date_end BETWEEN '".$date_start."' ".
                        "AND '".$date_end."') ".
                        "OR (date_start < '".$date_start."' ".
                        "AND date_end > '".$date_end."')) ".
                        "AND status = 'confirmed'";

    if($user_id) {
      $select_statement .= " AND user_id = ".$user_id;
    }

    $prepared_statement = $wpdb->prepare($select_statement, $item_id);

    $bookings_result = $wpdb->get_results($prepared_statement);

    return $bookings_result;
  }

  function handle_booking_edit() {
    //error_reporting(E_ALL);
    load_plugin_textdomain( 'commons-booking-admin-booking', false, CB_ADMIN_LANG_PATH );

    $booking_id = isset($_POST['booking_id']) && (int) $_POST['booking_id'] > 0 ? (int) $_POST['booking_id'] : null;
    if($booking_id) {
      //load booking with given id
      $cb_booking = new CB_Booking();
      $booking_data = $cb_booking->get_booking($booking_id);

      if($booking_data) {
        $date_end = new DateTime($booking_data['date_end']);
        $date_end->setTime(23, 59, 59);
        $now = new DateTime();
        $validate_dates = $booking_data['status'] !== 'canceled' && $booking_data['status'] !== 'blocked' && $date_end >= $now;

        $validation_result = $this->validate_booking_edit_form_input($validate_dates);
        $data = $validation_result['data'];

        $this->parse_validation_errors_and_respond($validation_result);

        if($validate_dates) {
          $result = $this->check_dates_start_end($data['date_start'], $data['date_end'], $booking_data);
        }
        else {
            $result = [
              'success' => true,
              'errors' => []
            ];
        }
      }
      else {
        $result = [
          'success' => false,
          'errors' => [___('BOOKING_ID_INVALID', 'commons-booking-admin-booking', 'invalid booking')]
        ];
      }
    }
    else {
      $result = [
        'success' => false,
        'errors' => [___('NO_BOOKING_ID', 'commons-booking-admin-booking', 'no booking id')]
      ];
    }

    if(!$result['success']) {
      $result['state'] = 'validation';
      echo json_encode($result, JSON_UNESCAPED_UNICODE);
      return wp_die();
    }

    $dates_changed = $validate_dates && ($booking_data['date_start'] != $data['date_start'] || $booking_data['date_end'] != $data['date_end']);
    $result = $this->handle_booking_edit_form_submit($booking_id, $cb_booking, $booking_data, $data, $dates_changed);
    $result['state'] = 'booking';
    echo json_encode($result, JSON_UNESCAPED_UNICODE);
    return wp_die();

  }

  function handle_booking_edit_form_submit($booking_id, $cb_booking, $booking_data, $data, $dates_changed) {

    if($dates_changed) {
      //check conflicts for given dates (ignore current booking)
      $booking_result = $this->check_booking_creation($cb_booking, $data['date_start'], $data['date_end'], $booking_data['item_id'], $booking_data['user_id'], $data['ignore_closed_days'], $data['usage_during_restriction'], [$booking_id]);

      if($booking_result['success'] == true) {
        $update_result = $this->update_booking($booking_id, $data['date_start'], $data['date_end'], $data['send_mail']);

        if($update_result === false) {
          $booking_result['success'] = false;
          $booking_result['message'] = ___('BOOKING_UPDATE_ERROR', 'commons-booking-admin-booking', 'An error occured while updating the booking.');
        }
        else {
          $this->save_comment($booking_id, $data['comment']);
          $this->save_special_fields($booking_id, $data['usage_during_restriction'], $data['exempt_from_limit']);

          $booking_result['message'] = ___('BOOKING_UPDATED', 'commons-booking-admin-booking', 'The booking was successfully updated.');
          $booking_result['message'] .= $data['send_mail'] ? ' Eine Bestätigungsmail wurde versandt.' : '';
        }

      }
    }
    else {
      $this->save_comment($booking_id, $data['comment']);
      $this->save_special_fields($booking_id, $data['usage_during_restriction'], $data['exempt_from_limit']);

      $booking_result = [
        'success' => true,
        'message' => ___('BOOKING_COMMENT_UPDATED', 'commons-booking-admin-booking', 'The booking was successfully updated.')
      ];
    }

    echo json_encode($booking_result, JSON_UNESCAPED_UNICODE);
    return wp_die();

  }

  function update_booking($booking_id, $date_start, $date_end, $send_mail) {
    //update in db
    global $wpdb;

    $table_name = $wpdb->prefix . 'cb_bookings';
    $update_result = $wpdb->update($table_name, ['date_start' => $date_start, 'date_end' => $date_end], array( 'id' => $booking_id));

    if($send_mail) {
      $cb_booking = new CB_Booking();
      $this->send_mail($cb_booking, $booking_id);
    }

    return $update_result;
  }

  function validate_booking_edit_form_input($validate_dates = true) {

    //validation
    $data = array();
    $errors = array();

    if($validate_dates) {
      $data['date_start'] = isset($_POST['date_start']) && strlen($_REQUEST['date_start']) > 0 ? new DateTime($_POST['date_start']) : null;
      $data['date_end'] = isset($_POST['date_end']) && strlen($_REQUEST['date_end']) > 0 ? new DateTime($_POST['date_end']) : null;

      if(!$data['date_start']) {
        $errors[] = ___('START_DATE_INVALID', 'commons-booking-admin-booking', 'invalid start date');
      }
      else {
        $data['date_start'] = $_REQUEST['date_start'];
      }

      if(!$data['date_end']) {
        $errors[] = ___('END_DATE_INVALID', 'commons-booking-admin-booking', 'invalid end date');
      }
      else {
        $data['date_end'] = $_REQUEST['date_end'];
      }
    }

    $data['send_mail'] = isset($_POST['send_mail']) ? true : false;
    $data['comment'] = sanitize_text_field($_POST['comment']);
    $data['ignore_closed_days'] = isset($_POST['ignore_closed_days']) ? true : false;
    $data['usage_during_restriction'] = isset($_POST['usage_during_restriction']) ? true : false;
    $data['exempt_from_limit'] = isset($_POST['exempt_from_limit']) ? true : false;

    if(strlen($data['comment']) == 0) {
      $errors[] = ___('NO_COMMENT', 'commons-booking-admin-booking', 'the comment is missing');
    }

    return array('data' => $data, 'errors' => $errors);
  }
}
