<?php

namespace Database\Seeders;

use App\Models\PlanFeature;
use App\Models\SubscriptionPlan;
use Illuminate\Database\Seeder;

class SubscriptionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create Plan Features
        $features = [
            [
                'name' => 'Web Monitoring',
                'slug' => 'web-monitoring',
                'description' => 'Monitor website uptime and performance',
                'icon' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 256 256"><rect width="256" height="256" fill="none"/><path d="M128,24A104,104,0,1,0,232,128,104.11,104.11,0,0,0,128,24Zm0,192a88,88,0,1,1,88-88A88.1,88.1,0,0,1,128,216Z"/></svg>',
                'order' => 1,
                'is_active' => true,
            ],
            [
                'name' => 'SSH Monitoring',
                'slug' => 'ssh-monitoring',
                'description' => 'Monitor SSH server connectivity and availability',
                'icon' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 256 256"><rect width="256" height="256" fill="none"/><path d="M128,24A104,104,0,1,0,232,128,104.11,104.11,0,0,0,128,24Zm0,192a88,88,0,1,1,88-88A88.1,88.1,0,0,1,128,216Z"/></svg>',
                'order' => 2,
                'is_active' => true,
            ],
            [
                'name' => 'FTP Monitoring',
                'slug' => 'ftp-monitoring',
                'description' => 'Monitor FTP server availability and response time',
                'icon' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 256 256"><rect width="256" height="256" fill="none"/><path d="M128,24A104,104,0,1,0,232,128,104.11,104.11,0,0,0,128,24Zm0,192a88,88,0,1,1,88-88A88.1,88.1,0,0,1,128,216Z"/></svg>',
                'order' => 3,
                'is_active' => true,
            ],
            [
                'name' => 'DNS Monitoring',
                'slug' => 'dns-monitoring',
                'description' => 'Monitor DNS resolution and response times',
                'icon' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 256 256"><rect width="256" height="256" fill="none"/><path d="M128,24A104,104,0,1,0,232,128,104.11,104.11,0,0,0,128,24Zm0,192a88,88,0,1,1,88-88A88.1,88.1,0,0,1,128,216Z"/></svg>',
                'order' => 4,
                'is_active' => true,
            ],
            [
                'name' => 'Uptime Monitoring',
                'slug' => 'uptime-monitoring',
                'description' => 'Track service uptime and availability metrics',
                'icon' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 256 256"><rect width="256" height="256" fill="none"/><path d="M128,24A104,104,0,1,0,232,128,104.11,104.11,0,0,0,128,24Zm0,192a88,88,0,1,1,88-88A88.1,88.1,0,0,1,128,216Z"/></svg>',
                'order' => 5,
                'is_active' => true,
            ],
            [
                'name' => 'Server Monitoring',
                'slug' => 'server-monitoring',
                'description' => 'Monitor server resources (CPU, RAM, Disk)',
                'icon' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 256 256"><rect width="256" height="256" fill="none"/><path d="M128,24A104,104,0,1,0,232,128,104.11,104.11,0,0,0,128,24Zm0,192a88,88,0,1,1,88-88A88.1,88.1,0,0,1,128,216Z"/></svg>',
                'order' => 6,
                'is_active' => true,
            ],
            [
                'name' => 'Email Notifications',
                'slug' => 'email-notifications',
                'description' => 'Receive email alerts for service outages',
                'icon' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 256 256"><rect width="256" height="256" fill="none"/><path d="M128,24A104,104,0,1,0,232,128,104.11,104.11,0,0,0,128,24Zm0,192a88,88,0,1,1,88-88A88.1,88.1,0,0,1,128,216Z"/></svg>',
                'order' => 7,
                'is_active' => true,
            ],
            [
                'name' => 'SMS Notifications',
                'slug' => 'sms-notifications',
                'description' => 'Receive SMS alerts for critical issues',
                'icon' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 256 256"><rect width="256" height="256" fill="none"/><path d="M128,24A104,104,0,1,0,232,128,104.11,104.11,0,0,0,128,24Zm0,192a88,88,0,1,1,88-88A88.1,88.1,0,0,1,128,216Z"/></svg>',
                'order' => 8,
                'is_active' => true,
            ],
            [
                'name' => 'API Access',
                'slug' => 'api-access',
                'description' => 'Access monitoring data via REST API',
                'icon' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 256 256"><rect width="256" height="256" fill="none"/><path d="M128,24A104,104,0,1,0,232,128,104.11,104.11,0,0,0,128,24Zm0,192a88,88,0,1,1,88-88A88.1,88.1,0,0,1,128,216Z"/></svg>',
                'order' => 9,
                'is_active' => true,
            ],
            [
                'name' => 'Advanced Analytics',
                'slug' => 'advanced-analytics',
                'description' => 'Detailed performance reports and analytics',
                'icon' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 256 256"><rect width="256" height="256" fill="none"/><path d="M128,24A104,104,0,1,0,232,128,104.11,104.11,0,0,0,128,24Zm0,192a88,88,0,1,1,88-88A88.1,88.1,0,0,1,128,216Z"/></svg>',
                'order' => 10,
                'is_active' => true,
            ],
        ];

        $createdFeatures = [];
        foreach ($features as $feature) {
            $createdFeatures[$feature['slug']] = PlanFeature::create($feature);
        }

        // Create Subscription Plans
        $basicPlan = SubscriptionPlan::create([
            'name' => 'Basic',
            'slug' => 'basic',
            'description' => 'Perfect for solo users who need essential monitoring features to get started efficiently.',
            'price_monthly' => 9.99,
            'price_yearly' => 99.99,
            'icon' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 256 256"><rect width="256" height="256" fill="none"/><path d="M230.93,220a8,8,0,0,1-6.93,4H32a8,8,0,0,1-6.92-12c15.23-26.33,38.7-45.21,66.09-54.16a72,72,0,1,1,73.66,0c27.39,8.95,50.86,27.83,66.09,54.16A8,8,0,0,1,230.93,220Z"/></svg>',
            'color' => 'primary',
            'is_recommended' => false,
            'order' => 1,
            'is_active' => true,
        ]);

        // Attach features to Basic plan
        $basicPlan->features()->attach([
            $createdFeatures['web-monitoring']->id => ['limit' => 5, 'limit_type' => 'count', 'value' => '5 Web Monitoring checks'],
            $createdFeatures['uptime-monitoring']->id => ['limit' => null, 'limit_type' => null, 'value' => 'Uptime Monitoring'],
            $createdFeatures['email-notifications']->id => ['limit' => null, 'limit_type' => null, 'value' => 'Email Notifications'],
        ]);

        $proPlan = SubscriptionPlan::create([
            'name' => 'Pro',
            'slug' => 'pro',
            'description' => 'Designed for small teams to monitor multiple services and boost productivity.',
            'price_monthly' => 29.99,
            'price_yearly' => 299.99,
            'icon' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 256 256"><rect width="256" height="256" fill="none"/><path d="M64.12,147.8a4,4,0,0,1-4,4.2H16a8,8,0,0,1-7.8-6.17,8.35,8.35,0,0,1,1.62-6.93A67.79,67.79,0,0,1,37,117.51a40,40,0,1,1,66.46-35.8,3.94,3.94,0,0,1-2.27,4.18A64.08,64.08,0,0,0,64,144C64,145.28,64,146.54,64.12,147.8Zm182-8.91A67.76,67.76,0,0,0,219,117.51a40,40,0,1,0-66.46-35.8,3.94,3.94,0,0,0,2.27,4.18A64.08,64.08,0,0,1,192,144c0,1.28,0,2.54-.12,3.8a4,4,0,0,0,4,4.2H240a8,8,0,0,0,7.8-6.17A8.33,8.33,0,0,0,246.17,138.89Zm-89,43.18a48,48,0,1,0-58.37,0A72.13,72.13,0,0,0,65.07,212,8,8,0,0,0,72,224H184a8,8,0,0,0,6.93-12A72.15,72.15,0,0,0,157.19,182.07Z"/></svg>',
            'color' => 'success',
            'is_recommended' => true,
            'order' => 2,
            'is_active' => true,
        ]);

        // Attach features to Pro plan
        $proPlan->features()->attach([
            $createdFeatures['web-monitoring']->id => ['limit' => 25, 'limit_type' => 'count', 'value' => '25 Web Monitoring checks'],
            $createdFeatures['ssh-monitoring']->id => ['limit' => 10, 'limit_type' => 'count', 'value' => '10 SSH Monitoring checks'],
            $createdFeatures['ftp-monitoring']->id => ['limit' => 10, 'limit_type' => 'count', 'value' => '10 FTP Monitoring checks'],
            $createdFeatures['dns-monitoring']->id => ['limit' => 15, 'limit_type' => 'count', 'value' => '15 DNS Monitoring checks'],
            $createdFeatures['uptime-monitoring']->id => ['limit' => null, 'limit_type' => null, 'value' => 'Uptime Monitoring'],
            $createdFeatures['server-monitoring']->id => ['limit' => 5, 'limit_type' => 'count', 'value' => '5 Server Monitoring checks'],
            $createdFeatures['email-notifications']->id => ['limit' => null, 'limit_type' => null, 'value' => 'Email Notifications'],
            $createdFeatures['sms-notifications']->id => ['limit' => null, 'limit_type' => null, 'value' => 'SMS Notifications'],
            $createdFeatures['api-access']->id => ['limit' => null, 'limit_type' => null, 'value' => 'API Access'],
        ]);

        $enterprisePlan = SubscriptionPlan::create([
            'name' => 'Enterprise',
            'slug' => 'enterprise',
            'description' => 'A comprehensive solution for large businesses with advanced tools and scalability.',
            'price_monthly' => 99.99,
            'price_yearly' => 999.99,
            'icon' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 256 256"><rect width="256" height="256" fill="none"/><path d="M239.73,208H224V96a16,16,0,0,0-16-16H164a4,4,0,0,0-4,4V208H144V32.41a16.43,16.43,0,0,0-6.16-13,16,16,0,0,0-18.72-.69L39.12,72A16,16,0,0,0,32,85.34V208H16.27A8.18,8.18,0,0,0,8,215.47,8,8,0,0,0,16,224H240a8,8,0,0,0,8-8.53A8.18,8.18,0,0,0,239.73,208ZM76,184a8,8,0,0,1-8.53,8A8.18,8.18,0,0,1,60,183.72V168.27A8.19,8.19,0,0,1,67.47,160,8,8,0,0,1,76,168Zm0-56a8,8,0,0,1-8.53,8A8.19,8.19,0,0,1,60,127.72V112.27A8.19,8.19,0,0,1,67.47,104,8,8,0,0,1,76,112Zm40,56a8,8,0,0,1-8.53,8,8.18,8.18,0,0,1-7.47-8.26V168.27a8.19,8.19,0,0,1,7.47-8.26,8,8,0,0,1,8.53,8Zm0-56a8,8,0,0,1-8.53,8,8.19,8.19,0,0,1-7.47-8.26V112.27a8.19,8.19,0,0,1,7.47-8.26,8,8,0,0,1,8.53,8Z"/></svg>',
            'color' => 'warning',
            'is_recommended' => false,
            'order' => 3,
            'is_active' => true,
        ]);

        // Attach all features to Enterprise plan (unlimited)
        $enterprisePlan->features()->attach([
            $createdFeatures['web-monitoring']->id => ['limit' => null, 'limit_type' => null, 'value' => 'Unlimited Web Monitoring'],
            $createdFeatures['ssh-monitoring']->id => ['limit' => null, 'limit_type' => null, 'value' => 'Unlimited SSH Monitoring'],
            $createdFeatures['ftp-monitoring']->id => ['limit' => null, 'limit_type' => null, 'value' => 'Unlimited FTP Monitoring'],
            $createdFeatures['dns-monitoring']->id => ['limit' => null, 'limit_type' => null, 'value' => 'Unlimited DNS Monitoring'],
            $createdFeatures['uptime-monitoring']->id => ['limit' => null, 'limit_type' => null, 'value' => 'Uptime Monitoring'],
            $createdFeatures['server-monitoring']->id => ['limit' => null, 'limit_type' => null, 'value' => 'Unlimited Server Monitoring'],
            $createdFeatures['email-notifications']->id => ['limit' => null, 'limit_type' => null, 'value' => 'Email Notifications'],
            $createdFeatures['sms-notifications']->id => ['limit' => null, 'limit_type' => null, 'value' => 'SMS Notifications'],
            $createdFeatures['api-access']->id => ['limit' => null, 'limit_type' => null, 'value' => 'API Access'],
            $createdFeatures['advanced-analytics']->id => ['limit' => null, 'limit_type' => null, 'value' => 'Advanced Analytics'],
        ]);
    }
}







