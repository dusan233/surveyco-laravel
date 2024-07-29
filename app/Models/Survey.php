<?php

namespace App\Models;

use App\Enums\SurveyStatusEnum;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;

class Survey extends Model
{
    use HasFactory, SoftDeletes, HasUuids;

    public $incrementing = false;
    protected $keyType = 'string';
    protected $guarded = [];

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, "author_id");
    }

    public function pages(): HasMany
    {
        return $this->hasMany(SurveyPage::class);
    }

    public function collectors(): HasMany
    {
        return $this->hasMany(SurveyCollector::class);
    }

    public function questions(): HasManyThrough
    {
        return $this->hasManyThrough(Question::class, SurveyPage::class);
    }

    public function responses(): HasManyThrough
    {
        return $this->hasManyThrough(SurveyResponse::class, SurveyCollector::class);
    }

    public function status(): SurveyStatusEnum
    {
        if ($this->collectors()->count() === 0) {
            return SurveyStatusEnum::DRAFT;
        }

        if ($this->collectors()->where('status', 'open')->count() !== 0) {
            return SurveyStatusEnum::OPEN;
        }

        return SurveyStatusEnum::CLOSED;
    }
}
