@extends('layouts.app')

@section('content')
@php
$maxDay  = max(array_values($dayCounts) ?: [1]);
$maxSlot = max(array_values($slotCounts) ?: [1]);
@endphp

<div class="page-header" style="display:flex; justify-content:space-between; align-items:center;">
    <div>
        <div style="font-size:13px; color:#999; margin-bottom:4px;">Бренд</div>
        <h1>{{ $brand->name }}</h1>
        @if($brand->country)
            <p style="color:#999; font-size:14px; margin-top:-4px;">{{ $brand->country }}</p>
        @endif
    </div>
    <a href="{{ route('tobaccos.index') }}" class="btn btn-secondary">← Назад</a>
</div>

<div style="display:grid; grid-template-columns:1fr 300px; gap:20px;">

    <div>
        {{-- Описание --}}
        @if($brand->description)
        <div class="card">
            <p style="font-size:14px; color:#444; line-height:1.6;">{{ $brand->description }}</p>
        </div>
        @endif

        {{-- Статистика --}}
        <div class="stats-grid" style="grid-template-columns:repeat(3,1fr);">
            <div class="stat-card">
                <div class="label">Всего заказов</div>
                <div class="value">{{ $totalOrders }}</div>
            </div>
            <div class="stat-card">
                <div class="label">За 30 дней</div>
                <div class="value" style="color:#4caf74;">{{ $last30 }}</div>
            </div>
            <div class="stat-card">
                <div class="label">Переделок</div>
                <div class="value" style="color:#c95a2a;">{{ $redoOrders }}</div>
            </div>
        </div>

        {{-- Топ табаков --}}
        @if($topTobaccos->count())
        <div class="card">
            <h3 style="margin-bottom:16px;">Топ табаков</h3>
            @foreach($topTobaccos as $item)
                <div style="display:flex; justify-content:space-between; align-items:center; padding:10px 0; border-bottom:1px solid #f0f0f0;">
                    <div>
                        <a href="{{ $item->tobacco ? route('tobaccos.show', $item->tobacco) : '#' }}" style="font-size:14px; font-weight:500;">
                            {{ $item->tobacco?->name ?? '—' }}
                        </a>
                        <div style="margin-top:2px;">
                            @foreach($item->tobacco?->flavorTags ?? [] as $tag)
                                <span style="background:#f0f0f0; color:#555; padding:1px 6px; border-radius:8px; font-size:11px;">{{ $tag->name }}</span>
                            @endforeach
                        </div>
                    </div>
                    <strong>{{ $item->total }}</strong>
                </div>
            @endforeach
        </div>
        @endif

        {{-- Когда заказывают --}}
        <div class="card">
            <h3 style="margin-bottom:16px;">Когда заказывают</h3>
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:20px;">
                <div>
                    <div style="font-size:13px; color:#666; margin-bottom:12px;">По времени суток</div>
                    @foreach($timeSlots as $key => $slot)
                        <div style="margin-bottom:10px;">
                            <div style="display:flex; justify-content:space-between; font-size:13px; margin-bottom:4px;">
                                <span>{{ $slot['label'] }}</span>
                                <strong>{{ $slotCounts[$key] ?? 0 }}</strong>
                            </div>
                            <div style="background:#f0f0f0; border-radius:4px; height:8px;">
                                <div style="background:#c9a84c; height:100%; width:{{ $maxSlot > 0 ? round(($slotCounts[$key] ?? 0) / $maxSlot * 100) : 0 }}%;"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
                <div>
                    <div style="font-size:13px; color:#666; margin-bottom:12px;">По дням недели</div>
                    @foreach($dayCounts as $day => $count)
                        <div style="margin-bottom:10px;">
                            <div style="display:flex; justify-content:space-between; font-size:13px; margin-bottom:4px;">
                                <span>{{ $day }}</span>
                                <strong>{{ $count }}</strong>
                            </div>
                            <div style="background:#f0f0f0; border-radius:4px; height:8px;">
                                <div style="background:#4caf74; height:100%; width:{{ $maxDay > 0 ? round($count / $maxDay * 100) : 0 }}%;"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    {{-- Правая колонка --}}
    <div>
        <div class="card" style="position:sticky; top:20px;">
            <h3 style="margin-bottom:12px;">Информация</h3>
            <table style="width:100%;">
                <tr>
                    <td style="padding:8px 0; color:#666; font-size:13px;">Страна</td>
                    <td style="font-size:14px;">{{ $brand->country ?? '—' }}</td>
                </tr>
                <tr>
                    <td style="padding:8px 0; color:#666; font-size:13px;">В меню</td>
                    <td style="font-size:14px; color:#28a745;">{{ $totalInVenue }}</td>
                </tr>
                <tr>
                    <td style="padding:8px 0; color:#666; font-size:13px;">Деактивировано</td>
                    <td style="font-size:14px; color:#dc3545;">{{ $totalInactive }}</td>
                </tr>
                <tr>
                    <td style="padding:8px 0; color:#666; font-size:13px;">Всего вкусов</td>
                    <td style="font-size:14px;">{{ $brand->tobaccos->count() }}</td>
                </tr>
            </table>
        </div>
    </div>

</div>
@endsection