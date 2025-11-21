<?php

namespace Auxfin\Global\Models;

use App\Models\AccessibleLocalGroup;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CountryAddress extends Model
{
    use HasFactory;
    protected $connection='global';
    protected $table = 'country_address';

    protected $fillable = ['country_code', 'country', 'area1', 'area2', 'area3', 'area4', 'area5', 'group', 'latitude', 'longitude', 'parent_id'];

    public function address(): HasMany
    {
        return $this->hasMany(Address::class, 'id', 'address_id');
    }
    public function scopeByMain($query, $value)
    {
        if ($value) {
            return $query->whereNull('area1')
                ->select(['id','country_code', 'country'])
                ->orderBy('country_code', 'asc')->distinct();
        } else {
            return $query;
        }
    }
    public function scopeByCountry($query, $value)
    {
        return $query->where('country_code', $value)
            ->select('id', 'country_code', 'country', 'area1', 'area2', 'area3', 'area4', 'area5', 'group')

            ->orderBy('area1', 'asc');
    }

    public function scopeByArea1($query, $value)
    {
        return $query->where('area1', $value)
            ->select('id', 'area1', 'area2', 'area3', 'area4', 'area5', 'group')
            ->orderBy('area2', 'asc')->distinct('area2');
    }

    public function scopeByArea2($query, $value)
    {
        return $query->where('area2', $value)
            ->select('id', 'area1', 'area2', 'area3', 'area4', 'area5', 'group')
            ->orderBy('area3', 'asc')->distinct('area3');
    }
    public function scopeByArea3($query, $value)
    {
        return $query->where('area3', $value)
            ->select('id', 'area1', 'area2', 'area3', 'area4', 'area5', 'group')
            ->orderBy('area4', 'asc')->distinct('area4');
    }
    public function scopeByArea4($query, $value)
    {
        return $query->where('area4', $value)
            ->select('id', 'area1', 'area2', 'area3', 'area4', 'area5', 'group')
            ->orderBy('area5', 'asc')->distinct('area5');
    }
    public function scopeByArea5($query, $value)
    {
        return $query->where('area5', $value)
            ->select('id', 'area1', 'area2', 'area3', 'area4', 'area5', 'group')
            ->orderBy('group', 'asc')->distinct('group');
    }

    public function scopeByGroup($query, $value)
    {
        return $query->where('group', $value)
            ->select('id', 'area1', 'area2', 'area3', 'area4', 'area5', 'group');
    }

    public function scopeFindById($query, $value)
    {
        return $query->find($value);
    }

    public function scopeByAddress($query, $value)
    {
        ini_set('memory_limit', -1);
        ini_set('max_execution_time', 0);
        return $query->join('addresses', 'addresses.address_id', '=', 'country_address.id')
            ->where('address_path', '~', '*.' . $value . '.*');
    }

    public function scopeByCountryAddress($query, $type)
    {
        if (isset($type['country_code'])) {
            $query->where('country_code', $type['country_code'])
                ->distinct('area1');
        }
        if (isset($type['area1'])) {
            $query->where('area1', $type['area1'])
                ->distinct('area2');
        }
        if (isset($type['area2'])) {
            $query->where('area2', $type['area2'])
                ->distinct('area3');
        }
        if (isset($type['area3'])) {
            $query->where('area3', $type['area3'])
                ->distinct('area4');
        }
        if (isset($type['area4'])) {
            $query->where('area4', $type['area4'])
                ->distinct('area5');
        }
        if (isset($type['area5'])) {
            $query->where('area5', $type['area5'])
                ->distinct('group');
        }

        return $query;
    }

    public function accessibleLocalGroups()
    {
        return $this->morphMany(AccessibleLocalGroup::class, 'groupable');
    }

    public function hasChild()
    {
        return self::where("parent_id", $this->id)->count() > 0 ? true : false;
    }

}
