<?php

namespace App\Models;

use App\Enums\ReportStatus;
use App\Enums\ReportType;
use App\Support\Budget;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

/**
 * A submission proposing a row for one of the datasets, awaiting review.
 */
#[Fillable([
    'user_id', 'type', 'status', 'province', 'region_id', 'regency', 'title',
    'occurred_on', 'victims', 'deaths', 'outlets', 'amount', 'agency', 'source_url', 'note',
])]
class Report extends Model
{
    /** The report in one line: where, how big, and when. */
    public function detail(): string
    {
        return collect(match ($this->type) {
            ReportType::Poisoning => [
                $this->title ? $this->regency : null,
                $this->province,
                $this->victims !== null ? number_format($this->victims).' poisoned' : 'victims not counted',
                $this->deaths > 0 ? number_format($this->deaths).' died' : null,
                $this->occurred_on?->format('j M Y'),
            ],
            ReportType::Sppg => [
                $this->province,
                $this->outlets !== null ? number_format($this->outlets).' outlets' : null,
                $this->occurred_on ? 'as of '.$this->occurred_on->format('j M Y') : null,
            ],
            ReportType::Corruption => [
                $this->regency ?: ($this->province ?: 'National'),
                $this->agency,
                $this->amount !== null ? Budget::rupiah($this->amount) : null,
                $this->occurred_on?->format('j M Y'),
            ],
        })->filter()->implode(' · ');
    }

    public function isPending(): bool
    {
        return $this->status === ReportStatus::Pending;
    }

    /**
     * Accept the report: write it into the dataset it was filed against, credited
     * to whoever reported it, and record that it was taken.
     */
    public function merge(): Model
    {
        return DB::transaction(function (): Model {
            $row = match ($this->type) {
                ReportType::Poisoning => Poisoned::create([
                    'uploaded_by' => $this->user_id,
                    'province' => $this->province,
                    'region_id' => $this->region_id,
                    'regency' => $this->regency,
                    'place' => $this->title,
                    'occurred_on' => $this->occurred_on,
                    'victims' => $this->victims,
                    // The column counts deaths, so an unstated number is none reported.
                    'deaths' => $this->deaths ?? 0,
                    'source_url' => $this->source_url,
                ]),
                ReportType::Sppg => Sppg::create([
                    'uploaded_by' => $this->user_id,
                    'province' => $this->province,
                    'region_id' => $this->region_id,
                    'outlets' => $this->outlets,
                    'as_of' => $this->occurred_on,
                    'source_url' => $this->source_url,
                ]),
                ReportType::Corruption => Corruption::create([
                    'uploaded_by' => $this->user_id,
                    'title' => $this->title,
                    'province' => $this->province,
                    'region_id' => $this->region_id,
                    'regency' => $this->regency,
                    'amount' => $this->amount,
                    'agency' => $this->agency,
                    'reported_on' => $this->occurred_on,
                    'source_url' => $this->source_url,
                ]),
            };

            // A corruption case names its institutions; resolve them into the pivot
            // so the case is counted against every body, not just the first.
            if ($row instanceof Corruption) {
                $row->agencies()->sync(Agency::fromRaw($this->agency));
            }

            $this->update(['status' => ReportStatus::Merged]);

            return $row;
        });
    }

    public function decline(): void
    {
        $this->update(['status' => ReportStatus::Declined]);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => ReportType::class,
            'status' => ReportStatus::class,
            'occurred_on' => 'date',
            'victims' => 'integer',
            'deaths' => 'integer',
            'outlets' => 'integer',
            'amount' => 'integer',
        ];
    }
}
