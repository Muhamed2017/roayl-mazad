<?php

namespace App\Http\Controllers;

use App\Models\Ad;
use App\Models\Auction;
use App\Models\Image;
use App\Models\Saved;
use App\Models\User;
use App\Models\Vehicle;
// use App\Support\Services\AddImagesToEntity;
// use App\Support\Services\AttachImagesToModel;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
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
            'vehicle_id' => 1, 'vehicle_title' => 'Tesla', 'model' => '2021', 'odemeter' => '196.520', 'fuel' => '95', 'lot' => '#533654 | MD - BALTIMORE | C/3550',
            'vehicle_starts_date' => '20/11/2018', 'vehicle_starts_time' => '10:00 AM',
            'img' => 'https://www.drivespark.com/images/2021-06/skoda-kushaq-39.jpg'
        ],
        [
            'vehicle_id' => 2, 'vehicle_title' => 'Nissan', 'model' => '2019', 'odemeter' => '192.53', 'fuel' => '90', 'lot' => '#966322 | MD - OMAN | C/3550',
            'vehicle_starts_date' => '18/12/2019', 'vehicle_starts_time' => '01:00 AM',
            'img' => 'https://www.drivespark.com/images/2021-06/ferrari-296-gtb-1.jpg'
        ],
        [
            'vehicle_id' => 3, 'vehicle_title' => 'Toyota', 'model' => '2020', 'odemeter' => '126.220', 'fuel' => '92', 'lot' => '#366512 | MD - EGYPT | C/3550',
            'vehicle_starts_date' => '13/11/2021', 'vehicle_starts_time' => '06:00 PM',
            'img' => 'https://www.drivespark.com/images/2021-06/2022-honda-civic-hatchback-3.jpg'
        ],
        [
            'vehicle_id' => 4, 'vehicle_title' => 'Nissan', 'model' => '2018', 'odemeter' => '166.16', 'fuel' => '80', 'lot' => '#200254 | MD - NY City | C/2550',
            'vehicle_starts_date' => '20/11/2018', 'vehicle_starts_time' => '10:30 AM',
            'img' => 'https://www.drivespark.com/images/2021-06/2022-honda-civic-hatchback-7.jpg'
        ],
        [
            'vehicle_id' => 95, 'user_id' => 5, 'vehicle_title' => 'BMW', 'model' => '2020', 'odemeter' => '255.520', 'fuel' => '92', 'lot' => '#369834 | MD - KWAIT | C/3130',
            'vehicle_starts_date' => '15/08/2022', 'vehicle_starts_time' => '12:30 AM',
            'img' => 'https://www.drivespark.com/images/2021-06/2022-honda-civic-hatchback-9.jpg', 'final_price' => 500
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
        $auction = new Auction();
        if ($vehicle->save()) {
            $firebase_auction = $this->database->getReference('/Auctions' . "/" . $vehicle->id)
                ->push([
                    'vehicle_id' => $vehicle->id,
                    'vehicle_title' => $vehicle->vehicle_title,
                    'listed_name' => $owner->name,
                    'lister_id' => $owner->id,
                    'vehicle_initial_price' => $vehicle->retail_value,
                    'vehicle_start_date' => $vehicle->starts_at_date,
                    'vehicle_start_time' => $vehicle->starts_at_time,
                    'sell_type' => $vehicle->sell_type,
                    'final_price' => 0,
                    'initial_price' => 0,
                    'negotiation_price' => 0
                    // 'vehicle_start_data' => Carbon::createFromDate()->addDays(5),
                ])->getKey();

            $auction->vehicle_id = $vehicle->id;
            $auction->firebase_id = $firebase_auction;
            $auction->vehicle_title = $vehicle->vehicle_title;
            $auction->lister_id = $owner->id;
            $auction->lister_name = $owner->name;
            $auction->retail_value = $vehicle->retail_value;
            $auction->vehicle_start_date = $vehicle->starts_at_date;
            $auction->vehicle_start_time = $vehicle->starts_at_time;
            $auction->sell_type = $vehicle->sell_type;
            $auction->final_price = 0;
            $auction->save();

            if ($request->hasFile('photos')) {
                foreach ($request->photos as $photo) {
                    $vehicle->attachMedia($photo);
                }
            }

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
            'starts_at_date',
            'starts_at_time'
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
            'starts_at_date' => 'nullable|date_format:d/m/Y',
            'starts_at_time' => 'nullable|date_format:h:i A',
        ];
    }

    public function updateGetValidationRules($id = '')
    {
        return [

            'vehicle_title' => 'nullable|string|max:250',
            'vehicle_vin' => 'nullable|string|max:250',
            'published' => 'nullable|string|max:250',
            'engine_type' => 'nullable|string|max:250',
            'primary_damage' => 'nullable|string|max:250',
            'retail_value' => 'nullable|numeric|max:10000',
            'featured' => 'nullable|boolean',
            'transmission' => 'nullable|string|max:250',
            'vat_added' => 'nullable|numeric|max:250',
            'selender' => 'nullable|numeric|max:250',
            'fuel' => 'nullable|string|max:250',
            'keys' => 'nullable|string|max:250',
            'drive' => 'nullable|string|max:250',
            'sell_type' => 'nullable|string|max:250',
            'special_notes' => 'nullable|array',
            'special_notes.*' => 'nullable|string|max:250',
            'odometer' => 'nullable|numeric',

            'company' => 'nullable|string|max:250',
            'category' => 'nullable|string|max:250',
            'color' => 'nullable|string|max:250',
            'year' => 'nullable|string|max:50',
            'model' => 'nullable|string|max:250',
            'starts_at_date' => 'date|nullable',
            'starts_at_time' => 'date|nullable',
        ];
    }


    public function update(Request $request, $id)
    {
        // if (!auth('admin')->user()->can('update product')) return response()->json($this->authorizationErr, 403);

        $vehicle = Vehicle::find($id);

        if (!$vehicle) return response()->json($this->entityNotFoundErr, 422);

        $this->validate($request, $this->updateGetValidationRules($id));
        $this->validate($request, [
            'photos' => 'nullable|array',
            'photos.*' => 'nullable|image|mimes:jpeg,bmp,jpg,png|between:1,6000|dimensions:min_width=1024,max_height=1024'
        ]);

        if ($vehicle->update($this->getInput($request))) {
            if ($request->hasFile('photos')) {
                foreach ($request->photos as $photo) {
                    $vehicle->attachMedia($photo);
                }
            }
            return response()->json($this->successfullUpdate('vehicle'), 200);
        }

        return response()->json($this->failedErr, 500);
    }


    public function finder(Request $request)
    {
        $year_min = $request->year_min ?? 1800;
        $year_max = $request->year_max ?? 2060;

        $vehicles = QueryBuilder::for(Vehicle::class)
            ->allowedFilters([
                AllowedFilter::exact('category'),
                AllowedFilter::exact('model'),
                AllowedFilter::exact('company'),
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

    public function destroy($id, Request $request)
    {
        // if (!auth('admin')->user()->can('delete product')) return response()->json($this->authorizationErr, 403);

        $vehicle = Vehicle::find($id);
        $vehicle_auction = Auction::all()->where('vehicle_id', '==', $id);
        $saved_vehicles = Saved::all()->where('vehicle_id', '==', $id);
        if (!$vehicle) return response()->json($this->entityNotFoundErr, 422);

        if ($vehicle->delete()) {
            foreach ($vehicle_auction as $auction) {
                $auction->delete();
            };
            foreach ($saved_vehicles as $saved) {
                $saved->delete();
            };
            return response()->json($this->entityDeletedSucc, 200);
        }

        return response()->json($this->failedErr, 500);
    }

    // getting all published vehicles in new feed pahe

    public function getAllVehicles()
    {
        // $vehicles = Vehicle::with('images')->where('published', '0')->latest()->get();
        $vehicles = Vehicle::latest()->get();

        if (!$vehicles) return response()->json([
            'message' => 'No Vehicles published'
        ], 404);

        return response()->json([
            'vehicles' => $vehicles
        ], 200);
    }



    // return all auctions ...
    public function allAuctions()
    {
        $auctions = Auction::all();

        // $date = Carbon::createFromFormat('d/m/Y h:i A', $auction->vehicle_start_data . $auction->vehicle_start_time);
        $today_auctions = [];
        $upcoming_auctions = [];
        $last_auctions = [];
        foreach ($auctions as $auction) {
            $auction_start_date = Carbon::createFromFormat('d/m/Y', $auction->vehicle_start_date)->toDateString();
            $today = Carbon::today()->toDateString();

            if ($auction_start_date == $today) {
                array_push($today_auctions, $auction);
            }
            if ($auction_start_date > $today) {
                array_push($upcoming_auctions, $auction);
            } else {
                array_push($last_auctions, $auction);
            }
        }
        return response()->json([
            'today_auctions' => $today_auctions,
            'upcoming_auctions' => $upcoming_auctions,
            'last_auctions' => $last_auctions,
            // 'today' => Carbon::today()->toDateString()
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
        $user = auth('user')->user();

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
            'user_cars_counter' => $vehicles,
            'user_saved_cars' => $vehicles
        ], 200);
    }

    // public function getHomes()
    // {
    //     $user = auth('user')->user();
    //     $data = User::find($user->id)->with('savedVehicles')->get();
    //     // return response()->json(['MM' => $user], 200);
    //     $vehicles = collect($this->dummy_vehicles);

    //     $ads = collect([
    //         [
    //             'title' => 'Advertisment One',
    //             'link' => '#',
    //             'img' => 'https://via.placeholder.com/150/FF0000/FFFFFF?Text=AdOne'
    //         ],
    //         [
    //             'title' => 'Advertisment Two',
    //             'link' => '#',
    //             'img' => 'https://via.placeholder.com/150/00FF00/FFFFFF?Text=AdTwo'
    //         ],
    //         [
    //             'title' => 'Advertisment Three',
    //             'link' => '#',
    //             'img' => 'https://via.placeholder.com/150/FFFF00/FFFFFF?Text=AdThree'
    //         ],
    //         [
    //             'title' => 'Advertisment Four',
    //             'link' => '#',
    //             'img' => 'https://via.placeholder.com/150/000000/FFFFFF?Text=AdFour'
    //         ],
    //         [
    //             'title' => 'Advertisment Five',
    //             'link' => '#',
    //             'img' => 'https://via.placeholder.com/150/0000FF/FFFFFF?Text=AdFive'
    //         ],
    //     ]);
    //     if (!$vehicles || !$ads) return response()->json([
    //         'message' => 'some thing went weong!'
    //     ], 500);

    //     return response()->json([
    //         // 'ads' => $ads,
    //         // 'fetuered' => $vehicles,
    //         // 'user_cars_won' => $vehicles,
    //         // 'user_cars_counter' => $vehicles,
    //         // 'user_saved' => $data ?? ""
    //     ], 200);
    // }

    //saving a vehicle

    public function save($id)
    {
        $user = auth('user')->user();
        $vehicle = Vehicle::find($id);
        if (!$vehicle) {
            return response()->json([
                'successful' => '0',
                'status' => '02',
                'message' => 'vehicle not found'
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

        $user_id = auth('user')->user()->id;
        if (!$user_id) {
            $is_saved = false;
        } else {
            $is_saved = Saved::all()->where('user_id', '==', $user_id)
                ->where('vehicle_id', '==', $id)
                ->first();
        }
        $vehicle = Vehicle::find($id);

        if (!$vehicle) {
            return response()->json([
                'message' => "No Such Vehicle"
            ], 404);
        }

        $auction_firebase_id = Auction::all()->where('vehicle_id', $id);

        return response()->json([
            'vehicle' => $vehicle,
            'images' => $vehicle->fetchAllMedia(),
            'final_price' => 500,
            'is_saved' => $is_saved ? true : false,
            'fb_id' => $auction_firebase_id
        ], 200);
    }
}
