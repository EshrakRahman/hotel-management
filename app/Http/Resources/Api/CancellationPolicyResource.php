<?php

namespace App\Http\Resources\Api;

use App\Models\CancellationPolicy;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin CancellationPolicy
 */
class CancellationPolicyResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'name' => $this->name,
            'description' => $this->description,
            'free_cancellation_days' => $this->free_cancellation_days,
            'cancellation_fee' => $this->cancellation_fee,
        ];
    }
}
