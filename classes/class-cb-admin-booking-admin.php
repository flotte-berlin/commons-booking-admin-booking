<?php

class CB_Admin_Booking_Admin {

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

  function validate_booking_form_input() {

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
    $data['ignore_blocking_item_usage_restriction'] = isset($_POST['ignore_blocking_item_usage_restriction']) ? true : false;
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

    return array('data' => $data, 'errors' => $errors);

  }

  function check_booking_creation($cb_booking, $date_start, $date_end, $item_id, $user_id, $ignore_closed_days, $ignore_blocking_item_usage_restriction) {

    //check if location (timeframe) exists
    $location_id = $cb_booking->get_booking_location_id($date_start, $date_end, $item_id);
    $booking_result = [
      'success' => false,
      'message' => null
    ];
    if($location_id) {

      //check if no bookings exist in wanted period
      $conflict_bookings = $this->fetch_bookings_in_period($date_start, $date_end, $item_id);

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
            trigger_error('$date_start_valid: ' . json_encode($locations_special_closed_days));
            $date_start_valid = count($locations_special_closed_days) == 0;
          }

          if($date_end_valid) {
            $locations_special_closed_days = CB_Special_Days::get_locations_special_closed_days($location_id, strtotime($date_end), strtotime($date_end));
            $date_end_valid = count($locations_special_closed_days) == 0;
          }

        }

      }

      if($ignore_closed_days || $date_start_valid && $date_end_valid) {
        error_reporting(E_ALL);
        $conflict_bookings_count = count($conflict_bookings);

        if(cb_admin_booking\is_plugin_active('commons-booking-item-usage-restriction.php') && $ignore_blocking_item_usage_restriction) {
          $blocking_user_id = get_option('cb_item_restriction_blocking_user_id', null);
          if($blocking_user_id) {
            foreach ($conflict_bookings as $conflict_booking) {
              if($conflict_booking->user_id == $blocking_user_id) {
                $conflict_bookings_count--;
              }
            }
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

  /**
  * check start/end date for logical error
  **/
  function check_dates_start_end($date_start, $date_end) {

    $booking_result = [
      'success' => true,
      'message' => ''
    ];

    if( strtotime($date_start) > strtotime($date_end)) {
      $booking_result['success'] = false;
      $booking_result['message'] = ___('START_DATE_AFTER_END_DATE', 'commons-booking-admin-booking', 'end date must be after start date');

    }

    return $booking_result;
  }

  function handle_serial_booking_check() {
    $this->load_bookings_creation(true);

    $validation_result = $this->validate_booking_form_input();
    $data = $validation_result['data'];

    if(count($validation_result['errors']) > 0) {
      $error_list = str_replace(',', ', ', implode(",", $validation_result['errors']));
      $booking_result = [
        'success' => false,
        'message' => ___('INPUT_ERRORS_OCCURED', 'commons-booking-admin-booking', 'There are input erros in the request.') . ': ' . $error_list
      ];
      $booking_result['state'] = 'validation';

      echo json_encode($booking_result);
      return wp_die();
    }

    $result = $this->check_dates_start_end($data['date_start'], $data['date_end']);

    if(!$result['success']) {
      $result['state'] = 'validation';
      echo json_encode($result, JSON_UNESCAPED_UNICODE);
      return wp_die();
    }

    $result = $this->handle_booking_form_submit($data, true);
    $result['state'] = 'booking';
    echo json_encode($result, JSON_UNESCAPED_UNICODE);
    return wp_die();
  }

  /**
  * handle submit of booking creation form
  */
  function handle_booking_form_submit($data, $test = false) {
    $date_start = $data['date_start'];
    $date_end = $data['date_end'];
    $item_id = $data['item_id'];
    $user_id = $data['user_id'];
    $send_mail = $data['send_mail'];
    $comment = $data['comment'];
    $ignore_closed_days = $data['ignore_closed_days'];
    $ignore_blocking_item_usage_restriction = $data['ignore_blocking_item_usage_restriction'];

    $cb_booking = new CB_Booking();

    //single booking
    if($data['booking_mode'] == 1) {
      $booking_result = $this->check_dates_start_end($date_start, $date_end);

      if($booking_result['success'] == false) {
        return $booking_result;
      }

      //logical booking precheck
      $booking_result = $this->check_booking_creation($cb_booking, $date_start, $date_end, $item_id, $user_id, $ignore_closed_days, $ignore_blocking_item_usage_restriction);

      if($booking_result['success'] == true) {
        $location_id = $cb_booking->get_booking_location_id($date_start, $date_end, $item_id);
        $booking_id = $this->create_booking($date_start, $date_end, $item_id, $user_id, 'confirmed', $location_id, $send_mail, $comment);

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

          //check, if booking is possible
          $booking_check_result = $this->check_booking_creation($cb_booking, $booking['date_start'], $booking['date_end'], $item_id, $user_id, $ignore_closed_days, $ignore_blocking_item_usage_restriction);
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

        $validation_result = $this->validate_booking_form_input();

        if(count($validation_result['errors']) > 0) {
          $error_list = str_replace(',', ', ', implode(",", $validation_result['errors']));
          $booking_result = [
            'success' => false,
            'message' => ___('INPUT_ERRORS_OCCURED', 'commons-booking-admin-booking', 'There are input erros in the request.') . ': ' . $error_list
          ];

        }
        else {
          $booking_result = $this->handle_booking_form_submit($validation_result['data']);

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

    //check if there are at least one location and item created
    if(count($location) > 0 && count($this->cb_items) > 0) {

      //prefill form
      $data = isset($validation_result) ? $validation_result['data'] : array();
      //var_dump($data);

      $date_min = new DateTime();
      $date_start = !$booking_result['success'] && isset($data['date_start_valid']) ? $data['date_start_valid'] : new DateTime();
      $date_end = !$booking_result['success'] && isset($data['date_end_valid']) ? $data['date_end_valid'] : new DateTime();

      $user_id = !$booking_result['success'] && isset($data['user_id']) ? $data['user_id'] : null;
      $item_id = !$booking_result['success'] && isset($data['item_id']) ? $data['item_id'] : null;

      $cb_items = $this->cb_items;

      $comment = !$booking_result['success'] && isset($data['comment']) ? $data['comment'] : null;

      $ignore_closed_days = !$booking_result['success'] && isset($data['ignore_closed_days']) ? $data['ignore_closed_days'] : null;

      $send_mail = !$booking_result['success'] && isset($data['send_mail']) ? $data['send_mail'] : null;

      if(cb_admin_booking\is_plugin_active('commons-booking-item-usage-restriction.php')) {
        $render_ibiur_option = true;
        $ignore_blocking_item_usage_restriction = !$booking_result['success'] && isset($data['ignore_blocking_item_usage_restriction']) ? $data['ignore_blocking_item_usage_restriction'] : null;
      }
      else {
        $render_ibiur_option = false;
      }

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
  function create_booking($date_start, $date_end, $item_id, $user_id, $status, $location_id, $send_mail, $comment) {

    $cb_booking = new CB_Booking();

    //set wanted user
    $cb_booking->user_id = $user_id;

    //create booking (pending)
    $cb_booking->hash = $cb_booking->create_hash();
    $booking_id = $cb_booking->create_booking( $date_start, $date_end, $item_id);

      if(strlen($comment) > 0) {
        $this->save_comment($booking_id, $comment);
      }

      if($booking_id) {

        //set status - (default is pending - it will be deleted by Commons Booking cronjob, if it's not confirmed)
        //set_booking_status is a private method, it has to be made accessible first
        $method = new ReflectionMethod('CB_Booking', 'set_booking_status');
        $method->setAccessible(true);
        $method->invoke($cb_booking, $booking_id, $status);

        if($send_mail) {

          //prepare  CB_Booking instance to send email
          $cb_booking->item_id = $item_id;
          $cb_booking->location_id = $location_id;
          $cb_booking->date_start = $date_start;
          $cb_booking->date_end = $date_end;

          $cb_booking->booking = $cb_booking->get_booking($booking_id);

          $cb_booking->email_messages = $cb_booking->settings->get_settings( 'mail' );

          $this->set_booking_vars($cb_booking);

          $cb_booking->send_mail($cb_booking->user['email']);
      }

    }

    return $booking_id;

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

    foreach ($totalusers as $user) {
      $result[] = [
        "id" => $user->ID,
        "name" => $user->first_name . ' ' . $user->last_name . ' (' . $user->display_name . ')',
      ];
    }

    echo json_encode($result);
    wp_die();
  }

  /**
  * fetches bookings in period determined by start and end date from db for given item
  */
  function fetch_bookings_in_period($date_start, $date_end, $item_id) {
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

    $prepared_statement = $wpdb->prepare($select_statement, $item_id);

    $bookings_result = $wpdb->get_results($prepared_statement);

    return $bookings_result;
  }
}
