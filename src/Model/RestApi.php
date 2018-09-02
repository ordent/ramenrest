<?php 
namespace Ordent\RamenRest\Model;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Contracts\Support\Arrayable;
use ArrayAccess;
use JsonSerializable;


class RestApi implements Jsonable, Arrayable, ArrayAccess, JsonSerializable{
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    protected $primaryKey = 'id';
    protected $keyType = 'integer';
    protected $perPage = 15;
    public $incrementing = false;
    public $timestamps = false;
    protected $attributes = [];
    protected $original = [];
    protected $relations = [];
    protected $hidden = [];
    protected $visible = [];
    protected $appends = [];
    protected $fillable = [];
    protected $guarded = [];
    protected $dates = [];
    protected $dateFormat = 'Y-m-d';
    protected $casts = [];
    protected $touches = [];
    protected $observables = [];
    protected $with = [];
    protected $morphClass = [];
    protected $exists = false;
    protected $wasRecentlyCreated = false;
    protected $snakeAttributes = true;

    

}