<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
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

            $pricingData = $this->calculatePricing(
                $hotel,
                $data['items'],
                $data['services'] ?? [],
                $data['promotion_id'] ?? null,
                true // lock rooms for update
            );

            // Create Booking record
            $bookingRef = strtoupper(sprintf('BK-%s-%s', now()->format('Ymd'), Str::random(4)));

            $booking = Booking::create([
                'booking_ref' => $bookingRef,
                'user_id' => $user->id,
                'hotel_id' => $hotelId,
                'promotion_id' => $pricingData['promotion'] ? $pricingData['promotion']->id : null,
                'total_amount' => $pricingData['pricing']['total_amount'],
                'tax_amount' => $pricingData['pricing']['tax_amount'],
                'platform_fee' => $pricingData['pricing']['platform_fee'],
                'total_service_amount' => $pricingData['pricing']['service_subtotal'],
                'status' => BookingStatus::PENDING,
                'payment_status' => PaymentStatus::PENDING,
                'special_request' => $data['special_requests'] ?? null,
            ]);

            // Save booking items
            foreach ($pricingData['items'] as $item) {
                BookingItem::create([
                    'booking_id' => $booking->id,
                    'room_type_id' => $item['room_type_id'],
                    'room_id' => $item['assigned_room']->id,
                    'check_in' => $item['check_in'],
                    'check_out' => $item['check_out'],
                    'price_at_booking' => $item['price_per_night'],
                    'nights' => $item['nights'],
                    'subtotal' => $item['subtotal'],
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
            foreach ($pricingData['services'] as $sItem) {
                BookingServiceModel::create([
                    'booking_id' => $booking->id,
                    'serviceable_id' => $sItem['service_id'],
                    'serviceable_type' => Service::class,
                    'price_at_booking' => $sItem['price'],
                    'quantity' => $sItem['quantity'],
                ]);
            }

            // Load relationships before returning
            $booking->load(['bookingItems.roomType', 'bookingItems.room', 'bookingGuests', 'bookingServices.serviceable']);

            return $booking;
        });
    }

    /**
     * Generate a price quote for a potential booking.
     *
     * @throws ValidationException
     */
    public function getQuote(array $data): array
    {
        $hotel = Hotel::findOrFail($data['hotel_id']);

        $pricingData = $this->calculatePricing(
            $hotel,
            $data['items'],
            $data['services'] ?? [],
            $data['promotion_id'] ?? null,
            false // do not lock rooms for update
        );

        return [
            'pricing' => $pricingData['pricing'],
            'items' => array_map(function ($item) {
                unset($item['assigned_room']);

                return $item;
            }, $pricingData['items']),
            'services' => $pricingData['services'],
        ];
    }

    /**
     * Calculate room subtotals, service subtotals, discounts, taxes, fees, and verify availability.
     *
     * @throws ValidationException
     */
    protected function calculatePricing(Hotel $hotel, array $items, array $services = [], ?int $promotionId = null, bool $lockRooms = false): array
    {
        $allocatedRooms = []; // room_type_id => Array of assigned Room models
        $roomSubtotal = 0.00;
        $roomDetails = [];

        foreach ($items as $index => $itemData) {
            $roomTypeId = $itemData['room_type_id'];
            $checkIn = $itemData['check_in'];
            $checkOut = $itemData['check_out'];

            $roomType = RoomType::where('hotel_id', $hotel->id)->findOrFail($roomTypeId);

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

            // Select available rooms
            $availableRoomsQuery = Room::where('room_type_id', $roomTypeId)
                ->where('status', RoomStatus::AVAILABLE)
                ->whereNotIn('id', $bookedRoomIds);

            if ($lockRooms) {
                $availableRoomsQuery->lockForUpdate();
            }

            $availableRooms = $availableRoomsQuery->get();

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

            $roomDetails[] = [
                'room_type_id' => $roomTypeId,
                'room_type_name' => $roomType->name,
                'check_in' => $checkIn,
                'check_out' => $checkOut,
                'nights' => $nights,
                'price_per_night' => number_format($roomType->base_price, 2, '.', ''),
                'subtotal' => number_format($subtotal, 2, '.', ''),
                'assigned_room' => $assignedRoom,
            ];
        }

        // 2. Calculate service subtotals
        $serviceSubtotal = 0.00;
        $serviceDetails = [];
        foreach ($services as $serviceData) {
            $service = Service::where('hotel_id', $hotel->id)->where('is_active', true)->findOrFail($serviceData['service_id']);
            $qty = $serviceData['quantity'];
            $price = $service->base_price;
            $sub = $price * $qty;
            $serviceSubtotal += $sub;

            $serviceDetails[] = [
                'service_id' => $service->id,
                'name' => $service->name,
                'price' => number_format($price, 2, '.', ''),
                'quantity' => $qty,
                'subtotal' => number_format($sub, 2, '.', ''),
            ];
        }

        // 3. Calculate promotion discount
        $discountAmount = 0.00;
        $promotion = null;
        if ($promotionId) {
            $promotion = Promotion::where('is_active', true)
                ->where('start_date', '<=', now())
                ->where('end_date', '>=', now())
                ->findOrFail($promotionId);

            // Discount applies to rooms subtotal
            $discountAmount = $promotion->calculateDiscount($roomSubtotal);
        }

        // 4. Calculate fees and taxes
        $commissionRate = 10.00; // default 10%
        if ($hotel->hotelSetting) {
            $commissionRate = $hotel->hotelSetting->platform_commission;
        }
        $netAmount = max(0, ($roomSubtotal + $serviceSubtotal - $discountAmount));
        $platformFee = $netAmount * ($commissionRate / 100);
        $taxAmount = $netAmount * 0.10;
        $totalAmount = $netAmount + $taxAmount + $platformFee;

        return [
            'pricing' => [
                'room_subtotal' => number_format($roomSubtotal, 2, '.', ''),
                'service_subtotal' => number_format($serviceSubtotal, 2, '.', ''),
                'discount_amount' => number_format($discountAmount, 2, '.', ''),
                'net_amount' => number_format($netAmount, 2, '.', ''),
                'platform_fee' => number_format($platformFee, 2, '.', ''),
                'tax_amount' => number_format($taxAmount, 2, '.', ''),
                'total_amount' => number_format($totalAmount, 2, '.', ''),
            ],
            'items' => $roomDetails,
            'services' => $serviceDetails,
            'allocated_rooms' => $allocatedRooms,
            'promotion' => $promotion,
        ];
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
