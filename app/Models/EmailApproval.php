<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailApproval extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'contact_list_name', 'message', 'topic_name', 'subject', 'status', 'status'
    ];
}
