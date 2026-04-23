<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Cv;
use App\Models\JobApplication;

class FixLegacyCvCities extends Command
{
    protected $signature = 'cvs:fix-cities';

    protected $description = 'Fill missing city field for legacy CVs';

    public function handle()
    {
        $updated = 0;

        $cvs = Cv::whereNull('city')->get();

        foreach ($cvs as $cv) {

            // 1️⃣ try structured profile first
            $city =
                data_get($cv->structured_profile, 'city') ??
                data_get($cv->structured_profile, 'location.city') ??
                data_get($cv->structured_profile, 'address.city');

            // 2️⃣ fallback: job application
            if (!$city && $cv->source_type === 'application' && $cv->source_id) {

                $application = JobApplication::find($cv->source_id);

                if ($application && $application->city) {
                    $city = $application->city;
                }
            }

            // 3️⃣ fallback: detect inside extracted text
            if (!$city && $cv->encrypted_extracted_text) {

                if (preg_match('/(Casablanca|Rabat|Marrakech|Tanger|Fès|Agadir)/i', $cv->encrypted_extracted_text, $match)) {
                    $city = $match[0];
                }
            }

            if ($city) {
                $cv->update(['city' => $city]);
                $updated++;
            }
        }

        $this->info("Updated {$updated} CV city values.");

        return Command::SUCCESS;
    }
}