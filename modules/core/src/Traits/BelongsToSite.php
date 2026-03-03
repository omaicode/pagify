<?php

namespace Modules\Core\Traits;

use Illuminate\Database\Eloquent\Builder;
use Modules\Core\Support\SiteContext;

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
                $builder->where($builder->getModel()->getTable() . '.site_id', $siteId);
            }
        });
    }
}
