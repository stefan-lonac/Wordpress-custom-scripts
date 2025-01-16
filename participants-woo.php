<?php 

/*  
  * Custom WooCommerce Checkout Fields for participants
  * Used for Kadence Wordpress theme - not mandatory
	* Default language are serbian - no mandatory
*/

// Custom WooCommerce Checkout Fields based on Quantity
add_action('woocommerce_before_order_notes', 'attendees_checkout_fields');
function attendees_checkout_fields($checkout)
{
	echo '<h3>' . __('Detalji učesnika', 'kadence-child') . '</h3>';

	echo '<p class="form-row form-row-wide" id="number_of_attendees_field">
	<label for="number_of_attendees">' . __('Broj učesnika', 'kadence-child') . ' <span class="required">*</span></label>
	<input type="number" min="1" max="10" name="number_of_attendees" id="number_of_attendees" class="input-text" value="' . (isset($_POST['number_of_attendees']) ? esc_attr($_POST['number_of_attendees']) : '') . '" />
	</p>';

	echo '<div id="attendees_fields_container"></div>';
}

// JavaScript for dynamically adding attendee fields
add_action('woocommerce_after_checkout_form', 'dynamic_attendee_fields');
function dynamic_attendee_fields()
{
	?>
	<script type="text/javascript">
		jQuery(function ($) {
			$('#number_of_attendees').change(function () {
				var numAttendees = $(this).val();
				var attendeesFields = '';

				for (var i = 1; i <= numAttendees; i++) {
					attendeesFields += '<div class="attendee-container-checkout">';
					attendeesFields += '<h4><?php echo __('Detalji učesnika', 'kadence-child'); ?> ' + i + '</h4>';
					attendeesFields += '<p><input type="text" name="cstm_full_name' + i + '" placeholder="<?php echo __('Puno ime', 'kadence-child'); ?>"></p>';
					attendeesFields += '<p><input type="text" name="cstm_phone' + i + '" placeholder="<?php echo __('Broj telefona', 'kadence-child'); ?>"></p>';
					attendeesFields += '<p><input type="email" name="cstm_email' + i + '" placeholder="<?php echo __('Email', 'kadence-child'); ?>"></p>';
					attendeesFields += '</div>';
				}

				$('#attendees_fields_container').html(attendeesFields);
			});
		});
	</script>
	<?php
}

// Utility function
function attendee_fields_keys_labels()
{
	return array(
		'cstm_full_name' => __('Puno ime'),
		'cstm_phone' => __('Broj telefona'),
		'cstm_email' => __('Email'),
	);
}

// Save value of fields
add_action('woocommerce_checkout_create_order', 'save_custom_checkout_fields_data');
function save_custom_checkout_fields_data($order)
{
	if (isset($_POST['number_of_attendees']) && !empty($_POST['number_of_attendees'])) {
		$count = intval($_POST['number_of_attendees']);
		$order->update_meta_data('items_count', $count);

		for ($i = 1; $i <= $count; $i++) {
			foreach (attendee_fields_keys_labels() as $field_key => $field_label) {
				if (isset($_POST[$field_key . $i]) && !empty($_POST[$field_key . $i])) {
					$order->update_meta_data($field_key . $i, sanitize_textarea_field($_POST[$field_key . $i]));
				}
			}
		}
	}
}

