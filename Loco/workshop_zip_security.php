<?php
// Validate ZIP metadata before storing a user-submitted Workshop archive.
// Archives are NEVER extracted on the web server. This is not an antivirus scan.
function locoValidateWorkshopZip(string $path): void {
    if (!class_exists('ZipArchive')) {
        throw new RuntimeException('Workshop ZIP uploads require the PHP zip extension on this host.');
    }
    $zip = new ZipArchive();
    if ($zip->open($path, ZipArchive::CHECKCONS) !== true) {
        throw new RuntimeException('The uploaded ZIP is damaged or invalid.');
    }
    try {
        if ($zip->numFiles < 1 || $zip->numFiles > 300) {
            throw new RuntimeException('Workshop ZIP files must contain 1 to 300 entries.');
        }
        $totalSize = 0;
        $fileCount = 0;
        $seen = [];
        $blockedExtensions = ['php','phtml','phar','cgi','pl','exe','dll','bat','cmd','sh','ps1','com','scr','msi','jar','zip','rar','7z','tar','gz'];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $entry = $zip->statIndex($i);
            if ($entry === false) throw new RuntimeException('Unable to inspect ZIP entry.');
            $name = (string)$entry['name'];
            if ($name === '' || strlen($name) > 255 || strpos($name, chr(0)) !== false ||
                preg_match('/[\\x00-\\x1f\\x7f]/', $name) ||
                $name[0] === '/' || strpos($name, chr(92)) !== false ||
                preg_match('/^[A-Za-z]:/', $name)) {
                throw new RuntimeException('ZIP contains an unsafe filename.');
            }
            $isDirectory = substr($name, -1) === '/';
            $parts = explode('/', $isDirectory ? substr($name, 0, -1) : $name);
            foreach ($parts as $part) {
                if ($part === '' || $part === '.' || $part === '..') {
                    throw new RuntimeException('ZIP contains an unsafe path.');
                }
            }
            $normalized = strtolower(implode('/', $parts));
            if (isset($seen[$normalized])) throw new RuntimeException('ZIP contains duplicate filenames.');
            $seen[$normalized] = true;
            if (isset($entry['encryption_method']) && (int)$entry['encryption_method'] !== 0) {
                throw new RuntimeException('Encrypted ZIP entries are not supported.');
            }
            $opsys = 0;
            $attributes = 0;
            if ($zip->getExternalAttributesIndex($i, $opsys, $attributes) &&
                $opsys === 3 && (($attributes >> 16) & 0170000) === 0120000) {
                throw new RuntimeException('ZIP symbolic links are not allowed.');
            }
            if ($isDirectory) continue;
            $fileCount++;
            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            if (in_array($ext, $blockedExtensions, true)) {
                throw new RuntimeException('Workshop ZIP contains a blocked executable or nested archive.');
            }
            $size = (int)($entry['size'] ?? 0);
            $compressed = (int)($entry['comp_size'] ?? 0);
            if ($size < 0 || $size > 20 * 1024 * 1024) {
                throw new RuntimeException('A Workshop ZIP entry exceeds the size limit.');
            }
            $totalSize += $size;
            if ($totalSize > 50 * 1024 * 1024) {
                throw new RuntimeException('Workshop ZIP expands beyond the 50 MB limit.');
            }
            if ($size > 1024 * 1024 && ($compressed <= 0 || $size / $compressed > 150)) {
                throw new RuntimeException('Workshop ZIP contains an unusually high compression ratio.');
            }
        }
        if ($fileCount === 0) throw new RuntimeException('Workshop ZIP must contain at least one file.');
    } finally {
        $zip->close();
    }
}
