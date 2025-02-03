<?php

namespace FluxErp\Livewire\Project;

use FluxErp\Actions\Task\DeleteTask;
use FluxErp\Htmlables\TabButton;
use FluxErp\Livewire\DataTables\TaskList as BaseTaskList;
use FluxErp\Livewire\Forms\TaskForm;
use FluxErp\Models\Task;
use FluxErp\Traits\Livewire\Actions;
use FluxErp\Traits\Livewire\WithTabs;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Renderless;
use Spatie\Permission\Exceptions\UnauthorizedException;
use TeamNiftyGmbH\DataTable\Htmlables\DataTableButton;

class ProjectTaskList extends BaseTaskList
{
    use Actions, WithTabs;

    protected string $view = 'flux::livewire.project.project-task-list';

    public string $taskTab = 'task.general';

    public TaskForm $task;

    public array $availableStates = [];

    public ?int $projectId;

    public bool $hasNoRedirect = true;

    public function mount(): void
    {
        parent::mount();

        $this->task->project_id = $this->projectId;
        $this->task->additionalColumns = array_fill_keys(
            resolve_static(Task::class, 'additionalColumnsQuery')->pluck('name')?->toArray() ?? [],
            null
        );

        $this->availableStates = app(Task::class)->getStatesFor('state')
            ->map(function (string $state) {
                return [
                    'label' => __(Str::headline($state)),
                    'name' => $state,
                ];
            })
            ->toArray();
    }

    #[Renderless]
    protected function getTableActions(): array
    {
        return [
            DataTableButton::make()
                ->label(__('New'))
                ->color('primary')
                ->attributes([
                    'x-on:click' => '$wire.edit()',
                ]),
        ];
    }

    #[Renderless]
    public function getTabs(): array
    {
        return [
            TabButton::make('task.general')->label(__('General')),
            TabButton::make('task.comments')->label(__('Comments'))
                ->attributes([
                    'x-bind:disabled' => '! $wire.task.id',
                ]),
            TabButton::make('task.media')->label(__('Media'))
                ->attributes([
                    'x-bind:disabled' => '! $wire.task.id',
                ]),
        ];
    }

    public function edit(Task $task): void
    {
        $this->reset('taskTab');
        $task->project_id = $this->projectId;
        $this->task->reset();
        $this->task->fill($task);
        $this->task->users = $task->users()->pluck('users.id')->toArray();
        $this->task->additionalColumns = array_intersect_key(
            $task->toArray(),
            array_fill_keys(
                $task->additionalColumns()->pluck('name')?->toArray() ?? [],
                null
            )
        );

        $this->js(<<<'JS'
            $openModal('task-form-modal');
        JS);
    }

    #[Renderless]
    public function save(): bool
    {
        try {
            $this->task->save();
        } catch (ValidationException|UnauthorizedException $e) {
            exception_to_notifications($e, $this);

            return false;
        }

        $this->loadData();

        return true;
    }

    public function delete(): bool
    {
        try {
            DeleteTask::make($this->task->toArray())
                ->checkPermission()
                ->validate()
                ->execute();
        } catch (ValidationException|UnauthorizedException $e) {
            exception_to_notifications($e, $this);

            return false;
        }

        $this->loadData();

        return true;
    }

    public function updatedTaskTab(): void
    {
        $this->forceRender();
    }
}
