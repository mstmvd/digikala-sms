<?php
/**
 * Created by PhpStorm.
 * User: mostafa
 * Date: 7/19/19
 * Time: 11:56 PM
 */

namespace App\Models;


use Illuminate\Database\Eloquent\Model;

class SmsLog extends Model
{
    public $timestamps = true;
    protected $table = 'sms_logs';
    protected $guarded = [];
    protected $fillable = ['sms_id', 'api', 'status', 'call_at'];

    public function sms()
    {
        return $this->belongsTo(Sms::class);
    }
}