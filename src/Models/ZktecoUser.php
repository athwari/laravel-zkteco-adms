<?php

namespace Athwari\LaravelZktecoAdms\Models;

use Athwari\LaravelZktecoAdms\Database\Factories\ZktecoUserFactory;
use Athwari\LaravelZktecoAdms\Enums\UserPrivilege;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ZktecoUser extends Model
{
    use HasFactory;

    protected static function newFactory(): Factory
    {
        return ZktecoUserFactory::new();
    }

    /**
     * @var array<int, string>
     */
    protected $guarded = [];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'privilege' => UserPrivilege::class,
        'is_enabled' => 'boolean',
        'fingerprints' => 'array',
        'face_templates' => 'array',
    ];

    public function getTable(): string
    {
        return config('zkteco-adms.table_prefix', 'zkteco_').'users';
    }

    /** @return HasMany<Model, $this> */
    public function attendanceLogs(): HasMany
    {
        $model = config('zkteco-adms.models.attendance_log', ZktecoAttendanceLog::class);

        return $this->hasMany($model, 'pin', 'pin');
    }

    /**
     * Optional relationship to the host application's User model.
     *
     * @return BelongsTo<Model, $this>
     */
    public function appUser(): BelongsTo
    {
        $userModel = config('zkteco-adms.user_model', 'App\Models\User');

        return $this->belongsTo($userModel, 'app_user_id');
    }
}
