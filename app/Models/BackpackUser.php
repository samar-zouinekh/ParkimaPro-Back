<?php

namespace App\Models;

use App\User;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;

class BackpackUser extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;


    protected $table = 'admins';

    /**
     * Send the password reset notification.
     *
     * @param string $token
     *
     * @return void
     */

    /**
     * Get the e-mail address where password reset links are sent.
     *
     * @return string
     */
    public function getEmailForPasswordReset()
    {
        return $this->email;
    }

    public function parkings()
    {
        return $this->belongsToMany('App\Models\Parking', 'admin_parking', 'admin_id', 'parking_id');
    }
    public function parkingsAgentsRelation()
    {
        return $this->belongsToMany('App\Models\Parking', 'agent_parking', 'agent_id', 'parking_id');
    }
    public function setPasswordAttribute($value){
        $this->attributes['password'] = bcrypt($value);
    }
}
