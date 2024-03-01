<?php

namespace FluxErp\Listeners;

use FluxErp\Models\Snapshot;

class SnapshotEventSubscriber
{
    /**
     * Handle incoming events.
     */
    public function createSnapshot($event): void
    {
        $snapshot = app(Snapshot::class)->query()
            ->where('model_type', app($event->model)->getMorphClass())
            ->where('model_id', $event->model->id)
            ->exists();

        if (! $snapshot) {
            $snapshot = app(Snapshot::class);
            $snapshot->model_type = app($event->model)->getMorhClass();
            $snapshot->model_id = $event->model->id;
            $snapshot->snapshot = method_exists($event->model, 'relationships') ?
                $event->model->with(array_keys($event->model->relationships())) : $event->model;
            $snapshot->save();
        }
    }

    /**
     * Register the listeners for the subscriber.
     * E.g. CommentCreated::class => 'createSnapshot'
     *
     * @param  \Illuminate\Events\Dispatcher  $events
     */
    public function subscribe($events): array
    {
        return [

        ];
    }
}
