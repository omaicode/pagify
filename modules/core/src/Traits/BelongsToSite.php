<?php

namespace Pagify\Core\Traits;

use Illuminate\Database\Eloquent\Builder;
use Pagify\Core\Support\SiteContext;

trait BelongsToSite
{
    public static function bootBelongsToSite(): void
    {
        static::creating(static function ($model): void {
            if (! isset($model->site_id)) {
                $siteId = app(SiteContext::class)->siteId();

                if ($siteId !== null) {
                    $model->site_id = $siteId;
                }
            }
        });

        static::addGlobalScope('site', static function (Builder $builder): void {
            $siteId = app(SiteContext::class)->siteId();

            if ($siteId !== null) {
                $column = $builder->getModel()->getTable() . '.site_id';
                $allowGlobal = property_exists($builder->getModel(), 'allowGlobalSiteRecords')
                    && $builder->getModel()->allowGlobalSiteRecords === true;

                if ($allowGlobal) {
                    $builder->where(static function (Builder $siteBuilder) use ($column, $siteId): void {
                        $siteBuilder
                            ->where($column, $siteId)
                            ->orWhereNull($column);
                    });

                    return;
                }

                $builder->where($column, $siteId);
            }
        });
    }
}
