# Agent Build and Deployment Instructions

## Building Agents for All Platforms

### Prerequisites
- Go 1.21 or later installed
- Cross-compilation enabled (default in Go)

### Build All Platforms

1. Navigate to the agent directory:
```bash
cd agent
```

2. Run the build script:
```bash
./build-all.sh [VERSION]
```

Example:
```bash
./build-all.sh 1.0.0
```

This will create binaries for:
- Linux (amd64, arm64)
- Windows (amd64, arm64)
- macOS/Darwin (amd64, arm64)
- FreeBSD (amd64, arm64)

### Output Location

Binaries will be created in the `agent/builds/` directory with the following naming:
- `pingxeno-agent-{os}-{arch}` (Linux, macOS, FreeBSD)
- `pingxeno-agent-{os}-{arch}.exe` (Windows)

Archives will also be created:
- `.tar.gz` for Linux, macOS, FreeBSD
- `.zip` for Windows

### Deploying Binaries to Laravel

1. Copy binaries to Laravel's public storage:
```bash
# From agent directory
mkdir -p ../pannel/storage/app/public/agents
cp builds/pingxeno-agent-* ../pannel/storage/app/public/agents/
```

2. Create symbolic link (if not exists):
```bash
cd ../pannel
php artisan storage:link
```

### File Structure

After deployment, your `storage/app/public/agents/` directory should contain:
```
agents/
├── pingxeno-agent-linux-amd64
├── pingxeno-agent-linux-arm64
├── pingxeno-agent-windows-amd64.exe
├── pingxeno-agent-windows-arm64.exe
├── pingxeno-agent-darwin-amd64
├── pingxeno-agent-darwin-arm64
├── pingxeno-agent-freebsd-amd64
└── pingxeno-agent-freebsd-arm64
```

## Installation Methods

The system provides three installation methods:

### 1. Direct Download
Users can download the binary directly and configure it manually.

### 2. Installation Script (One-liner)
Users can copy a one-liner command that automatically:
- Downloads the agent
- Installs it as a service
- Configures it with server key and API key
- Starts the service

### 3. SSH Auto-Installation
For publicly accessible servers, admins can:
- Provide SSH credentials
- System automatically connects and installs
- No manual intervention needed

## Notes

- The SSH installation requires the `php-ssh2` extension
- Install it with: `pecl install ssh2` or via package manager
- For production, consider using a CDN for binary distribution
- Ensure binaries are executable (chmod +x) for Unix-like systems

