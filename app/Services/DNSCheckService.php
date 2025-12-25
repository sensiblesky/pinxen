<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class DNSCheckService
{
    /**
     * Check DNS records for a domain.
     * 
     * @param string $domain
     * @param array $recordTypes Array of record types to check (A, AAAA, CNAME, MX, NS, TXT, SOA)
     * @return array Returns DNS records by type or null on failure
     */
    public function checkDNSRecords(string $domain, array $recordTypes = ['A', 'AAAA', 'CNAME', 'MX', 'NS', 'TXT', 'SOA']): ?array
    {
        try {
            // Remove protocol and path if present
            $domain = preg_replace('#^https?://#', '', $domain);
            $domain = preg_replace('#/.*$#', '', $domain);
            $domain = trim($domain);

            $results = [];

            foreach ($recordTypes as $recordType) {
                try {
                    $records = $this->getDNSRecords($domain, $recordType);
                    $results[$recordType] = $records;
                } catch (\Exception $e) {
                    Log::warning("Failed to get DNS records for {$recordType}", [
                        'domain' => $domain,
                        'error' => $e->getMessage(),
                    ]);
                    $results[$recordType] = [];
                }
            }

            return $results;
        } catch (\Exception $e) {
            Log::error('DNS check failed', [
                'domain' => $domain,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return null;
        }
    }

    /**
     * Get DNS records for a specific type.
     * 
     * @param string $domain
     * @param string $recordType (A, AAAA, CNAME, MX, NS, TXT, SOA)
     * @return array
     */
    private function getDNSRecords(string $domain, string $recordType): array
    {
        $records = [];

        try {
            // Set DNS timeout (default is usually 5 seconds, increase to 30)
            $originalTimeout = ini_get('default_socket_timeout');
            ini_set('default_socket_timeout', 30);
            
            try {
                // Use PHP's dns_get_record function with timeout protection
                $dnsRecords = @dns_get_record($domain, $this->getDNSConstant($recordType));

                if ($dnsRecords === false) {
                    return [];
                }

                // Format records based on type
                foreach ($dnsRecords as $record) {
                    $formatted = $this->formatDNSRecord($record, $recordType);
                    if ($formatted) {
                        $records[] = $formatted;
                    }
                }
            } finally {
                // Restore original timeout
                ini_set('default_socket_timeout', $originalTimeout);
            }
        } catch (\Exception $e) {
            Log::warning("dns_get_record failed for {$recordType}", [
                'domain' => $domain,
                'error' => $e->getMessage(),
            ]);
        }

        return $records;
    }

    /**
     * Get DNS constant for record type.
     */
    private function getDNSConstant(string $recordType): int
    {
        return match(strtoupper($recordType)) {
            'A' => DNS_A,
            'AAAA' => DNS_AAAA,
            'CNAME' => DNS_CNAME,
            'MX' => DNS_MX,
            'NS' => DNS_NS,
            'TXT' => DNS_TXT,
            'SOA' => DNS_SOA,
            default => DNS_ANY,
        };
    }

    /**
     * Format DNS record for storage.
     */
    private function formatDNSRecord(array $record, string $recordType): ?array
    {
        $formatted = [
            'type' => $recordType,
            'host' => $record['host'] ?? null,
            'ttl' => $record['ttl'] ?? null,
        ];

        switch (strtoupper($recordType)) {
            case 'A':
                $formatted['ip'] = $record['ip'] ?? null;
                break;

            case 'AAAA':
                $formatted['ipv6'] = $record['ipv6'] ?? null;
                break;

            case 'CNAME':
                $formatted['target'] = $record['target'] ?? null;
                break;

            case 'MX':
                $formatted['target'] = $record['target'] ?? null;
                $formatted['pri'] = $record['pri'] ?? null;
                break;

            case 'NS':
                $formatted['target'] = $record['target'] ?? null;
                break;

            case 'TXT':
                $formatted['txt'] = $record['txt'] ?? null;
                break;

            case 'SOA':
                $formatted['mname'] = $record['mname'] ?? null;
                $formatted['rname'] = $record['rname'] ?? null;
                $formatted['serial'] = $record['serial'] ?? null;
                $formatted['refresh'] = $record['refresh'] ?? null;
                $formatted['retry'] = $record['retry'] ?? null;
                $formatted['expire'] = $record['expire'] ?? null;
                $formatted['minimum-ttl'] = $record['minimum-ttl'] ?? null;
                break;
        }

        return $formatted;
    }

    /**
     * Compare two DNS record arrays to detect changes.
     * 
     * @param array $currentRecords
     * @param array $previousRecords
     * @return array Returns ['has_changes' => bool, 'added' => [], 'removed' => [], 'modified' => []]
     */
    public function compareDNSRecords(array $currentRecords, array $previousRecords): array
    {
        $hasChanges = false;
        $added = [];
        $removed = [];
        $modified = [];

        // Normalize records for comparison (remove TTL and other metadata that changes)
        $normalize = function($records) {
            $normalized = [];
            foreach ($records as $record) {
                $key = $this->getRecordKey($record);
                $normalized[$key] = $this->getRecordValue($record);
            }
            return $normalized;
        };

        $currentNormalized = $normalize($currentRecords);
        $previousNormalized = $normalize($previousRecords);

        // Find added records
        foreach ($currentNormalized as $key => $value) {
            if (!isset($previousNormalized[$key])) {
                $added[] = $currentRecords[array_search($key, array_map(fn($r) => $this->getRecordKey($r), $currentRecords))];
                $hasChanges = true;
            } elseif ($previousNormalized[$key] !== $value) {
                $modified[] = [
                    'old' => $previousRecords[array_search($key, array_map(fn($r) => $this->getRecordKey($r), $previousRecords))],
                    'new' => $currentRecords[array_search($key, array_map(fn($r) => $this->getRecordKey($r), $currentRecords))],
                ];
                $hasChanges = true;
            }
        }

        // Find removed records
        foreach ($previousNormalized as $key => $value) {
            if (!isset($currentNormalized[$key])) {
                $removed[] = $previousRecords[array_search($key, array_map(fn($r) => $this->getRecordKey($r), $previousRecords))];
                $hasChanges = true;
            }
        }

        return [
            'has_changes' => $hasChanges,
            'added' => $added,
            'removed' => $removed,
            'modified' => $modified,
        ];
    }

    /**
     * Get a unique key for a DNS record for comparison.
     */
    private function getRecordKey(array $record): string
    {
        $type = $record['type'] ?? 'UNKNOWN';
        
        switch ($type) {
            case 'A':
                return 'A:' . ($record['ip'] ?? '');
            case 'AAAA':
                return 'AAAA:' . ($record['ipv6'] ?? '');
            case 'CNAME':
                return 'CNAME:' . ($record['target'] ?? '');
            case 'MX':
                return 'MX:' . ($record['target'] ?? '') . ':' . ($record['pri'] ?? '');
            case 'NS':
                return 'NS:' . ($record['target'] ?? '');
            case 'TXT':
                return 'TXT:' . md5($record['txt'] ?? '');
            case 'SOA':
                return 'SOA:' . ($record['mname'] ?? '');
            default:
                return md5(json_encode($record));
        }
    }

    /**
     * Get the value of a DNS record for comparison.
     */
    private function getRecordValue(array $record): string
    {
        return md5(json_encode($record));
    }
}


