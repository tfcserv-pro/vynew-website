<?php

if (! defined('WP_DEBUG')) {
    die('Direct access forbidden.');
}

add_action('wp_enqueue_scripts', function () {
    wp_enqueue_style('parent-style', get_template_directory_uri() . '/style.css');
});

add_action('admin_post_nopriv_zucora_referral_request', 'handle_zucora_referral_request');
add_action('admin_post_zucora_referral_request', 'handle_zucora_referral_request');

function vynew_turnstile_enabled() {
    return defined('VYNEW_TURNSTILE_ENABLED') && filter_var(VYNEW_TURNSTILE_ENABLED, FILTER_VALIDATE_BOOLEAN);
}

function handle_zucora_referral_request() {

    if (
        !isset($_POST['zucora_nonce']) ||
        !wp_verify_nonce($_POST['zucora_nonce'], 'zucora_referral_request_nonce')
    ) {
        wp_die('Security check failed.');
    }

    $full_name = isset($_POST['full_name']) ? sanitize_text_field($_POST['full_name']) : '';
    $phone = isset($_POST['phone']) ? preg_replace('/\\D+/', '', $_POST['phone']) : '';
    $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';

    $furniture_type = isset($_POST['furniture_type']) ? sanitize_text_field($_POST['furniture_type']) : '';
    $issue_type = isset($_POST['issue_type']) ? sanitize_text_field($_POST['issue_type']) : '';
    $description = isset($_POST['description']) ? sanitize_textarea_field($_POST['description']) : '';

    $address = isset($_POST['address']) ? sanitize_text_field($_POST['address']) : '';
    $city = isset($_POST['city']) ? sanitize_text_field($_POST['city']) : '';
    $province = isset($_POST['province']) ? sanitize_text_field($_POST['province']) : '';
    $postal_code = isset($_POST['postal_code']) ? sanitize_text_field($_POST['postal_code']) : '';

    $request_source = isset($_POST['request_source'])
        ? sanitize_key($_POST['request_source'])
        : 'zucora';

    if ($request_source === 'vynew') {

		$service_agent = 'VYNEW';
		$source = 'Vynew Website Service Request';
		$form_key = 'personal_request';

		$success_url = home_url('/service-request/thank-you/');
		$failed_url  = home_url('/service-request/?status=failed');

	} else {

		$service_agent = 'ZUCORA';
		$source = 'Zucora Referral Web Form';
		$form_key = 'zucora_referral_request';

		$success_url = home_url('/zucora-referral-request/thank-you/');
		$failed_url  = home_url('/zucora-referral-request/?status=failed');
	}

    // Furnigo verifies Turnstile before any WordPress upload is processed.
    $verified = null;
    if (vynew_turnstile_enabled()) {
        $turnstile_token = isset($_POST['cf-turnstile-response']) && is_string($_POST['cf-turnstile-response'])
            ? wp_unslash($_POST['cf-turnstile-response']) : '';
        if ($turnstile_token === '' || strlen($turnstile_token) > 2048) {
            wp_redirect($failed_url);
            exit;
        }
        $verification = wp_remote_post('https://portal.furnigo.com/api/furnigo/verify-public-form', [
            'timeout' => 15,
            'headers' => ['Content-Type' => 'application/json', 'Accept' => 'application/json'],
            'body' => wp_json_encode(['turnstile_token' => $turnstile_token]),
        ]);
        $verified = !is_wp_error($verification) && wp_remote_retrieve_response_code($verification) === 200
            ? json_decode(wp_remote_retrieve_body($verification), true) : null;
        if (!is_array($verified) || ($verified['success'] ?? false) !== true
            || !is_string($verified['verification_ticket'] ?? null) || $verified['verification_ticket'] === '') {
            wp_redirect($failed_url);
            exit;
        }

    }

    $uploaded_files = [];

    if (!empty($_FILES['photos']['name'][0])) {
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        $files = $_FILES['photos'];

        foreach ($files['name'] as $key => $value) {
            if ($files['name'][$key]) {
                $file = [
                    'name'     => $files['name'][$key],
                    'type'     => $files['type'][$key],
                    'tmp_name' => $files['tmp_name'][$key],
                    'error'    => $files['error'][$key],
                    'size'     => $files['size'][$key]
                ];

                $upload = wp_handle_upload($file, ['test_form' => false]);

                if (!isset($upload['error'])) {
                    $uploaded_files[] = $upload['url'];
                }
            }
        }
    }

    $payload = [
		'form_key' => $form_key,
		'source' => $source,
		'service_agent' => $service_agent,

		'customer_name'  => $full_name,
		'customer_phone' => $phone,
		'customer_email' => $email,

		'type'    => $furniture_type,
		'issue'   => $issue_type,
		'details' => $description,

		'address' => [
			'postal_code' => $postal_code,
		],

		'attachments' => $uploaded_files,
	];

    if ($verified !== null) {
        $payload['turnstile_ticket'] = $verified['verification_ticket'];
    }
    $api_url = 'https://portal.furnigo.com/api/furnigo/service-orders';

	//$api_url = 'https://wrinkle-snipping-presume.ngrok-free.dev/api/furnigo/service-orders';

    $response = wp_remote_post($api_url, [
        'timeout' => 60,
        'headers' => [
            'Content-Type' => 'application/json',
            'Accept'       => 'application/json',
        ],
        'body' => wp_json_encode($payload),
    ]);

    if (is_wp_error($response)) {
        error_log('[SERVICE_REQUEST_API_ERROR] ' . $response->get_error_message());
        wp_redirect($failed_url);
        exit;
    }

    $status_code = wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);

    error_log('[SERVICE_REQUEST_API_STATUS] ' . $status_code);
    error_log('[SERVICE_REQUEST_API_RESPONSE] ' . $body);

    if ($status_code >= 200 && $status_code < 300) {
        wp_redirect($success_url);
        exit;
    }

    wp_redirect($failed_url);
    exit;
}
