<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class StartAutomaticProcessing extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sensors:start-auto {--stop : Stop all automatic processing} {--status : Show status of automatic processing}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    private array $processes = [];
    private string $logPath;

    public function __construct()
    {
        parent::__construct();
        $this->logPath = storage_path('logs/automatic-processing.log');
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if ($this->option('stop')) {
            return $this->stopAutomaticProcessing();
        }

        if ($this->option('status')) {
            return $this->showStatus();
        }

        return $this->startAutomaticProcessing();
    }

    private function startAutomaticProcessing(): int
    {
        $this->info('🚀 INICIANDO PROCESSAMENTO AUTOMÁTICO DOS SENSORES');
        $this->newLine();

        try {
            // 1. Parar processos existentes primeiro
            $this->stopExistingProcesses();

            // 2. Verificar sistema
            $this->checkSystemRequirements();

            // 3. Iniciar workers das filas
            $this->startQueueWorkers();

            // 4. Iniciar processamento de horas
            $this->startHourlyProcessing();

            // 5. Iniciar processamento de dias
            $this->startDailyProcessing();

            // 6. Salvar PIDs para controle
            $this->savePids();

            // 7. Mostrar status final
            $this->showStartupStatus();

            $this->info('✅ Processamento automático iniciado com sucesso!');
            $this->comment("Logs em: {$this->logPath}");

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error("Erro ao iniciar processamento automático: " . $e->getMessage());
            return Command::FAILURE;
        }
    }

    private function stopAutomaticProcessing(): int
    {
        $this->warn('🛑 PARANDO PROCESSAMENTO AUTOMÁTICO DOS SENSORES');
        $this->newLine();

        try {
            $this->stopExistingProcesses();
            $this->info('✅ Processamento automático parado com sucesso!');
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Erro ao parar processamento automático: " . $e->getMessage());
            return Command::FAILURE;
        }
    }

    private function showStatus(): int
    {
        $this->info('📊 STATUS DO PROCESSAMENTO AUTOMÁTICO');
        $this->line('═══════════════════════════════════════');

        // Verificar workers das filas
        $queueWorkers = $this->countProcesses('queue:work');
        $this->info("Workers das filas: {$queueWorkers} ativos");

        // Verificar jobs pendentes
        $pendingJobs = DB::table('jobs')->count();
        $failedJobs = DB::table('failed_jobs')->count();
        $this->info("Jobs pendentes: {$pendingJobs}");
        $this->info("Jobs falhados: {$failedJobs}");

        // Verificar dados agregados
        $this->showAggregationStatus();

        // Verificar últimos logs
        $this->showRecentLogs();

        return Command::SUCCESS;
    }

    private function stopExistingProcesses(): void
    {
        $this->comment('Parando processos existentes...');

        // Parar workers das filas
        $queueWorkers = $this->countProcesses('queue:work');
        if ($queueWorkers > 0) {
            $this->info("Parando {$queueWorkers} workers das filas...");

            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                // Windows - usar taskkill
                $this->executeCommand('taskkill /F /IM php.exe /FI "WINDOWTITLE eq *queue:work*" 2>nul');
            } else {
                // Linux/Unix - usar pkill
                $this->executeCommand('pkill -f "queue:work"');
            }
            sleep(2);
        }

        // Restart das filas para limpar workers em memória
        $this->executeCommand('php artisan queue:restart');

        $this->info('✓ Processos existentes parados');
    }

    private function checkSystemRequirements(): void
    {
        $this->comment('Verificando requisitos do sistema...');

        // Verificar conexão com banco
        try {
            DB::connection()->getPdo();
            $this->info('✓ Conexão com banco de dados: OK');
        } catch (\Exception $e) {
            throw new \Exception('Erro na conexão com banco de dados: ' . $e->getMessage());
        }

        // Verificar tabelas essenciais
        $requiredTables = ['jobs', 's_temperature', 'm_temperature', 'h_temperature', 'd_temperature'];
        foreach ($requiredTables as $table) {
            if (!DB::getSchemaBuilder()->hasTable($table)) {
                throw new \Exception("Tabela essencial não encontrada: {$table}");
            }
        }

        // Verificar diretórios de storage
        $requiredDirs = [storage_path('app/temp'), storage_path('app/errors'), storage_path('logs')];
        foreach ($requiredDirs as $dir) {
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
                $this->info("✓ Diretório criado: {$dir}");
            }
        }

        $this->info('✓ Requisitos do sistema verificados');
    }

    private function startQueueWorkers(): void
    {
        $this->comment('Iniciando workers das filas...');

        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            // Windows - usar start /B para background
            $cmd1 = 'start /B php artisan queue:work --daemon --tries=3 --timeout=300 --sleep=3 --memory=512';
            $cmd2 = 'start /B php artisan queue:work --daemon --tries=3 --timeout=300 --sleep=5 --memory=512';

            $this->executeCommand($cmd1);
            sleep(1);
            $this->executeCommand($cmd2);
        } else {
            // Linux/Unix - usar nohup
            $cmd1 = 'nohup php artisan queue:work --daemon --tries=3 --timeout=300 --sleep=3 --memory=512 > ' .
                    storage_path('logs/queue-worker-1.log') . ' 2>&1 &';
            $cmd2 = 'nohup php artisan queue:work --daemon --tries=3 --timeout=300 --sleep=5 --memory=512 > ' .
                    storage_path('logs/queue-worker-2.log') . ' 2>&1 &';

            $this->executeCommand($cmd1);
            sleep(1);
            $this->executeCommand($cmd2);
        }

        sleep(2);

        $activeWorkers = $this->countProcesses('queue:work');
        $this->info("✓ {$activeWorkers} workers das filas iniciados");

        if ($activeWorkers === 0) {
            $this->warn("⚠️ Nenhum worker foi iniciado! Verifique os logs.");
            $this->comment("Tente executar manualmente: php artisan queue:work");
        }
    }

    private function startHourlyProcessing(): void
    {
        $this->comment('Configurando processamento de horas...');

        // Processar horas pendentes imediatamente
        $this->executeCommand('php artisan sensors:process-hours --lookback=24 > ' .
                              storage_path('logs/hourly-initial.log') . ' 2>&1 &');

        $this->info('✓ Processamento de horas configurado');
    }

    private function startDailyProcessing(): void
    {
        $this->comment('Configurando processamento de dias...');

        // Processar dias pendentes imediatamente
        $this->executeCommand('php artisan sensors:process-days --lookback=7 > ' .
                              storage_path('logs/daily-initial.log') . ' 2>&1 &');

        $this->info('✓ Processamento de dias configurado');
    }

    private function savePids(): void
    {
        $pids = [
            'queue_workers' => $this->getProcessPids('queue:work'),
            'started_at' => now()->toDateTimeString(),
            'config' => [
                'workers' => 2,
                'hourly_processing' => true,
                'daily_processing' => true
            ]
        ];

        file_put_contents(storage_path('automatic-processing.json'), json_encode($pids, JSON_PRETTY_PRINT));
        $this->info('✓ Configuração salva');
    }

    private function showStartupStatus(): void
    {
        $this->newLine();
        $this->info('📋 STATUS ATUAL:');
        $this->line('──────────────────────');

        $queueWorkers = $this->countProcesses('queue:work');
        $this->line("Workers ativos: {$queueWorkers}");

        $pendingJobs = DB::table('jobs')->count();
        $this->line("Jobs na fila: {$pendingJobs}");

        $this->newLine();
        $this->comment('COMANDOS ÚTEIS:');
        $this->comment('sensors:start-auto --status    : Ver status atual');
        $this->comment('sensors:start-auto --stop      : Parar processamento');
        $this->comment('sensors:test-processing --full : Teste completo');

        $this->newLine();
        $this->comment('LOGS:');
        $this->comment('tail -f ' . storage_path('logs/queue-worker-1.log'));
        $this->comment('tail -f ' . storage_path('logs/laravel.log'));
    }

    private function showAggregationStatus(): void
    {
        $this->newLine();
        $this->comment('Status das agregações (últimas 24h):');

        $now = Carbon::now();
        $yesterday = $now->copy()->subDay();

        // Verificar horas processadas hoje
        $hoursToday = DB::table('h_temperature')
            ->where('hour_timestamp', '>=', $now->startOfDay())
            ->count();
        $this->line("Horas processadas hoje: {$hoursToday}/24");

        // Verificar dias processados (última semana)
        $daysWeek = DB::table('d_temperature')
            ->where('day_date', '>=', $now->copy()->subDays(7)->format('Y-m-d'))
            ->count();
        $this->line("Dias processados (7 dias): {$daysWeek}/7");
    }

    private function showRecentLogs(): void
    {
        $this->newLine();
        $this->comment('Últimos logs (últimas 10 linhas):');

        if (file_exists($this->logPath)) {
            $logs = array_slice(file($this->logPath), -10);
            foreach ($logs as $log) {
                $this->line(trim($log));
            }
        } else {
            $this->line('Nenhum log encontrado ainda.');
        }
    }

    private function countProcesses(string $pattern): int
    {
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            // Windows - usar tasklist
            $result = shell_exec("tasklist /FI \"IMAGENAME eq php.exe\" | find /C \"queue:work\"");
            return (int) trim($result);
        }

        // Linux/Unix - usar ps
        $result = shell_exec("ps aux | grep '{$pattern}' | grep -v grep | wc -l");
        return (int) trim($result);
    }

    private function getProcessPids(string $pattern): array
    {
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            // Windows - extrair PIDs do tasklist
            $result = shell_exec("tasklist /FI \"IMAGENAME eq php.exe\" /FO CSV | find \"queue:work\"");
            if ($result) {
                $lines = explode("\n", trim($result));
                $pids = [];
                foreach ($lines as $line) {
                    if (preg_match('/\"(\d+)\"/', $line, $matches)) {
                        $pids[] = (int) $matches[1];
                    }
                }
                return $pids;
            }
            return [];
        }

        // Linux/Unix
        $result = shell_exec("ps aux | grep '{$pattern}' | grep -v grep | awk '{print \$2}'");
        return $result ? array_map('intval', array_filter(explode("\n", trim($result)))) : [];
    }

    private function executeCommand(string $command): void
    {
        $output = shell_exec($command);
        Log::info("Comando executado: {$command}", ['output' => $output]);
    }
}
