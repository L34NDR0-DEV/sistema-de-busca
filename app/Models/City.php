<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany; // Adicione esta linha

class City extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'state',
        'country',
    ];

    // Relacionamento: Uma cidade pode ter muitas regras de preço
    public function pricingRules(): HasMany
    {
        return $this->hasMany(PricingRule::class);
    }
}