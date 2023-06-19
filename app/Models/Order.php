<?php

namespace App\Models;

use DateTime;
use DateTimeZone;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class Order extends Model
{
    use HasFactory;

    private function getCurrentTime(): string
    {
        $currentTime = new DateTime();
        $currentTime->setTimezone(new DateTimeZone('UTC'));
        $currentTime->modify('+7 hours');
        return $currentTime->format('Y-m-d H:i:s');
    }

    function addOrder(mixed $data): int
    {
        return DB::table('orders')
            ->insertGetId([
                'order_name'    => $data['order_name'],
                'order_end'     => $data['order_end'],
                'order_start'   => $data['order_start'],
                'order_status'  => 1,
                'order_time'    => $data['order_time'],
                'bike_quantity' => $data['bike_quantity'],
                'order_total'   => $data['order_total'],
                'user_id'       => $data['user_id'],
                'order_address' => $data['order_address'],
                'created_at'    => $this->getCurrentTime(),
            ]);
    }

    function addOrderDetail(int $id, mixed $data): void
    {
        foreach ($data as $value) {
            DB::table('order_detail')
                ->insert([
                    'order_id' => $id,
                    'bike_id'  => $value['bike_id']
                ]);
        }
    }

    function updateOrderStatus(mixed $data): int
    {
        return DB::table('orders')
            ->where('orders.order_id', '=', $data['order_id'])
            ->update([
                'order_status' => $data['order_status'],
                'updated_at'   => $this->getCurrentTime()
            ]);
    }

    function getOrder(int $id, mixed $data): Collection
    {
        $valid_column = ['orders.order_name', 'orders.order_id', 'orders.order_end', 'orders.order_start', 'orders.order_address',
                         'orders.order_status', 'orders.order_time', 'orders.bike_quantity', 'orders.order_total', 'orders.user_id'];
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

        return DB::table('orders')
            ->select($column_query)
            ->where('orders.order_id', '=', $id)
            ->get();
    }


    function getBikeInOrder(int $id, mixed $data): Collection
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
            ->leftJoin('order_detail', 'order_detail.bike_id', '=', 'bikes.bike_id')
            ->where('order_detail.order_id', '=', $id)
            ->get();


    }

    function getOrders(mixed $data): LengthAwarePaginator
    {
        $valid_column = ['orders.order_name', 'orders.order_id', 'orders.order_end', 'orders.order_start', 'orders.order_address',
                         'orders.order_status', 'orders.order_time', 'orders.bike_quantity', 'orders.order_total', 'orders.user_id'];
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

        $db = DB::table('orders')
            ->select($column_query);

        if (!empty($data['user_id'])) {
            $db = $db->where('orders.user_id', '=', $data['user_id']);
        }

        if (!empty($data['keyword'])) {
            if ($data['search_by'] == 'order_status') {
                $db = $db->where('orders.order_status', 'LIKE', '%' . $data['keyword'] . '%', 'AND');
            } else {
                $db = $db->where('orders.order_name', 'LIKE', '%' . $data['keyword'] . '%', 'AND');
            }
        }

        return $db->orderBy('orders.order_start')->paginate($data['limit']);
    }

}
