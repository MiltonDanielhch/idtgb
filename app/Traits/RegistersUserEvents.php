<?php

namespace App\Traits;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

trait RegistersUserEvents
{
    protected static function bootRegistersUserEvents()
    {
        static::creating(function ($model) {
            if (Auth::check()) {
                $user = Auth::user();
                $model->registerUser_id = $user->id;
                $model->registerRole = $user->role->name;
            }
        });

        static::deleting(function ($model) {
            if (Auth::check()) {
                $user = Auth::user();
                $model->deleteUser_id = $user->id;
                $model->deleteRole = $user->role->name;
                $model->deleteObservation = request()->input('deleteObservation');

                $model->save();
            }
        });
    }
}
