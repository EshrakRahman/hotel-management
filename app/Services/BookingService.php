<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Enums\paymentStatus;
use App\Enums\PromotionsDiscountType;
use App\Enums\RoomStatus;
use App\Models\Booking;
use App\Models\BookingGuest;
use App\Models\BookingItem;
use App\Models\BookingService as BookingServiceModel;
use App\Models\Hotel;
use App\Models\Promotion;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\Service;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class BookingService
{
    /**
     * Create a new booking reservation.
     *
     * @throws ValidationException
     */
    public function createBooking(User $user, array $data): Booking
    {
        return DB::transaction(function () use ($user, $data) {
            $hotelId = $data['hotel_id'];
            $hotel = Hotel::findOrFail($hotelId);

            // 1. Perform availability verification & room allocation
            $allocatedRooms = []; // room_type_id => Array of assigned Room models
            $roomSubtotal = 0.00;

            foreach ($data['items'] as $index => $itemData) {
                $roomTypeId = $itemData['room_type_id'];
                $checkIn = $itemData['check_in'];
                $checkOut = $itemData['check_out'];

                $roomType = RoomType::where('hotel_id', $hotelId)->findOrFail($roomTypeId);

                // Find rooms currently booked/held for this date range
                $bookedRoomIds = BookingItem::where('room_type_id', $roomTypeId)
                    ->where('check_in', '<', $checkOut)
                    ->where('check_out', '>', $checkIn)
                    ->whereHas('booking', function ($query) {
                        $query->whereIn('status', [BookingStatus::CONFIRMED, BookingStatus::PENDING])
                            ->where('created_at', '>=', now()->subMinutes(15));
                    })
                    ->whereNotNull('room_id')
                    ->pluck('room_id')
                    ->toArray();

                // Select available rooms and lock them to serialize concurrent checkouts
                $availableRooms = Room::where('room_type_id', $roomTypeId)
                    ->where('status', RoomStatus::AVAILABLE)
                    ->whereNotIn('id', $bookedRoomIds)
                    ->lockForUpdate()
                    ->get();

                // Exclude rooms already allocated to other items in the same request payload
                $unusedRooms = $availableRooms->reject(function ($r) use ($allocatedRooms, $roomTypeId) {
                    return in_array($r->id, array_column($allocatedRooms[$roomTypeId] ?? [], 'id'));
                });

                if ($unusedRooms->isEmpty()) {
                    throw ValidationException::withMessages([
                        "items.{$index}.room_type_id" => "No rooms of type {$roomType->name} are available for the selected dates.",
                    ]);
                }

                $assignedRoom = $unusedRooms->first();
                $allocatedRooms[$roomTypeId][] = $assignedRoom;

                // Calculate nights and subtotal
                $nights = Carbon::parse($checkIn)->diffInDays(Carbon::parse($checkOut));
                $subtotal = $roomType->base_price * $nights;
                $roomSubtotal += $subtotal;
            }

            // 2. Calculate service subtotals
            $serviceSubtotal = 0.00;
            $serviceItems = []; // Array of arrays with details to insert
            if (! empty($data['services'])) {
                foreach ($data['services'] as $index => $serviceData) {
                    $service = Service::where('hotel_id', $hotelId)->where('is_active', true)->findOrFail($serviceData['service_id']);
                    $qty = $serviceData['quantity'];
                    $price = $service->base_price;
                    $sub = $price * $qty;
                    $serviceSubtotal += $sub;

                    $serviceItems[] = [
                        'serviceable_id' => $service->id,
                        'serviceable_type' => Service::class,
                        'price_at_booking' => $price,
                        'quantity' => $qty,
                    ];
                }
            }

            // 3. Calculate promotion discount
            $discountAmount = 0.00;
            $promotionId = $data['promotion_id'] ?? null;
            if ($promotionId) {
                $promotion = Promotion::where('is_active', true)
                    ->where('start_date', '<=', now())
                    ->where('end_date', '>=', now())
                    ->findOrFail($promotionId);

                // Discount applies to rooms subtotal
                if ($promotion->discount_type === PromotionsDiscountType::PERCENTAGE) {
                    $discountAmount = ($roomSubtotal * ($promotion->discount_value / 100));
                } else {
                    $discountAmount = min($promotion->discount_value, $roomSubtotal);
                }
            }

            // 4. Calculate fees and taxes
            // Platform commission
            $commissionRate = 10.00; // default 10%
            if ($hotel->hotelSetting) {
                $commissionRate = $hotel->hotelSetting->platform_commission;
            }
            $netAmount = max(0, ($roomSubtotal + $serviceSubtotal - $discountAmount));
            $platformFee = $netAmount * ($commissionRate / 100);

            // Taxes (e.g. flat 10%)
            $taxAmount = $netAmount * 0.10;

            // Final billing amount
            $totalAmount = $netAmount + $taxAmount + $platformFee;

            // 5. Generate Booking record
            $bookingRef = strtoupper(sprintf('BK-%s-%s', now()->format('Ymd'), Str::random(4)));

            $booking = Booking::create([
                'booking_ref' => $bookingRef,
                'user_id' => $user->id,
                'hotel_id' => $hotelId,
                'promotion_id' => $promotionId,
                'total_amount' => $totalAmount,
                'tax_amount' => $taxAmount,
                'platform_fee' => $platformFee,
                'total_service_amount' => $serviceSubtotal,
                'status' => BookingStatus::PENDING,
                'payment_status' => paymentStatus::PENDING,
                'special_request' => $data['special_requests'] ?? null,
            ]);

            // Save booking items
            $roomAllocationCount = [];
            foreach ($data['items'] as $itemData) {
                $roomTypeId = $itemData['room_type_id'];
                $checkIn = $itemData['check_in'];
                $checkOut = $itemData['check_out'];

                $roomType = RoomType::findOrFail($roomTypeId);
                $nights = Carbon::parse($checkIn)->diffInDays(Carbon::parse($checkOut));

                $allocationIndex = $roomAllocationCount[$roomTypeId] ?? 0;
                $room = $allocatedRooms[$roomTypeId][$allocationIndex];
                $roomAllocationCount[$roomTypeId] = $allocationIndex + 1;

                BookingItem::create([
                    'booking_id' => $booking->id,
                    'room_type_id' => $roomTypeId,
                    'room_id' => $room->id,
                    'check_in' => $checkIn,
                    'check_out' => $checkOut,
                    'price_at_booking' => $roomType->base_price,
                    'nights' => $nights,
                    'subtotal' => $roomType->base_price * $nights,
                ]);
            }

            // Save booking guests
            foreach ($data['guests'] as $guestData) {
                BookingGuest::create([
                    'booking_id' => $booking->id,
                    'name' => $guestData['name'],
                    'email' => $guestData['email'] ?? null,
                    'phone' => $guestData['phone'] ?? null,
                    'is_primary' => $guestData['is_primary'],
                ]);
            }

            // Save booking services
            foreach ($serviceItems as $sItem) {
                BookingServiceModel::create(array_merge(['booking_id' => $booking->id], $sItem));
            }

            // Load relationships before returning
            $booking->load(['bookingItems.roomType', 'bookingItems.room', 'bookingGuests', 'bookingServices.serviceable']);

            // Event placeholder: event(new BookingCreated($booking));

            return $booking;
        });
    }

    /**
     * Cancel an existing booking.
     */
    public function cancelBooking(Booking $booking): Booking
    {
        return DB::transaction(function () use ($booking) {
            $booking->status = BookingStatus::CANCELLED;

            // Calculate penalty using check-in date of the earliest booking item
            $firstItem = $booking->bookingItems()->orderBy('check_in', 'asc')->first();

            if ($firstItem) {
                $checkIn = Carbon::parse($firstItem->check_in);
                $noticeDays = now()->diffInDays($checkIn, false); // false keeps negative if check-in has passed

                $policy = $booking->hotel->cancellationPolicy;
                if ($policy) {
                    // If cancellation happens with fewer days than free cancellation window, apply penalty
                    if ($noticeDays < $policy->free_cancellation_days) {
                        $penalty = $booking->total_amount * ($policy->cancellation_fee / 100);
                        $booking->cancellation_penalty = $penalty;
                    }
                }
            }

            $booking->save();

            // Event placeholder: event(new BookingCancelled($booking));

            return $booking;
        });
    }
}
