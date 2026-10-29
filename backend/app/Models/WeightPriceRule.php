<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class WeightPriceRule extends Model { protected $fillable=['product_id','min_weight','max_weight','price','unit','is_active']; protected function casts(): array { return ['min_weight'=>'decimal:3','max_weight'=>'decimal:3','price'=>'decimal:2','is_active'=>'boolean']; } public function product(): BelongsTo { return $this->belongsTo(Product::class); } }
