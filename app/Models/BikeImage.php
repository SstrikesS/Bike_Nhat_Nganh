<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class BikeImage extends Model
{
    use HasFactory;

    function getBikeImage(int $id): Collection
    {
        return DB::table('bike_images')
            ->orderBy('bike_images.bike_id')
            ->select('bike_images.image_id', 'bike_images.bike_image', 'bike_images.bike_title')
            ->where('bike_images.bike_id', '=', $id)
            ->get();
    }


}
