<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo; // Adicione esta linha

class PricingRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'city_id',
        'service_category_id',
        'base_fare',
        'price_per_km',
        'price_per_minute',
        'min_fare',
        'time_rules_json',
        'is_active',
        'valid_from',
        'valid_to',
    ];

    protected $casts = [
        'time_rules_json' => 'array',
        'is_active' => 'boolean',
        'base_fare' => 'decimal:2',
        'price_per_km' => 'decimal:2',
        'price_per_minute' => 'decimal:2',
        'min_fare' => 'decimal:2',
        'valid_from' => 'date',
        'valid_to' => 'date',
    ];

    // Relacionamento: Uma regra de preço pertence a uma cidade (ou a nenhuma, se global)
    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    // Relacionamento: Uma regra de preço pertence a uma categoria de serviço
    public function serviceCategory(): BelongsTo
    {
        return $this->belongsTo(ServiceCategory::class);
    }
}