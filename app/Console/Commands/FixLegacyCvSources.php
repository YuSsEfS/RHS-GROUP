<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Cv;
use App\Models\JobApplication;

class FixLegacyCvSources extends Command
{
    protected $signature = 'cvs:fix-sources';

    protected $description = 'Assign source_type and source_id to legacy CVs';

    public function handle()
    {
        $count = 0;

        $cvs = Cv::whereNull('source_type')->get();

        foreach ($cvs as $cv) {

            $application = JobApplication::query()
                ->where(function ($q) use ($cv) {

                    if ($cv->email) {
                        $q->where('email', $cv->email);
                    }

                    if ($cv->candidate_name) {
                        $q->orWhere('full_name', $cv->candidate_name);
                    }

                })
                ->latest('id')
                ->first();

            if ($application) {

                $cv->update([
                    'source_type' => 'application',
                    'source_id' => $application->id,
                ]);

                $count++;
            }
        }

        $this->info("Updated {$count} legacy CV(s).");

        return Command::SUCCESS;
    }
}