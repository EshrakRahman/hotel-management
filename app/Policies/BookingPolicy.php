<?php

namespace App\Policies;

use App\Models\Booking;
use App\Models\User;

class BookingPolicy
{
    /**
     * Determine whether the user can view the booking.
     */
    public function view(User $user, Booking $booking): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        return $booking->user_id === $user->id || $booking->hotel->user_id === $user->id;
    }

    /**
     * Determine whether the user can cancel the booking.
     */
    public function cancel(User $user, Booking $booking): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        return $booking->user_id === $user->id || $booking->hotel->user_id === $user->id;
    }

    /**
     * Determine whether the user can initiate payment for the booking.
     */
    public function pay(User $user, Booking $booking): bool
    {
        return $user->hasRole('admin') || $booking->user_id === $user->id;
    }

    /**
     * Determine whether the user can review the booking.
     */
    public function review(User $user, Booking $booking): bool
    {
        return $booking->user_id === $user->id;
    }
}
