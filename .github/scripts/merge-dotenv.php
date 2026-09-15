<?php

declare(strict_types=1);

if ($argc !== 4) {
    fwrite(STDERR, "Usage: merge-dotenv.php <existing> <updates> <output>\n");
    exit(64);
}

[, $existingPath, $updatesPath, $outputPath] = $argv;
$updateLines = file($updatesPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

if ($updateLines === false) {
    fwrite(STDERR, "Unable to read deployment environment updates.\n");
    exit(66);
}

$updates = [];

foreach ($updateLines as $line) {
    [$name, $value] = array_pad(explode('=', $line, 2), 2, null);

    if ($value === null || preg_match('/^[A-Z][A-Z0-9_]*$/', $name) !== 1) {
        fwrite(STDERR, "Invalid deployment environment entry.\n");
        exit(65);
    }

    $updates[$name] = $value;
}

$existingLines = file_exists($existingPath)
    ? file($existingPath, FILE_IGNORE_NEW_LINES)
    : [];

if ($existingLines === false) {
    fwrite(STDERR, "Unable to read the existing environment file.\n");
    exit(66);
}

$managedKeys = array_fill_keys(array_keys($updates), true);
$writtenKeys = [];
$outputLines = [];

foreach ($existingLines as $line) {
    if (preg_match('/^([A-Z][A-Z0-9_]*)=/', $line, $matches) !== 1) {
        $outputLines[] = $line;
        continue;
    }

    $name = $matches[1];

    if (! isset($managedKeys[$name])) {
        $outputLines[] = $line;
        continue;
    }

    if (! isset($writtenKeys[$name])) {
        $outputLines[] = $name.'='.$updates[$name];
        $writtenKeys[$name] = true;
    }
}

$missingKeys = array_diff_key($updates, $writtenKeys);

if ($missingKeys !== [] && $outputLines !== [] && end($outputLines) !== '') {
    $outputLines[] = '';
}

foreach ($missingKeys as $name => $value) {
    $outputLines[] = $name.'='.$value;
}

$contents = implode(PHP_EOL, $outputLines).PHP_EOL;

if (file_put_contents($outputPath, $contents, LOCK_EX) === false) {
    fwrite(STDERR, "Unable to write the merged environment file.\n");
    exit(73);
}
