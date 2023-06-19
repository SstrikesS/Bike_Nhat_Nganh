<?php

namespace App\Http\Controllers;

use App\Models\Bike;
use App\Models\BikeImage;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class OrderController extends Controller
{

    /**
     * Display a listing of the orders.
     */
    public function index(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'page'         => 'integer',
            'limit'        => 'integer',
            'keyword'      => 'string',
            'column_query' => 'string',
            'search_by'    => 'string',
            'user_id'      => 'integer',
        ]);

        if (empty($request->query('page'))) {
            $request->merge(['page' => 1]);
        }

        if (empty($request->query('limit'))) {
            $request->merge(['limit' => 10]);
        }

        $filter_data = [
            'page'      => $request->query('page'),
            'keyword'   => $request->query('keyword'),
            'limit'     => $request->query('limit'),
            'search_by' => $request->query('search_by')
        ];

        if (!empty($request->query('user_id'))) {
            $filter_data['user_id'] = $request->query('user_id');
            $filter_data['search_by'] = 'order_name';
        }

        if (!empty($request->query('column_query'))) {
            foreach (explode(",", $request->query('column_query')) as $value) {
                $filter_data['column'][] = 'orders.' . $value;
            }
        }

        if ($validator->fails()) {
            $errors = $validator->errors();

            return response()->json([
                'code'  => 400,
                'error' => [
                    'warning' => $errors,
                ]
            ]);
        }

        $result = (new Order())->getOrders($filter_data);

        $data['items'] = [];

        foreach ($result->items() as $value) {
            $data['items'][] = get_object_vars($value);
        }
        $data['meta_field'] = [
            'total'        => $result->total(),
            'per_page'     => $result->perPage(),
            'current_page' => $result->currentPage(),
            'last_page'    => $result->lastPage(),
            'next_page'    => $result->currentPage() >= $result->lastPage() ? null : ($result->currentPage() + 1)
        ];

        $data['code'] = 200;
        $data['success'] = true;

        return response()->json($data);

    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'order_start'   => [
                'required',
                'date_format:Y-m-d H:i:s',
                function ($attribute, $value, $fail) {
                    $orderStart = Carbon::parse($value)->setTimezone('UTC');
                    $now = Carbon::now()->setTimezone('UTC')->addHours(7);

                    if ($orderStart <= $now) {
                        $fail('The ' . $attribute . ' must be a date and time after the current time.');
                    }
                },
            ],
            'order_end'     => 'required|date_format:Y-m-d H:i:s|after_or_equal:order_start',
            'order_name'    => 'required',
            'bike_quantity' => 'required|integer',
            'order_total'   => 'required',
            'order_time'    => 'required',
            'user_id'       => 'required|integer',
            'order_address' => 'required',
            'bikes'         => 'required|string'
        ]);

        $filter_data = $request->only('order_name', 'order_start', 'order_end',
            'order_time', 'bike_quantity', 'user_id', 'order_total', 'order_address');

        $bikes = [];

        $validator->after(function ($validator) use ($request, &$filter_data, &$bikes) {
            $orderStart = strtotime($request->post('order_start'));
            $orderEnd = strtotime($request->post('order_end'));

            if ($orderStart === false || $orderEnd === false) {
                $validator->errors()->add('order_start', 'Invalid date format');
                $validator->errors()->add('order_end', 'Invalid date format');

                return false;
            }
            $result = [];
            foreach (explode(",", $request->post('bikes')) as $value) {
                $result[] = (new Bike())->getBike((int)$value, [])->toArray();

                if (empty($result)) {

                    $validator->errors()->add('bikes', 'Không tìm thấy bike_id');
                    return false;
                }
            }

            foreach ($result as $value) {
                $bikes[] = [
                    'bike_name' => ((array)($value['0']))['bike_name'],
                    'bike_address' => ((array)($value['0']))['bike_address'],
                    'bike_id'      => ((array)($value['0']))['bike_id'],
                    'bike_price' =>  ((array)($value['0']))['bike_price'],
                    'bike_classify' =>  ((array)($value['0']))['bike_classify'],
                    'bike_local' =>  ((array)($value['0']))['bike_local'],
                ];
            }

            $invalid_bike = [];
            $check = 0;

            foreach ($bikes as $value) {
                if ($value['bike_address'] != $request->post('order_address')) {
                    $validator->errors()->add('bikes', 'Các xe có bike_address khác với order_address');
                    $invalid_bike[] = $value;
                    $check++;
                }
            }

            if($check){
                $validator->errors()->add('invalid_bikes', $invalid_bike);

                return false;
            }

            $check = 0;
            $invalid_bike = [];
            foreach ($bikes as $value) {
                $invalid_bike = DB::table('orders')
                    ->select('orders.order_start', 'orders.order_end')
                    ->leftJoin('order_detail', 'order_detail.order_id', '=', 'orders.order_id')
                    ->where('order_detail.bike_id', '=', $value['bike_id'])
                    ->get()->toArray();
//
                if (!empty($invalid_bike) && (strtotime(((array)$invalid_bike['0'])['order_start'])) >= $orderStart &&
                    (strtotime(((array)$invalid_bike['0'])['order_start'])) <= $orderEnd ||
                    (strtotime(((array)$invalid_bike['0'])['order_end'])) >= $orderStart &&
                    (strtotime(((array)$invalid_bike['0'])['order_end'])) <= $orderEnd) {

                    $validator->errors()->add('bikes ' . $value['bike_id'], 'Xe đã được đặt trong khung giờ này!');
                    $invalid_bike[] = $value;
                    $check++;
                }
            }

            if($check){
                $validator->errors()->add('invalid_bikes', $invalid_bike);

                return false;

            }

            if (count($bikes) != $request->post('bike_quantity')) {
                $request->merge(['bike_quantity' => count($bikes)]);
            }

            $orderTime = round(($orderEnd - $orderStart) / 3600);
            $filter_data['order_time'] = $orderTime;

            return true;

        });

        if ($validator->fails()) {
            $errors = $validator->errors();

            return response()->json([
                'code'  => 400,
                'error' => [
                    'warning' => $errors,
                ]
            ]);
        } else {
            $order_id = (new Order())->addOrder($filter_data);
            if ($order_id) {
                (new Order())->addOrderDetail($order_id, $bikes);

                return response()->json([
                    'code'    => 200,
                    'success' => true,
                ]);
            } else {            $bikes = [];

                return response()->json([
                    'code'  => 200,
                    'error' => [
                        'warning' => 'Server is not responding'
                    ],
                ]);
            }
        }
    }

    /**
     * Display the specified order.
     */
    public function show(int $id, Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'column_query' => 'nullable',
        ]);

        $filter_data = [];
        $data = [];

        if (!empty($column_query)) {
            foreach (explode(",", $column_query) as $value) {
                $filter_data['column'][] = 'orders.' . $value;
            }
        }

        $order = (new Order)->getOrder($id, $filter_data)->toArray();

        $validator->after(function ($validator) use ($request, $order, $id) {
            if (empty($order)) {
                $validator->errors()->add('order_id', 'Không tìm thấy order_id');

                return false;
            }

            return true;
        });

        if ($validator->fails()) {
            $errors = $validator->errors();

            return response()->json([
                'code'  => 400,
                'error' => [
                    'warning' => $errors,
                ]
            ]);
        }

        $bikes = (new Order)->getBikeInOrder($id, [])->toArray();

        foreach ($order as $value) {
            $data['item']['order'] = (array)$value;
        }

        foreach ($bikes as $value) {
            $bikeImage = (new BikeImage())->getBikeImage(((array)$value)['bike_id']);
            $data['item']['bikes'][] = [
                ...(array)$value,
                ...($bikeImage->toArray())
            ];
        }
        $data['code'] = 200;
        $data['success'] = true;

        return response()->json($data);
    }

    /**
     * Update the specified resource in database.
     */
    public function update(int $id, Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'order_status' => 'required|integer|min:0|max:4',
        ]);

        $validator->after(function ($validator) use ($request, &$data, $id) {
            $order = (new Order)->getOrder($id, [])->toArray();
            if (empty($order)) {
                $validator->errors()->add('order_id', 'Không tìm thấy order_id');

                return false;
            }

            return true;
        });

        if ($validator->fails()) {
            $errors = $validator->errors();

            return response()->json([
                'code'  => 400,
                'error' => [
                    'warning' => $errors,
                ]
            ]);
        }

        $filter_data['order_status'] = $request->post('order_status');
        $filter_data['order_id'] = $id;

        $result = (new Order())->updateOrderStatus($filter_data);

        if ($result) {
            return response()->json([
                'code'    => 200,
                'success' => true,
            ]);

        } else {

            return response()->json([
                'code'  => 500,
                'error' => [
                    'warning' => 'Query Failed',
                ]
            ]);
        }
    }

    /**
     * Remove the specified resource from database.
     */
    public function destroy(Order $order)
    {
        //
    }
}
