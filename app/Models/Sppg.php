<?php

namespace App\Models;

use App\Models\Concerns\Uploaded;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * Count of operational MBG kitchens (Satuan Pelayanan Pemenuhan Gizi) in a province.
 */
#[Fillable(['uploaded_by', 'province', 'region_id', 'outlets', 'as_of', 'source_url'])]
class Sppg extends Model
{
    use Uploaded;

    protected $table = 'sppg';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'as_of' => 'date',
            'outlets' => 'integer',
        ];
    }
}
