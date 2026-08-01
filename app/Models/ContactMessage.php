<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * An enquiry from the public landing page.
 *
 * Deliberately NOT tenant-scoped: these arrive before the sender has an
 * organisation, so there is no tenant to attribute them to.
 */
class ContactMessage extends Model
{
    protected $guarded = [];

    protected $casts = [
        'handled_at' => 'datetime',
    ];

    public function isHandled(): bool
    {
        return $this->handled_at !== null;
    }

    public static function topics(): array
    {
        return [
            'general' => 'General enquiry',
            'demo' => 'Book a demo',
            'pricing' => 'Pricing question',
            'migration' => 'Moving from another system',
            'support' => 'Support',
        ];
    }
}
