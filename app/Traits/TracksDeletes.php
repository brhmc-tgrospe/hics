<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Model;

trait TracksDeletes
{
    /**
     * Boot the trait.
     */
    protected static function bootTracksDeletes()
    {
        static::deleting(function (Model $model) {
            if (in_array(\Illuminate\Database\Eloquent\SoftDeletes::class, class_uses_recursive($model))) {
                if (!$model->isForceDeleting() && auth()->check()) {
                    $model->deleted_by = auth()->id();
                    $model->saveQuietly();
                }
            }
        });

        static::restoring(function (Model $model) {
            if (in_array(\Illuminate\Database\Eloquent\SoftDeletes::class, class_uses_recursive($model))) {
                $model->deleted_by = null;
            }
        });
    }

    /**
     * Get the user who deleted the record.
     */
    public function deleter()
    {
        return $this->belongsTo(\App\Models\User::class, 'deleted_by');
    }
}
