<?php

namespace App\Services\Backup;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
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
        $name = 'backup-'.now()->format('Ymd-His').'.sql.gz';
        $path = $this->directory().'/'.$name;
        $sql = tempnam(sys_get_temp_dir(), 'bk');

        try {
            $driver = DB::connection()->getDriverName();
            $binary = in_array($driver, ['mysql', 'mariadb'], true) ? $this->findMysqldump() : null;

            if ($binary) {
                $this->dumpWithMysqldump($binary, $sql);
            } else {
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

    private function findMysqldump(): ?string
    {
        $configured = config('backup.mysqldump_path');
        if ($configured) {
            return is_executable($configured) ? $configured : null;
        }
        foreach (['/usr/bin/mysqldump', '/usr/local/bin/mysqldump', '/usr/bin/mariadb-dump'] as $candidate) {
            if (is_executable($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    private function dumpWithMysqldump(string $binary, string $target): void
    {
        $c = config('database.connections.'.config('database.default'));

        // Kredensial lewat berkas sementara agar password tidak tampil di daftar proses.
        $cnf = tempnam(sys_get_temp_dir(), 'cnf');
        chmod($cnf, 0600);
        $esc = fn ($v) => '"'.addcslashes((string) $v, '"\\').'"';
        file_put_contents($cnf, "[client]\nuser={$esc($c['username'])}\npassword={$esc($c['password'])}\n");

        try {
            $args = [$binary, '--defaults-extra-file='.$cnf, '--single-transaction', '--routines', '--no-tablespaces', '--default-character-set=utf8mb4', '--result-file='.$target];
            if (! empty($c['unix_socket'])) {
                $args[] = '--socket='.$c['unix_socket'];
            } else {
                $args[] = '--host='.$c['host'];
                $args[] = '--port='.$c['port'];
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
        fwrite($fh, '-- Backup Rapor STS '.now()->toDateTimeString()."\n");
        if ($driver !== 'sqlite') {
            fwrite($fh, "SET FOREIGN_KEY_CHECKS=0;\n");
        }

        foreach (Schema::getTableListing() as $table) {
            $table = preg_replace('/^.*\./', '', $table);
            if ($table === 'sqlite_sequence') {
                continue;
            }
            $q = $conn->getQueryGrammar()->wrapTable($table);

            if ($driver === 'sqlite') {
                $ddl = $conn->selectOne('select sql from sqlite_master where name = ?', [$table])->sql ?? null;
            } else {
                $ddl = array_values((array) $conn->selectOne("show create table {$q}"))[1] ?? null;
            }
            fwrite($fh, "\nDROP TABLE IF EXISTS {$q};\n{$ddl};\n");

            foreach ($conn->table($table)->cursor() as $row) {
                $vals = array_map(fn ($v) => $v === null ? 'NULL' : $pdo->quote((string) $v), (array) $row);
                fwrite($fh, "INSERT INTO {$q} VALUES (".implode(',', $vals).");\n");
            }
        }

        if ($driver !== 'sqlite') {
            fwrite($fh, "\nSET FOREIGN_KEY_CHECKS=1;\n");
        }
        fclose($fh);
    }
}
