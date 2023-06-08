<?php

namespace App\Http\Controllers;

use App\Models\Bike;
use App\Models\BikeImage;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class BikeController extends Controller
{
    /**
     * Display a listing of the bikes.
     */
    public function index(Request $request): JsonResponse
    {
        $page = $request->query('page') ?? 1;
        $keyword = $request->query('keyword') ?? '';
        $search_by = $request->query('search_by') ?? null;
        $column_query = $request->query('column_query') ?? null;
        $limit = $request->query('limit') ?? 30;

        if (empty($page)) $page = 1;
        if (empty($keyword)) $keyword = '';
        if (empty($limit)) $limit = 10;

        $filter_data = [
            'page'    => $page,
            'keyword' => $keyword,
            'limit'   => $limit
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
        }else if(!empty($keyword) && empty($search_by)){
            $filter_data['search_by']['bike_local'] = true;
        }


        $result = (new Bike)->getBikes($filter_data);

        $data = [];

        foreach ($result->items() as $value){
            $data['items'][] = get_object_vars($value);
        }

        foreach ($data['items'] as &$value) {
            $bikeImage = (new BikeImage)->getBikeImage($value['bike_id']);
            $value['bikeImage'] = $bikeImage->toArray();

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

        return new JsonResponse($data, 200, [], 0);
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

            $data = [
                'code'  => 400,
                'error' => [
                    'warning' => 'Bad Request! Không tìm thấy bike_id'
                ]
            ];

            return new JsonResponse($data, 400, [], 0);
        }

        $bike = (new Bike)->getBike($id, $filter_data);
        $bikeImage = (new BikeImage)->getBikeImage($id);

        if (empty($bike->toArray())) {

            $data = [
                'code'  => 400,
                'error' => [
                    'warning' => 'Bad Request! Không tìm thấy bike_id'
                ]
            ];

            return new JsonResponse($data, 400, [], 0);
        }



        $data['items'] = (array)$bike["0"];
        $data['items']['bikeImage'] = $bikeImage;
        $data['code'] = 200;
        $data['success'] = true;

        return new JsonResponse($data, 200, [], 0);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Bike $bike)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Bike $bike)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Bike $bike)
    {
        //
    }

}
