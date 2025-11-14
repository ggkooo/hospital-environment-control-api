<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Log;
use App\Jobs\ProcessHourlyAggregation;
use App\Jobs\ProcessSensorData;
use Carbon\Carbon;

class TestProcessing extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sensors:test-processing {--queue : Test queue functionality} {--data : Test with sample data} {--full : Run full system test} {--auto : Test automatic processing system}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Testa o sistema completo de processamento de dados dos sensores';

    protected array $sensorTypes = ['temperature', 'humidity', 'noise', 'pressure', 'eco2', 'tvoc'];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('=== TESTE DO SISTEMA DE PROCESSAMENTO COMPLETO ===');
        $this->newLine();

        // Verifica status geral
        $this->checkSystemStatus();

        if ($this->option('queue')) {
            $this->testQueueFunctionality();
        }

        if ($this->option('data')) {
            $this->testWithSampleData();
        }

        if ($this->option('auto')) {
            $this->testAutomaticProcessing();
        }

        if ($this->option('full')) {
            $this->runFullSystemTest();
        }

        if (!$this->option('queue') && !$this->option('data') && !$this->option('full') && !$this->option('auto')) {
            $this->showBasicStatus();
        }

        return Command::SUCCESS;
    }

    private function checkSystemStatus(): void
    {
        $this->info('1. VERIFICANDO STATUS DO SISTEMA');
        $this->line('─────────────────────────────────────');

        // Verifica conexão com banco
        try {
            DB::connection()->getPdo();
            $this->info('✓ Conexão com banco de dados: OK');
        } catch (\Exception $e) {
            $this->error('✗ Erro na conexão com banco: ' . $e->getMessage());
            return;
        }

        // Verifica se tabelas existem
        $tables = ['s_temperature', 'm_temperature', 'h_temperature', 'jobs'];
        foreach ($tables as $table) {
            if (DB::getSchemaBuilder()->hasTable($table)) {
                $this->info("✓ Tabela {$table}: OK");
            } else {
                $this->error("✗ Tabela {$table}: NÃO EXISTE");
            }
        }

        // Verifica jobs pendentes
        $pendingJobs = DB::table('jobs')->count();
        $failedJobs = DB::table('failed_jobs')->count();

        $this->info("📊 Jobs pendentes: {$pendingJobs}");
        $this->info("❌ Jobs falhados: {$failedJobs}");

        $this->newLine();
    }

    private function testQueueFunctionality(): void
    {
        $this->info('2. TESTANDO FUNCIONALIDADE DAS FILAS AVANÇADA');
        $this->line('─────────────────────────────────────────────────');

        // Verifica se driver de fila está funcionando
        $queueConnection = config('queue.default');
        $this->info("✓ Driver de fila configurado: {$queueConnection}");

        // Verifica workers ativos
        $activeWorkers = $this->countActiveWorkers();
        $this->info("📊 Workers ativos: {$activeWorkers}");

        if ($activeWorkers === 0) {
            $this->warn("⚠️ Nenhum worker ativo detectado!");
            $this->comment("Execute: php artisan sensors:start-auto");
        }

        // Testa job simples
        $this->info("🧪 Testando job de processamento...");

        try {
            // Criar dados de teste
            $testData = [
                'sensor_type' => 'temperature',
                'value' => 25.5,
                'location' => 'TEST',
                'timestamp' => now(),
                'test_mode' => true
            ];

            // Adicionar à fila
            ProcessSensorData::dispatch($testData);
            $this->info("✓ Job de teste adicionado à fila");

            // Verificar se foi processado
            sleep(3);
            $pendingJobs = DB::table('jobs')->count();
            $this->info("📊 Jobs pendentes após teste: {$pendingJobs}");

        } catch (\Exception $e) {
            $this->error("✗ Erro ao testar job: " . $e->getMessage());
        }

        $this->newLine();
    }

    private function testWithSampleData(): void
    {
        $this->info('3. TESTANDO COM DADOS DE EXEMPLO');
        $this->line('─────────────────────────────────────');

        // Cria exatamente 12 registros por minuto (validação rigorosa)
        $testTimestamp = Carbon::now()->subMinutes(5);
        $sampleData = [];

        // Criar exatamente 12 leituras para um minuto completo (00, 05, 10, 15, 20, 25, 30, 35, 40, 45, 50, 55)
        for ($i = 0; $i < 12; $i++) {
            $seconds = $i * 5; // 0, 5, 10, 15, ..., 55
            $sampleData[] = [
                'timestamp' => $testTimestamp->copy()->addSeconds($seconds)->format('Y-m-d H:i:s'),
                'temperature' => rand(200, 250) / 10, // 20.0 - 25.0
                'humidity' => rand(400, 600) / 10,    // 40.0 - 60.0
                'noise' => rand(300, 500) / 10,       // 30.0 - 50.0
                'pression' => rand(9800, 10200) / 10, // 980.0 - 1020.0
                'eco2' => rand(4000, 8000) / 10,      // 400.0 - 800.0
                'tvoc' => rand(1000, 2000) / 10       // 100.0 - 200.0
            ];
        }

        try {
            // Salva dados temporários
            $tempFile = 'temp/test_sensor_data_' . time() . '.json';
            \Storage::put($tempFile, json_encode($sampleData));

            $this->info("Criados exatamente 12 registros (um minuto completo)");
            $this->info("Intervalo: {$testTimestamp->format('H:i:00')} - {$testTimestamp->format('H:i:55')}");
            $this->info("Arquivo temporário: {$tempFile}");

            // Dispara processamento
            ProcessSensorData::dispatch($tempFile, $sampleData);
            $this->info('✓ Job de processamento de dados disparado');

        } catch (\Exception $e) {
            $this->error('✗ Erro ao criar dados de teste: ' . $e->getMessage());
        }

        $this->newLine();
    }

    private function testAutomaticProcessing(): void
    {
        $this->info('3. TESTANDO SISTEMA DE PROCESSAMENTO AUTOMÁTICO');
        $this->line('────────────────────────────────────────────────');

        // Verificar cron jobs
        $this->comment('Verificando cron jobs...');
        $cronJobs = shell_exec("crontab -l 2>/dev/null | grep 'sensors:'");

        if ($cronJobs) {
            $jobCount = substr_count($cronJobs, 'sensors:');
            $this->info("✓ {$jobCount} cron jobs dos sensores encontrados");
        } else {
            $this->warn("⚠️ Nenhum cron job configurado!");
            $this->comment("Execute: php artisan sensors:setup-cron");
        }

        // Verificar comando de verificação de queues
        $queueCheckExists = file_exists(app_path('Console/Commands/QueueCheck.php'));
        $this->info($queueCheckExists ? "✓ Comando queue-check: Disponível" : "✗ Comando queue-check: Ausente");

        // Testar verificação manual de queues
        if ($queueCheckExists) {
            $this->comment('Testando verificação de queues...');
            try {
                $this->call('sensors:queue-check');
                $this->info("✓ Verificação de queues executada com sucesso");
            } catch (\Exception $e) {
                $this->error("✗ Erro na verificação de queues: " . $e->getMessage());
            }
        }

        // Verificar arquivos de configuração
        $configFiles = [
            storage_path('automatic-processing.json') => 'Configuração de processamento',
            storage_path('logs/queue-worker-1.log') => 'Log do worker 1',
            storage_path('logs/queue-worker-2.log') => 'Log do worker 2'
        ];

        foreach ($configFiles as $file => $description) {
            $exists = file_exists($file);
            $status = $exists ? "✓" : "⚠️";
            $this->line("{$status} {$description}: " . ($exists ? "Presente" : "Ausente"));
        }

        $this->newLine();
    }

    private function runFullSystemTest(): void
    {
        $this->info('4. EXECUTANDO TESTE COMPLETO DO SISTEMA');
        $this->line('═══════════════════════════════════════════');

        // 1. Testar funcionalidade básica
        $this->testQueueFunctionality();

        // 2. Testar sistema automático
        $this->testAutomaticProcessing();

        // 3. Testar com dados de exemplo
        $this->testWithSampleData();

        // 4. Testar processamento de agregações
        $this->testAggregationProcessing();

        // 5. Verificar integridade dos dados
        $this->testDataIntegrity();

        // 6. Teste de performance
        $this->testPerformance();

        $this->newLine();
        $this->info('✅ TESTE COMPLETO FINALIZADO');
        $this->comment('Verifique os logs para detalhes adicionais.');
    }

    private function testAggregationProcessing(): void
    {
        $this->comment('Testando processamento de agregações...');

        try {
            // Testar processamento de horas
            $this->comment('Executando processamento de horas...');
            $this->call('sensors:process-hours', ['--lookback' => 1]);

            // Testar processamento de dias
            $this->comment('Executando processamento de dias...');
            $this->call('sensors:process-days', ['--lookback' => 1]);

            $this->info('✓ Processamento de agregações testado');

        } catch (\Exception $e) {
            $this->error("✗ Erro no processamento de agregações: " . $e->getMessage());
        }
    }

    private function testDataIntegrity(): void
    {
        $this->comment('Verificando integridade dos dados...');

        $tables = ['s_temperature', 'm_temperature', 'h_temperature', 'd_temperature'];

        foreach ($tables as $table) {
            try {
                $count = DB::table($table)->count();
                $this->info("✓ Tabela {$table}: {$count} registros");
            } catch (\Exception $e) {
                $this->error("✗ Erro na tabela {$table}: " . $e->getMessage());
            }
        }

        // Verificar consistência temporal
        $this->verifyTimeConsistency();
    }

    private function testPerformance(): void
    {
        $this->comment('Testando performance do sistema...');

        $startTime = microtime(true);

        // Simular carga de trabalho
        $testJobs = 10;
        for ($i = 0; $i < $testJobs; $i++) {
            $testData = [
                'sensor_type' => 'temperature',
                'value' => 20 + rand(0, 10),
                'location' => "TEST_{$i}",
                'timestamp' => now()->subMinutes(rand(1, 60)),
                'test_mode' => true
            ];
            ProcessSensorData::dispatch($testData);
        }

        $endTime = microtime(true);
        $duration = round($endTime - $startTime, 3);

        $this->info("✓ {$testJobs} jobs criados em {$duration}s");

        // Aguardar processamento
        $this->comment("Aguardando processamento...");
        sleep(5);

        $remainingJobs = DB::table('jobs')->count();
        $this->info("📊 Jobs restantes: {$remainingJobs}");
    }

    private function verifyTimeConsistency(): void
    {
        $this->comment('Verificando consistência temporal...');

        // Verificar se há dados futuros (possível erro de timezone)
        $futureData = DB::table('s_temperature')
            ->where('timestamp', '>', now()->addMinute())
            ->count();

        if ($futureData > 0) {
            $this->warn("⚠️ {$futureData} registros com timestamp futuro detectados");
        } else {
            $this->info("✓ Consistência temporal: OK");
        }

        // Verificar gaps nas agregações horárias
        $lastHour = DB::table('h_temperature')
            ->orderBy('hour_timestamp', 'desc')
            ->first();

        if ($lastHour) {
            $hoursSinceLastAggregation = now()->diffInHours($lastHour->hour_timestamp);
            if ($hoursSinceLastAggregation > 2) {
                $this->warn("⚠️ Última agregação horária há {$hoursSinceLastAggregation}h");
            } else {
                $this->info("✓ Agregações horárias: Atualizadas");
            }
        }
    }

    private function countActiveWorkers(): int
    {
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            // Windows - usar tasklist
            $result = shell_exec('tasklist /FI "IMAGENAME eq php.exe" | find /C "queue:work"');
            return (int) trim($result);
        }

        // Linux/Unix
        $result = shell_exec("ps aux | grep 'queue:work' | grep -v grep | wc -l");
        return (int) trim($result);
    }

    private function showLatestRecords(): void
    {
        $this->newLine();
        $this->info('ÚLTIMOS REGISTROS:');

        // Último dado bruto
        $latestRaw = DB::table('s_temperature')->latest('timestamp')->first();
        if ($latestRaw) {
            $this->line("Último dado bruto: {$latestRaw->timestamp} = {$latestRaw->value}°C");
        }

        // Último dado de minuto
        $latestMinute = DB::table('m_temperature')->latest('minute_timestamp')->first();
        if ($latestMinute) {
            $this->line("Último minuto: {$latestMinute->minute_timestamp} = {$latestMinute->avg_value}°C (avg)");
        }

        // Último dado de hora
        $latestHour = DB::table('h_temperature')->latest('hour_timestamp')->first();
        if ($latestHour) {
            $this->line("Última hora: {$latestHour->hour_timestamp} = {$latestHour->avg_value}°C (avg)");
        }
    }

    private function showBasicStatus(): void
    {
        $this->info('STATUS BÁSICO DO SISTEMA');
        $this->line('─────────────────────────');

        // Contadores gerais incluindo dados diários
        foreach ($this->sensorTypes as $sensor) {
            $rawCount = DB::table("s_{$sensor}")->count();
            $minuteCount = DB::table("m_{$sensor}")->count();
            $hourCount = DB::table("h_{$sensor}")->count();
            $dayCount = DB::table("d_{$sensor}")->count();

            $this->line("{$sensor}: {$rawCount} brutos | {$minuteCount} minutos | {$hourCount} horas | {$dayCount} dias");
        }

        $this->newLine();
        $this->comment('Use as opções para testes específicos:');
        $this->comment('--queue   : Testa funcionalidade das filas');
        $this->comment('--data    : Testa com dados de exemplo');
        $this->comment('--full    : Executa teste completo');
        $this->newLine();
        $this->comment('Comandos disponíveis:');
        $this->comment('php artisan sensors:process-hours    : Processa horas pendentes');
        $this->comment('php artisan sensors:process-days     : Processa dias pendentes');
    }
}
