<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;

/**
 * An audit row per write the assistant attempted, kept separately from the
 * conversation.
 *
 * "Why did this deal move to Won?" has to be answerable after the user has
 * deleted the chat it happened in — so the record cannot live only in the
 * transcript. Refusals are recorded too: an attempt that authorisation blocked
 * is exactly the thing worth seeing later.
 */
class AssistantAction extends Model
{
    use BelongsToOrganization;

    public const EXECUTED = 'executed';

    public const REFUSED = 'refused';

    public const FAILED = 'failed';

    protected $guarded = [];

    protected $casts = [
        'arguments' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
