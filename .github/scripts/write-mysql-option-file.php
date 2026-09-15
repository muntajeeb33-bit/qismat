<?php

declare(strict_types=1);

if ($argc !== 2) {
    fwrite(STDERR, "Usage: write-mysql-option-file.php <output>\n");
    exit(64);
}

$user = getenv('DATABASE_USER');
$password = getenv('DATABASE_PASSWORD');

if ($user === false || $user === '' || $password === false || $password === '') {
    fwrite(STDERR, "Database credentials are unavailable.\n");
    exit(78);
}

$quote = static fn (string $value): string => str_replace(
    ["\\", '"', "\r", "\n"],
    ["\\\\", '\\"', '\\r', '\\n'],
    $value,
);

$contents = sprintf(
    "[client]\nuser=\"%s\"\npassword=\"%s\"\nhost=\"localhost\"\n",
    $quote($user),
    $quote($password),
);

if (file_put_contents($argv[1], $contents, LOCK_EX) === false) {
    fwrite(STDERR, "Unable to write the MySQL option file.\n");
    exit(73);
}
