<?php

namespace App\Services\Backup;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use RuntimeException;
use Symfony\Component\Process\Process;

class DatabaseBackup
{
    public const FILE_PATTERN = '/^backup-\d{8}-\d{6}\.sql\.gz$/';

    public function directory(): string
    {
        $dir = config('backup.path');
        File::ensureDirectoryExists($dir);

        return $dir;
    }

    /**
     * Buat backup baru (.sql.gz) lalu pangkas berkas lama. Mengembalikan nama berkas.
     */
    public function run(): string
    {
        $timestamp = now();
        do {
            $name = 'backup-'.$timestamp->format('Ymd-His').'.sql.gz';
            $timestamp->addSecond();
        } while (is_file($this->directory().'/'.$name));
        $path = $this->directory().'/'.$name;
        $sql = tempnam(sys_get_temp_dir(), 'bk');

        try {
            $driver = DB::connection()->getDriverName();
            $binary = in_array($driver, ['mysql', 'mariadb'], true) ? $this->findMysqldump() : null;

            $dumped = false;
            if ($binary && function_exists('proc_open')) {
                try {
                    $this->dumpWithMysqldump($binary, $sql);
                    $dumped = true;
                } catch (\Throwable $dumpEx) {
                    report($dumpEx);
                }
            }

            if (! $dumped) {
                $this->dumpWithPhp($sql);
            }

            $in = fopen($sql, 'rb');
            $out = gzopen($path, 'wb9');
            if (! $in || ! $out) {
                throw new RuntimeException('Gagal menulis berkas backup.');
            }
            while (! feof($in)) {
                gzwrite($out, fread($in, 1048576));
            }
            fclose($in);
            gzclose($out);
        } catch (\Throwable $e) {
            @unlink($path);
            throw $e;
        } finally {
            @unlink($sql);
        }

        @chmod($path, 0640);
        $this->prune();

        return $name;
    }

    /** @return list<array{name:string,size:int,created_at:Carbon}> */
    public function list(): array
    {
        $files = [];
        foreach (File::files($this->directory()) as $file) {
            if (preg_match(self::FILE_PATTERN, $file->getFilename())) {
                $files[] = [
                    'name' => $file->getFilename(),
                    'size' => $file->getSize(),
                    'created_at' => Carbon::createFromTimestamp($file->getMTime()),
                ];
            }
        }
        usort($files, fn ($a, $b) => strcmp($b['name'], $a['name']));

        return $files;
    }

    /** Path absolut berkas valid, atau null (mencegah path traversal). */
    public function resolve(string $name): ?string
    {
        if (! preg_match(self::FILE_PATTERN, $name)) {
            return null;
        }
        $path = $this->directory().'/'.$name;

        return is_file($path) ? $path : null;
    }

    public function prune(): void
    {
        $keep = max(1, (int) config('backup.keep'));
        foreach (array_slice($this->list(), $keep) as $old) {
            @unlink($this->directory().'/'.$old['name']);
        }
    }

    /** Restore a compressed SQL backup. A fresh backup is kept before changing data. */
    public function restore(string $archive): string
    {
        $sql = tempnam(sys_get_temp_dir(), 'restore-');
        if ($sql === false) {
            throw new RuntimeException('Tidak dapat menyiapkan berkas sementara restore.');
        }

        try {
            $input = @gzopen($archive, 'rb');
            $output = fopen($sql, 'wb');
            if (! $input || ! $output) {
                if ($input) {
                    gzclose($input);
                }
                if ($output) {
                    fclose($output);
                }
                throw new RuntimeException('Berkas backup tidak dapat dibaca.');
            }

            $size = 0;
            try {
                while (! gzeof($input)) {
                    $chunk = @gzread($input, 1048576);
                    if ($chunk === false || ($chunk === '' && ! gzeof($input))) {
                        throw new RuntimeException('Berkas backup rusak.');
                    }
                    $size += strlen($chunk);
                    if ($size > (int) config('backup.restore_max_bytes', 536870912)) {
                        throw new RuntimeException('Isi backup melebihi batas restore.');
                    }
                    if ($chunk !== '' && fwrite($output, $chunk) !== strlen($chunk)) {
                        throw new RuntimeException('Gagal menyiapkan berkas restore.');
                    }
                }
            } finally {
                gzclose($input);
                fclose($output);
            }

            $head = file_get_contents($sql, false, null, 0, 4096);
            $isValidSql = $size > 0 && (
                preg_match('/(?:-- Backup Rapor STS|-- (?:MySQL|MariaDB) dump)/i', $head)
                || preg_match('/(?:CREATE TABLE|INSERT INTO|DROP TABLE)/i', $head)
            );
            if (! $isValidSql) {
                throw new RuntimeException('Berkas bukan backup SQL yang didukung.');
            }

            $driver = DB::connection()->getDriverName();
            if ($driver === 'sqlite' && ! str_contains($head, 'Backup Rapor STS') && ! preg_match('/sqlite/i', $head)) {
                throw new RuntimeException('Format backup tidak sesuai dengan database SQLite.');
            }
            if (in_array($driver, ['mysql', 'mariadb'], true) && str_contains($head, '(sqlite)')) {
                throw new RuntimeException('Format backup tidak sesuai dengan database MySQL.');
            }

            $safetyName = $this->run();
            if ($driver === 'sqlite') {
                $pdo = DB::connection()->getPdo();
                $pdo->beginTransaction();
                try {
                    $pdo->exec('PRAGMA defer_foreign_keys = ON');
                    $pdo->exec(file_get_contents($sql));
                    $pdo->commit();
                } catch (\Throwable $e) {
                    $pdo->rollBack();
                    throw $e;
                }
            } elseif (in_array($driver, ['mysql', 'mariadb'], true)) {
                $this->restoreMysql($sql);
            } else {
                throw new RuntimeException('Driver database tidak mendukung restore.');
            }

            return $safetyName;
        } finally {
            @unlink($sql);
        }
    }

