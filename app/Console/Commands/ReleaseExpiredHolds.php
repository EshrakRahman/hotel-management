<?php

namespace App\Console\Commands;

use App\Enums\BookingStatus;
use App\Enums\paymentStatus;
use App\Models\Booking;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ReleaseExpiredHolds extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bookings:release-expired-holds';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Release bookings in pending hold state that have exceeded the payment window';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $expiredTime = now()->subMinutes(15);

        $expiredBookings = Booking::where('status', BookingStatus::PENDING)
            ->where('payment_status', paymentStatus::PENDING)
            ->where('created_at', '<=', $expiredTime)
            ->get();

        if ($expiredBookings->isEmpty()) {
            $this->info('No expired holds to release.');

            return 0;
        }

        $count = 0;

        DB::transaction(function () use ($expiredBookings, &$count) {
            foreach ($expiredBookings as $booking) {
                $booking->update([
                    'status' => BookingStatus::CANCELLED,
                    'payment_status' => paymentStatus::FAILED,
                ]);
                $count++;
            }
        });

        $this->info("Successfully released {$count} expired holds.");

        return 0;
    }
}
