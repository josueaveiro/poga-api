<?php

namespace Raffles\Models\Traits;

use Raffles\Notifications\ResetPasswordNotification;

trait UserTrait
{
    /**
     * Get the user's last name.
     *
     * @param  string $value
     * @return string
     */
    public function getLastNameAttribute($value)
    {
        return $value === null ? '' : $value;
    }

    /**
     * Get the user's name.
     *
     * @return string
     */
    public function getNameAttribute()
    {
        return $this->attributes['name'] = trim($this->first_name.' '.$this->last_name);
    }

    /**
     * Set the user's password.
     *
     * @param  string $value
     * @return string
     */
    public function setPasswordAttribute($value)
    {
        if ('' !== $value) {
            $this->attributes['password'] = bcrypt($value);
        }
    }

    /**
     * Send the password reset notification.
     *
     * @param  string $token
     * @return void
     */
    public function sendPasswordResetNotification($token)
    {
        $this->notify(new ResetPasswordNotification($token));
    }
}