    private function restoreMysql(string $sql): void
    {
        $binary = $this->findMysql();
        if ($binary && function_exists('proc_open')) {
            try {
                $this->restoreMysqlWithCli($binary, $sql);
                return;
            } catch (\Throwable $e) {
                report($e);
            }
        }

        $this->restoreMysqlWithPhp($sql);
    }

    private function restoreMysqlWithCli(string $binary, string $sql): void
    {
        $c = config('database.connections.'.config('database.default'));
        $cnf = tempnam(sys_get_temp_dir(), 'cnf');
        if ($cnf === false) {
            throw new RuntimeException('Tidak dapat menyiapkan kredensial database.');
        }
        chmod($cnf, 0600);
        $esc = fn ($v) => '"'.addcslashes((string) $v, '"\\').'"';
        file_put_contents($cnf, "[client]\nuser={$esc($c['username'])}\npassword={$esc($c['password'])}\n");

        try {
            $args = [$binary, '--defaults-extra-file='.$cnf, '--default-character-set=utf8mb4'];
            if (! empty($c['unix_socket'])) {
                $args[] = '--socket='.$c['unix_socket'];
            } else {
                $args[] = '--host='.$c['host'];
                if (! empty($c['port'])) {
                    $args[] = '--port='.$c['port'];
                }
            }
            $args[] = $c['database'];

            $input = fopen($sql, 'rb');
            try {
                $process = new Process($args);
                $process->setInput($input);
                $process->setTimeout(600);
                $process->run();
                if (! $process->isSuccessful()) {
                    throw new RuntimeException('Import SQL gagal: '.trim($process->getErrorOutput()));
                }
            } finally {
                fclose($input);
            }
        } finally {
            @unlink($cnf);
        }
    }

    private function restoreMysqlWithPhp(string $sql): void
    {
        $pdo = DB::connection()->getPdo();
        $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
        $pdo->exec("SET SQL_MODE='NO_AUTO_VALUE_ON_ZERO'");

        $fh = fopen($sql, 'r');
        if (! $fh) {
            throw new RuntimeException('Tidak dapat membuka berkas SQL untuk restore.');
        }

        $query = '';
        while (($line = fgets($fh)) !== false) {
            $trimmed = trim($line);
            if ($trimmed === '' || str_starts_with($trimmed, '--') || str_starts_with($trimmed, '/*')) {
                continue;
            }

            $query .= $line;
            if (str_ends_with($trimmed, ';')) {
                $pdo->exec($query);
                $query = '';
            }
        }

        if (trim($query) !== '') {
            $pdo->exec($query);
        }

        fclose($fh);
        $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
    }

