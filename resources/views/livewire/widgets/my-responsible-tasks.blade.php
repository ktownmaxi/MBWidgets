<div class="!py-0 !px-0">
    <div>
        <h2 class="text-gray-400 text-xl">{{ __('My Responsible Tasks') }}</h2>
    </div>
    <div class="w-full" x-data="{formatter: @js(resolve_static(\FluxErp\Models\Ticket::class, 'typeScriptAttributes'))}">
        <div class="overflow-auto">
        @foreach($tasks as $task)
            <x-list-item :item="$task">
                <x-slot:avatar>
                    {!! $task->state->badge() !!}
                </x-slot:avatar>
                <x-slot:sub-value>
                    @if($task->due_date)
                        <x-badge
                            :color="$task->due_date?->diffInDays(now(), false) > 0
                                ? 'negative'
                                : ($task->due_date?->diffInDays(now(), false) === 0 ? 'warning' : 'positive')
                            "
                            :label="__('Due At') . ' ' .$task->due_date?->locale(app()->getLocale())->isoFormat('L')"
                        />
                    @endif
                    @foreach($task->users as $user)
                        <x-badge
                            :color="$user->id === auth()->id() ? 'primary' : 'secondary'"
                            :label="$user->name"
                        />
                    @endforeach
                </x-slot:sub-value>
                <x-slot:actions>
                    <x-button
                        icon="clock"
                        x-on:click="
                            $dispatch(
                                'start-time-tracking',
                                {
                                    trackable_type: 'FluxErp\\\Models\\\Task',
                                    trackable_id: {{ $task->id }},
                                    name: '{{ $task->name }}',
                                    description: {{ json_encode($task->description) }}
                                }
                            )"
                        >
                        <div class="hidden sm:block">{{ __('Track Time') }}</div>
                    </x-button>
                    <x-button icon="eye" wire:navigate :href="route('tasks.id', $task->id)">
                        <div class="hidden sm:block">{{ __('View') }}</div>
                    </x-button>
                </x-slot:actions>
            </x-list-item>
        @endforeach
        </div>
    </div>
</div>
