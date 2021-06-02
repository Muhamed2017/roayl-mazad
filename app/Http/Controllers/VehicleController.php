<?php

namespace App\Http\Controllers;

use App\Models\Image;
use App\Models\Vehicle;
use App\Support\Services\AddImagesToEntity;
use App\Support\Services\AttachImagesToModel;
use Illuminate\Http\Request;
use Tymon\JWTAuth\JWTAuth;
// use Kreait\Firebase;
// use Kreait\Firebase\Factory;
// use Kreait\Firebase\ServiceAccount;
// use Kreait\Firebase\Messaging\Notification;
use Illuminate\Support\Facades\Storage;
use Kreait\Firebase\Database;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class VehicleController extends Controller
{
    protected $auth;
    private $database;

    public function __construct(JWTAuth $auth, Database $database)
    {
        $this->auth = $auth;
        $this->database = $database;
    }

    public function store(Request $request)
    {

        $this->validate($request, $this->getValidationRules());
        $this->validate($request, [
            'photos' => 'nullable|array',
            'photos.*' => 'nullable|image|mimes:jpeg,bmp,jpg,png|between:1,6000|dimensions:min_width=1024,max_height=1024'
        ]);
        $guard = $request->route()->getName();
        $input = $this->getInput($request);
        $input['user_id'] = auth($guard)->id();
        $input['listed_by'] = $guard;
        $vehicle = new Vehicle($input);

        if ($vehicle->save()) {
            $this->database->getReference('/vehicles')
                ->push([
                    'vehicle_id' => '2',
                    'vehicle_title' => $vehicle->vehicle_title,
                    'vehicle_initial_price' => $vehicle->retail_value
                ]);

            if ($request->hasFile('photos')) (new AttachImagesToModel($request->photos, $vehicle))->saveImages();


            // $this->attachRelatedModels($vehicle, $request);
            return response()->json($this->entityCreatedSucc, 200);
        }

        return response()->json($this->failedErr, 500);
    }

    public function getInput(Request $request)
    {
        $input = $request->only(
            'vehicle_title',
            'vehicle_vin',
            'vehicle_vrn',
            'primary_damage',
            'secondary_damage',
            'category',
            'color',
            'transmission',
            'fuel',
            'engine_type',
            'vat_added',
            'body_style',
            'sell_type',
            'drive',
            'keys',
            'state',
            'published',
            'model',
            'year',
            'company',
            'starts_at_date',
            'is_finished',
            'odometer',
            'notes',
            'retail_value'
        );

        return $input;
    }

    public function getValidationRules($id = '')
    {
        return [

            'vehicle_title' => 'required|string|max:250',
            'category' => 'required|string|max:250',
            'vehicle_vin' => 'required|string|max:250',
            'vehicle_vrn' => 'required|string|max:250',
            'state' => 'nullable|string|max:250',
            'published' => 'nullable|string|max:250',
            'company' => 'nullable|string|max:250',
            'engine_type' => 'required|string|max:250',
            'primary_damage' => 'required|string|max:250',
            'retail_value' => 'required|string|max:250',
            'secondary_damage' => 'required|string|max:250',
            'color' => 'required|string|max:250',
            'transmission' => 'required|string|max:250',
            'vat_added' => 'required|numeric|max:250',
            'fuel' => 'required|string|max:250',
            'keys' => 'required|string|max:250',
            'drive' => 'required|string|max:250',
            'sell_type' => 'required|string|max:250',
            'notes' => 'nullable|array',
            'notes.*' => 'nullable|string|max:250',
            'body_style' => 'string|max:250',
            'odometer' => 'required|array',
            'odometer.*' => 'required|string|max:250',
            'year' => 'string|max:50',
            'model' => 'string|max:250',
            'is_finished' => 'string|max:50',
            'starts_at_date' => 'string|max:250',


        ];
    }

    // attaching vehicle model to image model function
    public function attachRelatedModels($vehicle, $request)
    {
        if ($request->hasFile('photos')) (new AddImagesToEntity($request->photos, $vehicle, ["width" => 1024]))->execute();
    }


    public function finder(Request $request)
    {
        $year_min = 1800;
        $year_max = 2060;
        if ($request->has('year_min')) $year_min = $request->year_min;
        if ($request->has('year_max')) $year_max = $request->year_max;

        $vehicles = QueryBuilder::for(Vehicle::class)->with('images')
            ->allowedFilters([
                AllowedFilter::exact('category'),
                AllowedFilter::scope('term_search')
            ])->whereBetween('year', [$year_min, $year_max])
            ->get();

        if (empty($vehicles)) return response()->json(['message' => 'No such vehicles'], 404);

        return response()->json([
            'number_of_vehicles' => count($vehicles),
            'vehicles' => $vehicles
        ], 200);
    }

    // getting all published vehicles in new feed pahe

    public function getAllVehicles()
    {
        $vehicles = Vehicle::with('images')->where('published', 'published')->latest()->get();

        if (!$vehicles) return response()->json([
            'message' => 'No Vehicles published'
        ], 404);

        return response()->json([
            'vehicles' => $vehicles
        ], 200);
    }

    // save vehicles images locally
    // public function saveImages($images, $entity)
    // {
    //     foreach ($images as $image) {
    //         $new_name = rand() . '.' . $image->getClientOriginalExtension();
    //         $path = $image->move(storage_path('/public/vehicles'), $new_name);

    //         // dimension
    //         $dimensions = getimagesize($path);
    //         $stored = new Image();
    //         $stored->img_url = url($path);
    //         $stored->thumb_url = 'sssss';
    //         $stored->img_public_id = 'sssss';
    //         $stored->thumb_public_id = '555555';
    //         $stored->img_width = $dimensions[0];
    //         $stored->img_height = $dimensions[1];
    //         $stored->thumb_width = '25564';
    //         $stored->thumb_height = '25564';
    //         $stored->img_bytes = $path->getSize() / 1024;
    //         $stored->thumb_bytes = '25564';
    //         $stored->format = $image->getClientOriginalExtension();
    //         $stored->original_filename = $image->getClientOriginalName();
    //         $entity->images()->save($stored);
    //     }

    //     return response()->json([
    //         'images' => $stored
    //     ], 200);
    // }
}
