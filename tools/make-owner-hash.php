<?php
/**
 * Generate the SQL that creates the first admin owner (run on YOUR PC, never on the server).
 *
 * Usage:
 *   php tools/make-owner-hash.php you@example.com            (prompts for the password; recommended)
 *   php tools/make-owner-hash.php you@example.com 'Password' (password ends up in shell history)
 *
 * Paste the printed INSERT into phpMyAdmin -> SQL on the portfolio database.
 * The INSERT fails if that email already exists, so an existing account is never overwritten.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

if ($argc < 2) {
    fwrite(STDERR, "Usage: php tools/make-owner-hash.php email [password]\n");
    exit(1);
}

$email = trim($argv[1]);
if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
    fwrite(STDERR, "Invalid email address.\n");
    exit(1);
}

$password = $argv[2] ?? null;
if ($password === null) {
    fwrite(STDOUT, 'Password (min 12 chars): ');
    if (DIRECTORY_SEPARATOR === '/') {
        system('stty -echo');
        $password = rtrim((string) fgets(STDIN), "\r\n");
        system('stty echo');
        fwrite(STDOUT, "\n");
    } else {
        $password = rtrim((string) fgets(STDIN), "\r\n");
    }
}

if (strlen($password) < 12) {
    fwrite(STDERR, "Password must be at least 12 characters.\n");
    exit(1);
}

$q = static fn (string $v): string => "'".str_replace(["\\", "'"], ["\\\\", "''"], $v)."'";

$hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
$now = gmdate('Y-m-d H:i:s');

echo "\nSQL (phpMyAdmin -> your portfolio database -> SQL -> Go):\n\n";
echo "INSERT INTO `users` (`name`, `email`, `email_verified_at`, `password`, `is_owner`, `remember_token`, `created_at`, `updated_at`)\n";
echo 'VALUES ('.$q('Owner').', '.$q($email).', NULL, '.$q($hash).', 1, NULL, '.$q($now).', '.$q($now).");\n\n";
echo "Do not save this output in the repository or on the server.\n";
