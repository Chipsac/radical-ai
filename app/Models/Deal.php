<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;

class Deal extends Model
{
    use BelongsToOrganization;

    public const STAGES = ['lead', 'qualified', 'proposal', 'won', 'lost'];

    protected $guarded = [];

    protected $casts = [
        'expected_close_date' => 'date',
        'value' => 'decimal:2',
    ];

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function activities()
    {
        return $this->hasMany(Activity::class, 'subject_id')->where('subject_type', self::class);
    }

    public static function stageLabel(string $stage): string
    {
        return ucfirst($stage);
    }
}
