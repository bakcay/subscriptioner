<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 *
 *
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property string $subscription_id
 * @property string $status
 * @property string|null $start_date
 * @property string|null $end_date
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder|Subscription newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Subscription newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Subscription query()
 * @method static \Illuminate\Database\Eloquent\Builder|Subscription whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Subscription whereEndDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Subscription whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Subscription whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Subscription whereStartDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Subscription whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Subscription whereSubscriptionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Subscription whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Subscription whereUserId($value)
 * @property string $last_check
 * @method static \Illuminate\Database\Eloquent\Builder|Subscription whereLastCheck($value)
 * @mixin \Eloquent
 */
class Subscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'status',
        'start_date',
        'end_date',
        'last_check',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

}
