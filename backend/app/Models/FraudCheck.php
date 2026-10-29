<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class FraudCheck extends Model { protected $fillable=['order_id','risk_level','risk_score','signals','status']; protected function casts(): array { return ['risk_score'=>'integer','signals'=>'array']; } public function order(): BelongsTo { return $this->belongsTo(Order::class); } }
