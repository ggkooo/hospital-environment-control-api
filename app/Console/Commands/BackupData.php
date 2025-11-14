<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Carbon\Carbon;

class BackupData extends Command
{
    protected $signature = 'sensors:backup-data {--compress : Compress backup files} {--days=7 : Days to keep backups}';
    protected $description = 'Cria backup dos dados críticos dos sensores';

    public function handle()
    {
        $this->info('📦 INICIANDO BACKUP DOS DADOS DOS SENSORES');
        $this->newLine();

        try {
            $backupDir = storage_path('backups/sensors');
            $this->ensureBackupDirectory($backupDir);

            $timestamp = now()->format('Y-m-d_H-i-s');
            $backupPath = "{$backupDir}/backup_{$timestamp}";

            // Criar backup das tabelas críticas
            $this->backupCriticalTables($backupPath);

            // Backup de configurações
            $this->backupConfigurations($backupPath);

            // Comprimir se solicitado
            if ($this->option('compress')) {
                $this->compressBackup($backupPath);
            }

            // Limpar backups antigos
            $this->cleanOldBackups($backupDir, (int) $this->option('days'));

            $this->info("✅ Backup criado com sucesso: {$backupPath}");
            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error("Erro ao criar backup: " . $e->getMessage());
            return Command::FAILURE;
        }
    }

    private function ensureBackupDirectory(string $path): void
    {
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
            $this->info("✓ Diretório de backup criado: {$path}");
        }
    }

    private function backupCriticalTables(string $backupPath): void
    {
        $this->comment('Fazendo backup das tabelas críticas...');

        $tables = [
            'd_temperature', 'd_humidity', 'd_noise', 'd_pressure', 'd_eco2', 'd_tvoc',
            'h_temperature', 'h_humidity', 'h_noise', 'h_pressure', 'h_eco2', 'h_tvoc'
        ];

        foreach ($tables as $table) {
            try {
                $data = DB::table($table)->get();
                $filename = "{$backupPath}/{$table}.json";

                File::put($filename, $data->toJson(JSON_PRETTY_PRINT));
                $this->info("✓ Backup da tabela {$table}: " . $data->count() . " registros");

            } catch (\Exception $e) {
                $this->warn("⚠️ Erro no backup da tabela {$table}: " . $e->getMessage());
            }
        }
    }

    private function backupConfigurations(string $backupPath): void
    {
        $this->comment('Fazendo backup das configurações...');

        $configs = [
            'automatic-processing.json' => storage_path('automatic-processing.json'),
            'app-config.json' => base_path('config/app.php'),
            'queue-config.json' => base_path('config/queue.php')
        ];

        foreach ($configs as $name => $source) {
            try {
                if (file_exists($source)) {
                    $destination = "{$backupPath}/{$name}";

                    if (str_ends_with($source, '.php')) {
                        // Para arquivos PHP, salvar como JSON
                        $config = include $source;
                        File::put($destination, json_encode($config, JSON_PRETTY_PRINT));
                    } else {
                        copy($source, $destination);
                    }

                    $this->info("✓ Backup de configuração: {$name}");
                }
            } catch (\Exception $e) {
                $this->warn("⚠️ Erro no backup de {$name}: " . $e->getMessage());
            }
        }

        // Salvar informações do sistema
        $systemInfo = [
            'backup_date' => now()->toDateTimeString(),
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'database_driver' => config('database.default'),
            'queue_driver' => config('queue.default'),
            'statistics' => $this->getSystemStatistics()
        ];

        File::put("{$backupPath}/system_info.json", json_encode($systemInfo, JSON_PRETTY_PRINT));
        $this->info("✓ Informações do sistema salvas");
    }

    private function getSystemStatistics(): array
    {
        try {
            $stats = [];

            // Estatísticas das tabelas principais
            $sensorTypes = ['temperature', 'humidity', 'noise', 'pressure', 'eco2', 'tvoc'];

            foreach ($sensorTypes as $sensor) {
                $stats[$sensor] = [
                    'raw_count' => DB::table("s_{$sensor}")->count(),
                    'minute_count' => DB::table("m_{$sensor}")->count(),
                    'hour_count' => DB::table("h_{$sensor}")->count(),
                    'day_count' => DB::table("d_{$sensor}")->count(),
                    'latest_raw' => DB::table("s_{$sensor}")->max('timestamp'),
                    'latest_hour' => DB::table("h_{$sensor}")->max('hour_timestamp'),
                    'latest_day' => DB::table("d_{$sensor}")->max('day_date')
                ];
            }

            // Estatísticas das filas
            $stats['queues'] = [
                'pending_jobs' => DB::table('jobs')->count(),
                'failed_jobs' => DB::table('failed_jobs')->count()
            ];

            return $stats;

        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    private function compressBackup(string $backupPath): void
    {
        $this->comment('Comprimindo backup...');

        try {
            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                // Windows - usar PowerShell Compress-Archive se disponível
                $zipPath = "{$backupPath}.zip";
                $command = "powershell Compress-Archive -Path '{$backupPath}\\*' -DestinationPath '{$zipPath}'";
                shell_exec($command);

                if (file_exists($zipPath)) {
                    $this->removeDirectory($backupPath);
                    $this->info("✓ Backup comprimido: " . basename($zipPath));
                }
            } else {
                // Linux/Unix - usar tar
                $tarPath = "{$backupPath}.tar.gz";
                $command = "tar -czf '{$tarPath}' -C '" . dirname($backupPath) . "' '" . basename($backupPath) . "'";
                shell_exec($command);

                if (file_exists($tarPath)) {
                    $this->removeDirectory($backupPath);
                    $this->info("✓ Backup comprimido: " . basename($tarPath));
                }
            }
        } catch (\Exception $e) {
            $this->warn("⚠️ Erro na compressão: " . $e->getMessage());
        }
    }

    private function cleanOldBackups(string $backupDir, int $daysToKeep): void
    {
        $this->comment("Limpando backups com mais de {$daysToKeep} dias...");

        $cutoffDate = Carbon::now()->subDays($daysToKeep);
        $cleaned = 0;

        $files = glob("{$backupDir}/backup_*");
        foreach ($files as $file) {
            $fileTime = filemtime($file);
            if ($fileTime && Carbon::createFromTimestamp($fileTime)->lt($cutoffDate)) {
                if (is_dir($file)) {
                    $this->removeDirectory($file);
                } else {
                    unlink($file);
                }
                $cleaned++;
            }
        }

        if ($cleaned > 0) {
            $this->info("✓ {$cleaned} backups antigos removidos");
        } else {
            $this->comment("Nenhum backup antigo para remover");
        }
    }

    private function removeDirectory(string $dir): void
    {
        if (is_dir($dir)) {
            $files = array_diff(scandir($dir), ['.', '..']);
            foreach ($files as $file) {
                $filePath = "{$dir}/{$file}";
                if (is_dir($filePath)) {
                    $this->removeDirectory($filePath);
                } else {
                    unlink($filePath);
                }
            }
            rmdir($dir);
        }
    }
}
