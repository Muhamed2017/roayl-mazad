<?php

namespace App\Http\Controllers;

use App\Models\Ad;
use App\Models\Image;
use App\Models\Saved;
use App\Models\User;
use App\Models\Vehicle;
// use App\Support\Services\AddImagesToEntity;
use App\Support\Services\AttachImagesToModel;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Tymon\JWTAuth\JWTAuth;
use Illuminate\Support\Facades\Storage;
use Kreait\Firebase\Database;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;
use Illuminate\Validation\Validator;
use Illuminate\Validation\ValidationException;

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
        $owner = User::findOrFail(auth($guard)->id());

        $input = $this->getInput($request);
        $input['user_id'] = auth($guard)->id();
        $input['listed_by'] = $guard;
        $vehicle = new Vehicle($input);

        if ($vehicle->save()) {
            $this->database->getReference('/Auctions')
                ->push([
                    'vehicle_id' => $vehicle->id,
                    'vehicle_title' => $vehicle->vehicle_title,
                    'listed_name' => $owner->name,
                    'lister_id' => $owner->id,
                    'vehicle_initial_price' => $vehicle->retail_value,
                    'vehicle_start_data' => $vehicle->starts_at_date,
                    'sell_type' => $vehicle->sell_type,
                    'initial_price' => 0,
                    'negotiation_price' => 0
                    // 'vehicle_start_data' => Carbon::createFromDate()->addDays(5),

                ]);

            // if ($request->hasFile('photos')) (new AttachImagesToModel($request->photos, $vehicle))->saveImages();
            if ($request->hasFile('photos')) {
                foreach ($request->photos as $photo) {
                    $vehicle->attachMedia($photo);
                }
            }


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
            'primary_damage',
            'transmission',
            'fuel',
            'special_notes',
            'retail_value',
            'odometer',
            'engine_type',
            'vat_added',
            'sell_type',
            'selender',
            'drive',
            'keys',
            'published',
            'featured',

            'model',
            'year',
            'company',
            'category',
            'color',
            'starts_at_date'
        );

        return $input;
    }

    public function getValidationRules($id = '')
    {
        return [

            'vehicle_title' => 'required|string|max:250',
            'vehicle_vin' => 'required|string|max:250',
            'published' => 'nullable|string|max:250',
            'engine_type' => 'nullable|string|max:250',
            'primary_damage' => 'nullable|string|max:250',
            'retail_value' => 'required|numeric|max:10000',
            'featured' => 'nullable|boolean',
            'transmission' => 'required|string|max:250',
            'vat_added' => 'required|numeric|max:250',
            'selender' => 'required|numeric|max:250',
            'fuel' => 'required|string|max:250',
            'keys' => 'required|string|max:250',
            'drive' => 'required|string|max:250',
            'sell_type' => 'required|string|max:250',
            'special_notes' => 'nullable|array',
            'special_notes.*' => 'nullable|string|max:250',
            'odometer' => 'required|numeric',

            'company' => 'nullable|string|max:250',
            'category' => 'nullable|string|max:250',
            'color' => 'nullable|string|max:250',
            'year' => 'nullable|string|max:50',
            'model' => 'nullable|string|max:250',
            'starts_at_date' => 'nullable|string|max:250',
        ];
    }

    // attaching vehicle model to image model function
    public function attachRelatedModels($vehicle, $request)
    {
        // if ($request->hasFile('photos')) (new AddImagesToEntity($request->photos, $vehicle, ["width" => 1024]))->execute();
    }


    public function finder(Request $request)
    {
        $year_min = $request->year_min ?? 1800;
        $year_max = $request->year_max ?? 2060;

        $vehicles = QueryBuilder::for(Vehicle::class)->with('images')
            ->allowedFilters([
                AllowedFilter::exact('category'),
                // AllowedFilter::exact('year'),
                AllowedFilter::exact('model'),
                AllowedFilter::exact('company'),
                // AllowedFilter::scope('term_search')
                // ])
            ])->whereBetween('year', [$year_min, $year_max])
            ->select('id', 'vehicle_title', 'fuel', 'model', 'color', 'odometer', 'year')
            // ->where('published', 0) // to be changed to 1 ..
            ->get();

        if (empty($vehicles)) return response()->json(['message' => 'No such vehicles'], 404);

        return response()->json([
            'number_of_vehicless' => count($vehicles),
            'vehicles' => $vehicles,
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

    public function getHomestuff()
    {
        $vehicles = collect($this->dummy_vehicles);
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
        if (!$vehicles || !$ads) return response()->json([
            'message' => 'some thing went weong!'
        ], 500);

        return response()->json([
            'ads' => $ads,
            'fetuered' => $vehicles,
            'user_cars_won' => $vehicles,
            'user_cars_counter' => $vehicles
        ], 200);
    }


    //saving a vehicle

    public function save($id)
    {
        $user = auth('user')->user();
        $vehicle = Vehicle::find($id);
        if (!$user) {
            return response()->json([
                'successful' => '0',
                'status' => '02',
                'message' => 'user not found'
            ], 422);
        }
        $user_id = $user->id;

        $is_saved = Saved::all()->where('user_id', '==', $user_id)
            ->where('vehicle_id', '==', $id)
            ->first();

        if ($is_saved == null) {
            $saved = new Saved();
            $saved->user_id        = $user_id;
            $saved->vehicle_id       = $vehicle->id;
            if ($saved->save()) {
                return response()->json([
                    'successful' => '1',
                    'status' => '01',
                    'message' => 'Vehicle saved successfully',
                ], 200);
            } else {
                return response()->json([
                    'successful' => '0',
                    'status' => '02',
                    'message' => 'Something Went Wrong, Try again',
                ], 500);
            }
        } else {
            return response()->json([
                'successful' => '0',
                'status' => '02',
                'message' => 'Vehiicle is already saved',
            ], 409);
        }
    }

    //unsave vehicle
    public function unsave($id)
    {
        $user = auth('user')->user();
        if (!$user) {
            return response()->json([
                'successful' => '0',
                'status' => '02',
                'message' => 'user not found'
            ], 422);
        }
        $user_id = $user->id;

        $saved = Saved::all()->where('user_id', '==', $user_id)
            ->where('vehicle_id', '==', $id)
            ->first();

        if (empty($saved)) {
            return response()->json([
                'successful' => '0',
                'status' => '02',
                'message' => 'Vehicle was not saved'
            ], 409);
        } else {
            $vehicle = Vehicle::findOrFail($id);
            $saved->delete();

            $vehicle->save();
            return response()->json([
                'successful' => '1',
                'status' => '01',
                'message' => 'vehicle removed from saved successfully'
            ], 200);
        }
    }



    public function getVehicleById($id)
    {

        $vehicle = Vehicle::findOrFail($id);
        if (!$vehicle) {
            return response()->json([
                'message' => "No Such Vehicle"
            ], 404);
        }
        // $images = $vehicle->fetchAllMedia();

        return response()->json([
            'vehicle' => $vehicle,
            'images' => $vehicle->fetchAllMedia()
        ], 200);
    }
}
