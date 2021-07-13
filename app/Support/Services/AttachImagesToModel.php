<?php

namespace App\Support\Services;

use App\Models\Image;
use App\Models\User;
use App\Models\Vehicle;



class AttachImagesToModel
{
    public $uploaded_images;
    public $entity;
    public $entityClassName;

    public function __construct($images, $entity)
    {

        if (!is_array($images)) {
            $images = [$images];
        }
        $this->uploaded_images = $images;
        $this->entity = $entity;
        $this->entityClassName = get_class($entity);
    }

    public function locatedFolder($entity)
    {
        return '/public' . '/' . $entity . 's/';
    }


    public function saveImages()
    {
        foreach ($this->uploaded_images as $image) {
            $new_name = rand() . '.' . $image->getClientOriginalExtension();

            $imgData[] = $new_name;


            if ($this->entityClassName === Vehicle::class) {
                $path = $image->move(public_path('/uploads/vehicles'), $new_name);
            }
            if ($this->entityClassName === User::class) {
                $path = $image->move(public_path('/uploads/users/' . $this->entity->id), $new_name);
            }

            // $path = $image->move(storage_path(), $new_name);

            // dimension
            $dimensions = getimagesize($path);
            $stored = new Image();
            // $stored->img_url = url($path);
            $stored->img_url = $path;
            // $stored->img_url = json_encode($imgData);
            $stored->img_width = $dimensions[0];
            $stored->img_height = $dimensions[1];
            $stored->img_bytes = round($path->getSize() / 1024, 0);
            $stored->format = $image->getClientOriginalExtension();
            $stored->original_filename = $image->getClientOriginalName();
            $this->entity->images()->save($stored);
        }
        return response()->json([
            // 'images' => $stored
        ], 200);
    }
}
