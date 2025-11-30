<?php


namespace App\Console\Commands;


use Illuminate\Console\Command;
use App\Services\HoldService;


class ReleaseExpiredHolds extends Command
{
    protected $signature = 'holds:release-expired {--chunk=100}';
    protected $description = 'Release expired holds and restore product stock';

    public function __construct(protected HoldService $holdService)
    {
        parent::__construct();
    }


    public function handle()
    {
        $chunk = (int) $this->option('chunk');
        $this->holdService->ReleaseExpiredHolds($chunk);
        return 0;
    }
}
