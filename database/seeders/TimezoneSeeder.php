<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TimezoneSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $timezones = [
            ['name' => '(GMT-11:00) Midway Island, Samoa', 'timezone' => 'Pacific/Midway', 'offset' => 'GMT-11:00', 'is_active' => true],
            ['name' => '(GMT-10:00) Hawaii-Aleutian', 'timezone' => 'America/Adak', 'offset' => 'GMT-10:00', 'is_active' => true],
            ['name' => '(GMT-10:00) Hawaii', 'timezone' => 'Etc/GMT+10', 'offset' => 'GMT-10:00', 'is_active' => true],
            ['name' => '(GMT-09:30) Marquesas Islands', 'timezone' => 'Pacific/Marquesas', 'offset' => 'GMT-09:30', 'is_active' => true],
            ['name' => '(GMT-09:00) Gambier Islands', 'timezone' => 'Pacific/Gambier', 'offset' => 'GMT-09:00', 'is_active' => true],
            ['name' => '(GMT-09:00) Alaska', 'timezone' => 'America/Anchorage', 'offset' => 'GMT-09:00', 'is_active' => true],
            ['name' => '(GMT-08:00) Pacific Time (US & Canada)', 'timezone' => 'America/Los_Angeles', 'offset' => 'GMT-08:00', 'is_active' => true],
            ['name' => '(GMT-07:00) Mountain Time (US & Canada)', 'timezone' => 'America/Denver', 'offset' => 'GMT-07:00', 'is_active' => true],
            ['name' => '(GMT-06:00) Central Time (US & Canada)', 'timezone' => 'America/Chicago', 'offset' => 'GMT-06:00', 'is_active' => true],
            ['name' => '(GMT-05:00) Eastern Time (US & Canada)', 'timezone' => 'America/New_York', 'offset' => 'GMT-05:00', 'is_active' => true],
            ['name' => '(GMT) Greenwich Mean Time : London', 'timezone' => 'Europe/London', 'offset' => 'GMT', 'is_active' => true],
            ['name' => '(GMT+01:00) Amsterdam, Berlin, Bern, Rome, Stockholm, Vienna', 'timezone' => 'Europe/Amsterdam', 'offset' => 'GMT+01:00', 'is_active' => true],
            ['name' => '(GMT+02:00) Cairo', 'timezone' => 'Africa/Cairo', 'offset' => 'GMT+02:00', 'is_active' => true],
            ['name' => '(GMT+03:00) Moscow, St. Petersburg, Volgograd', 'timezone' => 'Europe/Moscow', 'offset' => 'GMT+03:00', 'is_active' => true],
            ['name' => '(GMT+05:30) Chennai, Kolkata, Mumbai, New Delhi', 'timezone' => 'Asia/Kolkata', 'offset' => 'GMT+05:30', 'is_active' => true],
            ['name' => '(GMT+08:00) Beijing, Chongqing, Hong Kong, Urumqi', 'timezone' => 'Asia/Hong_Kong', 'offset' => 'GMT+08:00', 'is_active' => true],
            ['name' => '(GMT+09:00) Osaka, Sapporo, Tokyo', 'timezone' => 'Asia/Tokyo', 'offset' => 'GMT+09:00', 'is_active' => true],
            ['name' => '(GMT+09:00) Seoul', 'timezone' => 'Asia/Seoul', 'offset' => 'GMT+09:00', 'is_active' => true],
            ['name' => '(GMT+12:00) Auckland, Wellington', 'timezone' => 'Pacific/Auckland', 'offset' => 'GMT+12:00', 'is_active' => true],
        ];

        foreach ($timezones as $timezone) {
            \App\Models\Timezone::create($timezone);
        }
    }
}
