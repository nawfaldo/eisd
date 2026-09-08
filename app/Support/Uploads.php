<?php

namespace App\Support;

use App\Models\Corruption;
use App\Models\Poisoned;
use App\Models\Sppg;
use App\Models\SppgCity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Every dataset row that has been uploaded, flattened into one describable list.
 *
 * The changelog shows all of them; the reports page shows one author's, since
 * anything already in a dataset is a report that was merged.
 */
class Uploads
{
    /** Filter keys, and the label each carries in a listing. */
    public const DATASETS = [
        'poisoning' => 'Poisoning',
        'corruption' => 'Corruption',
        'sppg' => 'SPPG province',
        'sppg-city' => 'SPPG city',
    ];

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public static function entries(?int $uploadedBy = null): Collection
    {
        return self::poisoned($uploadedBy)
            ->concat(self::corruption($uploadedBy))
            ->concat(self::sppg($uploadedBy))
            ->concat(self::cities($uploadedBy))
            ->sortByDesc('uploaded_at')
            ->values();
    }

    /**
     * @param  Builder<covariant \Illuminate\Database\Eloquent\Model>  $query
     * @return Builder<covariant \Illuminate\Database\Eloquent\Model>
     */
    private static function scoped(Builder $query, ?int $uploadedBy): Builder
    {
        return $query
            ->when($uploadedBy, fn (Builder $q) => $q->where('uploaded_by', $uploadedBy))
            ->orderByDesc('created_at');
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private static function poisoned(?int $uploadedBy): Collection
    {
        return self::scoped(Poisoned::query(), $uploadedBy)->get()
            ->map(fn (Poisoned $case) => [
                'dataset' => self::DATASETS['poisoning'],
                'title' => $case->place ?: ($case->regency ?: $case->province),
                'detail' => collect([
                    $case->place ? $case->regency : null,
                    $case->province,
                    $case->victims ? number_format($case->victims).' poisoned' : 'victims not counted',
                    $case->deaths > 0 ? number_format($case->deaths).' died' : null,
                    $case->occurred_on?->format('j M Y') ?: $case->occurred_raw,
                ])->filter()->implode(' · '),
                'region_id' => $case->region_id,
                'source_url' => $case->source_url,
                'uploaded_by' => $case->uploaded_by,
                'uploaded_at' => $case->created_at,
            ]);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private static function corruption(?int $uploadedBy): Collection
    {
        return self::scoped(Corruption::query(), $uploadedBy)->get()
            ->map(fn (Corruption $case) => [
                'dataset' => self::DATASETS['corruption'],
                'title' => $case->title,
                'detail' => collect([
                    $case->regency ?: ($case->province ?: 'National'),
                    $case->agency,
                    $case->status,
                    $case->reported_on?->format('j M Y') ?: $case->reported_raw,
                ])->filter()->implode(' · '),
                'region_id' => $case->region_id,
                'source_url' => $case->source_url,
                'uploaded_by' => $case->uploaded_by,
                'uploaded_at' => $case->created_at,
            ]);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private static function sppg(?int $uploadedBy): Collection
    {
        return self::scoped(Sppg::query(), $uploadedBy)->get()
            ->map(fn (Sppg $row) => [
                'dataset' => self::DATASETS['sppg'],
                'title' => $row->province,
                'detail' => collect([
                    number_format($row->outlets).' outlets',
                    'as of '.$row->as_of?->format('j M Y'),
                ])->filter()->implode(' · '),
                'region_id' => $row->region_id,
                'source_url' => $row->source_url,
                'uploaded_by' => $row->uploaded_by,
                'uploaded_at' => $row->created_at,
            ]);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private static function cities(?int $uploadedBy): Collection
    {
        return self::scoped(SppgCity::query(), $uploadedBy)->get()
            ->map(fn (SppgCity $row) => [
                'dataset' => self::DATASETS['sppg-city'],
                'title' => $row->isProvinceLevel() ? $row->city.' (province)' : $row->city,
                'detail' => collect([
                    $row->province,
                    number_format($row->outlets).' outlets',
                    $row->population ? 'pop '.number_format($row->population) : null,
                    'as of '.$row->as_of?->format('j M Y'),
                ])->filter()->implode(' · '),
                'region_id' => $row->region_id,
                'source_url' => $row->source_url,
                'uploaded_by' => $row->uploaded_by,
                'uploaded_at' => $row->created_at,
            ]);
    }
}
