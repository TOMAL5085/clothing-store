<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AccountTransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'account_id' => $this->account_id,
            'account' => $this->whenLoaded('account', fn () => ['code' => $this->account->code, 'name' => $this->account->name]),
            'reference_type' => $this->reference_type,
            'reference_id' => $this->reference_id,
            'direction' => $this->direction,
            'amount' => (float) $this->amount,
            'description' => $this->description,
            'transaction_date' => $this->transaction_date?->toISOString(),
            'created_by' => $this->created_by,
        ];
    }
}
