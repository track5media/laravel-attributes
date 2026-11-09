<?php

declare(strict_types=1);

namespace Rinvex\Attributes\Tests\Stubs;

use Illuminate\Database\Eloquent\Model;
use Rinvex\Attributes\Traits\Attributable;

class User extends Model
{
    use Attributable;

    protected $fillable = ['name', 'email', 'password'];

    /**
     * Forget the per-class attribute cache so newly created attributes are picked up.
     */
    public static function flushEntityAttributes(): void
    {
        static::$entityAttributes = null;
    }
}
