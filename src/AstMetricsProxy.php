<?php

namespace Halleck45\AstMetrics;

use Exception;

/**
 * This class is a proxy for the AST Metrics binary, for PHP
 * 
 * It downloads the binary from the latest release on GitHub, if it's not already downloaded
 * It then runs the binary with the provided arguments
 */
class AstMetricsProxy {

    private $binaryPath;

    public function __construct() {
        $this->binaryPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR. 'ast-metrics';
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
        $command = preg_replace('/\s+/', ' ', $command); // Replace multiple spaces with single space
        $command = str_replace(' analyze', ' analyze --non-interactive ', $command); // Add --non-interactive flag
        $command = str_replace(' a ', ' a  --non-interactive ', $command); // Add --non-interactive flag
        $command = preg_replace('/ a$/', ' a --non-interactive', $command); // Add --non-interactive flag

        return $command;
    }

    private function executeCommand($command) {
        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            $exception = new Exception("AST Metrics execution failed with code: $returnCode" . PHP_EOL . "Output:" . PHP_EOL . implode(PHP_EOL, $output));
            throw $exception;
        }

        echo implode(PHP_EOL, $output);
    }

    private function alreadyDownloaded() {
        return file_exists($this->binaryPath) && is_executable($this->binaryPath);
    }

    private function downloadBinary() {

        echo "First time running AST Metrics, downloading binary..." . PHP_EOL;

        // Define download URLs based on platform and architecture
        $version = $this->getLatestRelease();
        $downloads = [
            'Linux' => [
            'i386' => "https://github.com/Halleck45/ast-metrics/releases/download/$version/ast-metrics_Linux_i386",
            'x86_64' => "https://github.com/Halleck45/ast-metrics/releases/download/$version/ast-metrics_Linux_x86_64",
            'arm64' => "https://github.com/Halleck45/ast-metrics/releases/download/$version/ast-metrics_Linux_arm64",
            ],
            'Darwin' => [ // macOS
            'x86_64' => "https://github.com/Halleck45/ast-metrics/releases/download/$version/ast-metrics_Darwin_x86_64",
            'arm64' => "https://github.com/Halleck45/ast-metrics/releases/download/$version/ast-metrics_Darwin_arm64",
            ],
            'Windows' => [
            'i386' => "https://github.com/Halleck45/ast-metrics/releases/download/$version/ast-metrics_Windows_i386.exe",
            'x86_64' => "https://github.com/Halleck45/ast-metrics/releases/download/$version/ast-metrics_Windows_x86_64.exe",
            'arm64' => "https://github.com/Halleck45/ast-metrics/releases/download/$version/ast-metrics_Windows_arm64.exe",
            ],
        ];
        
        // Get current platform and architecture
        $platform = php_uname('s');
        $arch = php_uname('m');
        
        // Determine download URL based on platform and architecture
        $downloadUrl = null;
        if (!isset($downloads[$platform][$arch])) {
            throw new Exception("Unsupported platform or architecture: $platform - $arch");
        }

        $downloadUrl = $downloads[$platform][$arch];

        $content = file_get_contents($downloadUrl);
        if ($content === false) {
            throw new Exception("Failed to download AST Metrics binary");
        }

        // Save the binary
        file_put_contents($this->binaryPath, $content);
        
        // Set permissions (assuming executable extension for Linux/macOS)
        if (strpos($platform, 'Windows') === false) {
            chmod($this->binaryPath, 0755);
        }
    }

    private function getLatestRelease() {
        $context = stream_context_create([
            'http' => [
                'header' => 'User-Agent: PHP'
            ]
        ]);

        $latestRelease = json_decode(file_get_contents('https://api.github.com/repos/Halleck45/ast-metrics/releases/latest', false, $context), true);
        if ($latestRelease === null) {
            throw new Exception("Failed to get latest release information");
        }

        return $latestRelease['tag_name'];
    }

}
