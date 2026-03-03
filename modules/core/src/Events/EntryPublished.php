<?php

namespace Modules\Core\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class EntryPublished
{
    use Dispatchable;
    use SerializesModels;

    /**
     * @param array<string, mixed> $payload
     */
    public function __construct(
        public readonly string $entryType,
        public readonly string|int $entryId,
        public readonly array $payload = [],
    ) {
    }
}
