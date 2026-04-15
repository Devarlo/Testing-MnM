<?php

namespace Modules\ManajemenMahasiswa\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Vote extends Model
{
    protected $table = 'mk_votes';

    protected $fillable = [
        'user_id',
        'voteable_type',
        'voteable_id',
        'value',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    public function voteable(): MorphTo
    {
        return $this->morphTo();
    }
}
