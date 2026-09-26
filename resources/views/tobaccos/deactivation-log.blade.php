@extends('layouts.app')

@section('content')
<div class="page-header" style="display:flex; justify-content:space-between; align-items:center;">
    <div>
        <h1>Журнал деактивации табаков</h1>
        <p>История активаций и деактиваций</p>
    </div>
    <a href="{{ route('tobaccos.index') }}" class="btn btn-secondary">← Назад</a>
</div>

<div class="card">
    <table>
        <thead>
            <tr>
                <th>Табак</th>
                <th>Действие</th>
                <th>Кто</th>
                <th>Комментарий</th>
                <th>Когда</th>
            </tr>
        </thead>
        <tbody>
            @forelse($logs as $log)
                <tr>
                    <td>
                        <div style="font-size:12px; color:#999;">{{ $log->venueTobacco->tobacco->brand?->name }}</div>
                        <a href="{{ route('tobaccos.show', $log->venueTobacco->tobacco) }}" style="font-size:14px;">
                            {{ $log->venueTobacco->tobacco->name }}
                        </a>
                    </td>
                    <td>
                        @if($log->action === 'deactivated')
                            <span class="badge" style="background:#f8d7da; color:#721c24;">⛔ Деактивирован</span>
                        @else
                            <span class="badge" style="background:#d4edda; color:#155724;">✅ Активирован</span>
                        @endif
                    </td>
                    <td style="font-size:13px;">{{ $log->user?->name ?? '—' }}</td>
                    <td style="font-size:13px; color:#666;">{{ $log->comment ?? '—' }}</td>
                    <td style="font-size:13px; color:#999;">{{ $log->created_at->format('d.m.Y H:i') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" style="text-align:center; color:#999; padding:40px;">Действий пока нет</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div style="margin-top:16px;">{{ $logs->links() }}</div>
</div>
@endsection