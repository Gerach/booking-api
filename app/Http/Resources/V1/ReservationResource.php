<?php

namespace App\Http\Resources\V1;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use JsonSerializable;

class ReservationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     *
     * @return array|Arrayable|JsonSerializable
     */
    public function toArray($request): array | JsonSerializable|Arrayable
    {
        return [
            'id' => $this->id,
            'reservedSince' => (new CarbonImmutable($this->reserved_since))->format('Y-m-d'),
            'reservedTill' => (new CarbonImmutable($this->reserved_till))->format('Y-m-d'),
        ];
    }
}
