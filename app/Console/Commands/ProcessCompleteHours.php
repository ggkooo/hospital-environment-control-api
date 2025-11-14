<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Jobs\ProcessHourlyAggregation;
use Carbon\Carbon;

class ProcessCompleteHours extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sensors:process-hours {--lookback=24 : Hours to look back for processing}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Processa agregações por hora quando existem 60 minutos completos de dados';

    protected array $sensorTypes = ['temperature', 'humidity', 'noise', 'pressure', 'eco2', 'tvoc'];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $lookbackHours = $this->option('lookback');

        $this->info("Iniciando verificação de horas completas (últimas {$lookbackHours} horas)");

        $processedCount = 0;
        $skippedCount = 0;

        // Verifica as últimas X horas
        $endTime = Carbon::now()->startOfHour();
        $startTime = $endTime->copy()->subHours($lookbackHours);

        $currentHour = $startTime->copy();

        while ($currentHour->lt($endTime)) {
            $hourTimestamp = $currentHour->format('Y-m-d H:00:00');

            if ($this->shouldProcessHour($currentHour)) {
                $this->info("Processando hora: {$hourTimestamp}");

                try {
                    // Dispatch do job para processar esta hora
                    ProcessHourlyAggregation::dispatch($hourTimestamp);
                    $processedCount++;

                    Log::info('Job de agregação por hora disparado', [
                        'hour' => $hourTimestamp
                    ]);

                } catch (\Exception $e) {
                    $this->error("Erro ao processar hora {$hourTimestamp}: " . $e->getMessage());

                    Log::error('Erro ao disparar job de agregação por hora', [
                        'hour' => $hourTimestamp,
                        'error' => $e->getMessage()
                    ]);
                }
            } else {
                $this->comment("Hora {$hourTimestamp} já processada ou sem dados suficientes");
                $skippedCount++;
            }

            $currentHour->addHour();
        }

        $this->info("\nProcessamento concluído:");
        $this->info("- Horas processadas: {$processedCount}");
        $this->info("- Horas ignoradas: {$skippedCount}");

        return Command::SUCCESS;
    }

    private function shouldProcessHour(Carbon $hour): bool
    {
        $hourTimestamp = $hour->format('Y-m-d H:00:00');

        // Verifica se já existe dados agregados para esta hora
        if ($this->hourlyDataExists($hourTimestamp)) {
            return false;
        }

        // VALIDAÇÃO RIGOROSA: Verifica se temos pelo menos 50 minutos de dados
        $minMinutesRequired = 50;

        foreach ($this->sensorTypes as $sensorType) {
            $minuteCount = DB::table("m_{$sensorType}")
                ->where('minute_timestamp', '>=', $hour->format('Y-m-d H:00:00'))
                ->where('minute_timestamp', '<=', $hour->format('Y-m-d H:59:59'))
                ->count();

            if ($minuteCount < $minMinutesRequired) {
                return false;
            }

            // VALIDAÇÃO ADICIONAL: Verificar cobertura temporal
            $firstRecord = DB::table("m_{$sensorType}")
                ->where('minute_timestamp', '>=', $hour->format('Y-m-d H:00:00'))
                ->where('minute_timestamp', '<=', $hour->format('Y-m-d H:59:59'))
                ->orderBy('minute_timestamp')
                ->first();

            $lastRecord = DB::table("m_{$sensorType}")
                ->where('minute_timestamp', '>=', $hour->format('Y-m-d H:00:00'))
                ->where('minute_timestamp', '<=', $hour->format('Y-m-d H:59:59'))
                ->orderBy('minute_timestamp', 'desc')
                ->first();

            if ($firstRecord && $lastRecord) {
                $firstMinute = Carbon::parse($firstRecord->minute_timestamp);
                $lastMinute = Carbon::parse($lastRecord->minute_timestamp);
                $coverageMinutes = $lastMinute->diffInMinutes($firstMinute) + 1;

                // Requer pelo menos 45 minutos de cobertura contínua
                if ($coverageMinutes < 45) {
                    return false;
                }
            }
        }

        return true;
    }

    private function hourlyDataExists(string $hourTimestamp): bool
    {
        foreach ($this->sensorTypes as $sensorType) {
            $exists = DB::table("h_{$sensorType}")
                ->where('hour_timestamp', $hourTimestamp)
                ->exists();

            if ($exists) {
                return true;
            }
        }
        return false;
    }

    private function getMinuteDataSummary(Carbon $hour): array
    {
        $summary = [];
        $nextHour = $hour->copy()->addHour();

        foreach ($this->sensorTypes as $sensorType) {
            $count = DB::table("m_{$sensorType}")
                ->where('minute_timestamp', '>=', $hour->format('Y-m-d H:i:s'))
                ->where('minute_timestamp', '<', $nextHour->format('Y-m-d H:i:s'))
                ->count();

            $summary[$sensorType] = $count;
        }

        return $summary;
    }
}
