<?php

namespace App\Models;

use App\Models\Traits\Tracked;
use Illuminate\Database\Eloquent\Model;

/**
 * Records the path and revision where a page was deleted.
 * @package App\Models
 */
class DeletedPage extends Model
{
	use Tracked;

	protected $fillable = [
        'revision_id',
        'path',
        'created_by',
        'updated_by',
        'created_at',
        'updated_at'
	];

	public function revision()
    {
        return $this->belongsTo(Revision::class);
    }
}
