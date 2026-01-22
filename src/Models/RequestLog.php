<?php

namespace GreeLogix\RequestLogger\Models;

use Illuminate\Database\Eloquent\Model;

class RequestLog extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'gl_request_logs';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'method',
        'path',
        'status_code',
        'ip',
        'user_id',
        'headers',
        'body',
        'response_body',
        'duration_ms',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'headers' => 'array',
        'body' => 'array',
        'response_body' => 'array',
    ];

    /**
     * Get the user that made the request.
     */
    public function user()
    {
        $userModel = config('auth.providers.users.model', \App\Models\User::class);
        return $this->belongsTo($userModel, 'user_id');
    }
}
