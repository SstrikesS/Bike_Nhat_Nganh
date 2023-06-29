<?php

namespace App\Http\Controllers;

use App\Models\Bike;
use App\Models\BikeImage;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class BikeController extends Controller
{
    /**
     * Display a listing of the bikes.
     */
    public function index(Request $request): JsonResponse
    {
        $page = $request->query('page') ?? 1;
        $keyword = $request->query('keyword') ?? '';
        $local = $request->query('local') ?? '';
        $search_by = $request->query('search_by') ?? null;
        $column_query = $request->query('column_query') ?? null;
        $limit = $request->query('limit') ?? 30;
        $price_min = $request->query('min_price') ?? 0;
        $price_max = $request->query('max_price') ?? 0;

        if (empty($page)) $page = 1;
        if (empty($keyword)) $keyword = '';
        if (empty($limit)) $limit = 10;

        if (!empty($price_max)) $filter_data['price_max'] = $price_max;
        if (!empty($price_min)) $filter_data['price_min'] = $price_max;

        $filter_data = [
            'page'      => $page,
            'keyword'   => $keyword,
            'local'     => $local,
            'limit'     => $limit,
            'price_min' => $price_min,
            'price_max' => $price_max
        ];

        if (!empty($column_query)) {
            foreach (explode(",", $column_query) as $value) {
                $filter_data['column'][] = 'bikes.' . $value;
            }
        }

        if (!empty($keyword) && !empty($search_by)) {
            foreach (explode(",", $search_by) as $value) {
                $filter_data['search_by'][$value] = true;
            }
        } else if (!empty($keyword) && empty($search_by)) {
            $filter_data['search_by']['bike_local'] = true;
        }

        $result = (new Bike)->getBikes($filter_data);

        $data = [];

        foreach ($result->items() as $value) {
            $data['items'][] = get_object_vars($value);
        }
        if (!empty($data['items'])) {
            foreach ($data['items'] as &$value) {
                $bikeImage = (new BikeImage)->getBikeImage($value['bike_id']);
                $value['bikeImage'] = $bikeImage->toArray();

            }
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
     * Display the specified bike.
     */
    public function show(int $id, Request $request): JsonResponse
    {
        $column_query = $request->query('column_query') ?? null;

        $filter_data = [];

        if (!empty($column_query)) {
            foreach (explode(",", $column_query) as $value) {
                $filter_data['column'][] = 'bikes.' . $value;
            }
        }

        if (empty($id)) {

            return response()->json([
                'code'  => 400,
                'error' => [
                    'warning' => 'Bad Request! Không tìm thấy bike_id'
                ]
            ]);
        }

        $bike = (new Bike)->getBike($id, $filter_data);
        $bikeImage = (new BikeImage)->getBikeImage($id);

        if (empty($bike->toArray())) {

            return response()->json([
                'code'  => 400,
                'error' => [
                    'warning' => 'Bad Request! Không tìm thấy bike_id'
                ]
            ]);
        }


        $data['items'] = (array)$bike["0"];
        $data['items']['bikeImage'] = $bikeImage;
        $data['code'] = 200;
        $data['success'] = true;

        return response()->json($data);
    }

    /**
     * Show the form for creating a new bike.
     */
    public function create(Request $request): JsonResponse
    {
        $validate = Validator::make($request->all(), [
            'bike_name'        => 'required|string|max:255|min:1',
            'bike_price'       => 'required|int|min:1',
            'bike_classify'    => 'required|int|min:0|max:1',
            'bike_local'       => 'required|string|max:255|min:1',
            'bike_brand'       => 'required|string|max:255|min:1',
            'bike_plate_num'   => 'required|string|max:255|min:1',
            'bike_tank'        => 'required|string|max:255|min:1',
            'bike_consumption' => 'required|string|max:255|min:1',
            'bike_capacity'    => 'required|string|max:255|min:1',
            'bike_address'     => 'required|string|max:255|min:1',
            'bike_image'       => 'required|array',
        ]);

        if ($validate->fails()) {
            $error = $validate->errors();

            return response()->json([
                'error' => [
                    'warning' => $error
                ],
                'code'  => 400
            ], 400);

        } else if ($validate->passes()) {

            $value = (new Bike)->addBike([
                'bike_name'        => $request->post('bike_name'),
                'bike_price'       => $request->post('bike_price'),
                'bike_classify'    => $request->post('bike_classify'),
                'bike_local'       => $request->post('bike_local'),
                'bike_brand'       => $request->post('bike_brand'),
                'bike_plate_num'   => $request->post('bike_plate_num'),
                'bike_tank'        => $request->post('bike_tank'),
                'bike_consumption' => $request->post('bike_consumption'),
                'bike_capacity'    => $request->post('bike_capacity'),
                'bike_address'     => $request->post('bike_address'),
            ]);

            if (!$value) {
                return response()->json([
                    'error' => [
                        'warning' => 'Server is not respond'
                    ],
                    'code'  => 500
                ], 500);
            }

            foreach ($request->post('bike_image') as $bike_image) {
                dump($bike_image);
                (new Bike())->addBikeImage($value, [
                    'bike_title' => $request->post('bike_title'),
                    'bike_image' => $bike_image,
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'code'    => 200
        ], 200);
    }

    /**
     * Show the form for editing the specified bike.
     */
    public function edit(int $id, Request $request): JsonResponse
    {

        $validate = Validator::make($request->all(), [
            'bike_name'        => 'string|max:255|min:1',
            'bike_price'       => 'int|min:1',
            'bike_classify'    => 'int|min:0|max:1',
            'bike_local'       => 'string|max:255|min:1',
            'bike_brand'       => 'string|max:255|min:1',
            'bike_plate_num'   => 'string|max:255|min:1',
            'bike_tank'        => 'string|max:255|min:1',
            'bike_consumption' => 'string|max:255|min:1',
            'bike_capacity'    => 'string|max:255|min:1',
            'bike_address'     => 'string|max:255|min:1',
        ]);

        $bike = (new Bike)->getBike($id, [])->toArray();


        $validate->after(function ($validate) use ($request, $bike, $id) {
            if (empty($bike)) {
                $validate->errors()->add('bike_id', 'Không tìm thấy bike_id');

                return false;
            }

            return true;
        });

//        $bike_image = (new BikeImage)->getBikeImage($id)->toArray();

        if ($validate->fails()) {
            $error = $validate->errors();

            return response()->json([
                'error' => [
                    'warning' => $error
                ],
                'code'  => 400
            ], 400);

        } else if ($validate->passes()) {

            $value = (new Bike)->updateBike($id, [
                'bike_name'        => $request->post('bike_name') ?? $bike[0]->bike_name,
                'bike_price'       => $request->post('bike_price') ?? $bike[0]->bike_price,
                'bike_classify'    => $request->post('bike_classify') ?? $bike[0]->bike_classify,
                'bike_local'       => $request->post('bike_local') ?? $bike[0]->bike_local,
                'bike_brand'       => $request->post('bike_brand') ?? $bike[0]->bike_brand,
                'bike_plate_num'   => $request->post('bike_plate_num') ?? $bike[0]->bike_plate_num,
                'bike_tank'        => $request->post('bike_tank') ?? $bike[0]->bike_tank,
                'bike_consumption' => $request->post('bike_consumption') ?? $bike[0]->bike_consumption,
                'bike_capacity'    => $request->post('bike_capacity') ?? $bike[0]->bike_capacity,
                'bike_address'     => $request->post('bike_address') ?? $bike[0]->bike_address,
            ]);

            if (!$value) {
                return response()->json([
                    'error' => [
                        'warning' => 'Server is not respond'
                    ],
                    'code'  => 500
                ], 500);
            }
//
//            foreach ($request->post('bike_image') as $bike_image) {
//                dump($bike_image);
//                (new Bike())->updateBikeImage($value, [
//                    'bike_title' => $request->post('bike_title'),
//                    'bike_image' => $bike_image,
//                ]);
//            }

        }

        return response()->json([
            'success' => true,
            'code'    => 200
        ], 200);
    }

    /**
     * Remove the specified bike from database.
     */
    public function destroy(int $id): void
    {
        (new Bike)->deleteBike($id);
    }

}
