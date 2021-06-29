<?php

namespace App\Http\Controllers;

use App\Models\Ad;
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

    private $dummy_vehicles = [
        [
            'vehicle_title' => 'Tesla', 'model' => '2021', 'odemeter' => '196.520', 'fuel' => '95', 'lot' => '#533654 | MD - BALTIMORE | C/3550',
            'img' => 'https://www.drivespark.com/images/2021-06/skoda-kushaq-39.jpg'
        ],
        [
            'vehicle_title' => 'Nissan', 'model' => '2019', 'odemeter' => '192.53', 'fuel' => '90', 'lot' => '#966322 | MD - OMAN | C/3550',
            'img' => 'https://www.drivespark.com/images/2021-06/ferrari-296-gtb-1.jpg'
        ],
        [
            'vehicle_title' => 'Toyota', 'model' => '2020', 'odemeter' => '126.220', 'fuel' => '92', 'lot' => '#366512 | MD - EGYPT | C/3550',
            'img' => 'https://www.drivespark.com/images/2021-06/2022-honda-civic-hatchback-3.jpg'
        ],
        [
            'vehicle_title' => 'Nissan', 'model' => '2018', 'odemeter' => '166.16', 'fuel' => '80', 'lot' => '#200254 | MD - NY City | C/2550',
            'img' => 'https://www.drivespark.com/images/2021-06/2022-honda-civic-hatchback-7.jpg'
        ],
        [
            'vehicle_title' => 'BMW', 'model' => '2020', 'odemeter' => '255.520', 'fuel' => '92', 'lot' => '#369834 | MD - KWAIT | C/3130',
            'img' => 'https://www.drivespark.com/images/2021-06/2022-honda-civic-hatchback-9.jpg'
        ],
    ];

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
            'retail_value',
            'featured'
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
            'featured' => 'required|boolean',
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
        // $vehicles = Vehicle::with('images')->where('published', '0')->latest()->get();
        $vehicles = Vehicle::with('images')->latest()->get();

        if (!$vehicles) return response()->json([
            'message' => 'No Vehicles published'
        ], 404);

        return response()->json([
            'vehicles' => $vehicles
        ], 200);
    }



    // get all featured cars

    public function getFeaturedVehicles()
    {
        // $vehicles = Vehicle::with('images')->where('featured', true)->latest()->get('vehicle_title');

        $vehicles = collect($this->dummy_vehicles);
        if (!$vehicles) return response()->json([
            'message' => 'No Featured Vehicles'
        ], 404);

        return response()->json([
            'featured vehicles' => $vehicles,
        ], 200);
    }

    public function getHomeAds()
    {

        $ads = collect([
            [
                'title' => 'Advertisment One',
                'link' => '#',
                'img' => 'https://via.placeholder.com/150/FF0000/FFFFFF?Text=AdOne'
            ],
            [
                'title' => 'Advertisment Two',
                'link' => '#',
                'img' => 'https://via.placeholder.com/150/00FF00/FFFFFF?Text=AdTwo'
            ],
            [
                'title' => 'Advertisment Three',
                'link' => '#',
                'img' => 'https://via.placeholder.com/150/FFFF00/FFFFFF?Text=AdThree'
            ],
            [
                'title' => 'Advertisment Four',
                'link' => '#',
                'img' => 'https://via.placeholder.com/150/000000/FFFFFF?Text=AdFour'
            ],
            [
                'title' => 'Advertisment Five',
                'link' => '#',
                'img' => 'https://via.placeholder.com/150/0000FF/FFFFFF?Text=AdFive'
            ],
        ]);

        return response()->json([
            'Advirtesment' => $ads
        ], 200);
    }

    public function getUserVehicles()
    {
        $vehicles = collect($this->dummy_vehicles);
        if (!$vehicles) return response()->json([
            'message' => 'You have no vehicles yet!'
        ], 404);

        return response()->json([
            'vehicles' => $vehicles,
        ], 200);
    }
}
