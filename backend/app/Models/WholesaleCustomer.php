<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
class WholesaleCustomer extends Model { use HasFactory; protected $fillable=['code','name','email','phone','company','credit_limit','is_active']; protected function casts(): array { return ['credit_limit'=>'decimal:2','is_active'=>'boolean']; } public function ledger(): HasMany { return $this->hasMany(WholesaleCustomerLedger::class); } }
