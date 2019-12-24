<?php

add_action('wp_ajax_request_timing_change', 'request_timing_change');
function request_timing_change(){

	global $wpdb;
	$appointment_id = $_POST['appt_id'];
	$new_slot = $_POST['new_slot'];
	$appointment = $wpdb->get_row("SELECT * FROM " . DB_APPOINTMENTS . " WHERE id =" . $appointment_id, ARRAY_A);
	$date = $appointment['appointment_date'];

	// Convert the date string into a unix timestamp.
	$unixTimestamp = strtotime($date);

	//Get the day of the week using PHP's date function.
	$dayOfWeek = date("l", $unixTimestamp);
	$weekday = strtolower($dayOfWeek);

	$query = "SELECT DISTINCT * FROM " . DB_USERS . " JOIN " . DB_APPOINTMENTS . " ON " . DB_APPOINTMENTS . ".customer_id=" . DB_USERS . ".ID GROUP BY ". DB_APPOINTMENTS . ".id";
	$rowData = $wpdb->get_row($query, ARRAY_A);

	$currDateTime = ucfirst($weekday) .', '. date("F d, Y", strtotime($date)) .' : '. $appointment['appointment_time'];

	$token = my_simple_crypt($appointment_id);
	$verification_url = site_url('wp-admin/admin-ajax.php?action=ftoken&token='.$token);

	$args = array(
       "appointment_id" =>$appointment_id,
       "suggested_slot" => $new_slot,
       "token" => $token
   	);

   	if( $wpdb->insert(DB_APPOINTMENT_MODIFY, $args) ){

		// Email the user
        $cust_msg = "Hi {customer_name},<br><br>
            Admin has suggested a time change to <strong>{new_slot_time}</strong> for your appointment on {current_appt_date_time}<br/><br>
            <a target='_blank' href='{verification_url}'> Click here to approve time change </a><br>";

        preg_match_all('/{(.*?)}/', $cust_msg, $matches);

        if (in_array("customer_name", $matches[1])) {
        	$cust_msg = str_replace('{customer_name}', $rowData['display_name'], $cust_msg);
        }
        if (in_array("current_appt_date_time", $matches[1])) {
        	$cust_msg = str_replace('{current_appt_date_time}', $currDateTime, $cust_msg);
        }
        if (in_array("new_slot_time", $matches[1])) {
        	$cust_msg = str_replace('{new_slot_time}', $new_slot, $cust_msg);
        }
        if (in_array("verification_url", $matches[1])) {
        	$cust_msg = str_replace('{verification_url}', $verification_url, $cust_msg);
        }

        $headers[] = 'Content-Type: text/html; charset=UTF-8';
        $headers[] = 'From: ShootInSchool <noreply@shootinschool.com>';
        // wp_mail($cust_email, $cust_subject, $cust_msg, $headers);
        wp_mail($rowData['user_email'], 'Appointment Time Change Suggestion by Admin', $cust_msg, $headers);

		echo json_encode(['status' => TRUE, 'message' => 'Customer has notified about Appointment Time Change Suggestion.']);

   	} else{
   		echo json_encode(['status' => FALSE, 'message' => 'Operation failed.']);
   	}

   	die();
}