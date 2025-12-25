# Anti-Blocking Measures for Website Monitoring

## Overview

To prevent websites from blocking monitoring requests, the system implements several anti-blocking techniques that make requests appear like they're coming from real browsers.

## How It Works

### 1. **Realistic User-Agent Rotation**
- **Problem**: Using a single, obvious monitoring user-agent (like "PingXeno-Monitor/1.0") is easily detected and blocked
- **Solution**: Rotates through 10+ realistic browser user-agents:
  - Chrome on Windows/macOS
  - Firefox on Windows/macOS
  - Safari on macOS
  - Edge on Windows
- **Implementation**: Each request randomly selects a different user-agent

### 2. **Complete Browser Headers**
- **Problem**: Missing or incomplete headers look suspicious
- **Solution**: Sends full set of browser headers:
  - `Accept`: Realistic content types
  - `Accept-Language`: Browser language preferences
  - `Accept-Encoding`: Compression support (gzip, deflate, br)
  - `DNT`: Do Not Track header
  - `Connection`: keep-alive
  - `Upgrade-Insecure-Requests`: Security header
  - `Sec-Fetch-*`: Modern browser security headers
  - `Cache-Control`: Browser caching behavior
  - `Referer`: Points to same domain (looks like internal navigation)

### 3. **Random Request Delays**
- **Problem**: Perfectly timed requests look automated
- **Solution**: Adds random 0-200ms delay before each request
- **Benefit**: Mimics human behavior and prevents rate limiting

### 4. **Proper Redirect Handling**
- **Problem**: Not following redirects properly can trigger security checks
- **Solution**: 
  - Follows up to 5 redirects
  - Maintains referer chain
  - Handles both HTTP and HTTPS redirects
  - Auto-referer enabled

### 5. **Realistic Request Patterns**
- **Problem**: All requests from same IP with same pattern = bot detection
- **Solution**: 
  - Different user-agents per request
  - Varied timing (via queue processing)
  - Proper referer headers
  - Browser-like connection handling

## Current Implementation

The system uses `MonitorHttpService` which:
1. ✅ Rotates user-agents randomly
2. ✅ Sends complete browser headers
3. ✅ Adds random delays (0-200ms)
4. ✅ Handles redirects properly
5. ✅ Uses realistic Accept headers
6. ✅ Includes security headers (Sec-Fetch-*)

## Advanced Anti-Blocking (Future Enhancements)

For even better protection against blocking, consider:

### 1. **IP Rotation / Proxy Support**
```php
// Use proxy servers to rotate IPs
$request->withOptions([
    'proxy' => 'http://proxy-server:port',
]);
```

### 2. **Request Rate Limiting**
- Limit requests per domain/IP
- Add delays between requests to same domain
- Respect rate limits from response headers

### 3. **Cookie Support**
- Store and send cookies like a real browser
- Handle session cookies
- Maintain cookie jar per monitor

### 4. **JavaScript Rendering** (For SPAs)
- Use headless browser (Puppeteer/Playwright) for JavaScript-heavy sites
- Execute JavaScript before checking content
- Handle dynamic content loading

### 5. **Distributed Monitoring**
- Monitor from multiple geographic locations
- Use different IPs per region
- Reduce load per IP address

### 6. **Respect robots.txt**
- Check robots.txt before monitoring
- Respect crawl-delay directives
- Skip disallowed paths

### 7. **CAPTCHA Handling**
- Detect CAPTCHA challenges
- Alert admin instead of failing
- Option to use CAPTCHA solving services

## Testing Anti-Blocking

### Check What Headers Are Sent
```bash
# Monitor network traffic
# Use browser dev tools or tcpdump
tcpdump -i any -A 'host example.com and port 80'
```

### Test User-Agent Rotation
```php
// In tinker
>>> \App\Services\MonitorHttpService::getRandomUserAgent();
>>> \App\Services\MonitorHttpService::getRandomUserAgent(); // Different each time
```

### Verify Headers
```php
// Check what headers are being sent
$headers = \App\Services\MonitorHttpService::getRealisticHeaders('https://example.com');
print_r($headers);
```

## Common Blocking Scenarios

### 1. **Cloudflare Protection**
- **Detection**: Checks for browser-like headers
- **Solution**: Our headers include Sec-Fetch-* which Cloudflare looks for
- **Additional**: May need to handle CAPTCHA challenges

### 2. **Rate Limiting**
- **Detection**: Too many requests from same IP
- **Solution**: Random delays + distributed monitoring
- **Additional**: Respect Retry-After headers

### 3. **User-Agent Blocking**
- **Detection**: Blocks known bot user-agents
- **Solution**: Rotate through real browser user-agents ✅

### 4. **Missing Headers**
- **Detection**: Requests without proper headers
- **Solution**: Send complete browser headers ✅

### 5. **Perfect Timing**
- **Detection**: Requests at exact intervals
- **Solution**: Random delays + queue processing ✅

## Best Practices

1. **Respect Rate Limits**: Don't check too frequently
2. **Use Realistic Intervals**: 1-5 minutes minimum
3. **Monitor from Multiple IPs**: If possible, use proxies
4. **Handle Errors Gracefully**: Don't retry immediately on 429 (Too Many Requests)
5. **Log Blocking Events**: Track when sites block requests
6. **Alert on Blocks**: Notify if monitoring is being blocked

## Configuration

Currently, anti-blocking is **automatic** and **always enabled**. No configuration needed.

Future enhancements could include:
- Enable/disable specific features
- Configure proxy servers
- Set custom user-agent lists
- Adjust delay ranges





