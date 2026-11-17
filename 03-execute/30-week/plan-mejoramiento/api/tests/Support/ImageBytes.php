<?php

namespace Tests\Support;

/** Real image bytes built by hand, so the photo tests need neither GD nor files in the repository. */
final class ImageBytes
{
    /** A valid 1x1 PNG padded with a private ancillary chunk up to the exact size asked for. */
    public static function png(int $totalBytes = 1024): string
    {
        $chunk = fn (string $type, string $data) => pack('N', strlen($data)).$type.$data.pack('N', crc32($type.$data));
        $head = "\x89PNG\r\n\x1a\n".$chunk('IHDR', pack('NNCCCCC', 1, 1, 8, 2, 0, 0, 0)).$chunk('IDAT', gzcompress("\x00\xff\x00\x00"));
        $tail = $chunk('IEND', '');

        return $head.$chunk('raNd', str_repeat("\0", $totalBytes - strlen($head) - strlen($tail) - 12)).$tail;
    }

    public static function jpeg(): string
    {
        return "\xFF\xD8\xFF\xE0\x00\x10JFIF\x00\x01\x01\x00\x00\x01\x00\x01\x00\x00".str_repeat("\x00", 64)."\xFF\xD9";
    }
}
