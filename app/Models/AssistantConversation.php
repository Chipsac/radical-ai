<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;

class AssistantConversation extends Model
{
    use BelongsToOrganization;

    protected $guarded = [];

    protected $casts = [
        'last_message_at' => 'datetime',
    ];

    public function messages()
    {
        return $this->hasMany(AssistantMessage::class)->orderBy('id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * A conversation belongs to one person, not to the workspace.
     *
     * The tenant scope already keeps other organisations out. This keeps
     * colleagues out of each other's chats: the assistant answers as the person
     * asking, so a transcript can contain anything they are allowed to see —
     * salaries, someone's leave, a deal they own. A manager who wants that
     * information can ask their own assistant and get it through the same
     * authorisation checks.
     */
    public function scopeOwnedBy($query, User $user)
    {
        return $query->where('user_id', $user->id);
    }
}
