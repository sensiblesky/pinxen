<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class MultiProbeService
{
    /**
     * Perform multi-probe check with confirmation logic.
     * 
     * @param string $url
     * @param int $probes Number of probes to use
     * @param int $threshold Number of failures required to confirm DOWN
     * @param int $timeout
     * @param int $expectedStatusCode
     * @param bool $checkSsl
     * @param int $retryDelay Seconds between retries
     * @param int $maxRetries Maximum retry attempts
     * @return array
     */
    public static function performMultiProbeCheck(
        string $url,
        int $probes = 3,
        int $threshold = 2,
        int $timeout = 30,
        int $expectedStatusCode = 200,
        bool $checkSsl = true,
        int $retryDelay = 5,
        int $maxRetries = 3
    ): array {
        $probeResults = [];
        $failures = 0;
        $successes = 0;
        
        // Simulate different probes (in production, these could be actual different regions/ISPs)
        // For now, we'll use retries with exponential backoff to simulate different perspectives
        for ($probeIndex = 0; $probeIndex < $probes; $probeIndex++) {
            $probeName = self::getProbeName($probeIndex);
            
            // Add delay between probes (except first one)
            if ($probeIndex > 0) {
                sleep($retryDelay);
            }
            
            $probeResult = self::performSingleProbe(
                $url,
                $probeName,
                $timeout,
                $expectedStatusCode,
                $checkSsl,
                $probeIndex
            );
            
            $probeResults[] = $probeResult;
            
            if ($probeResult['status'] === 'down') {
                $failures++;
            } else {
                $successes++;
            }
            
            // Early exit if we've confirmed DOWN (threshold met)
            if ($failures >= $threshold) {
                break;
            }
            
            // Early exit if we've confirmed UP (majority success)
            if ($successes > ($probes - $threshold)) {
                break;
            }
        }
        
        // Determine final status based on threshold
        $isConfirmed = $failures >= $threshold;
        $finalStatus = $isConfirmed ? 'down' : 'up';
        
        // Get the best result (prefer success, otherwise use first failure)
        $bestResult = null;
        foreach ($probeResults as $result) {
            if ($result['status'] === 'up') {
                $bestResult = $result;
                break;
            }
        }
        if (!$bestResult && !empty($probeResults)) {
            $bestResult = $probeResults[0];
        }
        
        return [
            'status' => $finalStatus,
            'is_confirmed' => $isConfirmed,
            'probes_total' => count($probeResults),
            'probes_failed' => $failures,
            'probes_success' => $successes,
            'probe_results' => $probeResults,
            'response_time' => $bestResult['response_time'] ?? null,
            'status_code' => $bestResult['status_code'] ?? null,
            'error_message' => $bestResult['error_message'] ?? null,
            'failure_type' => $bestResult['failure_type'] ?? null,
            'failure_classification' => $bestResult['failure_classification'] ?? null,
            'layer_checks' => $bestResult['layer_checks'] ?? null,
        ];
    }
    
    /**
     * Perform a single probe check.
     */
    private static function performSingleProbe(
        string $url,
        string $probeName,
        int $timeout,
        int $expectedStatusCode,
        bool $checkSsl,
        int $probeIndex
    ): array {
        $startTime = microtime(true);
        
        try {
            // Add small random delay to simulate different network paths
            usleep(rand(100000, 500000)); // 100-500ms
            
            // Perform layer checks if this is the first probe
            $layerChecks = null;
            if ($probeIndex === 0) {
                $layerChecks = \App\Services\LayerCheckService::performLayerChecks(
                    $url,
                    $checkSsl,
                    $timeout
                );
            }
            
            // Perform HTTP check
            $response = Http::timeout($timeout)
                ->withOptions([
                    'verify' => $checkSsl,
                    'http_errors' => false,
                    'curl' => [
                        CURLOPT_SSL_VERIFYPEER => $checkSsl,
                        CURLOPT_SSL_VERIFYHOST => $checkSsl ? 2 : 0,
                    ],
                ])
                ->get($url);
            
            $responseTime = round((microtime(true) - $startTime) * 1000);
            $statusCode = $response->status();
            $isUp = ($statusCode === $expectedStatusCode);
            
            // Classify failure if down
            $failureClassification = null;
            if (!$isUp) {
                $failureClassification = \App\Services\FailureClassificationService::classifyFailure(
                    "HTTP {$statusCode}",
                    $statusCode,
                    $responseTime,
                    $url
                );
            }
            
            return [
                'probe_name' => $probeName,
                'status' => $isUp ? 'up' : 'down',
                'response_time' => $responseTime,
                'status_code' => $statusCode,
                'error_message' => $isUp ? null : "Expected status code {$expectedStatusCode}, got {$statusCode}",
                'failure_type' => $failureClassification['type'] ?? null,
                'failure_classification' => $failureClassification['classification'] ?? null,
                'layer_checks' => $layerChecks,
                'checked_at' => now()->toIso8601String(),
            ];
            
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            $responseTime = round((microtime(true) - $startTime) * 1000);
            $errorMessage = $e->getMessage();
            
            // Classify the failure
            $failureClassification = \App\Services\FailureClassificationService::classifyFailure(
                $errorMessage,
                null,
                $responseTime,
                $url
            );
            
            // Perform layer checks for connection failures (first probe only)
            $layerChecks = null;
            if ($probeIndex === 0) {
                try {
                    $layerChecks = \App\Services\LayerCheckService::performLayerChecks(
                        $url,
                        $checkSsl,
                        $timeout
                    );
                } catch (\Exception $layerException) {
                    // Ignore layer check errors
                }
            }
            
            return [
                'probe_name' => $probeName,
                'status' => 'down',
                'response_time' => $responseTime,
                'status_code' => null,
                'error_message' => 'Connection failed: ' . $errorMessage,
                'failure_type' => $failureClassification['type'] ?? null,
                'failure_classification' => $failureClassification['classification'] ?? null,
                'layer_checks' => $layerChecks,
                'checked_at' => now()->toIso8601String(),
            ];
            
        } catch (\Exception $e) {
            $responseTime = round((microtime(true) - $startTime) * 1000);
            
            $classificationResult = \App\Services\FailureClassificationService::classify(
                $e->getMessage(),
                null
            );
            $failureClassification = [
                'type' => $classificationResult['type'] ?? null,
                'classification' => $classificationResult['classification'] ?? null,
            ];
            
            return [
                'probe_name' => $probeName,
                'status' => 'down',
                'response_time' => $responseTime,
                'status_code' => null,
                'error_message' => 'Unexpected error: ' . $e->getMessage(),
                'failure_type' => $failureClassification['type'] ?? null,
                'failure_classification' => $failureClassification['classification'] ?? null,
                'layer_checks' => null,
                'checked_at' => now()->toIso8601String(),
            ];
        }
    }
    
    /**
     * Get probe name (simulating different regions/ISPs).
     */
    private static function getProbeName(int $index): string
    {
        $probes = [
            'Primary Probe',
            'Secondary Probe (Retry 1)',
            'Tertiary Probe (Retry 2)',
            'Backup Probe (Retry 3)',
            'Fallback Probe (Retry 4)',
        ];
        
        return $probes[$index] ?? "Probe " . ($index + 1);
    }
}

