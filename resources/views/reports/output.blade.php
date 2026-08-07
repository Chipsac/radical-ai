<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Task Progress Report — {{ $generatedAt->format('j M Y') }}</title>
    <style>
        body { font-family: Georgia, 'Times New Roman', serif; margin: 40px auto; max-width: 800px; color: #1a1a1a; line-height: 1.5; }
        h1 { font-size: 26px; border-bottom: 3px solid #E0A44E; padding-bottom: 8px; }
        h2 { font-size: 20px; margin-top: 36px; color: #333; }
        .meta { color: #777; font-size: 13px; }
        .task { border: 1px solid #ddd; border-radius: 8px; padding: 14px 16px; margin: 12px 0; page-break-inside: avoid; }
        .task-head { display: flex; justify-content: space-between; gap: 12px; }
        .ref { font-family: monospace; font-size: 12px; color: #999; }
        .badge { font-size: 11px; font-weight: bold; text-transform: uppercase; padding: 2px 8px; border-radius: 999px; white-space: nowrap; }
        .updates { margin-top: 10px; border-top: 1px dashed #ddd; padding-top: 8px; }
        .update { font-size: 13px; margin: 6px 0; }
        .update .who { font-weight: bold; }
        .update .when { color: #999; font-size: 11px; }
        @media print { .task { border-color: #bbb; } }
    </style>
</head>
<body>
    <h1>Crewly360 — Task Progress Report</h1>
    <p class="meta">
        Generated {{ $generatedAt->format('l j F Y, H:i') }} by {{ $generatedBy }} ·
        {{ $includeCompleted ? 'Including' : 'Excluding' }} completed tasks ·
        latest {{ $updatesPerTask }} update(s) per task
    </p>

    @forelse ($tasksByMember as $member => $tasks)
        <h2>{{ $member }} — {{ $tasks->count() }} task(s)</h2>
        @foreach ($tasks as $task)
            @php $color = \App\Models\Task::STATUS_COLORS[$task->status] ?? '#8A8A8A'; @endphp
            <div class="task">
                <div class="task-head">
                    <div>
                        <span class="ref">{{ $task->reference }}</span><br>
                        <strong>{{ $task->title }}</strong>
                        @if ($task->category) <span style="color:{{ $task->category->color }}">● {{ $task->category->name }}</span> @endif
                    </div>
                    <div style="text-align:right">
                        <span class="badge" style="background: {{ $color }}22; color: {{ $color }}">{{ $task->statusLabel() }}</span><br>
                        @if ($task->due_date)
                            <span class="when">Due {{ $task->due_date->format('j M Y') }}</span>
                        @endif
                    </div>
                </div>
                @if ($task->progressUpdates->isNotEmpty())
                    <div class="updates">
                        @foreach ($task->progressUpdates->take($updatesPerTask) as $update)
                            <p class="update">
                                <span class="who">{{ $update->user?->name ?? 'Unknown' }}</span>
                                <span class="when">({{ $update->created_at->format('j M Y') }})</span> —
                                {{ $update->body }}
                            </p>
                        @endforeach
                    </div>
                @endif
            </div>
        @endforeach
    @empty
        <p>No tasks matched the selection.</p>
    @endforelse
</body>
</html>
