<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;

class PdfOcrService
{
    public function isAvailable(): bool
    {
        return $this->bin('pdftoppm') !== null && $this->bin('tesseract') !== null;
    }

    /**
     * OCR one or more pages and return concatenated text.
     *
     * Requirements (installed on the host):
     * - poppler-utils (pdftoppm)
     * - tesseract
     */
    public function ocrPdfPages(string $pdfAbsPath, array $pages, int $dpi = 200): string
    {
        $pages = array_values(array_unique(array_filter(array_map('intval', $pages), fn ($p) => $p > 0)));
        if (empty($pages)) {
            return '';
        }

        $tmpRoot = Storage::disk('local')->path('tmp/ocr');
        if (!is_dir($tmpRoot)) {
            @mkdir($tmpRoot, 0775, true);
        }

        $jobDir = $tmpRoot.'/'.date('Ymd_His').'_'.bin2hex(random_bytes(4));
        @mkdir($jobDir, 0775, true);

        $out = [];

        try {
            foreach ($pages as $page) {
                $prefix = $jobDir.'/page_'.$page;

                $pdftoppm = $this->bin('pdftoppm');
                $render = new Process([
                    $pdftoppm,
                    '-f', (string) $page,
                    '-l', (string) $page,
                    '-png',
                    '-r', (string) $dpi,
                    $pdfAbsPath,
                    $prefix,
                ]);
                $render->setTimeout(60);
                $render->run();

                if (!$render->isSuccessful()) {
                    continue;
                }

                $imagePath = $prefix.'-'.sprintf('%d', $page).'.png';
                if (!file_exists($imagePath)) {
                    // Some builds output "page_2-1.png" when rendering a single page.
                    $fallback = glob($prefix.'-*.png');
                    $imagePath = $fallback[0] ?? null;
                }
                if (!$imagePath || !file_exists($imagePath)) {
                    continue;
                }

                $tesseract = $this->bin('tesseract');
                $ocr = new Process([
                    $tesseract,
                    $imagePath,
                    'stdout',
                    '-l',
                    env('TESSERACT_LANG', 'eng'),
                    '--psm',
                    env('TESSERACT_PSM', '6'),
                ]);
                $ocr->setTimeout(120);
                $ocr->run();

                if ($ocr->isSuccessful()) {
                    $out[] = $ocr->getOutput();
                }
            }
        } finally {
            $this->deleteDir($jobDir);
        }

        return trim(implode("\n\n", array_filter($out)));
    }

    private function bin(string $name): ?string
    {
        $envKey = strtoupper($name).'_BIN';
        $configured = env($envKey);
        if (is_string($configured) && $configured !== '') {
            return $configured;
        }

        // Let the OS PATH resolve it.
        return $name;
    }

    private function deleteDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $items = scandir($dir);
        if ($items === false) {
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $dir.DIRECTORY_SEPARATOR.$item;
            if (is_dir($path)) {
                $this->deleteDir($path);
            } else {
                @unlink($path);
            }
        }

        @rmdir($dir);
    }
}

