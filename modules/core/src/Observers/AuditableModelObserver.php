<?php

namespace Modules\Core\Observers;

use Illuminate\Database\Eloquent\Model;
use Modules\Core\Services\AuditLogger;

class AuditableModelObserver
{
    public function __construct(private readonly AuditLogger $auditLogger)
    {
    }

    public function created(Model $model): void
    {
        $this->auditLogger->log(
            action: 'model.created',
            entityType: $model::class,
            entityId: $model->getKey(),
            metadata: [
                'model' => $model::class,
                'table' => $model->getTable(),
                'attributes' => $model->getAttributes(),
            ],
        );
    }

    public function updated(Model $model): void
    {
        $changes = $model->getChanges();
        unset($changes['updated_at']);

        if ($changes === []) {
            return;
        }

        $original = [];

        foreach (array_keys($changes) as $attribute) {
            $original[$attribute] = $model->getOriginal($attribute);
        }

        $this->auditLogger->log(
            action: 'model.updated',
            entityType: $model::class,
            entityId: $model->getKey(),
            metadata: [
                'model' => $model::class,
                'table' => $model->getTable(),
                'changes' => $changes,
                'original' => $original,
            ],
        );
    }

    public function deleted(Model $model): void
    {
        $this->auditLogger->log(
            action: 'model.deleted',
            entityType: $model::class,
            entityId: $model->getKey(),
            metadata: [
                'model' => $model::class,
                'table' => $model->getTable(),
                'attributes' => $model->getOriginal(),
            ],
        );
    }
}
