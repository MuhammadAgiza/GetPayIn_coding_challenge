<?php


namespace App\Console\Commands;


use App\Services\OrderService;
use Illuminate\Console\Command;


class CancelExpiredOrders extends Command
{
    protected $signature = 'orders:cancel-expired {--chunk=100}';
    protected $description = 'Cancel expired Orders and restore product stock';

    public function __construct(protected OrderService $orderService)
    {
        parent::__construct();
    }


    public function handle()
    {
        $chunk = (int) $this->option('chunk');
        $this->orderService->CancelExpiredOrders($chunk);
        return 0;
    }
}
