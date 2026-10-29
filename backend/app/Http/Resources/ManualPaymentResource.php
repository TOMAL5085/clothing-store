<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ManualPaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reference_number' => $this->reference_number,
            'order_id' => $this->order_id,
            'order' => $this->whenLoaded('order', fn () => ['number' => $this->order->number]),
            'account_id' => $this->account_id,
            'account' => $this->whenLoaded('account', fn () => ['code' => $this->account->code, 'name' => $this->account->name]),
            'customer_name' => $this->customer_name,
            'customer_email' => $this->customer_email,
            'payment_method' => $this->payment_method,
            'amount' => (float) $this->amount,
            'currency' => $this->currency,
            'payment_date' => $this->payment_date?->toISOString(),
            'status' => $this->status,
            'transaction_id' => $this->transaction_id,
            'notes' => $this->notes,
            'receipt_path' => $this->receipt_path,
            'verified_by' => $this->verified_by,
            'verified_at' => $this->verified_at?->toISOString(),
        ];
    }
}
