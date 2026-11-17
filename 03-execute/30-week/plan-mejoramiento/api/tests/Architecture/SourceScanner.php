<?php

namespace Tests\Architecture;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/** Reads PHP files and finds the lines that break a rule, so each architecture test can name the offender. */
final class SourceScanner
{
    /** @return array<string, string> path relative to the api folder => source */
    public static function files(string $directory): array
    {
        $root = dirname(__DIR__, 2);
        $files = [];
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root.'/'.$directory, RecursiveDirectoryIterator::SKIP_DOTS));
        foreach ($iterator as $file) {
            if ($file->getExtension() === 'php') {
                $relative = str_replace('\\', '/', substr($file->getPathname(), strlen($root) + 1));
                $files[$relative] = (string) file_get_contents($file->getPathname());
            }
        }
        ksort($files);

        return $files;
    }

    /**
     * @param  array<string, string>  $files
     * @return list<string> "path:line: code" for every line that matches
     */
    public static function violations(array $files, string $pattern): array
    {
        $found = [];
        foreach ($files as $path => $source) {
            foreach (preg_split('/\R/', $source) as $index => $line) {
                if (preg_match($pattern, $line) === 1) {
                    $found[] = $path.':'.($index + 1).': '.trim($line);
                }
            }
        }

        return $found;
    }
}
