<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class FeatureUser extends Pivot
{
    /**
     * Indica se o modelo deve receber timestamps.
     *
     * @var bool
     */
    public $timestamps = true;
    
    /**
     * Os atributos que devem ser convertidos.
     *
     * @var array
     */
    protected $casts = [
        'enabled' => 'boolean',
    ];
    
    /**
     * Get the feature that owns the pivot.
     */
    public function feature()
    {
        return $this->belongsTo(Feature::class, 'feature_name', 'name');
    }
    
    /**
     * Get the user that owns the pivot.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}