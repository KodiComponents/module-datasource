<?php

namespace KodiCMS\Datasource\Model;

use Illuminate\Database\Eloquent\Model;

class SectionFolder extends Model
{
    protected static function boot()
    {
        parent::boot();

        static::deleting(function (SectionFolder $folder) {
            $folder->sections()->update([
                'folder_id' => 0
            ]);
        });
    }

    /**
     * @var string
     */
    protected $table = 'datasource_folders';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'position',
    ];

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'name'     => 'string',
        'position' => 'integer',
    ];

    /**
     * @return mixed
     */
    public function sections()
    {
        return $this->hasMany(Section::class, 'folder_id', 'id');
    }
}
