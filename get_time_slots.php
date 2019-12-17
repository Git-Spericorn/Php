<?php

// Get location, date and session type, and check for off days in all instructors, max capacity, and finally get list of all availabilities of instructors matching the criteria
function get_time_slots(){

    global $wpdb;

    parse_str($_POST['form_data'], $form_data); //This will convert the string to array

    $location_id = $_POST['location_id'];
    $session_id = explode('|', $_POST['session_id']);
    $date = date("Y-m-d", strtotime($_POST['date']));

    $timings_to_ignore = array();
    if(count($form_data['order_session']) > 1){

        foreach ($form_data['order_session'] as $main_index => $single_appt) {

            $existing_session_id = explode('|', $single_appt)[1];

            if(trim($form_data['appointment_time'][$main_index]) == '' // means only 1 booking instance exists
                || $form_data['location_id'][$main_index] != $location_id // means existing and submitted location IDs do not match
                || $existing_session_id != $session_id[1] // means existing and submitted session IDs do not match
                || $date != $form_data['appointment_date'][$main_index] ) // means existing and submitted appointments dates do not match
            {
                continue;
            }

            $existing_appointment_time = $form_data['appointment_time'][$main_index];

            $timings_to_ignore[] = $existing_appointment_time;
        }
    }

    // Convert the date string into a unix timestamp.
    $unixTimestamp = strtotime($date);

    //Get the day of the week using PHP's date function.
    $dayOfWeek = date("l", $unixTimestamp);
    $weekday = strtolower($dayOfWeek);

    // 1) Get availabilities of all instructors on selected date
    $query = "SELECT * FROM " . DB_INSTRUCTORS_AVAILABILITY . " t1 LEFT JOIN " . DB_INSTRUCTORS_OFF_DAYS . " t2 ON t1.instructor_id = t2.instructor_id WHERE t1.location_id = " . $location_id . "  AND t1.session_id = " . $session_id[1] . " AND t1.weekday = '$weekday'";

    $timings = $wpdb->get_results($query);

    $start_timing = array();
    $close_timing = array();
    $timing = array();

    foreach ($timings as $time) {
        // 2) Loop through timings and Omit OFF Days
        if ($time->off_day == $date) {
            continue;
        }

        $start = $time->start_time;
        $close = $time->end_time;
        if ($start != "" && $close != "") {
            $AddMins  = 60 * 60;
            $StartTime = strtotime($start);
            $EndTime = strtotime($close);
            while ($StartTime < $EndTime) {
                array_push($start_timing, $StartTime);
                array_push($close_timing, ($StartTime + $AddMins));
                $StartTime += $AddMins;
            }
        }
    }

    function sortByTime($a, $b)
    {
        return $a - $b;
    }

    usort($start_timing, 'sortByTime');
    usort($close_timing, 'sortByTime');

    for ($i = 0; $i < count($start_timing); $i++) {
        $t = date("G:i", $start_timing[$i]);
        $u = date("G:i", $close_timing[$i]);
        $slot = $t . ' - ' . $u;

        $timing[] = $slot;
    }

    // 3) Retrieve max capacity of selected session type
    $session = $wpdb->get_row("SELECT * FROM " . DB_PACKAGE_SESSIONS . " WHERE id = " . $session_id[1]);
    $max_capacity = (int) $session->max_capacity;
    $final_timing = array();

    // 4) Duplicate existing timings array * max_capacity
    for ($i = 0; $i < $max_capacity; $i++) {
        $final_timing = array_merge($final_timing, $timing);
    }

    // 5) Retrieve existing appointments
    $query = "SELECT appointment_time, appointment_date FROM " . DB_APPOINTMENTS . " WHERE is_cancelled = '0' AND appointment_date = '$date'";
    $current_appointments = $wpdb->get_results($query);
    foreach ($current_appointments as $value) {
        // 6) Omit booked timings, while retaining other instructors availability
        $key = array_search($value->appointment_time, $final_timing);
        if ($key !== FALSE) {
            unset($final_timing[$key]);
        }
    }

    // Exclude the already selected timings, before the current cloned div
    if(count($timings_to_ignore)){
        foreach ($timings_to_ignore as $single) {
            $key = array_search($single, $final_timing);
            if ($key !== FALSE) {
                unset($final_timing[$key]);
            }
        }
    }

    // 7) Remove duplicates
    $final_timing = array_values(array_unique($final_timing));

    function sortByTimeSlot($a, $b)
    {
        $a = preg_replace("/[^0-9]/", "", $a);
        $b = preg_replace("/[^0-9]/", "", $b);
        return $a - $b;
    }
    usort($final_timing, 'sortByTimeSlot');

    $response = new stdClass();
    $response->timings = $final_timing;
    echo json_encode($response);
    die();
}
add_action('wp_ajax_nopriv_get_time_slots', 'get_time_slots');
add_action('wp_ajax_get_time_slots', 'get_time_slots');
