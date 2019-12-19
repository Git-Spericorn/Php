/**
* Edit my account menu order
* Process Form Submission and credit management
*/
add_action( 'woocommerce_payment_complete', 'purchase_complete_handle' );
function purchase_complete_handle( $order_id ) {

    if ( ! $order_id )
        return;

    global $wpdb;
    $the_order = wc_get_order( $order_id );

    $customer_id = $the_order->get_customer_id();


    $order_items = $the_order->get_items();

    foreach ( $order_items as $order_item ) {
        $gravity_forms_history = null;
        $entry_id = false;

        $meta_data = $order_item->get_meta_data();
        if ( WC_GFPA_Compatibility::is_wc_version_gte_3_2() ) {
            foreach ( $meta_data as $meta_data_item ) {
                $d = $meta_data_item->get_data();
                if ( $d['key'] == '_gravity_forms_history' ) {
                    $gravity_forms_history = array( $meta_data_item );
                    break;
                }
            }
        } else {
            $gravity_forms_history = wp_list_filter( $meta_data, array( 'key' => '_gravity_forms_history' ) );
        }

        if ( $gravity_forms_history ) {
            $gravity_forms_history_value = array_pop( $gravity_forms_history );
            $entry_id = isset( $gravity_forms_history_value->value['_gravity_form_linked_entry_id'] ) && ! empty( $gravity_forms_history_value->value['_gravity_form_linked_entry_id'] ) ?
                $gravity_forms_history_value->value['_gravity_form_linked_entry_id'] : false;

            $form_data = $gravity_forms_history_value->value['_gravity_form_data'];
            $lead_data = $gravity_forms_history_value->value['_gravity_form_lead'];
            $form_id = $lead_data['form_id'];

            // Restrict only 3 gravity forms
            if( in_array($form_id, ['13', '15', '16']) ){
                if ( $entry_id && ! is_wp_error( $entry_id ) ) {

                    $entry = GFAPI::get_entry( $entry_id );
                    $form = GFFormsModel::get_form_meta( $form_id );
                    $submissionData = array();

                    if(is_array($form["fields"])){
                        foreach($form["fields"] as $field){
                            if(isset($field["inputs"]) && is_array($field["inputs"])){

                                foreach($field["inputs"] as $input)
                                    $submissionData[] =  array("fieldId" => $input["id"], "fieldName" => GFCommon::get_label($field, $input["id"]), "fieldValue" => $entry[$input["id"]]);
                            }
                            else if(!rgar($field, 'displayOnly')){
                                $submissionData[] =  array("fieldId" => $field["id"], "fieldName" => GFCommon::get_label($field), "fieldValue" => $entry[$field["id"]]);
                            }
                        }
                    }

                    // Search the multidimensional array for the passed fieldId, and returns Boolean false on failure OR integer(index of item) value
                    $package_Pos = array_search(1, array_column($submissionData, 'fieldId'), true);
                    $package_hidden_namePos = array_search(9, array_column($submissionData, 'fieldId'), true);
                    if($package_hidden_namePos !== false && $package_Pos !== false) {    // Position present
                        $planData = $submissionData[$package_Pos]['fieldValue'];
                        $sanitizedPlanData = explode( chr( 1 ), str_replace( array(' ', '|' ), chr( 1 ), $planData ) );
                        $planHiddenName = $submissionData[$package_hidden_namePos]['fieldValue'];

                        $credits = NULL;
                        $expiry = NULL;
                        $unlimited_type = NULL;
                        $today = date("Y-m-d");

                        if($planHiddenName == 'unlimited'){
                            $expiry = date("Y-m-d", strtotime($today ." +1 month"));
                            $unlimited_type = strtolower( $sanitizedPlanData[0] );
                        } else if( in_array($planHiddenName, ['personal', 'individual']) ){
                            $credits = (int)$sanitizedPlanData[0];
                        }

                        $args = array(
                            "customer_id" => $customer_id,
                            "order_id" => (int)$order_id,
                            "gf_form_id" => (int)$form_id,
                            "gform_entry_id" => (int)$entry_id,
                            "package_hidden_name" => $planHiddenName,
                            "credits" => $credits,
                            "is_unlimited_type" => $unlimited_type,
                            "expiry" => $expiry,
                        );

                        $sqlInsert = $wpdb->insert(DB_WC_GF_CUSTOMER_PURCHASES, $args);
                    }

                }
            }
        }
    }

}
