<?php
/**
 * LaraClassifier - Classified Ads Web Application
 * Copyright (c) BeDigit. All Rights Reserved
 *
 * Website: https://laraclassifier.com
 *
 * LICENSE
 * -------
 * This software is furnished under a license and may be used and copied
 * only in accordance with the terms of such license and with the inclusion
 * of the above copyright notice. If you Purchased from CodeCanyon,
 * Please read the full License from here - http://codecanyon.net/licenses/standard
 */

namespace App\Http\Controllers\Web\Search;

use App\Helpers\ArrayHelper;
use App\Http\Controllers\Web\FrontController;
use App\Http\Controllers\Web\Search\Traits\CategoryTrait;
use App\Http\Controllers\Web\Search\Traits\LocationTrait;
use App\Http\Controllers\Web\Search\Traits\TitleTrait;
use App\Models\Category;
use App\Models\SubAdmin1;
use App\Models\PostType;
use App\Models\City;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Larapen\LaravelDistance\Libraries\mysql\DistanceHelper;

class BaseController extends FrontController
{
	use CategoryTrait, LocationTrait, TitleTrait;
	
	public $request;
	//public $countries;
	
	/**
	 * All Types of Search
	 * Variables declaration required
	 */
	public $isIndexSearch = false;
	public $isCatSearch = false;
	public $isSubCatSearch = false;
	public $isCitySearch = false;
	public $isAdminSearch = false;
	public $isUserSearch = false;
	public $isTagSearch = false;
	
	private $cats;
	
	public $preSearch;
	public $cat = null;
	public $locationArr = null;
	public $city = null;
	public $admin = null;
	
	/**
	 * SearchController constructor.
	 *
	 * @param Request $request
	 */
	public function __construct(Request $request)
	{
		parent::__construct();
		
		$this->middleware(function ($request, $next) {
			$this->commonQueries();
			
			return $next($request);
		});
		
		$this->request = $request;
		
		// Create the MySQL Distance Calculation function, If doesn't exist
		if (!DistanceHelper::checkIfDistanceCalculationFunctionExists(config('settings.list.distance_calculation_formula'))) {
			$res = DistanceHelper::createDistanceCalculationFunction(config('settings.list.distance_calculation_formula'));
		}
	}
	
