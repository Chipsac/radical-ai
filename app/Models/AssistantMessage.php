<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;

class AssistantMessage extends Model
{
    use BelongsToOrganization;

    protected $guarded = [];

    protected $casts = [
        'blocks' => 'array',
    ];

    public function conversation()
    {
        return $this->belongsTo(AssistantConversation::class, 'assistant_conversation_id');
    }

    /**
     * The plain text of this message, for display.
     *
     * A turn's `blocks` also carry tool calls and tool results, which are
     * machine plumbing rather than something to render in the transcript.
     */
    public function text(): string
    {
        return collect($this->blocks)
            ->where('type', 'text')
            ->pluck('text')
            ->implode("\n\n");
    }
}
