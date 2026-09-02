<?php

namespace App\Console\Commands;

use App\Support\PublicStorage;
use Illuminate\Console\Command;

class MirrorPublicStorageCommand extends Command
{
    protected $signature = 'storage:mirror-public {--tenants : Mirror only tenants/ folder}';

    protected $description = 'Copy storage/app/public files into public/storage (fixes logo/images when symlink fails)';

    public function handle(): int
    {
        $dir = $this->option('tenants') ? 'tenants' : '';

        $count = PublicStorage::mirrorDirectory($dir);

        $this->info("Mirrored {$count} file(s) to public/storage/.");

        return self::SUCCESS;
    }
}
