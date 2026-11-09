<?php

namespace App\Models;

use App\Models\Concerns\HasPublicUuid;
use Database\Factories\FollowFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * One member following a creator. Following is one-way and needs no approval.
 *
 * @property int $id
 * @property string $uuid
 * @property int $follower_id
 * @property int $followee_id
 */
class Follow extends Pivot
{
    /** @use HasFactory<FollowFactory> */
    use HasFactory, HasPublicUuid;

    protected $table = 'follows';

    public $incrementing = true;
}
