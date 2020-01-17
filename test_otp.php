<?php

add_action( 'wp_ajax_di_otp_verification_and_create_user', 'di_otp_verification_and_create_user' );
	add_action( 'wp_ajax_nopriv_di_otp_verification_and_create_user','di_otp_verification_and_create_user' );
	function di_otp_verification_and_create_user() {
		global $di_apiSite;

		parse_str($_POST['form_data'], $form_data); //This will convert the string to array

		$firstName = $form_data['di_p_first_name'];
		$lastName = $form_data['di_p_last_name'];
		$mobile = $form_data['di_p_phone_code'] . $form_data['di_phone_input'];
		$otp = $form_data['opt_input'];
		$email = $form_data['di_p_email'];
		$password = $form_data['di_p_password'];
		$role = 'student';
		$organization_id = $_SESSION['parentTree']['di_organization_id'];
		// $organization_id = get_option('di_organization_id');

		$url = $di_apiSite . "/api/auth/mobile-otp";
		$data = array(
			'mobile' => $mobile,
			'code' => $otp
		);
		$data = json_encode($data);
		$get_response = json_decode(di_callAPI('POST', $url, $data, TRUE), TRUE);
		$response = json_decode($get_response['response'], TRUE);

		error_log( "\n\n******** di_otp_verification_and_create_user 01 ***********" );
		// error_log( print_r($response, TRUE) );

		// TEMPORARY COMMENTED
		if($get_response['http_code'] == 400){
			$tempArr = array("status" => FALSE, "message" => $response['message'] );
			echo json_encode($tempArr);
			wp_die();
		}

		// Create user
		$url = $di_apiSite . "auth/signup";
		$data = array(
			'firstName' => $form_data['di_p_first_name'],
			'lastName' => $form_data['di_p_last_name'],
			'mobile' => $form_data['di_p_phone_code'] . $form_data['di_phone_input'],
			'email' => $form_data['di_p_email'],
			'password' => $form_data['di_p_password'],
			'role' => 'student',
			'organization' => $organization_id
		);
		$data = json_encode($data);
		$get_response = json_decode(di_callAPI('POST', $url, $data, TRUE), TRUE);
		$response = json_decode($get_response['response'], TRUE);

		error_log( "\n\n******** di_otp_verification_and_create_user 02 ***********" );
		// error_log( print_r($response, TRUE) );

		if($get_response['http_code'] == 400){
			$tempArr = array("status" => FALSE, "message" => $response['message'] );
		} else{
			// For getting student Object
			$url = $di_apiSite . "auth/signin";
			$data = array(
				'email' => $form_data['di_p_email'],
				'password' => $form_data['di_p_password']
			);
			$data = json_encode($data);
			$get_response = json_decode(di_callAPI('POST', $url, $data, TRUE), TRUE);
			$response = json_decode($get_response['response'], TRUE);

			$_SESSION['parentTree']['di_student'] = $response['studentObj'];
			$tempArr = array("status" => TRUE, "message" => "Your account has been created successfully", "user_details" => array("student_id" => $response['studentObj']['_id'], "displayName" => $response['displayName']) );
		}

		echo json_encode($tempArr);
		wp_die();
	}