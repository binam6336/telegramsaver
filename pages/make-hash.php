<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("This script can only be run from CLI.\n");
}

$pwd = $argv[1] ?? '';
if ($pwd === '') {
    fwrite(STDERR, "Usage: php make-hash.php \"<password>\"\n");
    exit(1);
}

if (mb_strlen($pwd) < 8) {
    fwrite(STDERR, "WARNING: password shorter than 8 characters.\n");
}

echo password_hash($pwd, PASSWORD_DEFAULT) . PHP_EOL;
