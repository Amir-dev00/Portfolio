<?php
/**
 * Generate a bcrypt hash for the first admin owner (run locally, not on cPanel).
 *
 * Usage:
 *   php tools/make-owner-hash.php you@example.com 'YourStrongPassword'
 *
 * Then insert via phpMyAdmin (see INSTALL-CPANEL.md).
 */

if ($argc < 3) {
    fwrite(STDERR, "Usage: php tools/make-owner-hash.php email password\n");
    exit(1);
}

$email = $argv[1];
$password = $argv[2];
$hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
$name = 'Owner';
$now = gmdate('Y-m-d H:i:s');

echo "Email: {$email}\n";
echo "Hash:  {$hash}\n\n";
echo "SQL (run in phpMyAdmin on your portfolio database):\n\n";
echo "INSERT INTO users (name, email, email_verified_at, password, is_owner, remember_token, created_at, updated_at)\n";
echo "VALUES (\n";
echo "  ".json_encode($name).",\n";
echo "  ".json_encode($email).",\n";
echo "  NULL,\n";
echo "  ".json_encode($hash).",\n";
echo "  1,\n";
echo "  NULL,\n";
echo "  ".json_encode($now).",\n";
echo "  ".json_encode($now)."\n";
echo ");\n";