	/**
	 * Common Queries
	 */
	public function commonQueries()
	{
		//view()->share('countries', $this->countries ?? collect());
		
		// Get Root Categories
		$rootCats = $this->getRootCategories();
		view()->share('rootCats', $rootCats);
		
		// Get Category
		$this->cat = $this->getCategory();
		
		// Get Category's Subcategories
		$popCatId = (isset($cat->parent, $cat->parent->parent) && !empty($cat->parent->parent))
			? $cat->parent->parent->id
			: ((isset($cat->parent) && !empty($cat->parent)) ? $cat->parent->id : null);
		$cats = $this->getCategories($popCatId);
		view()->share('cats', $cats);
		
		// Get Location (City or Administrative Division)
		$this->locationArr = $this->getLocation();
		
		// PreSearch Array
		$this->preSearch = $this->locationArr;
		$this->preSearch['cat'] = $this->cat;
		
		
		// LEFT MENU VARS
		if (config('settings.list.left_sidebar')) {
			// Count Posts by Category
			$countPostsByCat = collect();
			if (config('settings.list.count_categories_listings')) {
				if (isset($this->city) && !empty($this->city) && $this->city instanceof City) {
					$cityId = $this->city->id;
					$cacheId = config('country.code') . '.' . $cityId . '.count.posts.by.cat.' . config('app.locale');
					$countPostsByCat = Cache::remember($cacheId, $this->cacheExpiration, function () use ($cityId) {
						$countPostsByCat = Category::countPostsByCategory($cityId);
						
						return $countPostsByCat;
					});
				} else {
					$cacheId = config('country.code') . '.count.posts.by.cat.' . config('app.locale');
					$countPostsByCat = Cache::remember($cacheId, $this->cacheExpiration, function () {
						$countPostsByCat = Category::countPostsByCategory();
						
						return $countPostsByCat;
					});
				}
			}
			view()->share('countPostsByCat', $countPostsByCat);
			
			// Get the 100 most populate Cities
			$limit = 100;
			if (config('settings.list.count_cities_listings')) {
				$cacheId = config('country.code') . '.cities.withCountPosts.take.' . $limit;
				$cities = Cache::remember($cacheId, $this->cacheExpiration, function () use ($limit) {
					return City::currentCountry()->withCount('posts')->take($limit)->orderBy('population', 'DESC')->orderBy('name')->get();
				});
			} else {
				$cacheId = config('country.code') . '.cities.take.' . $limit;
				$cities = Cache::remember($cacheId, $this->cacheExpiration, function () use ($limit) {
					return City::currentCountry()->take($limit)->orderBy('population', 'DESC')->orderBy('name')->get();
				});
			}
			view()->share('cities', $cities);
			
			// Get Date Ranges
			$dates = ArrayHelper::toObject([
				'2'  => '24 ' . t('hours'),
				'4'  => '3 ' . t('days'),
				'8'  => '7 ' . t('days'),
				'31' => '30 ' . t('days'),
			]);
			$this->dates = $dates;
			view()->share('dates', $dates);
		}
		// END - LEFT MENU VARS
		
		
		if (config('settings.single.show_listing_types')) {
			// Get Listing Types
			$cacheId = 'postTypes.all.' . config('app.locale');
			$postTypes = Cache::remember($cacheId, $this->cacheExpiration, function () {
				return PostType::orderBy('lft')->get();
			});
			view()->share('postTypes', $postTypes);
		}
		
		
		// Get the Country first Administrative Division
		$cacheId = config('country.code') . '.subAdmin1s.all';
		$modalAdmins = Cache::remember($cacheId, $this->cacheExpiration, function () {
			return SubAdmin1::currentCountry()->orderBy('name')->get(['code', 'name'])->keyBy('code');
		});
		view()->share('modalAdmins', $modalAdmins);
		
		
		// Get Distance Range
		$distanceRange = [];
		if (config('settings.list.cities_extended_searches')) {
			config()->set('distance.distanceRange.min', 0);
			config()->set('distance.distanceRange.max', config('settings.list.search_distance_max', 500));
			config()->set('distance.distanceRange.interval', config('settings.list.search_distance_interval', 150));
			$distanceRange = DistanceHelper::distanceRange();
			
			// Format the Array for the OrderBy SelectBox
			$defaultDistance = config('settings.list.search_distance_default', 100);
			$distanceRange = collect($distanceRange)->mapWithKeys(function ($item, $key) use ($defaultDistance) {
				return [
					$key => [
						'condition'  => (isset($this->city) && !empty($this->city)),
						'isSelected' => (request()->get('distance', $defaultDistance) == $item),
						'url'        => qsUrl(request()->url(), array_merge(request()->except('distance'), ['distance' => $item]), null, false),
						'label'      => t('around_x_distance', ['distance' => $item, 'unit' => getDistanceUnit()]),
					],
				];
			})->toArray();
		}
		
		// OrderBy SelectBox Options
		$orderByArray = [
			[
				'condition'  => true,
				'isSelected' => false,
				'url'        => qsUrl(request()->url(), request()->except(['orderBy', 'distance']), null, false),
				'label'      => t('Sort by'),
			],
			[
				'condition'  => true,
				'isSelected' => (request()->get('orderBy') == 'priceAsc'),
				'url'        => qsUrl(request()->url(), array_merge(request()->except('orderBy'), ['orderBy' => 'priceAsc']), null, false),
				'label'      => t('price_low_to_high'),
			],
			[
				'condition'  => true,
				'isSelected' => (request()->get('orderBy') == 'priceDesc'),
				'url'        => qsUrl(request()->url(), array_merge(request()->except('orderBy'), ['orderBy' => 'priceDesc']), null, false),
				'label'      => t('price_high_to_low'),
			],
			[
				'condition'  => request()->filled('q'),
				'isSelected' => (request()->get('orderBy') == 'relevance'),
				'url'        => qsUrl(request()->url(), array_merge(request()->except('orderBy'), ['orderBy' => 'relevance']), null, false),
				'label'      => t('Relevance'),
			],
			[
				'condition'  => true,
				'isSelected' => (request()->get('orderBy') == 'date'),
				'url'        => qsUrl(request()->url(), array_merge(request()->except('orderBy'), ['orderBy' => 'date']), null, false),
				'label'      => t('Date'),
			],
			[
				'condition'  => config('plugins.reviews.installed'),
				'isSelected' => (request()->get('orderBy') == 'rating'),
				'url'        => qsUrl(request()->url(), array_merge(request()->except('orderBy'), ['orderBy' => 'rating']), null, false),
				'label'      => trans('reviews::messages.Rating'),
			],
		];
		$orderByArray = array_merge($orderByArray, $distanceRange);
		view()->share('orderByArray', $orderByArray);
		
		// Display Mode Array
		$displayModesArray = [
			'make-grid'    => [
				'icon' => 'fas fa-th-large',
				'url'  => qsUrl(request()->url(), array_merge(request()->except('display'), ['display' => 'grid']), null, false),
			],
			'make-list'    => [
				'icon' => 'fas fa-th-list',
				'url'  => qsUrl(request()->url(), array_merge(request()->except('display'), ['display' => 'list']), null, false),
			],
			'make-compact' => [
				'icon' => 'fas fa-bars',
				'url'  => qsUrl(request()->url(), array_merge(request()->except('display'), ['display' => 'compact']), null, false),
			],
		];
		view()->share('displayModesArray', $displayModesArray);
	}
}
