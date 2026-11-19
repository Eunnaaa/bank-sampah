<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Tenant extends Model
{
    use HasUuids;

    protected $fillable = [
        'code', 'name', 'rt', 'rw', 'subdistrict', 'district', 'city', 'status',
        'collection_schedule', 'next_collection_date', 'collection_note',
    ];

    protected $casts = [
        'next_collection_date' => 'date',
    ];

    public function profiles()
    {
        return $this->hasMany(Profile::class);
    }

    public function wasteCategories()
    {
        return $this->hasMany(WasteCategory::class);
    }
}