    private function findMysql(): ?string
    {
        $configured = config('backup.mysql_path');
        if ($configured && is_executable($configured)) {
            return $configured;
        }

        $candidates = [
            '/usr/bin/mariadb',
            '/usr/bin/mysql',
            '/usr/local/bin/mariadb',
            '/usr/local/bin/mysql',
            '/usr/local/mysql/bin/mysql',
            'C:\\laragon\\bin\\mysql\\current\\bin\\mysql.exe',
            'C:\\xampp\\mysql\\bin\\mysql.exe',
        ];

        foreach ($candidates as $candidate) {
            if (is_executable($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    private function findMysqldump(): ?string
    {
        $configured = config('backup.mysqldump_path');
        if ($configured && is_executable($configured)) {
            return $configured;
        }

        $candidates = [
            '/usr/bin/mariadb-dump',
            '/usr/bin/mysqldump',
            '/usr/local/bin/mariadb-dump',
            '/usr/local/bin/mysqldump',
            '/usr/local/mysql/bin/mysqldump',
            'C:\\laragon\\bin\\mysql\\current\\bin\\mysqldump.exe',
            'C:\\xampp\\mysql\\bin\\mysqldump.exe',
        ];

        foreach ($candidates as $candidate) {
            if (is_executable($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    private function dumpWithMysqldump(string $binary, string $target): void
    {
        $c = config('database.connections.'.config('database.default'));

        $cnf = tempnam(sys_get_temp_dir(), 'cnf');
        chmod($cnf, 0600);
        $esc = fn ($v) => '"'.addcslashes((string) $v, '"\\').'"';
        file_put_contents($cnf, "[client]\nuser={$esc($c['username'])}\npassword={$esc($c['password'])}\n");

        try {
            $args = [$binary, '--defaults-extra-file='.$cnf, '--single-transaction', '--no-tablespaces', '--default-character-set=utf8mb4', '--result-file='.$target];
            if (! empty($c['unix_socket'])) {
                $args[] = '--socket='.$c['unix_socket'];
            } else {
                $args[] = '--host='.$c['host'];
                if (! empty($c['port'])) {
                    $args[] = '--port='.$c['port'];
                }
            }
            $args[] = $c['database'];

            $process = new Process($args);
            $process->setTimeout(600);
            $process->run();
            if (! $process->isSuccessful()) {
                throw new RuntimeException('mysqldump gagal: '.trim($process->getErrorOutput()));
            }
        } finally {
            @unlink($cnf);
        }
    }

    /** Fallback tanpa mysqldump: skema + data per tabel lewat query. */
    private function dumpWithPhp(string $target): void
    {
        $conn = DB::connection();
        $driver = $conn->getDriverName();
        $pdo = $conn->getPdo();
        $fh = fopen($target, 'wb');
        if (! $fh) {
            throw new RuntimeException('Tidak dapat membuka berkas target backup.');
        }

        fwrite($fh, '-- Backup Rapor STS ('.$driver.') '.now()->toDateTimeString()."\n");
        if ($driver !== 'sqlite') {
            fwrite($fh, "SET FOREIGN_KEY_CHECKS=0;\n");
            fwrite($fh, "SET SQL_MODE='NO_AUTO_VALUE_ON_ZERO';\n");
            fwrite($fh, "SET time_zone = '+00:00';\n\n");
        }

        $tables = $this->getTableNames($conn, $driver);

        foreach ($tables as $table) {
            $q = $conn->getQueryGrammar()->wrapTable($table);

            if ($driver === 'sqlite') {
                $ddl = $conn->selectOne('select sql from sqlite_master where name = ?', [$table])->sql ?? null;
            } else {
                $ddlRow = $conn->selectOne("SHOW CREATE TABLE {$q}");
                $ddl = $ddlRow ? array_values((array) $ddlRow)[1] : null;
            }

            if (! $ddl) {
                continue;
            }

            fwrite($fh, "\nDROP TABLE IF EXISTS {$q};\n{$ddl};\n");

            $batch = [];
            foreach ($conn->table($table)->cursor() as $row) {
                $vals = array_map(function ($v) use ($pdo) {
                    if ($v === null) {
                        return 'NULL';
                    }
                    if (is_bool($v)) {
                        return $v ? '1' : '0';
                    }
                    if (is_int($v) || is_float($v)) {
                        return (string) $v;
                    }

                    return $pdo->quote((string) $v);
                }, (array) $row);

                $batch[] = '('.implode(',', $vals).')';

                if (count($batch) >= 100) {
                    fwrite($fh, "INSERT INTO {$q} VALUES\n".implode(",\n", $batch).";\n");
                    $batch = [];
                }
            }

            if (! empty($batch)) {
                fwrite($fh, "INSERT INTO {$q} VALUES\n".implode(",\n", $batch).";\n");
            }
        }

        if ($driver !== 'sqlite') {
            fwrite($fh, "\nSET FOREIGN_KEY_CHECKS=1;\n");
        }
        fclose($fh);
    }

    private function getTableNames($conn, string $driver): array
    {
        if ($driver === 'sqlite') {
            $rows = $conn->select("SELECT name FROM sqlite_master WHERE type='table' AND name NOT IN ('sqlite_sequence', 'sqlite_stat1')");

            return array_map(fn ($r) => $r->name, $rows);
        }

        // MySQL / MariaDB: Hanya ambil tabel BASE TABLE di database aktif saat ini
        $rows = $conn->select("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'");
        $tables = [];
        foreach ($rows as $row) {
            $tables[] = array_values((array) $row)[0];
        }

        return $tables;
    }
}
