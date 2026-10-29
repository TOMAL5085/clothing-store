<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class VisitorCount extends Model { protected $fillable=['visit_date','period','ip_hash','path']; protected function casts(): array { return ['visit_date'=>'date']; } }
