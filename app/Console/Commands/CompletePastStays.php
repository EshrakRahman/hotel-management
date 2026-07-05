<?php

namespace App\Console\Commands;

use App\Enums\BookingStatus;
use App\Models\Booking;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CompletePastStays extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bookings:complete-past-stays';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Transition confirmed bookings with past checkout dates to completed state';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $today = now()->toDateString();

        $completedBookings = Booking::where('status', BookingStatus::CONFIRMED)
            ->whereDoesntHave('bookingItems', function ($query) use ($today) {
                $query->where('check_out', '>=', $today);
            })
            ->get();

        if ($completedBookings->isEmpty()) {
            $this->info('No bookings to complete.');

            return 0;
        }

        $count = 0;

        DB::transaction(function () use ($completedBookings, &$count) {
            foreach ($completedBookings as $booking) {
                $booking->update([
                    'status' => BookingStatus::COMPLETED,
                ]);
                $count++;
            }
        });

        $this->info("Successfully completed {$count} past stay bookings.");

        return 0;
    }
}
