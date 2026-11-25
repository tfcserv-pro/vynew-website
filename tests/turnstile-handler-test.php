<?php
// Isolated WordPress boundary harness. No live HTTP, uploads, or business writes.
$runtimeRoot = getenv('VYNEW_TEST_RUNTIME_ROOT') ?: dirname(__DIR__);
if (($argv[1] ?? '') === 'flag') {
    define('WP_DEBUG', true);
    if ($argv[2] !== 'absent') define('VYNEW_TURNSTILE_ENABLED', json_decode($argv[2], true));
    function add_action(...$args) {}
    require $runtimeRoot . '/functions.php';
    echo json_encode(vynew_turnstile_enabled());
    exit;
}
if (($argv[1] ?? '') === 'template') {
    define('WP_DEBUG', true);
    if (($argv[3] ?? 'enabled') !== 'absent') define('VYNEW_TURNSTILE_ENABLED', ($argv[3] ?? 'enabled') === 'enabled');
    define('VYNEW_TURNSTILE_SITE_KEY', 'test-public-site-key');
    function add_action(...$args) {}
    require $runtimeRoot . '/functions.php';
    function get_header() {}
    function get_footer() {}
    function is_page($slug) { return $GLOBALS['argv'][2] === 'zucora'; }
    function esc_url($value) { return htmlspecialchars($value); }
    function esc_attr($value) { return htmlspecialchars($value); }
    function admin_url($path) { return 'https://www.vynew.com/wp-admin/' . $path; }
    function wp_nonce_field(...$args) { echo '<input name="zucora_nonce" value="test-nonce">'; }
    require $runtimeRoot . '/zucora-referal-form.php';
    exit;
}
if (($argv[1] ?? '') === 'case') {
    [$script, $mode, $source, $scenario, $fixture] = $argv;
    define('WP_DEBUG', true);
    if ($scenario === 'disabled-false') define('VYNEW_TURNSTILE_ENABLED', false);
    elseif (!str_starts_with($scenario, 'disabled')) define('VYNEW_TURNSTILE_ENABLED', true);
    define('ABSPATH', $fixture . '/');
    $events = [];
    $_POST = ['zucora_nonce' => 'nonce', 'request_source' => $source, 'full_name' => 'Test',
        'phone' => '416-555-0100', 'email' => 'test@example.test', 'postal_code' => 'M5V 3A8',
        'cf-turnstile-response' => in_array($scenario, ['missing', 'disabled', 'disabled-nonce'], true) ? '' : 'test-token'];
    $_FILES = ['photos' => ['name' => ['test.jpg'], 'type' => ['image/jpeg'], 'tmp_name' => ['/unused'], 'error' => [0], 'size' => [10]]];
    function add_action(...$args) {}
    function wp_verify_nonce(...$args) { return !in_array($GLOBALS['scenario'], ['nonce', 'disabled-nonce'], true); }
    function wp_die($message) { $GLOBALS['events'][] = ['nonce_rejected']; exit; }
    function sanitize_text_field($value) { return $value; }
    function sanitize_textarea_field($value) { return $value; }
    function sanitize_email($value) { return $value; }
    function sanitize_key($value) { return $value; }
    function wp_unslash($value) { return $value; }
    function home_url($value) { return 'https://www.vynew.com' . $value; }
    function wp_json_encode($value) { return json_encode($value); }
    function wp_redirect($url) { $GLOBALS['events'][] = ['redirect', $url]; }
    class TestWpError { public function get_error_message() { return 'Simulated network failure'; } }
    function is_wp_error($value) { return $value instanceof TestWpError; }
    function wp_remote_retrieve_response_code($value) { return $value['status']; }
    function wp_remote_retrieve_body($value) { return json_encode($value['body']); }
    function wp_remote_post($url, $options) {
        $payload = json_decode($options['body'], true);
        $GLOBALS['events'][] = ['http', $url, $payload];
        if (str_ends_with($url, '/verify-public-form')) {
            if ($GLOBALS['scenario'] === 'network') return new TestWpError();
            return ['status' => $GLOBALS['scenario'] === 'invalid' ? 422 : 200,
                'body' => ['success' => true, 'verification_ticket' => 'signed-ticket']];
        }
        if ($GLOBALS['scenario'] === 'api-network') return new TestWpError();
        return ['status' => $GLOBALS['scenario'] === 'api-failure' ? 422 : 201, 'body' => ['success' => true]];
    }
    function wp_handle_upload($file, $options) {
        $GLOBALS['events'][] = ['upload', $file, $options];
        return ['url' => 'https://www.vynew.com/uploads/test.jpg'];
    }
    register_shutdown_function(static function () { echo json_encode($GLOBALS['events']); });
    require $runtimeRoot . '/functions.php';
    handle_zucora_referral_request();
    exit;
}
function check($condition, $message) { if (!$condition) throw new RuntimeException($message); }
$fixture = sys_get_temp_dir() . '/vynew-test-' . bin2hex(random_bytes(8));
mkdir($fixture . '/wp-admin/includes', 0700, true);
file_put_contents($fixture . '/wp-admin/includes/file.php', '<?php // Upload API is stubbed.');
try {
    foreach (['absent' => false, 'true' => true, 'false' => false, '1' => true, '0' => false,
        '"true"' => true, '"false"' => false, '"on"' => true, '"off"' => false,
        '"yes"' => true, '"no"' => false, '"invalid"' => false] as $value => $expected) {
        $process = proc_open([PHP_BINARY, __FILE__, 'flag', (string)$value], [1 => ['pipe','w'], 2 => ['pipe','w']], $pipes);
        $actual = json_decode(stream_get_contents($pipes[1]), true); $errors = stream_get_contents($pipes[2]);
        fclose($pipes[1]); fclose($pipes[2]);
        check(proc_close($process) === 0 && $actual === $expected, 'Flag parsing failed: ' . $value . $errors);
    }
    foreach (['vynew' => '/service-request/', 'zucora' => '/zucora-referral-request/'] as $source => $path) {
        foreach (['enabled','disabled','absent'] as $flag) {
        $process = proc_open([PHP_BINARY, __FILE__, 'template', $source, $flag], [1 => ['pipe','w'], 2 => ['pipe','w']], $pipes);
        $html = stream_get_contents($pipes[1]); $errors = stream_get_contents($pipes[2]);
        fclose($pipes[1]); fclose($pipes[2]);
        check(proc_close($process) === 0, $errors);
        check(str_contains($html, 'name="request_source" value="' . $source . '"'), 'Template routing changed');
        if ($flag === 'enabled') {
            check(substr_count($html, 'class="cf-turnstile"') === 1 && str_contains($html, 'data-action="public_service_request"')
                && str_contains($html, 'data-sitekey="test-public-site-key"') && str_contains($html, 'if (true &&'), 'Shared widget missing');
        } else {
            check(!str_contains($html, 'class="cf-turnstile"') && !str_contains($html, 'challenges.cloudflare.com')
                && str_contains($html, 'if (false &&'), 'Disabled template requires Turnstile');
        }
        }
        foreach (['valid','missing','invalid','network','nonce','api-failure','api-network','disabled','disabled-false','disabled-nonce'] as $scenario) {
            $process = proc_open([PHP_BINARY, __FILE__, 'case', $source, $scenario, $fixture], [1 => ['pipe','w'], 2 => ['pipe','w']], $pipes);
            $events = json_decode(stream_get_contents($pipes[1]), true);
            $errors = stream_get_contents($pipes[2]); fclose($pipes[1]); fclose($pipes[2]);
            check(proc_close($process) === 0 && is_array($events), 'Child failed: ' . $errors);
            $uploads = array_values(array_filter($events, fn ($event) => $event[0] === 'upload'));
            $calls = array_values(array_filter($events, fn ($event) => $event[0] === 'http'));
            if (in_array($scenario, ['missing','invalid','network','nonce','disabled-nonce'], true)) {
                check(count($uploads) === 0 && count($calls) <= 1, 'Rejected submission uploaded or called Intake');
            } elseif (in_array($scenario, ['disabled','disabled-false'], true)) {
                check(count($uploads) === 1 && count($calls) === 1 && str_ends_with($calls[0][1], '/service-orders'), 'Disabled must retain upload and single Intake call');
                check(!isset($calls[0][2]['turnstile_ticket']), 'Disabled should not send a ticket');
                check($calls[0][2]['form_key'] === ($source === 'vynew' ? 'personal_request' : 'zucora_referral_request'), 'Disabled routing changed');
            } else {
                check($events[0][0] === 'http' && $events[1][0] === 'upload' && $events[2][0] === 'http', 'Verification must precede uploads');
                $payload = $calls[1][2];
                check($payload['form_key'] === ($source === 'vynew' ? 'personal_request' : 'zucora_referral_request'), 'Wrong form mapping');
                check($payload['service_agent'] === strtoupper($source), 'Wrong agent');
                check($payload['attachments'] === ['https://www.vynew.com/uploads/test.jpg'], 'Upload mapping changed');
                check($payload['customer_phone'] === '4165550100', 'Phone normalization changed');
                check($payload['turnstile_ticket'] === 'signed-ticket' && !isset($payload['turnstile_token']), 'Credential forwarding incorrect');
            }
            if (!in_array($scenario, ['nonce', 'disabled-nonce'], true)) check(end($events) === ['redirect', 'https://www.vynew.com' . $path . (in_array($scenario, ['valid','disabled','disabled-false'], true) ? 'thank-you/' : '?status=failed')], 'Redirect changed');
        }
    }
    echo "PASS: both sources, nonce, missing/invalid/unavailable verification before uploads, token forwarding, payload mapping, uploads, success/failure/network redirects.\n";
} finally {
    unlink($fixture . '/wp-admin/includes/file.php');
    rmdir($fixture . '/wp-admin/includes'); rmdir($fixture . '/wp-admin'); rmdir($fixture);
}
