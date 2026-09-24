<?php
declare(strict_types=1);

$email = $argv[1] ?? ('verification-test-' . time() . '@example.com');
$base_url = rtrim(getenv('VISIONPATH_BASE_URL') ?: 'http://localhost/visionpath', '/');
$post_data = http_build_query([
    'role' => 'student',
    'full_name' => 'Response Test User',
    'email' => $email,
    'password' => 'TestPassword!123',
    'admission_number' => 'TEST-' . time(),
    'school_name' => 'Test School',
    'assigned_class' => 'Grade 7',
]);

$context = stream_context_create(['http' => [
    'method' => 'POST',
    'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
    'content' => $post_data,
    'ignore_errors' => true,
    'follow_location' => 0,
]]);
$response = @file_get_contents($base_url . '/register.php', false, $context);
if ($response === false) {
    fwrite(STDERR, "Could not reach {$base_url}/register.php\n");
    exit(2);
}

if (preg_match('/(?<!\d)\d{6}(?!\d)/', $response) || stripos($response, 'Local Testing Notice') !== false) {
    fwrite(STDERR, "FAIL: registration response contains a verification code or legacy leak text.\n");
    exit(1);
}

echo "PASS: no verification code found in the registration response body.\n";