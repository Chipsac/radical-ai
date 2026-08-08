<x-app-layout>
    <x-slot name="title">Assistant</x-slot>

    <div class="mx-auto flex h-[calc(100vh-9rem)] max-w-3xl flex-col"
         x-data="assistant({
             conversationId: {{ $conversation?->id ?? 'null' }},
             sendUrl: '{{ route('assistant.send') }}',
             confirmUrl: '{{ route('assistant.confirm') }}',
         })">

        <div class="mb-4 flex items-center justify-between">
            <div>
                <h1 class="font-display text-2xl font-semibold text-gray-900 dark:text-white">Assistant</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Ask for anything you could do yourself. It works with your access, not more.
                </p>
            </div>
        </div>

        <div class="card flex-1 overflow-y-auto p-5" x-ref="log">
            @forelse ($messages as $message)
                @if ($message->text() !== '')
                    <div class="mb-4 flex {{ $message->role === 'user' ? 'justify-end' : 'justify-start' }}">
                        <div class="max-w-[80%] rounded-2xl px-4 py-2.5 text-sm
                            {{ $message->role === 'user'
                                ? 'bg-gold text-ink-950'
                                : 'bg-gray-100 text-gray-800 dark:bg-ink-800 dark:text-gray-100' }}">
                            {!! nl2br(e($message->text())) !!}
                        </div>
                    </div>
                @endif
            @empty
                <div class="flex h-full flex-col items-center justify-center text-center">
                    <p class="text-sm font-medium text-gray-700 dark:text-gray-200">What would you like to do?</p>
                    <div class="mt-4 grid gap-2 text-left">
                        @foreach ([
                            "What's on my plate today?",
                            'Which deals are in proposal?',
                            "Who's off next week?",
                        ] as $example)
                            <button type="button" @click="prompt = @js($example); send()"
                                    class="rounded-lg border border-gray-200 px-3 py-2 text-sm text-gray-600 transition hover:border-gold hover:text-gold dark:border-ink-700 dark:text-gray-300">
                                {{ $example }}
                            </button>
                        @endforeach
                    </div>
                </div>
            @endforelse

            <template x-for="turn in turns" :key="turn.key">
                <div class="mb-4 flex" :class="turn.role === 'user' ? 'justify-end' : 'justify-start'">
                    <div class="max-w-[80%] rounded-2xl px-4 py-2.5 text-sm"
                         :class="turn.role === 'user'
                            ? 'bg-gold text-ink-950'
                            : 'bg-gray-100 text-gray-800 dark:bg-ink-800 dark:text-gray-100'"
                         x-text="turn.text"></div>
                </div>
            </template>

            <div x-show="busy" x-cloak class="mb-4 flex justify-start">
                <div class="rounded-2xl bg-gray-100 px-4 py-2.5 text-sm text-gray-500 dark:bg-ink-800 dark:text-gray-400">
                    Working…
                </div>
            </div>

            {{-- Nothing is written until this is answered. The description names
                 the actual records, so the user is confirming a change they can
                 recognise rather than approving an opaque tool call. --}}
            <div x-show="pending" x-cloak class="mb-4">
                <div class="rounded-xl border-2 border-gold/40 bg-gold/10 p-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-gold">Confirm this change</p>
                    <p class="mt-1 text-sm text-gray-800 dark:text-gray-100" x-text="pending?.description"></p>
                    <div class="mt-3 flex gap-2">
                        <button type="button" @click="decide(true)" class="btn-gold px-3 py-1.5 text-sm">Do it</button>
                        <button type="button" @click="decide(false)"
                                class="rounded-lg border border-gray-300 px-3 py-1.5 text-sm text-gray-600 dark:border-ink-600 dark:text-gray-300">
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <form @submit.prevent="send()" class="mt-4 flex gap-2">
            <input type="text" x-model="prompt" :disabled="busy || pending"
                   placeholder="Ask anything…" autocomplete="off"
                   class="input-app flex-1">
            <button type="submit" class="btn-gold px-5" :disabled="busy || pending || !prompt.trim()">Send</button>
        </form>

        <p class="mt-2 text-center text-xs text-gray-400 dark:text-gray-500">
            The assistant can be wrong. Anything that changes your data asks first.
        </p>
    </div>
</x-app-layout>
