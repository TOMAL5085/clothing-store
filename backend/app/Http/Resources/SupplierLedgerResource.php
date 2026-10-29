<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SupplierLedgerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'supplier_id' => $this->supplier_id,
            'reference_type' => $this->reference_type,
            'reference_id' => $this->reference_id,
            'debit' => (float) $this->debit,
            'credit' => (float) $this->credit,
            'balance' => (float) $this->balance,
            'description' => $this->description,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
