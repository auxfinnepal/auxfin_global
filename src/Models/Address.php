<?php

namespace Auxfin\Global\Models;

use App\Models\CountryAddress;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Address extends Model
{
    use HasFactory;

    public $fillable = ["address_id", "address_path", "address_type", "model_type", "model_id"];
    public $append = ['first_path', 'last_path'];
    protected $connection = 'global';
    protected $table = "addresses";

    public function detail(): HasOne
    {
        return $this->hasOne(CountryAddress::class, 'id', 'address_id');
    }

    protected function firstPath(): Attribute
    {

        $path = explode('.', $this->address_path);

        return Attribute::make(
            get: fn($value) => $path[0] !== '' ? $path[0] : $this->address_id,
        );
    }

    protected function lastPath(): Attribute
    {
        $path = explode('.', $this->address_path);

        return Attribute::make(
            get: fn($value) => $path[count($path) - 1],
        );
    }
}
