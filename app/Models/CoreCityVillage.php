<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CoreCityVillage extends Model
{
	protected $table = 'core_city_village';

	protected $primaryKey = 'id';

	public $timestamps = false;

	protected $fillable = [
		'state_id',
		'district_id',
		'division_name',
		'city_village_name',
		'city_village_code',
		'pincode',
		'longitude',
		'latitude',
		'is_active',
		'effective_date',
	];

	protected $casts = [
		'effective_date' => 'date',
		'is_active'      => 'boolean',
	];

	public function state()
	{
		return $this->belongsTo(CoreState::class, 'state_id');
	}
}
