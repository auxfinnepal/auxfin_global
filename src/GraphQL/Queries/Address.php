<?php

declare(strict_types=1);

namespace Auxfin\Global\GraphQL\Queries;

use App\Models\AddressAccessibleGroup;
use App\Models\CountryAddress;
use Auxfin\Global\Models\Address as ModelsAddress;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

final class Address
{

    public function getAddressHierarchy($_, array $args)
    {
        $country = $args['country'];
        $area1 = array_key_exists('area1', $args) ? $args['area1'] : null;
        $area2 = array_key_exists('area2', $args) ? $args['area2'] : null;
        $area3 = array_key_exists('area3', $args) ? $args['area3'] : null;
        $area4 = array_key_exists('area4', $args) ? $args['area4'] : null;
        $area5 = array_key_exists('area5', $args) ? $args['area5'] : null;
        $group = array_key_exists('group', $args) ? $args['group'] : null;
        $query = "SELECT country_address.id,country_address.country_code,
        CASE
            WHEN country_address.area1 IS NULL THEN country_address.country
            WHEN country_address.area1 IS NOT NULL AND country_address.area2 IS NULL THEN country_address.area1
            WHEN country_address.area2 IS NOT NULL AND country_address.area3 IS NULL THEN country_address.area2
            WHEN country_address.area3 IS NOT NULL AND country_address.area4 IS NULL THEN country_address.area3
            WHEN country_address.area4 IS NOT NULL AND country_address.area5 IS NULL THEN country_address.area4
            WHEN country_address.area5 IS NOT NULL AND country_address.\"group\" IS NULL THEN country_address.area5
            WHEN country_address.\"group\" IS NOT NULL THEN country_address.\"group\"
            ELSE NULL::character varying
        END AS name,
       CASE
           WHEN country_address.area1 IS NULL THEN ('country')
            WHEN country_address.area1 IS NOT NULL AND country_address.area2 IS NULL THEN('area1')
            WHEN country_address.area2 IS NOT NULL AND country_address.area3 IS NULL THEN ('area2')
            WHEN country_address.area3 IS NOT NULL AND country_address.area4 IS NULL THEN ('area3')
            WHEN country_address.area4 IS NOT NULL AND country_address.area5 IS NULL THEN ('area4')
            WHEN country_address.area5 IS NOT NULL AND country_address.\"group\" IS NULL THEN ('area5')
            WHEN country_address.\"group\" IS NOT NULL THEN 'group'
            ELSE NULL::character varying
        END AS address_level,
    case when (country_code=:country and area1 is null) then ''::text else country_address.parent_id::text End as parent_id
   FROM country_address
  WHERE country_address.country_code::text =:country";
        $variables = ["country" => $country];
        if ($area1) {
            $query .= " AND country_address.area1::text=:area1";
            $variables["area1"] = $area1;
        }
        if ($area2) {
            $query .= " AND country_address.area2::text=:area2";
            $variables["area2"] = $area2;
        }
        if ($area3) {
            $query .= " AND country_address.area3::text=:area3";
            $variables["area3"] = $area3;
        }
        if ($area4) {
            $query .= " AND country_address.area4::text=:area4";
            $variables["area4"] = $area4;
        }
        if ($area5) {
            $query .= " AND country_address.area5::text=:area5";
            $variables["area5"] = $area5;
        }
        if ($group) {
            $query .= " AND country_address.group::text=:group";
            $variables["group"] = $group;
        }

        $query .= " ORDER BY country_address.country_code NULLS FIRST, country_address.area1 NULLS FIRST, country_address.area2 NULLS FIRST, country_address.area3 NULLS FIRST, country_address.area4 NULLS FIRST, country_address.area5 NULLS FIRST, country_address.\"group\" NULLS FIRST";

        $data = DB::connection('global')->select($query, $variables);
        return $data;
    }
    public function getAllCountryAddresses($_, $args)
    {
    }

