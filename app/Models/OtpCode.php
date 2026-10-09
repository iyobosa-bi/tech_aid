<?php

namespace App\Models;

use App\Enums\OtpPurpose;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A one-time code, for signing in (Flow 1) or resetting a password. `code` is a hash, never the
 * digits; `attempts` counts wrong guesses. Issued and checked only through OtpService.
 */
class OtpCode extends Model
{
    protected $fillable = ['user_id', 'purpose', 'code', 'attempts', 'expires_at', 'used_at'];

    protected $hidden = ['code'];

    protected function casts(): array
    {
        return [
            'purpose' => OtpPurpose::class,
            'attempts' => 'integer',
            'expires_at' => 'datetime',
            'used_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
