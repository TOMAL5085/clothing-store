<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class StoreSetting extends Model { protected $fillable=['key','value','type','is_public']; protected function casts(): array { return ['is_public'=>'boolean']; } public function decodedValue(): mixed { return $this->type === 'boolean' ? filter_var($this->value,FILTER_VALIDATE_BOOLEAN) : ($this->type === 'json' ? json_decode($this->value,true) : $this->value); } }
