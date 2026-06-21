<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\Syncable;

class Notification extends Model
{
    use Syncable;

    protected $fillable = ['user_id','title','message','type','is_read'];
}