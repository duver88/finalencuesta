<?php

namespace App\Console\Commands;

use App\Models\SurveyToken;
use Illuminate\Console\Command;

class ReleaseExpiredTokenReservations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tokens:release-expired';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Liberar tokens con reservas expiradas (más de 5 minutos)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Liberando tokens con reservas expiradas...');

        $count = SurveyToken::releaseExpiredReservations();

        if ($count > 0) {
            $this->info("✓ Se liberaron {$count} token(s) con reservas expiradas.");
        } else {
            $this->info('No se encontraron tokens con reservas expiradas.');
        }

        return Command::SUCCESS;
    }
}
