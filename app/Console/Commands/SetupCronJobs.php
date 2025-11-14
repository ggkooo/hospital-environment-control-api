<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class SetupCronJobs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sensors:setup-cron {--remove : Remove cron jobs} {--show : Show current cron configuration}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Configura cron jobs automáticos para processamento de horas e dias';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if ($this->option('remove')) {
            return $this->removeCronJobs();
        }

        if ($this->option('show')) {
            return $this->showCronJobs();
        }

        return $this->setupCronJobs();
    }

    private function setupCronJobs(): int
    {
        $this->info('⏰ CONFIGURANDO CRON JOBS AUTOMÁTICOS COM GERENCIAMENTO DE QUEUES');
        $this->newLine();

        try {
            $projectPath = base_path();
            $logPath = '/var/log';

            // Verificar se está no Windows e ajustar paths
            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                $logPath = $projectPath . '/storage/logs';
            }

            // Cron jobs a serem configurados
            $cronJobs = [
                // Verificar e iniciar workers das queues a cada 5 minutos
                "*/5 * * * * cd {$projectPath} && php artisan sensors:queue-check >> {$logPath}/queue-check.log 2>&1",

                // Processar horas pendentes a cada 15 minutos
                "*/15 * * * * cd {$projectPath} && php artisan sensors:process-hours --lookback=2 >> {$logPath}/sensors-hourly.log 2>&1",

                // Processar dias pendentes a cada 2 horas
                "0 */2 * * * cd {$projectPath} && php artisan sensors:process-days --lookback=3 >> {$logPath}/sensors-daily.log 2>&1",

                // Verificar status geral e reiniciar sistema se necessário (a cada hora)
                "0 * * * * cd {$projectPath} && php artisan sensors:start-auto --status >> {$logPath}/sensors-status.log 2>&1",

                // Restart completo do sistema diariamente às 02:00
                "0 2 * * * cd {$projectPath} && php artisan sensors:start-auto --stop && sleep 30 && php artisan sensors:start-auto >> {$logPath}/sensors-restart.log 2>&1",

                // Limpeza de arquivos temp antigos (diariamente às 03:00)
                "0 3 * * * find {$projectPath}/storage/app/temp -name '*.json' -mtime +1 -delete 2>/dev/null",

                // Limpeza de logs antigos (semanalmente no domingo às 04:00)
                "0 4 * * 0 find {$logPath} -name '*.log' -mtime +7 -exec gzip {} \\; 2>/dev/null",

                // Backup semanal de dados críticos (domingo às 05:00)
                "0 5 * * 0 cd {$projectPath} && php artisan sensors:backup-data >> {$logPath}/backup.log 2>&1"
            ];

            $this->createCronFile($cronJobs);
            $this->installCronJobs();
            $this->createQueueCheckCommand();
            $this->showInstalledJobs();

            $this->info('✅ Cron jobs com gerenciamento de queues configurados com sucesso!');
            $this->newLine();
            $this->comment('Logs dos cron jobs:');
            $this->comment("- Queue Check: {$logPath}/queue-check.log");
            $this->comment("- Horas: {$logPath}/sensors-hourly.log");
            $this->comment("- Dias: {$logPath}/sensors-daily.log");
            $this->comment("- Status: {$logPath}/sensors-status.log");
            $this->comment("- Restart: {$logPath}/sensors-restart.log");

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error("Erro ao configurar cron jobs: " . $e->getMessage());
            return Command::FAILURE;
        }
    }

    private function removeCronJobs(): int
    {
        $this->warn('🗑️  REMOVENDO CRON JOBS AUTOMÁTICOS');
        $this->newLine();

        try {
            // Remove cron jobs relacionados aos sensores
            $output = shell_exec("crontab -l 2>/dev/null | grep -v 'sensors:' | crontab -");

            $this->info('✅ Cron jobs dos sensores removidos com sucesso!');
            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error("Erro ao remover cron jobs: " . $e->getMessage());
            return Command::FAILURE;
        }
    }

    private function showCronJobs(): int
    {
        $this->info('📋 CRON JOBS ATUAIS RELACIONADOS AOS SENSORES');
        $this->line('═══════════════════════════════════════════════');

        $cronOutput = shell_exec("crontab -l 2>/dev/null | grep -E '(sensors:|artisan)'");

        if ($cronOutput) {
            $this->info('Cron jobs encontrados:');
            $this->newLine();

            $lines = explode("\n", trim($cronOutput));
            foreach ($lines as $line) {
                if (trim($line)) {
                    $this->line($line);
                }
            }
        } else {
            $this->comment('Nenhum cron job relacionado aos sensores encontrado.');
        }

        $this->newLine();
        $this->comment('Para ver todos os cron jobs: crontab -l');

        return Command::SUCCESS;
    }

    private function createCronFile(array $cronJobs): void
    {
        $this->comment('Criando arquivo de cron jobs...');

        $cronFile = storage_path('app/sensors-cron.txt');
        $cronContent = implode("\n", $cronJobs) . "\n";

        File::put($cronFile, $cronContent);
        $this->info("✓ Arquivo criado: {$cronFile}");
    }

    private function installCronJobs(): void
    {
        $this->comment('Instalando cron jobs...');

        $cronFile = storage_path('app/sensors-cron.txt');

        // Backup do crontab atual
        shell_exec("crontab -l 2>/dev/null > " . storage_path('app/crontab-backup.txt'));

        // Remove cron jobs existentes dos sensores e adiciona os novos
        $commands = [
            "crontab -l 2>/dev/null | grep -v 'sensors:' > " . storage_path('app/temp-cron.txt'),
            "cat {$cronFile} >> " . storage_path('app/temp-cron.txt'),
            "crontab " . storage_path('app/temp-cron.txt'),
            "rm " . storage_path('app/temp-cron.txt')
        ];

        foreach ($commands as $cmd) {
            shell_exec($cmd);
        }

        $this->info('✓ Cron jobs instalados no sistema');
    }

    private function showInstalledJobs(): void
    {
        $this->newLine();
        $this->info('📋 CRON JOBS CONFIGURADOS COM QUEUES:');
        $this->line('─────────────────────────────────────────');

        $schedule = [
            'A cada 5 minutos → Verificar e manter workers das queues ativos',
            'A cada 15 minutos → Processar horas pendentes',
            'A cada 2 horas → Processar dias pendentes',
            'A cada 1 hora → Verificar status geral do sistema',
            'Diariamente às 02:00 → Restart completo do sistema',
            'Diariamente às 03:00 → Limpeza de arquivos temporários',
            'Semanalmente às 04:00 → Compactação de logs antigos',
            'Semanalmente às 05:00 → Backup de dados críticos'
        ];

        foreach ($schedule as $job) {
            $this->line("• {$job}");
        }

        $this->newLine();
        $this->comment('COMANDOS ÚTEIS:');
        $this->comment('sensors:setup-cron --show     : Ver cron jobs atuais');
        $this->comment('sensors:setup-cron --remove   : Remover cron jobs');
        $this->comment('sensors:start-auto --status   : Status do sistema');
        $this->comment('sensors:test-processing --full: Teste completo');
        $this->comment('sensors:queue-check           : Verificar queues manualmente');
    }

    private function createQueueCheckCommand(): void
    {
        $this->comment('Criando comando de verificação de queues...');

        $commandContent = $this->getQueueCheckCommandContent();
        $commandPath = app_path('Console/Commands/QueueCheck.php');

        if (!file_exists($commandPath)) {
            file_put_contents($commandPath, $commandContent);
            $this->info('✓ Comando sensors:queue-check criado');
        } else {
            $this->comment('✓ Comando sensors:queue-check já existe');
        }
    }

    private function getQueueCheckCommandContent(): string
    {
        return '<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class QueueCheck extends Command
{
    protected $signature = \'sensors:queue-check {--restart : Force restart of workers}\';
    protected $description = \'Verifica e mantém workers das queues ativos\';

    public function handle()
    {
        $this->checkAndMaintainWorkers();
        return Command::SUCCESS;
    }

    private function checkAndMaintainWorkers(): void
    {
        $activeWorkers = $this->countActiveWorkers();
        $requiredWorkers = 2;
        $pendingJobs = DB::table(\'jobs\')->count();

        // Log do status atual
        Log::info("Queue Check", [
            \'active_workers\' => $activeWorkers,
            \'pending_jobs\' => $pendingJobs,
            \'timestamp\' => now()->toDateTimeString()
        ]);

        // Se há jobs pendentes mas nenhum worker ativo
        if ($pendingJobs > 0 && $activeWorkers === 0) {
            $this->startWorkers();
            return;
        }

        // Se há poucos workers para muitos jobs
        if ($pendingJobs > 50 && $activeWorkers < $requiredWorkers) {
            $this->startAdditionalWorkers($requiredWorkers - $activeWorkers);
            return;
        }

        // Se forçar restart
        if ($this->option(\'restart\')) {
            $this->restartWorkers();
            return;
        }

        // Verificar se workers estão respondendo
        if ($activeWorkers > 0 && !$this->areWorkersResponsive()) {
            $this->restartWorkers();
        }
    }

    private function countActiveWorkers(): int
    {
        if (strtoupper(substr(PHP_OS, 0, 3)) === \'WIN\') {
            // Windows - usar tasklist
            $result = shell_exec(\'tasklist /FI "IMAGENAME eq php.exe" /FI "WINDOWTITLE eq *queue:work*" 2>nul | find /C "php.exe"\');
            return (int) trim($result);
        }

        // Linux/Unix
        $result = shell_exec("ps aux | grep \'queue:work\' | grep -v grep | wc -l");
        return (int) trim($result);
    }

    private function startWorkers(): void
    {
        Log::info("Iniciando workers das queues");

        if (strtoupper(substr(PHP_OS, 0, 3)) === \'WIN\') {
            // Windows
            $cmd1 = \'start /B php artisan queue:work --daemon --tries=3 --timeout=300 --sleep=3 --memory=512\';
            $cmd2 = \'start /B php artisan queue:work --daemon --tries=3 --timeout=300 --sleep=5 --memory=512\';
        } else {
            // Linux/Unix
            $cmd1 = \'nohup php artisan queue:work --daemon --tries=3 --timeout=300 --sleep=3 --memory=512 > \' .
                    storage_path(\'logs/queue-worker-1.log\') . \' 2>&1 &\';
            $cmd2 = \'nohup php artisan queue:work --daemon --tries=3 --timeout=300 --sleep=5 --memory=512 > \' .
                    storage_path(\'logs/queue-worker-2.log\') . \' 2>&1 &\';
        }

        shell_exec($cmd1);
        sleep(1);
        shell_exec($cmd2);

        Log::info("Workers iniciados");
    }

    private function startAdditionalWorkers(int $count): void
    {
        Log::info("Iniciando {$count} workers adicionais");

        for ($i = 0; $i < $count; $i++) {
            if (strtoupper(substr(PHP_OS, 0, 3)) === \'WIN\') {
                shell_exec(\'start /B php artisan queue:work --daemon --tries=3 --timeout=300 --sleep=3 --memory=512\');
            } else {
                shell_exec(\'nohup php artisan queue:work --daemon --tries=3 --timeout=300 --sleep=3 --memory=512 > /dev/null 2>&1 &\');
            }
            sleep(1);
        }
    }

    private function restartWorkers(): void
    {
        Log::info("Reiniciando todos os workers");

        // Parar workers existentes
        if (strtoupper(substr(PHP_OS, 0, 3)) === \'WIN\') {
            shell_exec(\'taskkill /F /IM php.exe /FI "WINDOWTITLE eq *queue:work*" 2>nul\');
        } else {
            shell_exec(\'pkill -f "queue:work"\');
        }

        // Restart das filas
        shell_exec(\'php artisan queue:restart\');
        sleep(3);

        // Iniciar novos workers
        $this->startWorkers();
    }

    private function areWorkersResponsive(): bool
    {
        // Criar um job de teste simples
        $testJobCount = DB::table(\'jobs\')->where(\'payload\', \'like\', \'%TestJob%\')->count();

        // Se há muitos jobs de teste não processados, workers podem estar travados
        return $testJobCount < 10;
    }
}
';
    }
}

