<?php

namespace Modules\ManajemenMahasiswa\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Badge extends Model
{
    protected $table = 'mk_badges';

    protected $fillable = [
        'name',
        'description',
        'icon_path',
        'required_xp',
    ];

    public function userBadges(): HasMany
    {
        return $this->hasMany(UserBadge::class, 'badge_id');
    }
}
