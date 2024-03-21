<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Backpack\CRUD\app\Models\Traits\SpatieTranslatable\HasTranslations;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Parking extends Model
{
    // use HasTranslations;
    // use CrudTrait;

    /*
    |--------------------------------------------------------------------------
    | GLOBAL VARIABLES
    |--------------------------------------------------------------------------
    */

    protected $table = 'parkings';
    // protected $primaryKey = 'id';
    // public $timestamps = false;
    protected $guarded = ['id', 'pivot_price'];
    protected $casts = ['images' => 'array'];

    protected $fillable = [
        'gateway_id',
        'status_id',
        'name',
        'address',
        'parking_image',
        'all_places',
        'schedule',
        'latitude',
        'longitude',
        'price',
        'pay_mode',
        'description',
        'unpaid_grace_period',
        'paid_grace_period',
        'promotion_id',
        'automated',
        'images',
        'parking_express_image',
        'tariff_image',
        'tariff_description',
        'country_id',
        'state_id',
        'city_id',
        'price_type',
        'latitude_start',
        'longitude_start',
        'latitude_end',
        'longitude_end',
        'pound_location',
        'contact_agent',
    ];
    // protected $hidden = [];
    // protected $dates = [];
    protected $translatable = ['name', 'address', 'description', 'schedule'];

    /*
    |--------------------------------------------------------------------------
    | FUNCTIONS
    |--------------------------------------------------------------------------
    */

    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */

    public function admins()
    {
        return $this->belongsToMany('App\Models\BackpackUser', 'admin_parking', 'parking_id', 'admin_id');
    }

    public function agents()
    {
        return $this->belongsToMany('App\Models\BackpackUser', 'agent_parking', 'parking_id', 'agent_id');
    }
    public function gateway()
    {
        return $this->belongsTo('\App\Models\Gateway');
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPES
    |--------------------------------------------------------------------------
    */

    /*
    |--------------------------------------------------------------------------
    | ACCESSORS
    |--------------------------------------------------------------------------
    */

    /*
    |--------------------------------------------------------------------------
    | MUTATORS
    |--------------------------------------------------------------------------
    */
    public function setParkingImageAttribute($value)
    {
        $attribute_name = "parking_image";
        $disk = config('backpack.base.root_disk_name'); // or use your own disk, defined in config/filesystems.php
        $destination_path = "public/uploads/folder_1/folder_2"; // path relative to the disk above

        // if the image was erased
        if ($value == null) {
            // delete the image from disk
            \Storage::disk($disk)->delete($this->{$attribute_name});

            // set null in the database column
            $this->attributes[$attribute_name] = null;
        }

        // if a base64 was sent, store it in the db
        if (Str::startsWith($value, 'data:image')) {
            // 0. Make the image
            $image = \Image::make($value)->encode('jpg', 90);
            // 1. Generate a filename.
            $filename = md5($value . time()) . '.jpg';
            // 2. Store the image on disk.
            \Storage::disk($disk)->put($destination_path . '/' . $filename, $image->stream());
            // 3. Save the public path to the database
            // but first, remove "public/" from the path, since we're pointing to it from the root folder
            // that way, what gets saved in the database is the user-accesible URL
            $public_destination_path = Str::replaceFirst('public/', '', $destination_path);
            $this->attributes[$attribute_name] = $public_destination_path . '/' . $filename;
        }
    }


    public function setParkingExpressImageAttribute($value)
    {
        $attribute_name = "parking_express_image";
        $disk = config('backpack.base.root_disk_name'); // or use your own disk, defined in config/filesystems.php
        $destination_path = "public/uploads/folder_1/folder_2"; // path relative to the disk above

        // if the image was erased
        if ($value == null) {
            // delete the image from disk
            \Storage::disk($disk)->delete($this->{$attribute_name});

            // set null in the database column
            $this->attributes[$attribute_name] = null;
        }

        // if a base64 was sent, store it in the db
        if (Str::startsWith($value, 'data:image')) {
            // 0. Make the image
            $image = \Image::make($value)->encode('jpg', 90);
            // 1. Generate a filename.
            $filename = md5($value . time()) . '.jpg';
            // 2. Store the image on disk.
            \Storage::disk($disk)->put($destination_path . '/' . $filename, $image->stream());
            // 3. Save the public path to the database
            // but first, remove "public/" from the path, since we're pointing to it from the root folder
            // that way, what gets saved in the database is the user-accesible URL
            $public_destination_path = Str::replaceFirst('public/', '', $destination_path);
            $this->attributes[$attribute_name] = $public_destination_path . '/' . $filename;
        }
    }


    public function setTariffImageAttribute($value)
    {
        $attribute_name = "tariff_image";
        $disk = config('backpack.base.root_disk_name'); // or use your own disk, defined in config/filesystems.php
        $destination_path = "public/uploads/folder_1/folder_2"; // path relative to the disk above

        // if the image was erased
        if ($value == null) {
            // delete the image from disk
            \Storage::disk($disk)->delete($this->{$attribute_name});

            // set null in the database column
            $this->attributes[$attribute_name] = null;
        }

        // if a base64 was sent, store it in the db
        if (Str::startsWith($value, 'data:image')) {
            // 0. Make the image
            $image = \Image::make($value)->encode('jpg', 90);
            // 1. Generate a filename.
            $filename = md5($value . time()) . '.jpg';
            // 2. Store the image on disk.
            \Storage::disk($disk)->put($destination_path . '/' . $filename, $image->stream());
            // 3. Save the public path to the database
            // but first, remove "public/" from the path, since we're pointing to it from the root folder
            // that way, what gets saved in the database is the user-accesible URL
            $public_destination_path = Str::replaceFirst('public/', '', $destination_path);
            $this->attributes[$attribute_name] = $public_destination_path . '/' . $filename;
        }
    }


    public function setImagesAttribute($value)
    {
        $attribute_name = "images";
        $disk = config('backpack.base.root_disk_name'); // or use your own disk, defined in config/filesystems.php
        $destination_path = "public/uploads/folder_1/folder_2"; // path relative to the disk above

        $this->uploadMultipleFilesToDisk($value, $attribute_name, $disk, $destination_path);
    }

}