    public function getGroupByAddress($_, array $args)
    {
        $province = array_key_exists("province", $args) ? $args["province"] : null;
        $commune = array_key_exists("commune", $args) ? $args["commune"] : null;
        $zone = array_key_exists("zone", $args) ? $args["zone"] : null;
        $colline = array_key_exists("colline", $args) ? $args["colline"] : null;
        $locality = array_key_exists("locality", $args) ? $args["locality"] : null;
        $group = array_key_exists("group", $args) ? $args["group"] : null;

        $include_subgroups = array_key_exists("include_subgroups", $args) ? $args['include_subgroups'] : null;

        try {
            $message = [
                "group.required" => "*.field_required",
                "country.required" => "*.field_required",
            ];

            $validator = Validator::make($args, [
                "country" => 'required',
                "province" => 'nullable|exists:country_address,area1',
                "commune" => 'nullable|exists:country_address,area2',
                "zone" => 'nullable|exists:country_address,area3',
                "colline" => 'nullable|exists:country_address,area4',
                "locality" => 'nullable|exists:country_address,area5',

            ], $message);
            if ($validator->fails()) {
                throw new ValidationException($validator);
            }
            ini_set('memory_limit', -1);
            ini_set('max_execution_time', 0);
            $address = CountryAddress::ByCountry($args['country']);


            if ($province) {
                $address->byArea1($province);
            }

            if ($commune) {
                $address->byArea2($commune);
            }
            if ($zone) {
                $address->byArea3($zone);
            }
            if ($colline) {
                $address->byArea4($colline);
            }
            if ($locality) {
                $address->ByArea5($locality);
            }

            if ($include_subgroups) {
                $data = $address->pluck('id')->toArray();

                $groups = [];
                $dts = array_chunk($data, 1000);

                foreach ($dts as $dt) {
                    $query = AddressAccessibleGroup::whereIn('address_id', $dt);
                    $query->where("published", true);
                    if ($group) {
                        $query->where('accessible_group_id', '=', $group);
                    }
                    $query->whereIn('address_id', $data);

                    $groups = array_merge($groups, $query->with('address_country')->get()->toArray());
                }
            } else {
                $data = $address->first();

                $query = AddressAccessibleGroup::where('address_id', '=', $data->id);

                if ($group) {
                    $query->where('accessible_group_id', '=', $group);
                }

                $groups = $query->get()->toArray();
            }
            $result = [];
            $array = [];
            foreach ($groups as $grp) {
                $result = [
                    'group_name' => $grp['group_name'],
                    'accessible_group_id' => $grp['accessible_group_id'],
                    'address_id' => $grp['address_id'],
                    'created_at' => $grp['created_at'],
                    'updated_at' => $grp['updated_at'],
                    'country_code' => $grp['address_country']['country_code'],
                    'country' => $grp['address_country']['country'],
                    'province' => $grp['address_country']['area1'],
                    'commune' => $grp['address_country']['area2'],
                    'zone' => $grp['address_country']['area3'],
                    'colline' => $grp['address_country']['area4'],
                    'locality' => $grp['address_country']['area5'],
                    'group' => $grp['address_country']['group'],
                    'latitude' => $grp['address_country']['latitude'],
                    'longitude' => $grp['address_country']['longitude'],
                    'published' => $grp['published'],
                ];
                $array[] = $result;
            }

            return $array;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function getAddresswithgroup($_, array $args)
    {
        $address_id = array_key_exists("address_id", $args) ? $args["address_id"] : null;
        ini_set('memory_limit', -1);
        ini_set('max_execution_time', 0);
        $address = ModelsAddress::where('address_path', '~', '*.' . $address_id . '.*')
            ->pluck('address_id')
            ->toArray();

        $full_address = CountryAddress::whereIn('country_address.id', $address)
            ->get()->toArray();

        $result = [];
        $array = [];
        foreach ($full_address as $grp) {
            $result = [
                'id' => $grp['id'],
                'country_code' => $grp['country_code'],
                'country' => $grp['country'],
                'area1' => $grp['area1'],
                'area2' => $grp['area2'],
                'area3' => $grp['area3'],
                'area4' => $grp['area4'],
                'area5' => $grp['area5'],
                'group' => $grp['group'],
                'latitude' => $grp['latitude'],
                'longitude' => $grp['longitude'],
                'parent_id' => $grp['parent_id'],

            ];
            $array[] = $result;
        }

        return $array;
    }
}
