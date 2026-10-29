<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class WholesaleCustomerLedger extends Model { protected $table='wholesale_customer_ledger'; protected $fillable=['wholesale_customer_id','reference','entry_type','amount','description','entry_date']; protected function casts(): array { return ['amount'=>'decimal:2','entry_date'=>'date']; } public function customer(): BelongsTo { return $this->belongsTo(WholesaleCustomer::class,'wholesale_customer_id'); } }
