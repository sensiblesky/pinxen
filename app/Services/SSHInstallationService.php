<?php

namespace App\Services;

use App\Models\Server;
use Exception;
use Illuminate\Support\Facades\Log;

class SSHInstallationService
{
    /**
     * Attempt to install agent via SSH
     */
    public function installViaSSH(Server $server, array $credentials): array
    {
        $host = $credentials['host'] ?? $server->ip_address ?? $server->hostname;
        $port = $credentials['port'] ?? 22;
        $username = $credentials['username'];
        $password = $credentials['password'] ?? null;
        $privateKey = $credentials['private_key'] ?? null;
        $os = $credentials['os'] ?? 'linux';
        $arch = $credentials['arch'] ?? 'amd64';

        if (!$host) {
            throw new Exception('Host address is required (IP address or hostname)');
        }

        if (!$username) {
            throw new Exception('SSH username is required');
        }

        if (!$password && !$privateKey) {
            throw new Exception('Either password or private key is required');
        }

        try {
            // Detect OS if not provided
            if (!$os || $os === 'auto') {
                $os = $this->detectOS($host, $port, $username, $password, $privateKey);
            }

            // Download and install agent
            $result = $this->performInstallation($host, $port, $username, $password, $privateKey, $os, $arch, $server);

            return [
                'success' => true,
                'message' => 'Agent installed successfully via SSH',
                'os_detected' => $os,
                'details' => $result
            ];
        } catch (Exception $e) {
            Log::error('SSH Installation failed', [
                'server_id' => $server->id,
                'host' => $host,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'SSH installation failed: ' . $e->getMessage(),
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Detect operating system via SSH
     */
    private function detectOS(string $host, int $port, string $username, ?string $password, ?string $privateKey): string
    {
        $connection = $this->connectSSH($host, $port, $username, $password, $privateKey);
        
        // Try to detect OS
        $commands = [
            'uname -s' => ['Linux' => 'linux', 'Darwin' => 'darwin', 'FreeBSD' => 'freebsd'],
            'cat /etc/os-release | grep ^ID=' => ['linux'],
        ];

        foreach ($commands as $cmd => $mapping) {
            try {
                $output = ssh2_exec($connection, $cmd);
                stream_set_blocking($output, true);
                $result = stream_get_contents($output);
                
                if ($result) {
                    $result = trim(strtolower($result));
                    if (strpos($result, 'linux') !== false) return 'linux';
                    if (strpos($result, 'darwin') !== false) return 'darwin';
                    if (strpos($result, 'freebsd') !== false) return 'freebsd';
                }
            } catch (Exception $e) {
                continue;
            }
        }

        // Default to linux
        return 'linux';
    }

    /**
     * Perform the actual installation
     */
    private function performInstallation(string $host, int $port, string $username, ?string $password, ?string $privateKey, string $os, string $arch, Server $server): array
    {
        $connection = $this->connectSSH($host, $port, $username, $password, $privateKey);
        
        $apiKey = $server->apiKey;
        $apiUrl = config('app.url') . '/api/v1';
        $downloadUrl = route('agents.download', [
            'server' => $server->uid,
            'os' => $os,
            'arch' => $arch
        ]);

        $ext = $os === 'windows' ? '.exe' : '';
        $binaryName = "pingxeno-agent-{$os}-{$arch}{$ext}";

        // Create installation commands based on OS
        $commands = $this->generateInstallCommands($os, $arch, $server, $apiKey, $apiUrl, $downloadUrl, $binaryName);

        $outputs = [];
        foreach ($commands as $command) {
            $stream = ssh2_exec($connection, $command);
            stream_set_blocking($stream, true);
            $output = stream_get_contents($stream);
            $outputs[] = $output;
        }

        ssh2_disconnect($connection);

        return [
            'commands_executed' => count($commands),
            'outputs' => $outputs
        ];
    }

    /**
     * Generate installation commands for the OS
     */
    private function generateInstallCommands(string $os, string $arch, Server $server, $apiKey, string $apiUrl, string $downloadUrl, string $binaryName): array
    {
        if ($os === 'linux' || $os === 'freebsd') {
            return [
                "mkdir -p /etc/pingxeno",
                "curl -L -o /tmp/{$binaryName} '{$downloadUrl}' || wget -O /tmp/{$binaryName} '{$downloadUrl}'",
                "chmod +x /tmp/{$binaryName}",
                "mv /tmp/{$binaryName} /usr/local/bin/pingxeno-agent",
                "cat > /etc/pingxeno/agent.yaml <<EOF
api_url: {$apiUrl}
api_key: {$apiKey->key}
server_key: {$server->server_key}
interval: 60
EOF",
                "cat > /etc/systemd/system/pingxeno-agent.service <<EOF
[Unit]
Description=PingXeno Monitoring Agent
After=network.target

[Service]
Type=simple
User=root
ExecStart=/usr/local/bin/pingxeno-agent run --config /etc/pingxeno/agent.yaml
Restart=always
RestartSec=10

[Install]
WantedBy=multi-user.target
EOF",
                "systemctl daemon-reload",
                "systemctl enable pingxeno-agent",
                "systemctl start pingxeno-agent"
            ];
        } elseif ($os === 'darwin') {
            return [
                "mkdir -p /usr/local/etc/pingxeno",
                "curl -L -o /tmp/{$binaryName} '{$downloadUrl}'",
                "chmod +x /tmp/{$binaryName}",
                "mv /tmp/{$binaryName} /usr/local/bin/pingxeno-agent",
                "cat > /usr/local/etc/pingxeno/agent.yaml <<EOF
api_url: {$apiUrl}
api_key: {$apiKey->key}
server_key: {$server->server_key}
interval: 60
EOF",
                // Launchd plist creation would go here
                "launchctl load -w /Library/LaunchDaemons/com.pingxeno.agent.plist || true"
            ];
        }

        return [];
    }

    /**
     * Connect via SSH
     */
    private function connectSSH(string $host, int $port, string $username, ?string $password, ?string $privateKey)
    {
        if (!function_exists('ssh2_connect')) {
            throw new Exception('SSH2 extension is not installed. Please install php-ssh2 extension.');
        }

        $connection = @ssh2_connect($host, $port);
        if (!$connection) {
            throw new Exception("Failed to connect to {$host}:{$port}");
        }

        // Authenticate
        if ($privateKey) {
            // Key-based authentication
            if (!@ssh2_auth_pubkey_file($connection, $username, $privateKey . '.pub', $privateKey)) {
                throw new Exception('SSH key authentication failed');
            }
        } else {
            // Password authentication
            if (!@ssh2_auth_password($connection, $username, $password)) {
                throw new Exception('SSH password authentication failed');
            }
        }

        return $connection;
    }

    /**
     * Test SSH connection
     */
    public function testConnection(array $credentials): array
    {
        $host = $credentials['host'] ?? null;
        $port = $credentials['port'] ?? 22;
        $username = $credentials['username'] ?? null;
        $password = $credentials['password'] ?? null;
        $privateKey = $credentials['private_key'] ?? null;

        if (!$host || !$username) {
            return [
                'success' => false,
                'message' => 'Host and username are required'
            ];
        }

        try {
            $connection = $this->connectSSH($host, $port, $username, $password, $privateKey);
            
            // Test command execution
            $stream = ssh2_exec($connection, 'uname -a');
            stream_set_blocking($stream, true);
            $output = stream_get_contents($stream);
            
            ssh2_disconnect($connection);

            return [
                'success' => true,
                'message' => 'SSH connection successful',
                'system_info' => trim($output)
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
}

