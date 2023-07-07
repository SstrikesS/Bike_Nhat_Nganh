<?php

namespace App\Models;

use DateTime;
use DateTimeZone;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class Bike extends Model
{
    use HasFactory;

    private function getCurrentTime(): string
    {
        $currentTime = new DateTime();
        $currentTime->setTimezone(new DateTimeZone('UTC'));
        $currentTime->modify('+7 hours');
        return $currentTime->format('Y-m-d H:i:s');
    }

    function getBike(int $id, mixed $data): Collection
    {
        $valid_column = ['bikes.bike_id', 'bikes.bike_price', 'bikes.bike_name', 'bikes.bike_classify', 'bikes.bike_local', 'bikes.bike_address',
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
        $valid_column = ['bikes.bike_id', 'bikes.bike_price', 'bikes.bike_name', 'bikes.bike_classify', 'bikes.bike_local', 'bikes.bike_address',
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


        if (!empty($data['search_by']['brand'])) {
            $db = $db->where('bikes.bike_brand', 'LIKE', '%' . $data['keyword'] . '%', 'OR');
        }

        if (!empty($data['search_by']['name'])) {
            $db = $db->where('bikes.bike_name', 'LIKE', '%' . $data['keyword'] . '%', 'OR');
        }

        if (!empty($data['local'])) {
            $db = $db->where('bikes.bike_local', 'LIKE', '%' . $data['local'] . '%', 'AND');
        }

        if (!empty($data['price_min'])) {
            $db = $db->where('bikes.bike_price', '>=', $data['price_min'], 'AND');
        }

        if (!empty($data['price_max'])) {
            $db = $db->where('bikes.bike_price', '<=', $data['price_max'], 'AND');
        }

        $db->orderBy('bikes.bike_price');

        return $db->paginate($data['limit'], '*', 'page=' . $data['page'], $data['page']);
    }

    function addBike(mixed $data): int
    {

        return DB::table('bikes')
            ->insertGetId([
                'bike_name'        => $data['bike_name'],
                'bike_price'       => $data['bike_price'],
                'bike_classify'    => $data['bike_classify'],
                'bike_local'       => $data['bike_local'],
                'bike_brand'       => $data['bike_brand'],
                'bike_plate_num'   => $data['bike_plate_num'],
                'bike_tank'        => $data['bike_tank'],
                'bike_consumption' => $data['bike_consumption'],
                'bike_capacity'    => $data['bike_capacity'],
                'bike_address'     => $data['bike_address'],
                'created_at'       => $this->getCurrentTime()
            ]);
    }

    function addBikeImage(int $id, mixed $data): void
    {
        foreach ($data as $value) {
            DB::table('bike_images')
                ->insert([
                    'created_at' => $this->getCurrentTime(),
                    'bike_id'    => $id,
                    'bike_title' => $data['bike_title'],
                    'bike_image' => $data['bike_image'],
                ]);
        }
    }

    function updateBikeImage(int $id, mixed $data): void
    {
        foreach ($data as $value) {
            DB::table('bike_images')
                ->insert([
                    'updated_at' => $this->getCurrentTime(),
                    'bike_id'    => $id,
                    'bike_title' => $data['bike_title'],
                    'bike_image' => $data['bike_image'],
                ]);
        }
    }

    function updateBike(int $id, mixed $data): int
    {
        return DB::table('bikes')
            ->where('bikes.bike_id', '=', $id)
            ->update([
                'updated_at' => $this->getCurrentTime(),
                'bike_name' => $data['bike_name'],
                'bike_price'       => $data['bike_price'],
                'bike_classify'    => $data['bike_classify'],
                'bike_local'       => $data['bike_local'],
                'bike_brand'       => $data['bike_brand'],
                'bike_plate_num'   => $data['bike_plate_num'],
                'bike_tank'        => $data['bike_tank'],
                'bike_consumption' => $data['bike_consumption'],
                'bike_capacity'    => $data['bike_capacity'],
                'bike_address'     => $data['bike_address'],

            ]);
    }

    function deleteBike(int $id): void
    {
        DB::table('bikes')
	    ->where('bikes.bike_id', '=', $id)
            ->delete();
        DB::table('bike_images')
	    ->where('bike_images.bike_id', '=', $id)
            ->delete();
    }

}

