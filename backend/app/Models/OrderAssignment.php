<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class OrderAssignment extends Model { protected $fillable=['order_id','staff_id','assigned_by','status','assigned_at','completed_at']; protected function casts(): array { return ['assigned_at'=>'datetime','completed_at'=>'datetime']; } public function order(): BelongsTo { return $this->belongsTo(Order::class); } public function staff(): BelongsTo { return $this->belongsTo(User::class,'staff_id'); } }
