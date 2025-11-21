<?php

namespace Auxfin\Global\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AddressLabel extends Model
{
    use HasFactory;
    protected $connection='gobal';
    protected $table = 'address_label';

    public $timestamps = false;

    protected $primaryKey = 'id';

    public function scopeByCountry($query, $value)
    {
        return $query->where('country_code', $value);
    }

}
