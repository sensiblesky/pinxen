<?php

namespace Database\Seeders;

use App\Models\MonitoringService;
use Illuminate\Database\Seeder;

class MonitoringServiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $services = [
            // Core Monitoring Services (Must-Have)
            [
                'key' => 'uptime',
                'name' => 'Uptime / HTTP Monitoring',
                'description' => 'Check if a website is online by sending periodic HTTP/HTTPS requests. Monitor status code, response time, keyword presence, and SSL validity.',
                'category' => 'core',
                'icon' => 'ri-global-line',
                'config_schema' => [
                    'url' => ['type' => 'url', 'required' => true, 'label' => 'Website URL'],
                    'expected_status_code' => ['type' => 'number', 'required' => true, 'default' => 200, 'label' => 'Expected Status Code'],
                    'keyword_present' => ['type' => 'text', 'required' => false, 'label' => 'Keyword Must Be Present'],
                    'keyword_absent' => ['type' => 'text', 'required' => false, 'label' => 'Keyword Must Be Absent'],
                    'check_ssl' => ['type' => 'boolean', 'required' => false, 'default' => true, 'label' => 'Check SSL Validity'],
                ],
                'is_active' => true,
                'order' => 1,
            ],
            [
                'key' => 'dns',
                'name' => 'DNS Monitoring',
                'description' => 'Monitor DNS records for unexpected changes. Supports A, AAAA, CNAME, MX, NS, TXT, and SOA records.',
                'category' => 'core',
                'icon' => 'ri-dns-line',
                'config_schema' => [
                    'domain' => ['type' => 'text', 'required' => true, 'label' => 'Domain Name'],
                    'record_type' => ['type' => 'select', 'required' => true, 'options' => ['A', 'AAAA', 'CNAME', 'MX', 'NS', 'TXT', 'SOA'], 'label' => 'Record Type'],
                    'expected_value' => ['type' => 'text', 'required' => false, 'label' => 'Expected Value (optional)'],
                ],
                'is_active' => true,
                'order' => 2,
            ],
            [
                'key' => 'domain_expiration',
                'name' => 'Domain Expiration Monitoring',
                'description' => 'Alerts when a domain is about to expire. Monitor expiration dates and get notified before renewal deadlines.',
                'category' => 'core',
                'icon' => 'ri-calendar-close-line',
                'config_schema' => [
                    'domain' => ['type' => 'text', 'required' => true, 'label' => 'Domain Name'],
                    'alert_days_before' => ['type' => 'number', 'required' => true, 'default' => 30, 'label' => 'Alert Days Before Expiration'],
                ],
                'is_active' => true,
                'order' => 3,
            ],
            [
                'key' => 'whois',
                'name' => 'WHOIS Monitoring',
                'description' => 'Track WHOIS fields for changes: registrar, nameservers, contact email, and expiration date.',
                'category' => 'core',
                'icon' => 'ri-file-search-line',
                'config_schema' => [
                    'domain' => ['type' => 'text', 'required' => true, 'label' => 'Domain Name'],
                    'monitor_fields' => ['type' => 'multiselect', 'required' => false, 'options' => ['registrar', 'nameservers', 'contact_email', 'expiration_date'], 'label' => 'Fields to Monitor'],
                ],
                'is_active' => true,
                'order' => 4,
            ],

            // Performance Monitoring
            [
                'key' => 'page_speed',
                'name' => 'Page Speed Monitoring',
                'description' => 'Use Lighthouse to measure performance score, First Contentful Paint, and Time to Interactive. Useful for SEO and UX.',
                'category' => 'performance',
                'icon' => 'ri-speed-line',
                'config_schema' => [
                    'url' => ['type' => 'url', 'required' => true, 'label' => 'Page URL'],
                    'threshold_score' => ['type' => 'number', 'required' => false, 'default' => 80, 'min' => 0, 'max' => 100, 'label' => 'Minimum Performance Score'],
                ],
                'is_active' => true,
                'order' => 5,
            ],
            [
                'key' => 'ttfb',
                'name' => 'TTFB Monitoring (Time to First Byte)',
                'description' => 'Monitor Time to First Byte. Slow TTFB indicates server or hosting issues.',
                'category' => 'performance',
                'icon' => 'ri-time-line',
                'config_schema' => [
                    'url' => ['type' => 'url', 'required' => true, 'label' => 'Page URL'],
                    'max_ttfb_ms' => ['type' => 'number', 'required' => true, 'default' => 600, 'label' => 'Maximum TTFB (milliseconds)'],
                ],
                'is_active' => true,
                'order' => 6,
            ],
            [
                'key' => 'ssl_certificate',
                'name' => 'SSL/TLS Certificate Monitoring',
                'description' => 'Monitor SSL certificate expiration date, issuer, signature algorithm, and detect improper chain or weak keys.',
                'category' => 'performance',
                'icon' => 'ri-shield-check-line',
                'config_schema' => [
                    'domain' => ['type' => 'text', 'required' => true, 'label' => 'Domain Name'],
                    'port' => ['type' => 'number', 'required' => false, 'default' => 443, 'label' => 'Port'],
                    'alert_days_before' => ['type' => 'number', 'required' => true, 'default' => 30, 'label' => 'Alert Days Before Expiration'],
                ],
                'is_active' => true,
                'order' => 7,
            ],

            // Security Monitoring
            [
                'key' => 'security_headers',
                'name' => 'Security Headers Monitoring',
                'description' => 'Check for security headers: CSP, X-Frame-Options, X-Content-Type-Options, HSTS, and Referrer-Policy.',
                'category' => 'security',
                'icon' => 'ri-shield-line',
                'config_schema' => [
                    'url' => ['type' => 'url', 'required' => true, 'label' => 'Website URL'],
                    'required_headers' => ['type' => 'multiselect', 'required' => false, 'options' => ['CSP', 'X-Frame-Options', 'X-Content-Type-Options', 'HSTS', 'Referrer-Policy'], 'label' => 'Required Headers'],
                ],
                'is_active' => true,
                'order' => 8,
            ],
            [
                'key' => 'port',
                'name' => 'Port Monitoring',
                'description' => 'Scan important ports: 80, 443, custom ports (8080, 3000), SMTP, FTP, SSH.',
                'category' => 'security',
                'icon' => 'ri-router-line',
                'config_schema' => [
                    'host' => ['type' => 'text', 'required' => true, 'label' => 'Host/IP Address'],
                    'ports' => ['type' => 'text', 'required' => true, 'label' => 'Ports (comma-separated)', 'placeholder' => '80,443,8080'],
                ],
                'is_active' => true,
                'order' => 9,
            ],
            [
                'key' => 'blacklist',
                'name' => 'Blacklist / Reputation Check',
                'description' => 'Monitor if domain or IP appears on blacklists: Spamhaus, Google Safe Browsing, VirusTotal.',
                'category' => 'security',
                'icon' => 'ri-alert-line',
                'config_schema' => [
                    'domain_or_ip' => ['type' => 'text', 'required' => true, 'label' => 'Domain or IP Address'],
                    'check_services' => ['type' => 'multiselect', 'required' => false, 'options' => ['spamhaus', 'google_safe_browsing', 'virustotal'], 'label' => 'Services to Check'],
                ],
                'is_active' => true,
                'order' => 10,
            ],
            [
                'key' => 'ssl_pinning',
                'name' => 'SSL Public Key Pinning / Fingerprint Changes',
                'description' => 'Detect MITM attacks or certificate swaps by monitoring SSL certificate fingerprints.',
                'category' => 'security',
                'icon' => 'ri-fingerprint-line',
                'config_schema' => [
                    'domain' => ['type' => 'text', 'required' => true, 'label' => 'Domain Name'],
                    'port' => ['type' => 'number', 'required' => false, 'default' => 443, 'label' => 'Port'],
                ],
                'is_active' => true,
                'order' => 11,
            ],

            // Content Monitoring
            [
                'key' => 'content_change',
                'name' => 'Content Change Monitoring',
                'description' => 'Detect changes in specific page sections: text snippets, meta tags, HTML elements.',
                'category' => 'content',
                'icon' => 'ri-file-edit-line',
                'config_schema' => [
                    'url' => ['type' => 'url', 'required' => true, 'label' => 'Page URL'],
                    'selector' => ['type' => 'text', 'required' => false, 'label' => 'CSS Selector (optional)'],
                    'monitor_type' => ['type' => 'select', 'required' => true, 'options' => ['full_page', 'selector', 'meta_tags'], 'label' => 'Monitor Type'],
                ],
                'is_active' => true,
                'order' => 12,
            ],
            [
                'key' => 'keyword',
                'name' => 'Keyword / Phrase Alerting',
                'description' => 'Notify when a keyword disappears or appears on a page.',
                'category' => 'content',
                'icon' => 'ri-search-line',
                'config_schema' => [
                    'url' => ['type' => 'url', 'required' => true, 'label' => 'Page URL'],
                    'keywords' => ['type' => 'text', 'required' => true, 'label' => 'Keywords (comma-separated)'],
                    'alert_on' => ['type' => 'select', 'required' => true, 'options' => ['appears', 'disappears', 'both'], 'label' => 'Alert When'],
                ],
                'is_active' => true,
                'order' => 13,
            ],
            [
                'key' => 'broken_links',
                'name' => 'Broken Links Monitoring',
                'description' => 'Scan site for internal/external broken links.',
                'category' => 'content',
                'icon' => 'ri-links-line',
                'config_schema' => [
                    'url' => ['type' => 'url', 'required' => true, 'label' => 'Website URL'],
                    'scan_depth' => ['type' => 'number', 'required' => false, 'default' => 3, 'label' => 'Scan Depth'],
                    'check_external' => ['type' => 'boolean', 'required' => false, 'default' => true, 'label' => 'Check External Links'],
                ],
                'is_active' => true,
                'order' => 14,
            ],
            [
                'key' => 'screenshot',
                'name' => 'Screenshot Monitoring',
                'description' => 'Capture daily screenshots to detect visual changes.',
                'category' => 'content',
                'icon' => 'ri-image-line',
                'config_schema' => [
                    'url' => ['type' => 'url', 'required' => true, 'label' => 'Page URL'],
                    'viewport_width' => ['type' => 'number', 'required' => false, 'default' => 1920, 'label' => 'Viewport Width'],
                    'viewport_height' => ['type' => 'number', 'required' => false, 'default' => 1080, 'label' => 'Viewport Height'],
                ],
                'is_active' => true,
                'order' => 15,
            ],

            // Infrastructure Monitoring
            [
                'key' => 'tcp',
                'name' => 'TCP Service Monitoring',
                'description' => 'Check if server responds on a port (like SSH, Redis, MySQL).',
                'category' => 'infrastructure',
                'icon' => 'ri-server-line',
                'config_schema' => [
                    'host' => ['type' => 'text', 'required' => true, 'label' => 'Host/IP Address'],
                    'port' => ['type' => 'number', 'required' => true, 'label' => 'Port'],
                    'service_name' => ['type' => 'text', 'required' => false, 'label' => 'Service Name (optional)'],
                ],
                'is_active' => true,
                'order' => 16,
            ],
            [
                'key' => 'ping',
                'name' => 'Ping / Latency Monitoring',
                'description' => 'Measure latency and packet loss.',
                'category' => 'infrastructure',
                'icon' => 'ri-ping-pong-line',
                'config_schema' => [
                    'host' => ['type' => 'text', 'required' => true, 'label' => 'Host/IP Address'],
                    'max_latency_ms' => ['type' => 'number', 'required' => true, 'default' => 100, 'label' => 'Maximum Latency (ms)'],
                    'max_packet_loss' => ['type' => 'number', 'required' => false, 'default' => 5, 'label' => 'Maximum Packet Loss (%)'],
                ],
                'is_active' => true,
                'order' => 17,
            ],
            [
                'key' => 'geo',
                'name' => 'Geo Monitoring',
                'description' => 'Monitoring from multiple regions: Europe, US, Asia, Africa.',
                'category' => 'infrastructure',
                'icon' => 'ri-global-line',
                'config_schema' => [
                    'url' => ['type' => 'url', 'required' => true, 'label' => 'Website URL'],
                    'regions' => ['type' => 'multiselect', 'required' => true, 'options' => ['us', 'eu', 'asia', 'africa', 'oceania'], 'label' => 'Regions to Monitor'],
                ],
                'is_active' => true,
                'order' => 18,
            ],
            [
                'key' => 'cdn_cache',
                'name' => 'CDN Cache Status Monitoring',
                'description' => 'Check CDN caching status (Cloudflare, etc.).',
                'category' => 'infrastructure',
                'icon' => 'ri-cloud-line',
                'config_schema' => [
                    'url' => ['type' => 'url', 'required' => true, 'label' => 'Website URL'],
                    'cdn_provider' => ['type' => 'select', 'required' => false, 'options' => ['cloudflare', 'aws_cloudfront', 'fastly', 'other'], 'label' => 'CDN Provider'],
                ],
                'is_active' => true,
                'order' => 19,
            ],

            // Email / API Monitoring
            [
                'key' => 'smtp',
                'name' => 'SMTP Server Monitoring',
                'description' => 'Check mail server health: port open, TLS, response time.',
                'category' => 'email_api',
                'icon' => 'ri-mail-send-line',
                'config_schema' => [
                    'host' => ['type' => 'text', 'required' => true, 'label' => 'SMTP Host'],
                    'port' => ['type' => 'number', 'required' => true, 'default' => 587, 'label' => 'Port'],
                    'check_tls' => ['type' => 'boolean', 'required' => false, 'default' => true, 'label' => 'Check TLS'],
                ],
                'is_active' => true,
                'order' => 20,
            ],
            [
                'key' => 'api',
                'name' => 'API Monitoring',
                'description' => 'Monitor API endpoints: status code, response JSON validation, execution time.',
                'category' => 'email_api',
                'icon' => 'ri-code-line',
                'config_schema' => [
                    'url' => ['type' => 'url', 'required' => true, 'label' => 'API Endpoint URL'],
                    'method' => ['type' => 'select', 'required' => true, 'options' => ['GET', 'POST', 'PUT', 'DELETE', 'PATCH'], 'default' => 'GET', 'label' => 'HTTP Method'],
                    'expected_status_code' => ['type' => 'number', 'required' => true, 'default' => 200, 'label' => 'Expected Status Code'],
                    'json_path' => ['type' => 'text', 'required' => false, 'label' => 'JSON Path to Validate (optional)'],
                    'headers' => ['type' => 'textarea', 'required' => false, 'label' => 'Custom Headers (JSON)'],
                    'body' => ['type' => 'textarea', 'required' => false, 'label' => 'Request Body (JSON)'],
                ],
                'is_active' => true,
                'order' => 21,
            ],

            // Premium Features
            [
                'key' => 'malware_scan',
                'name' => 'Malware Scan',
                'description' => 'Daily check using external APIs (e.g., Safe Browsing) to detect malware.',
                'category' => 'premium',
                'icon' => 'ri-virus-line',
                'config_schema' => [
                    'url' => ['type' => 'url', 'required' => true, 'label' => 'Website URL'],
                    'scan_provider' => ['type' => 'select', 'required' => false, 'options' => ['google_safe_browsing', 'virustotal'], 'label' => 'Scan Provider'],
                ],
                'is_active' => true,
                'order' => 22,
            ],
            [
                'key' => 'tech_stack',
                'name' => 'Technology Stack Detection',
                'description' => 'Detect changes in server software, CMS, frameworks, and libraries.',
                'category' => 'premium',
                'icon' => 'ri-stack-line',
                'config_schema' => [
                    'url' => ['type' => 'url', 'required' => true, 'label' => 'Website URL'],
                    'monitor_changes' => ['type' => 'boolean', 'required' => false, 'default' => true, 'label' => 'Alert on Technology Changes'],
                ],
                'is_active' => true,
                'order' => 23,
            ],
            [
                'key' => 'cookies_trackers',
                'name' => 'Cookie & Tracker Monitoring',
                'description' => 'Detect suspicious new cookies or trackers on your website.',
                'category' => 'premium',
                'icon' => 'ri-cookie-line',
                'config_schema' => [
                    'url' => ['type' => 'url', 'required' => true, 'label' => 'Website URL'],
                    'alert_on_new' => ['type' => 'boolean', 'required' => false, 'default' => true, 'label' => 'Alert on New Cookies/Trackers'],
                ],
                'is_active' => true,
                'order' => 24,
            ],
            [
                'key' => 'redirect_chain',
                'name' => 'Redirect Chain Monitoring',
                'description' => 'Detect long or suspicious redirects.',
                'category' => 'premium',
                'icon' => 'ri-arrow-right-circle-line',
                'config_schema' => [
                    'url' => ['type' => 'url', 'required' => true, 'label' => 'Starting URL'],
                    'max_redirects' => ['type' => 'number', 'required' => false, 'default' => 3, 'label' => 'Maximum Allowed Redirects'],
                ],
                'is_active' => true,
                'order' => 25,
            ],
            [
                'key' => 'serp_seo',
                'name' => 'SERP / SEO Monitoring',
                'description' => 'Track indexed pages, meta changes, and robots.txt changes. Good for SEO and security.',
                'category' => 'premium',
                'icon' => 'ri-search-eye-line',
                'config_schema' => [
                    'domain' => ['type' => 'text', 'required' => true, 'label' => 'Domain Name'],
                    'monitor_items' => ['type' => 'multiselect', 'required' => false, 'options' => ['indexed_pages', 'meta_tags', 'robots_txt'], 'label' => 'Items to Monitor'],
                ],
                'is_active' => true,
                'order' => 26,
            ],
        ];

        foreach ($services as $service) {
            MonitoringService::updateOrCreate(
                ['key' => $service['key']],
                $service
            );
        }
    }
}