// Display attendees on email notifications
add_action('woocommerce_email_order_details', 'action_after_email_order_details', 25, 4);
function action_after_email_order_details($order, $sent_to_admin, $plain_text, $email)
{
	$count = $order->get_meta('items_count');

	if (!$count)
		return;

	// The HTML Structure
	$html_output = '<h2>' . __('Detalji učesnika', 'kadence-child') . '</h2>
    <div class="discount-info">';

	// Loop though the data array to set the fields
	for ($i = 1; $i <= $count; $i++) {
		$html_output .= '<table cellspacing="0" cellpadding="6">
        <thead><tr><th colspan="2"><h4>' . __('Učesnici', 'kadence-child') . ' ' . $i . '</h4></th></tr></thead>
        <tbody>';

		foreach (attendee_fields_keys_labels() as $field_key => $field_label) {
			if ($meta_value = $order->get_meta($field_key . $i)) {
				$html_output .= '<tr>
                    <th>' . $field_label . '</th>
                    <td>' . $meta_value . '</td>
                </tr>';
			}
		}
		$html_output .= '</tbody></table>';
	}
	$html_output .= '</div><br>'; // HTML (end)

	// The CSS styling
	$styles = '<style>
        .discount-info table{width: 100%; font-family: \'Helvetica Neue\', Helvetica, Roboto, Arial, sans-serif;
            color: #737373; border: 1px solid #e4e4e4; margin-bottom:8px;}
        .discount-info table th, table.tracking-info td{text-align: left; border-top-width: 4px;
            color: #737373; border: 1px solid #e4e4e4; padding: 12px; width:58%;}
        .discount-info table td{text-align: left; border-top-width: 4px; color: #737373; border: 1px solid #e4e4e4; padding: 12px;}
    </style>';

	// The Output CSS + HTML
	echo $styles . $html_output;
}

// Display attendees on Customer orders
add_action('woocommerce_view_order', 'woocommerce_order_attendees_table', 15);
add_action('woocommerce_thankyou', 'woocommerce_order_attendees_table', 15);
function woocommerce_order_attendees_table($order_id)
{
	$order = wc_get_order($order_id);
	$count = $order->get_meta('items_count');

	if (!$count)
		return;

	echo '<section class="woocommerce-attendees">
    <h2 class="woocommerce-attendees__title">' . esc_html__('Učesnici', 'kadence-child') . '</h2>
    <table class="woocommerce-table woocommerce-table--attendees shop_table attendees">
    <tbody>';
	// Loop though the data array to set the fields
	for ($i = 1; $i <= $count; $i++) {
		echo '<tr><th colspan="4">
            <h4>' . __('Učesnik') . ' ' . $i . '</h4>
        </th></tr><tr>';

		foreach (attendee_fields_keys_labels() as $field_key => $field_label) {
			echo '<th>' . $field_label . '</th>';
		}
		echo '</tr><tr>';

		foreach (attendee_fields_keys_labels() as $field_key => $field_label) {
			if ($meta_value = $order->get_meta($field_key . $i)) {
				echo '<td>' . $meta_value . '</td>';
			}
		}
		echo '</tr>';
	}
	echo '</tbody></table></section>';
}

// Display attendees on Admin Order edit pages: Custom metabox (right column)
add_action('add_meta_boxes', 'add_attendees_metabox');
function add_attendees_metabox()
{
	add_meta_box(
		'attendees',
		__('Učesnici'),
		'attendees_metabox_content',
		'shop_order',
		'side', // or 'normal'
		'default' // or 'high'
	);
}

// Adding the content for the custom metabox
function attendees_metabox_content()
{
	$order = wc_get_order(get_the_id());
	$count = $order->get_meta('items_count');

	if (!$count)
		return;

	for ($i = 1; $i <= $order->get_meta('items_count'); $i++) {
		echo '<div style="background-color:#eee;padding:2px;margin-bottom:.4em;">
        <h4 style="margin:.5em 0;padding:2px;">Učesnik ' . $i . '</h4>
        <table style="text-align:left;margin-bottom:.7em;" cellpadding="2"><tbody>';

		foreach (attendee_fields_keys_labels() as $field_key => $field_label) {
			if ($meta_value = $order->get_meta($field_key . $i)) {
				echo '<tr><th valign="top">' . $field_label . ':</th><td>' . $meta_value . '</td></tr>';
			}
		}
		echo '</tbody></table></div>';
	}
}

// Send email for every customer added from custom fields
add_action('woocommerce_checkout_order_processed', 'create_quest_account_for_attendees', 10, 1);
function create_quest_account_for_attendees($order_id)
{

	$order = wc_get_order($order_id);
	$count = $order->get_meta('items_count');
	$purchased_course_id = $order->get_meta('purchased_course_id');

	// Loop through each attendee and create Quest account
	for ($i = 1; $i <= $count; $i++) {
		$full_name = $order->get_meta('cstm_full_name' . $i);
		$email = $order->get_meta('cstm_email' . $i);

		// Check if user already exists with provided email
		$user = get_user_by('email', $email);

		if (!$user) {
			// User does not exist, create a new user
			$random_password = wp_generate_password();

			$data = array(
				'user_login' => $email, // the user's login username.
				'user_pass' => $random_password, // not necessary to hash password ( The plain-text user password ).
				'user_email' => $email,
				'show_admin_bar_front' => false // display the Admin Bar for the user 'true' or 'false'
			);

			$user_id = wp_insert_user($data);

			// Set display name
			wp_update_user(array('ID' => $user_id, 'display_name' => $full_name));

			// Send user notification email with password
			wp_new_user_notification($user_id, $random_password);

			$items = $order->get_items();
			$course_ids = array();

			foreach ($items as $item) {
				$product_id = $item->get_product_id();
				$product = wc_get_product($product_id);

				if ($product->is_type('variable')) {
					$product_id = $item->get_product_id();
					$variation_id = $item->get_variation_id(); // Get the ID of the product variation
					$course_id = get_post_meta($variation_id, '_related_course', true);

					// Check if the product is a variable
					if (!empty($course_id)) {
						$course_ids[] = $course_id[0];
					}
				}

				if ($product->is_type('simple')) {
					// Check if the product is associated with the appropriate exchange rate
					$course_id = get_post_meta($product_id, '_related_course', true);

					if (!empty($course_id)) {
						$course_ids[] = $course_id;
					}

				}

				if ($product->is_type('course')) {
					// Check if the product is associated with the appropriate exchange rate
					$course_id = get_post_meta($product_id, '_related_course', true);
					if (!empty($course_id)) {
						$course_ids[] = $course_id[0];
					}
				}

			}

			// Remove duplicate course IDs
			$course_ids = array_unique($course_ids);

			// Update user access for each course ID
			foreach ($course_ids as $course_id) {
				ld_update_course_access($user_id, $course_id);
			}

			// Optionally, send welcome email or perform other actions
		} else {
			// User already exists, update display name if needed
			if ($user->display_name !== $full_name) {
				wp_update_user(array('ID' => $user->ID, 'display_name' => $full_name));
			}

			// User already exists, send email with login details
			$login_url = wp_login_url();
			$message = sprintf(__('Zdravo %s,<br><br>Vaš nalog za pristup kupljenom kursu je kreiran. Možete se prijaviti koristeći sledeće akreditive:<br><br>Korisničko ime: %s<br>Šifra: %s<br><br>You can log in <a href="%s">here</a>.', 'kadence-child'), $full_name, $email, $random_password, $login_url);
			$subject = __('Informacije tvog naloga', 'kadence-child');
			$headers = array('Content-Type: text/html; charset=UTF-8');
			wp_mail($email, $subject, $message, $headers);
		}
	}
}

// Function to get purchased course ID for a specific attendee
function get_purchased_course_id($order_id, $participant_number)
{
	$order = wc_get_order($order_id);
	$purchased_courses = $order->get_meta('purchased_courses');

	// Check if purchased courses array exists
	if ($purchased_courses && is_array($purchased_courses)) {
		// Loop through purchased courses to find the specific participant's purchased course
		foreach ($purchased_courses as $course_info) {
			if ($course_info['participant_number'] == $participant_number) {
				return $course_info['purchased_course_id'];
			}
		}
	}

	return false; // Return false if purchased course ID is not found
}

