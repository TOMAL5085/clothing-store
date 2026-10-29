<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExpenseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reference_number' => $this->reference_number,
            'account_id' => $this->account_id,
            'account' => $this->whenLoaded('account', fn () => ['code' => $this->account->code, 'name' => $this->account->name]),
            'category' => $this->category,
            'vendor' => $this->vendor,
            'amount' => (float) $this->amount,
            'currency' => $this->currency,
            'expense_date' => $this->expense_date?->toISOString(),
            'status' => $this->status,
            'description' => $this->description,
            'receipt_path' => $this->receipt_path,
            'approved_by' => $this->approved_by,
            'approved_at' => $this->approved_at?->toISOString(),
            'creator' => $this->whenLoaded('creator', fn () => ['id' => $this->creator->id, 'name' => $this->creator->name]),
        ];
    }
}
