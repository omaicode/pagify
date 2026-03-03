<?php

namespace Modules\Core\Support;

use Modules\Core\Models\Site;

class SiteContext
{
    private ?Site $site = null;

    public function setSite(?Site $site): void
    {
        $this->site = $site;
    }

    public function site(): ?Site
    {
        return $this->site;
    }

    public function siteId(): ?int
    {
        return $this->site?->id;
    }

    public function hasSite(): bool
    {
        return $this->site !== null;
    }
}
