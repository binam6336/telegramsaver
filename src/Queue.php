<?php
declare(strict_types=1);

final class Queue
{
    /** @var string */
    private $queueDir;

    /** @var string */
    private $processingDir;

    /** @var string */
    private $failedDir;

    public function __construct(string $queueDir, string $processingDir, string $failedDir)
    {
        $this->queueDir      = $queueDir;
        $this->processingDir = $processingDir;
        $this->failedDir     = $failedDir;

        foreach ([$queueDir, $processingDir, $failedDir] as $d) {
            if (!is_dir($d)) {
                @mkdir($d, 0775, true);
            }
        }
    }

    /** ذخیره اتمیک در صف */
    public function push(array $messages): string
    {
        $name = sprintf(
            '%s_%s_%s.json',
            date('Ymd_His'),
            bin2hex(random_bytes(5)),
            getmypid()
        );

        $final = $this->queueDir . '/' . $name;
        $tmp   = $final . '.tmp';

        $json = json_encode($messages, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            throw new RuntimeException('JSON encode failed: ' . json_last_error_msg());
        }

        if (file_put_contents($tmp, $json, LOCK_EX) === false) {
            throw new RuntimeException('Cannot write queue file: ' . $tmp);
        }

        if (!@rename($tmp, $final)) {
            @unlink($tmp);
            throw new RuntimeException('Cannot finalize queue file');
        }

        return $name;
    }

    /** لیست فایل‌های صف با محدودیت تعداد */
    public function listPending(int $limit): array
    {
        $files = [];
        $dh = @opendir($this->queueDir);
        if ($dh === false) {
            return $files;
        }

        while (($entry = readdir($dh)) !== false) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            // معادل str_ends_with() در PHP 7.4
            if (substr($entry, -5) !== '.json') {
                continue;
            }

            $files[] = $this->queueDir . '/' . $entry;
            if (count($files) >= $limit) {
                break;
            }
        }
        closedir($dh);

        sort($files);
        return $files;
    }

    /** انتقال اتمیک از صف به processing */
    public function claim(string $path): ?string
    {
        $target = $this->processingDir . '/' . basename($path);
        return @rename($path, $target) ? $target : null;
    }

    public function done(string $path): void
    {
        @unlink($path);
    }

    public function fail(string $path): void
    {
        @rename($path, $this->failedDir . '/' . basename($path));
    }
}