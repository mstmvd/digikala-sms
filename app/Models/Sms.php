<?php
/**
 * Created by PhpStorm.
 * User: mostafa
 * Date: 7/19/19
 * Time: 11:56 PM
 */

namespace App\Models;


use Illuminate\Database\Eloquent\Model;

class Sms extends Model
{
    public $timestamps = true;
    protected $table = 'sms';
    protected $guarded = [];
    protected $fillable = ['number', 'body', 'api', 'status', 'call_at'];

}