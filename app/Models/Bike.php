<?php

namespace App\Models;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class Bike extends Model
{
    use HasFactory;

    function getBike(int $id, mixed $data): Collection
    {
        $valid_column = ['bikes.bike_id', 'bikes.bike_price', 'bikes.bike_name', 'bikes.bike_classify', 'bikes.bike_local',
                         'bikes.bike_brand', 'bikes.bike_plate_num', 'bikes.bike_tank', 'bikes.bike_consumption', 'bikes.bike_capacity'];
        $column_query = [];

        if (!empty($data['column'])) {
            foreach ($data['column'] as $value) {
                if (in_array($value, $valid_column)) {
                    $column_query[] = $value;
                }
            }
            if (empty($column_query)) {
                $column_query = $valid_column;
            }
        } else {
            $column_query = $valid_column;
        }

        return DB::table('bikes')
            ->select($column_query)
            ->where('bikes.bike_id', '=', $id)
            ->get();
    }

    function getBikes(mixed $data): LengthAwarePaginator
    {
        $valid_column = ['bikes.bike_id', 'bikes.bike_price', 'bikes.bike_name', 'bikes.bike_classify', 'bikes.bike_local',
                         'bikes.bike_brand', 'bikes.bike_plate_num', 'bikes.bike_tank', 'bikes.bike_consumption', 'bikes.bike_capacity'];
        $column_query = [];


        if (!empty($data['column'])) {
            foreach ($data['column'] as $value) {
                if (in_array($value, $valid_column)) {
                    $column_query[] = $value;
                }
            }
            if (empty($column_query)) {
                $column_query = $valid_column;
            }
        } else {
            $column_query = $valid_column;
        }

        $db = DB::table('bikes')
            ->select($column_query);

        if (!empty($data['local'])) {
            $db = $db->where('bikes.bike_local', 'LIKE', '%' . $data['local'] . '%', 'AND');
        }

        if (!empty($data['search_by']['price'])) {
            $db = $db->where('bikes.bike_price', 'LIKE', '%' . $data['keyword'] . '%', 'OR');
        }

        if (!empty($data['search_by']['brand'])) {
            $db = $db->where('bikes.bike_brand', 'LIKE', '%' . $data['keyword'] . '%', 'OR');
        }

        if (!empty($data['search_by']['name'])) {
            $db = $db->where('bikes.bike_name', 'LIKE', '%' . $data['keyword'] . '%', 'OR');
        }

        $db->orderBy('bikes.bike_price');

        return $db->paginate($data['limit'], '*', 'page=' . $data['page'], $data['page']);
    }
}

