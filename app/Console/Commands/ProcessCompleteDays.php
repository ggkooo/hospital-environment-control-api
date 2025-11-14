<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Jobs\ProcessDailyAggregation;
use Carbon\Carbon;

class ProcessCompleteDays extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sensors:process-days {--lookback=7 : Days to look back for processing}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Processa agregações diárias quando existem 24 horas completas de dados';

    protected array $sensorTypes = ['temperature', 'humidity', 'noise', 'pressure', 'eco2', 'tvoc'];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $lookbackDays = $this->option('lookback');

        $this->info("Iniciando verificação de dias completos (últimos {$lookbackDays} dias)");

        $processedCount = 0;
        $skippedCount = 0;

        // Verifica os últimos X dias
        $endDate = Carbon::now()->startOfDay();
        $startDate = $endDate->copy()->subDays($lookbackDays);

        $currentDay = $startDate->copy();

        while ($currentDay->lt($endDate)) {
            $dayDate = $currentDay->format('Y-m-d');

            if ($this->shouldProcessDay($currentDay)) {
                $this->info("Processando dia: {$dayDate}");

                try {
                    // Dispatch do job para processar este dia
                    ProcessDailyAggregation::dispatch($dayDate);
                    $processedCount++;

                    Log::info('Job de agregação diária disparado', [
                        'day' => $dayDate
                    ]);

                } catch (\Exception $e) {
                    $this->error("Erro ao processar dia {$dayDate}: " . $e->getMessage());

                    Log::error('Erro ao disparar job de agregação diária', [
                        'day' => $dayDate,
                        'error' => $e->getMessage()
                    ]);
                }
            } else {
                $this->comment("Dia {$dayDate} já processado ou sem dados suficientes");
                $skippedCount++;
            }

            $currentDay->addDay();
        }

        $this->info("\nProcessamento concluído:");
        $this->info("- Dias processados: {$processedCount}");
        $this->info("- Dias ignorados: {$skippedCount}");

        return Command::SUCCESS;
    }

    private function shouldProcessDay(Carbon $day): bool
    {
        $dayDate = $day->format('Y-m-d');

        // Verifica se já existe dados agregados para este dia
        if ($this->dailyDataExists($dayDate)) {
            return false;
        }

        // VALIDAÇÃO RIGOROSA: Verifica se temos pelo menos 20 horas de dados
        $minHoursRequired = 20;

        foreach ($this->sensorTypes as $sensorType) {
            $hourCount = DB::table("h_{$sensorType}")
                ->where('hour_timestamp', '>=', $day->format('Y-m-d 00:00:00'))
                ->where('hour_timestamp', '<=', $day->format('Y-m-d 23:00:00'))
                ->count();

            if ($hourCount < $minHoursRequired) {
                return false;
            }

            // VALIDAÇÃO ADICIONAL: Verificar cobertura temporal
            $firstRecord = DB::table("h_{$sensorType}")
                ->where('hour_timestamp', '>=', $day->format('Y-m-d 00:00:00'))
                ->where('hour_timestamp', '<=', $day->format('Y-m-d 23:00:00'))
                ->orderBy('hour_timestamp')
                ->first();

            $lastRecord = DB::table("h_{$sensorType}")
                ->where('hour_timestamp', '>=', $day->format('Y-m-d 00:00:00'))
                ->where('hour_timestamp', '<=', $day->format('Y-m-d 23:00:00'))
                ->orderBy('hour_timestamp', 'desc')
                ->first();

            if ($firstRecord && $lastRecord) {
                $firstHour = Carbon::parse($firstRecord->hour_timestamp);
                $lastHour = Carbon::parse($lastRecord->hour_timestamp);
                $coverageHours = $lastHour->diffInHours($firstHour) + 1;

                // Requer pelo menos 18 horas de cobertura contínua
                if ($coverageHours < 18) {
                    return false;
                }
            }
        }

        return true;
    }

    private function dailyDataExists(string $dayDate): bool
    {
        foreach ($this->sensorTypes as $sensorType) {
            $exists = DB::table("d_{$sensorType}")
                ->where('day_date', $dayDate)
                ->exists();

            if ($exists) {
                return true;
            }
        }
        return false;
    }
}
