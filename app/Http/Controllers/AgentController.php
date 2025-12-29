<?php

namespace App\Http\Controllers;

use App\Models\Server;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AgentController extends Controller
{
    /**
     * Download agent binary for a specific OS/Architecture
     */
    public function download(Request $request, Server $server, string $os, string $arch = 'amd64')
    {
        $user = Auth::user();
        
        // Check authorization (admin can access any server, client can only access their own)
        if ($user->role != 1 && $server->user_id !== $user->id) {
            abort(403, 'Unauthorized access.');
        }

        // Validate OS
        $validOS = ['linux', 'windows', 'darwin', 'freebsd'];
        if (!in_array($os, $validOS)) {
            abort(400, 'Invalid operating system.');
        }

        // Validate architecture
        $validArch = ['amd64', 'arm64'];
        if (!in_array($arch, $validArch)) {
            abort(400, 'Invalid architecture.');
        }

        // Determine file extension
        $ext = $os === 'windows' ? '.exe' : '';
        $filename = "pingxeno-agent-{$os}-{$arch}{$ext}";
        $path = "agents/{$filename}";

        // Check if file exists
        if (!Storage::disk('public')->exists($path)) {
            abort(404, 'Agent binary not found. Please contact support.');
        }

        return Storage::disk('public')->download($path, $filename);
    }

    /**
     * Generate and return installation script
     */
    public function installScript(Request $request, Server $server, string $os, string $arch = 'amd64')
    {
        $user = Auth::user();
        
        // Check authorization (admin can access any server, client can only access their own)
        if ($user->role != 1 && $server->user_id !== $user->id) {
            abort(403, 'Unauthorized access.');
        }

        // Validate OS
        $validOS = ['linux', 'windows', 'darwin', 'freebsd'];
        if (!in_array($os, $validOS)) {
            abort(400, 'Invalid operating system.');
        }

        // Validate architecture
        $validArch = ['amd64', 'arm64'];
        if (!in_array($arch, $validArch)) {
            abort(400, 'Invalid architecture.');
        }

        $apiKey = $server->apiKey;
        $apiUrl = config('app.url') . '/api/v1';
        
        // Use the appropriate route based on current route prefix
        $routeName = request()->is('panel/*') ? 'panel.agents.download' : 'agents.download';
        
        // Generate installation script based on OS (authenticated route, no server key needed)
        $script = $this->generateInstallScript($os, $arch, $server, $apiKey, $apiUrl, $routeName, false);

        $filename = "install-pingxeno-agent-{$os}-{$arch}.sh";
        
        return response($script, 200, [
            'Content-Type' => 'text/plain',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Generate installation script content
     */
    private function generateInstallScript(string $os, string $arch, Server $server, $apiKey, string $apiUrl, string $routeName = 'agents.download', bool $includeServerKey = false): string
    {
        try {
            $ext = $os === 'windows' ? '.exe' : '';
            $binaryName = "pingxeno-agent-{$os}-{$arch}{$ext}";
            
            // Generate download URL
            try {
                $downloadUrl = route($routeName, [
                    'server' => $server->uid,
                    'os' => $os,
                    'arch' => $arch
                ]);
            } catch (\Exception $e) {
                // Fallback: construct URL manually if route doesn't exist
                $baseUrl = config('app.url');
                $downloadUrl = "{$baseUrl}/public/agents/{$server->uid}/download/{$os}/{$arch}";
            }
            
            // Add server key as query parameter for public routes
            if ($includeServerKey) {
                $downloadUrl .= '?key=' . urlencode($server->server_key);
            }
        } catch (\Exception $e) {
            \Log::error('Error generating download URL: ' . $e->getMessage());
            throw $e;
        }

        if ($os === 'linux' || $os === 'freebsd') {
            return $this->generateLinuxScript($os, $arch, $server, $apiKey, $apiUrl, $binaryName, $downloadUrl);
        } elseif ($os === 'darwin') {
            return $this->generateDarwinScript($arch, $server, $apiKey, $apiUrl, $binaryName, $downloadUrl);
        } elseif ($os === 'windows') {
            return $this->generateWindowsScript($arch, $server, $apiKey, $apiUrl, $binaryName, $downloadUrl);
        }

        return '';
    }

    /**
     * Generate Linux/FreeBSD installation script
     */
    private function generateLinuxScript(string $os, string $arch, Server $server, $apiKey, string $apiUrl, string $binaryName, string $downloadUrl): string
    {
        $serviceName = 'pingxeno-agent';
        $installDir = '/usr/local/bin';
        $configDir = '/etc/pingxeno';
        
        return <<<SCRIPT
#!/bin/bash
# PingXeno Agent Installation Script for {$os}/{$arch}
# Server: {$server->name}
# Generated: $(date('Y-m-d H:i:s'))

set -e

echo "Installing PingXeno Agent..."

# Check if running as root
if [ "$EUID" -ne 0 ]; then 
    echo "Please run as root (use sudo)"
    exit 1
fi

# Create config directory
mkdir -p {$configDir}

# Download agent binary
echo "Downloading agent binary..."
curl -L -o /tmp/{$binaryName} "{$downloadUrl}" || wget -O /tmp/{$binaryName} "{$downloadUrl}"

# Make executable
chmod +x /tmp/{$binaryName}

# Move to install directory
mv /tmp/{$binaryName} {$installDir}/pingxeno-agent

# Create configuration file
cat > {$configDir}/agent.yaml <<EOF
api_url: {$apiUrl}
api_key: " . ($apiKey ? $apiKey->key : '') . "
server_key: {$server->server_key}
interval: 60
EOF

# Create systemd service file
cat > /etc/systemd/system/{$serviceName}.service <<EOF
[Unit]
Description=PingXeno Monitoring Agent
After=network.target

[Service]
Type=simple
User=root
ExecStart={$installDir}/pingxeno-agent run --config {$configDir}/agent.yaml
Restart=always
RestartSec=10
StandardOutput=journal
StandardError=journal

[Install]
WantedBy=multi-user.target
EOF

# Reload systemd
systemctl daemon-reload

# Enable and start service
systemctl enable {$serviceName}
systemctl start {$serviceName}

echo "PingXeno Agent installed and started successfully!"
echo "Check status with: systemctl status {$serviceName}"
echo "View logs with: journalctl -u {$serviceName} -f"

SCRIPT;
    }

    /**
     * Generate macOS installation script
     */
    private function generateDarwinScript(string $arch, Server $server, $apiKey, string $apiUrl, string $binaryName, string $downloadUrl): string
    {
        $installDir = '/usr/local/bin';
        $configDir = '/usr/local/etc/pingxeno';
        
        return <<<SCRIPT
#!/bin/bash
# PingXeno Agent Installation Script for macOS/{$arch}
# Server: {$server->name}
# Generated: $(date('Y-m-d H:i:s'))

set -e

echo "Installing PingXeno Agent..."

# Check if running as root
if [ "$EUID" -ne 0 ]; then 
    echo "Please run as root (use sudo)"
    exit 1
fi

# Create config directory
mkdir -p {$configDir}

# Download agent binary
echo "Downloading agent binary..."
curl -L -o /tmp/{$binaryName} "{$downloadUrl}"

# Make executable
chmod +x /tmp/{$binaryName}

# Move to install directory
mv /tmp/{$binaryName} {$installDir}/pingxeno-agent

# Create configuration file
cat > {$configDir}/agent.yaml <<EOF
api_url: {$apiUrl}
api_key: " . ($apiKey ? $apiKey->key : '') . "
server_key: {$server->server_key}
interval: 60
EOF

# Create launchd plist
cat > /Library/LaunchDaemons/com.pingxeno.agent.plist <<EOF
<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE plist PUBLIC "-//Apple//DTD PLIST 1.0//EN" "http://www.apple.com/DTDs/PropertyList-1.0.dtd">
<plist version="1.0">
<dict>
    <key>Label</key>
    <string>com.pingxeno.agent</string>
    <key>ProgramArguments</key>
    <array>
        <string>{$installDir}/pingxeno-agent</string>
        <string>run</string>
        <string>--config</string>
        <string>{$configDir}/agent.yaml</string>
    </array>
    <key>RunAtLoad</key>
    <true/>
    <key>KeepAlive</key>
    <true/>
    <key>StandardOutPath</key>
    <string>/var/log/pingxeno-agent.log</string>
    <key>StandardErrorPath</key>
    <string>/var/log/pingxeno-agent.error.log</string>
</dict>
</plist>
EOF

# Load service
launchctl load -w /Library/LaunchDaemons/com.pingxeno.agent.plist

echo "PingXeno Agent installed and started successfully!"
echo "Check status with: launchctl list | grep pingxeno"
echo "View logs with: tail -f /var/log/pingxeno-agent.log"

SCRIPT;
    }

    /**
     * Generate Windows installation script (PowerShell)
     */
    private function generateWindowsScript(string $arch, Server $server, $apiKey, string $apiUrl, string $binaryName, string $downloadUrl): string
    {
        $installDir = 'C:\\Program Files\\PingXeno';
        $configFile = "$installDir\\agent.yaml";
        $serviceName = 'PingXenoAgent';
        
        // Extract API key value to avoid parsing issues in heredoc
        $apiKeyValue = $apiKey ? $apiKey->key : '';
        $serverKey = $server->server_key;
        $serverName = $server->name;
        $generatedDate = date('Y-m-d H:i:s');
        
        return <<<SCRIPT
# PingXeno Agent Installation Script for Windows/{$arch}
# Server: {$serverName}
# Generated: {$generatedDate}

#Requires -RunAsAdministrator

Write-Host "Installing PingXeno Agent..." -ForegroundColor Green

# Create installation directory
New-Item -ItemType Directory -Force -Path "{$installDir}" | Out-Null

# Download agent binary
Write-Host "Downloading agent binary..." -ForegroundColor Yellow
\$binaryPath = "$installDir\\pingxeno-agent.exe"
Invoke-WebRequest -Uri "{$downloadUrl}" -OutFile \$binaryPath

# Create log directory
\$logDir = "$env:ProgramData\\PingXeno"
New-Item -ItemType Directory -Force -Path \$logDir | Out-Null
\$logFile = "\$logDir\\agent.log"

# Create configuration file with logging enabled
\$apiKeyValue = '{$apiKeyValue}';
\$configContent = @"
server:
  api_url: {$apiUrl}
  api_key: \$apiKeyValue
  server_key: {$serverKey}
collection:
  interval: 60s
  jitter: 5s
logging:
  level: info
  file: \$logFile
"@
Set-Content -Path "{$configFile}" -Value \$configContent

Write-Host "Configuration saved to: {$configFile}" -ForegroundColor Green
Write-Host "Log file: \$logFile" -ForegroundColor Cyan

# Create Windows Service using NSSM (if available) or sc.exe
Write-Host "Creating Windows Service..." -ForegroundColor Yellow

# Try using NSSM if available, otherwise use sc.exe
\$nssmPath = "C:\\nssm\\nssm.exe"
if (Test-Path \$nssmPath) {
    Write-Host "Using NSSM to create service..." -ForegroundColor Green
    & \$nssmPath install {$serviceName} \$binaryPath "gui --config {$configFile}"
    & \$nssmPath set {$serviceName} AppDirectory "{$installDir}"
    & \$nssmPath set {$serviceName} DisplayName "PingXeno Monitoring Agent"
    & \$nssmPath set {$serviceName} Description "PingXeno system monitoring agent"
    & \$nssmPath set {$serviceName} Start SERVICE_AUTO_START
    & \$nssmPath set {$serviceName} AppStdout "\$logDir\\service.log"
    & \$nssmPath set {$serviceName} AppStderr "\$logDir\\service-error.log"
    Start-Service {$serviceName}
    Write-Host "Service started successfully!" -ForegroundColor Green
} else {
    Write-Host "NSSM not found. Creating service with sc.exe..." -ForegroundColor Yellow
    # Use sc.exe to create service (runs in background)
    \$serviceArgs = "gui --config {$configFile}"
    sc.exe create {$serviceName} binPath= "`"`$binaryPath `$serviceArgs`" start= auto DisplayName= "PingXeno Monitoring Agent"
    if (\$LASTEXITCODE -eq 0) {
        Start-Service {$serviceName}
        Write-Host "Service created and started!" -ForegroundColor Green
    } else {
        Write-Host "Failed to create service. You can run the agent manually with:" -ForegroundColor Yellow
        Write-Host "  Start-Process -FilePath `"`$binaryPath`" -ArgumentList `"gui --config {$configFile}`" -WindowStyle Hidden" -ForegroundColor Cyan
    }
}

Write-Host ""
Write-Host "PingXeno Agent installation completed!" -ForegroundColor Green
Write-Host "Service Status: Get-Service {$serviceName}" -ForegroundColor Cyan
Write-Host "View Logs: Get-Content \$logFile -Tail 50 -Wait" -ForegroundColor Cyan
Write-Host "Log file location: \$logFile" -ForegroundColor Cyan

SCRIPT;
    }

    /**
     * Get installation script as one-liner (for curl | bash)
     */
    public function installScriptOneLiner(Request $request, Server $server, string $os, string $arch = 'amd64')
    {
        $user = Auth::user();
        
        // Check authorization (admin can access any server, client can only access their own)
        if ($user->role != 1 && $server->user_id !== $user->id) {
            abort(403, 'Unauthorized access.');
        }

        // Validate OS
        $validOS = ['linux', 'windows', 'darwin', 'freebsd'];
        if (!in_array($os, $validOS)) {
            abort(400, 'Invalid operating system.');
        }

        // Use the appropriate route based on current route prefix
        $routeName = request()->is('panel/*') ? 'panel.agents.install-script' : 'agents.install-script';
        $scriptUrl = route($routeName, [
            'server' => $server->uid,
            'os' => $os,
            'arch' => $arch
        ]);

        // For Windows, return PowerShell command
        if ($os === 'windows') {
            $oneLiner = "powershell -ExecutionPolicy Bypass -Command \"Invoke-WebRequest -Uri '{$scriptUrl}' -OutFile install.ps1; .\\install.ps1\"";
        } else {
            $oneLiner = "curl -sSL {$scriptUrl} | sudo bash";
        }

        return response($oneLiner, 200, [
            'Content-Type' => 'text/plain',
        ]);
    }

    /**
     * Public download endpoint (uses server key for authentication)
     */
    public function downloadPublic(Request $request, Server $server, string $os, string $arch = 'amd64')
    {
        // Verify server key from query parameter
        $serverKey = $request->query('key');
        if (!$serverKey || $serverKey !== $server->server_key) {
            // Return plain text error instead of HTML
            return response('Error: Invalid or missing server key. Please provide the server key as a query parameter: ?key=YOUR_SERVER_KEY', 403, [
                'Content-Type' => 'text/plain; charset=utf-8',
            ]);
        }

        // Validate OS
        $validOS = ['linux', 'windows', 'darwin', 'freebsd'];
        if (!in_array($os, $validOS)) {
            return response('Error: Invalid operating system.', 400, [
                'Content-Type' => 'text/plain; charset=utf-8',
            ]);
        }

        // Validate architecture
        $validArch = ['amd64', 'arm64'];
        if (!in_array($arch, $validArch)) {
            return response('Error: Invalid architecture.', 400, [
                'Content-Type' => 'text/plain; charset=utf-8',
            ]);
        }

        // Determine file extension
        $ext = $os === 'windows' ? '.exe' : '';
        $filename = "pingxeno-agent-{$os}-{$arch}{$ext}";
        $path = "agents/{$filename}";

        // Check if file exists
        if (!Storage::disk('public')->exists($path)) {
            return response('Error: Agent binary not found. Please contact support.', 404, [
                'Content-Type' => 'text/plain; charset=utf-8',
            ]);
        }

        return Storage::disk('public')->download($path, $filename);
    }

    /**
     * Public install script endpoint (uses server key for authentication)
     */
    public function installScriptPublic(Request $request, Server $server, string $os, string $arch = 'amd64')
    {
        try {
            // Verify server key from query parameter
            $serverKey = $request->query('key');
            if (!$serverKey || $serverKey !== $server->server_key) {
                // Return plain text error instead of HTML
                return response('Error: Invalid or missing server key. Please provide the server key as a query parameter: ?key=YOUR_SERVER_KEY', 403, [
                    'Content-Type' => 'text/plain; charset=utf-8',
                ]);
            }

            // Validate OS
            $validOS = ['linux', 'windows', 'darwin', 'freebsd'];
            if (!in_array($os, $validOS)) {
                return response('Error: Invalid operating system.', 400, [
                    'Content-Type' => 'text/plain; charset=utf-8',
                ]);
            }

            // Validate architecture
            $validArch = ['amd64', 'arm64'];
            if (!in_array($arch, $validArch)) {
                return response('Error: Invalid architecture.', 400, [
                    'Content-Type' => 'text/plain; charset=utf-8',
                ]);
            }

            // Check if server has an API key
            $apiKey = $server->apiKey;
            if (!$apiKey) {
                return response('Error: Server does not have an API key associated. Please configure an API key for this server.', 400, [
                    'Content-Type' => 'text/plain; charset=utf-8',
                ]);
            }

            $apiUrl = config('app.url') . '/api/v1';
            
            // Use public download route with server key
            $routeName = 'agents.download.public';
            
            // Generate installation script based on OS (public route, include server key)
            $script = $this->generateInstallScript($os, $arch, $server, $apiKey, $apiUrl, $routeName, true);

            // For Windows, return .ps1 extension
            $ext = $os === 'windows' ? '.ps1' : '.sh';
            $filename = "install-pingxeno-agent-{$os}-{$arch}{$ext}";
            
            // For Windows PowerShell scripts, use proper content type and ensure it's plain text
            $contentType = $os === 'windows' ? 'text/plain; charset=utf-8' : 'text/plain';
            
            // Ensure we return plain text, not HTML
            return response($script, 200, [
                'Content-Type' => $contentType,
                'Content-Disposition' => "inline; filename=\"{$filename}\"",
                'X-Content-Type-Options' => 'nosniff',
            ]);
        } catch (\Exception $e) {
            // Log the error for debugging
            \Log::error('Error generating install script: ' . $e->getMessage(), [
                'server_id' => $server->id,
                'os' => $os,
                'arch' => $arch,
                'trace' => $e->getTraceAsString()
            ]);
            
            // Return plain text error instead of HTML
            return response('Error: Failed to generate installation script. ' . $e->getMessage(), 500, [
                'Content-Type' => 'text/plain; charset=utf-8',
            ]);
        }
    }

    /**
     * Public one-liner endpoint (uses server key for authentication)
     */
    public function installScriptOneLinerPublic(Request $request, Server $server, string $os, string $arch = 'amd64')
    {
        // Verify server key from query parameter
        $serverKey = $request->query('key');
        if (!$serverKey || $serverKey !== $server->server_key) {
            // Return plain text error instead of HTML
            return response('Error: Invalid or missing server key. Please provide the server key as a query parameter: ?key=YOUR_SERVER_KEY', 403, [
                'Content-Type' => 'text/plain; charset=utf-8',
            ]);
        }

        // Validate OS
        $validOS = ['linux', 'windows', 'darwin', 'freebsd'];
        if (!in_array($os, $validOS)) {
            abort(400, 'Invalid operating system.');
        }

        // Use public install script route with server key
        $routeName = 'agents.install-script.public';
        $scriptUrl = route($routeName, [
            'server' => $server->uid,
            'os' => $os,
            'arch' => $arch
        ]) . '?key=' . urlencode($server->server_key);

        // For Windows, return PowerShell command
        if ($os === 'windows') {
            $oneLiner = "powershell -ExecutionPolicy Bypass -Command \"Invoke-WebRequest -Uri '{$scriptUrl}' -OutFile install.ps1; .\\install.ps1\"";
        } else {
            $oneLiner = "curl -sSL '{$scriptUrl}' | sudo bash";
        }

        return response($oneLiner, 200, [
            'Content-Type' => 'text/plain',
        ]);
    }
}

