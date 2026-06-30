<?php

namespace Halleck45\AstMetrics;

use Exception;
use RuntimeException;

/**
 * This class is a proxy for the AST Metrics binary, for PHP
 *
 * It downloads the binary from the latest release on GitHub, if it's not already downloaded
 * It then runs the binary with the provided arguments
 */
class AstMetricsProxy {

    private $binaryPath;

    private static $archAliases = [
        'aarch64' => 'arm64',
        'x64'     => 'x86_64',
    ];

    public function __construct() {
        $this->binaryPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'ast-metrics';
    }

    public function run($arguments) {
        if (!$this->alreadyDownloaded()) {
            $this->downloadBinary();
        }

        $command = $this->buildCommand($arguments);
        $this->executeCommand($command);
    }

    private function buildCommand($arguments) {
        $command = "$this->binaryPath " . implode(' ', $arguments);
        $command = preg_replace('/\s+/', ' ', $command);
        $command = str_replace([' analyze', ' a '], [' analyze --non-interactive ', ' a --non-interactive '], $command);
        return preg_replace('/ a$/', ' a --non-interactive', $command);
    }

    private function executeCommand($command) {
        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            throw new RuntimeException(
                "AST Metrics execution failed with code: $returnCode" . PHP_EOL .
                "Output:" . PHP_EOL .
                implode(PHP_EOL, $output)
            );
        }

        echo implode(PHP_EOL, $output);
    }

    private function alreadyDownloaded() {
        return file_exists($this->binaryPath) && is_executable($this->binaryPath);
    }

    /**
     * @throws Exception
     */
    private function downloadBinary() {
        echo "First time running AST Metrics, downloading binary..." . PHP_EOL;

        $platform = php_uname('s');
        $arch     = $this->normalizeArch(php_uname('m'));
        $version  = $this->getLatestRelease();
        $url      = $this->resolveDownloadUrl($platform, $arch, $version);

        $content = file_get_contents($url);
        if ($content === false) {
            throw new Exception("Failed to download AST Metrics binary from: $url");
        }

        file_put_contents($this->binaryPath, $content);

        if (strpos($platform, 'Windows') === false) {
            chmod($this->binaryPath, 0755);
        }
    }

    private function normalizeArch($arch) {
        return self::$archAliases[$arch] ?? $arch;
    }

    private function resolveDownloadUrl($platform, $arch, $version): string
    {
        $ext = strpos($platform, 'Windows') !== false ? '.exe' : '';
        $downloads = [
            'Linux'   => ['i386', 'x86_64', 'arm64'],
            'Darwin'  => ['x86_64', 'arm64'],
            'Windows' => ['i386', 'x86_64', 'arm64'],
        ];

        if (!isset($downloads[$platform]) || !in_array($arch, $downloads[$platform], true)) {
            throw new RuntimeException("Unsupported platform or architecture: $platform / $arch");
        }

        return "https://github.com/Halleck45/ast-metrics/releases/download/$version/ast-metrics_{$platform}_{$arch}{$ext}";
    }

    private function getLatestRelease() {
        $context = stream_context_create([
            'http' => [
                'header' => 'User-Agent: PHP'
            ]
        ]);

        $latestRelease = json_decode(
            file_get_contents('https://api.github.com/repos/Halleck45/ast-metrics/releases/latest', false, $context),
            true
        );

        if ($latestRelease === null) {
            throw new RuntimeException("Failed to get latest release information");
        }

        return $latestRelease['tag_name'];
    }
}
