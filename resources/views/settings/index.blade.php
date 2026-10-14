@extends('layouts.app')

@push('styles')
<link rel="stylesheet" href="https://cdn.ckeditor.com/ckeditor5/43.3.1/ckeditor5.css">
@endpush

@section('content')
<header>
    <div class="headerTop">
        <h1>Настройки</h1>

        @include('partials.shift-toggle')

        @role('admin')
        <a href="{{ route('settings.index') }}" class="headerUser">
            <img src="/assets/img/brands/mix.svg" alt="">
        </a>
        @else
        <a href="{{ route('wiki.index') }}" class="headerUser">
            <img src="/assets/img/brands/mix.svg" alt="">
        </a>
        @endrole
    </div>
</header>

<section class="settingsSection">
    <div class="item">
        <div class="itemInfo">
            <p>Общие настройки заведения</p>
            <span>Временные слоты для аналитики, доступ к аналитике</span>
        </div>
        <button type="button" onclick="openSheet('settingsTab1')" class="btnOpen">Открыть</button>
    </div>

    <div class="item">
        <div class="itemInfo">
            <p>Уведомления</p>
            <span>Напоминание об обслуживании, интервал обслуживания, уведомления об статусах заказа</span>
        </div>
        <button type="button" onclick="openSheet('settingsTab2')" class="btnOpen">Открыть</button>
    </div>

    <div class="item">
        <div class="itemInfo">
            <p>Пороги для гостей</p>
            <span>Пороги для статусов гостей, порог для "Давно не приходили"</span>
        </div>
        <button type="button" onclick="openSheet('settingsTab3')" class="btnOpen">Открыть</button>
    </div>

    <div class="item">
        <div class="itemInfo">
            <p>Мастера</p>
            <span>Создание и редактирование мастеров</span>
        </div>
        <button type="button" onclick="openSheet('settingsTab4')" class="btnOpen">Открыть</button>
    </div>

    <div class="item">
        <div class="itemInfo">
            <p>Типы столов</p>
            <span>Создать и редактировать категории столов</span>
        </div>
        <button type="button" onclick="openSheet('settingsTab5')" class="btnOpen">Открыть</button>
    </div>

    <div class="item">
        <div class="itemInfo">
            <p>Столы</p>
            <span>Создать и редактировать столы</span>
        </div>
        <button type="button" onclick="openSheet('settingsTab6')" class="btnOpen">Открыть</button>
    </div>

    <div class="item">
        <div class="itemInfo">
            <p>Журнал смены мастеров действий</p>
            <span>Кто, когда и что переставил в заказе</span>
        </div>
        <button type="button" onclick="openSheet('settingsTab7')" class="btnOpen">Открыть</button>
    </div>

    <div class="item">
        <div class="itemInfo">
            <p>Памятка</p>
            <span>Внутренняя база знаний заведения</span>
        </div>
        <button type="button" onclick="openSheet('settingsTab8')" class="btnOpen">Открыть</button>
    </div>
</section>

{{-- Общие настройки --}}
<div class="actionTab viewProductSameTab settingsTab1" id="settingsTab1">
    <div class="actionTabWrapper">
        <div class="actionTabHeader">
            <h3>Общие настройки заведения</h3>
            <div class="actionTabHeaderBtns">
                <button type="button" onclick="closeSheet('settingsTab1')" class="actionTabHeaderBtn --tabBtnClose">
                    <img src="/assets/img/close-icon.svg" alt="">
                </button>
            </div>
        </div>

        <div class="actionTabCnt actionTabCnt6nsd5">
            <p class="analyticsCardTitle">Временные слоты для аналитики</p>
            <div class="actionTabCntRow7vh31" id="time-slots-list">
                @foreach($timeSlots as $key => $slot)
                <div class="item" data-slot-key="{{ $key }}">
                    <span class="title">{{ $slot['label'] }}</span>
                    <span>с</span>
                    @include('settings.partials._time-picker', ['id' => "slot-{$key}-from", 'value' => $slot['from']])
                    <span>до</span>
                    @include('settings.partials._time-picker', ['id' => "slot-{$key}-to", 'value' => $slot['to']])
                    <button type="button" onclick="removeTimeSlot('{{ $key }}')" class="removeTimeSlotBtn --removeTimeSlotBtn-o3c02">
                        <img src="/assets/img/close-icon.svg" alt="">
                    </button>
                </div>
                @endforeach
            </div>

            @php
            $allSlotDefs = [
                'morning' => 'Утро', 'day' => 'Обед', 'evening' => 'Вечер', 'night' => 'Ночь',
            ];
            $removedSlots = array_diff_key($allSlotDefs, $timeSlots);
            @endphp
            <div id="add-time-slot-wrap" style="{{ count($removedSlots) === 0 ? 'display:none;' : '' }} margin-top:10px;">
                <div class="defaultForm customSelect" id="add-time-slot-select">
                    <div class="customSelectValue" data-placeholder="Добавить слот">Добавить слот</div>
                    <svg class="customSelectArrow" viewBox="0 0 10 6" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M1 1l4 4 4-4"></path>
                    </svg>
                    <input type="hidden" id="add-time-slot-value">
                    <div class="customSelectDropdown">
                        @foreach($removedSlots as $key => $label)
                            <div class="customSelectOption" data-value="{{ $key }}" data-label="{{ $label }}">{{ $label }}</div>
                        @endforeach
                    </div>
                </div>
                <button type="button" onclick="addTimeSlot()" class="actionTabActionsButton --buttonSecondary --actionTabActionsButton-p42dx1" style="margin-top:6px;">Добавить</button>
            </div>
        </div>

        <div class="actionTabCnt --flex1">
            <p class="analyticsCardTitle">Доступ к аналитике</p>
            <div class="actionTabCntRow3dwa1">
                @php $analyticsAccess = $settings->get('analytics_access')?->value ?? 'admin'; @endphp
                <div class="defaultForm customSelect" id="analytics-access-select">
                    <div class="customSelectValue" data-placeholder="Выберите">{{ $analyticsAccess === 'all' ? 'Все' : 'Только админ' }}</div>
                    <svg class="customSelectArrow" viewBox="0 0 10 6" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M1 1l4 4 4-4"></path>
                    </svg>
                    <input type="hidden" id="analytics-access" value="{{ $analyticsAccess }}">
                    <div class="customSelectDropdown">
                        <div class="customSelectOption {{ $analyticsAccess === 'admin' ? '--active' : '' }}" data-value="admin">Только админ</div>
                        <div class="customSelectOption {{ $analyticsAccess === 'all' ? '--active' : '' }}" data-value="all">Все</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="actionTabActions --actionTabActions9f3d1">
            <div class="actionTabActionsButtons">
                <button type="button" onclick="submitGeneral()" class="actionTabActionsButton --buttonPrimary">Сохранить</button>
                <button type="button" onclick="closeSheet('settingsTab1')" class="actionTabActionsButton --buttonSecondary">Отменить</button>
            </div>
        </div>
    </div>
</div>

{{-- Уведомления --}}
<div class="actionTab viewProductSameTab settingsTab2" id="settingsTab2">
    <div class="actionTabWrapper">
        <div class="actionTabHeader">
            <h3>Уведомления</h3>
            <div class="actionTabHeaderBtns">
                <button type="button" onclick="closeSheet('settingsTab2')" class="actionTabHeaderBtn --tabBtnClose">
                    <img src="/assets/img/close-icon.svg" alt="">
                </button>
            </div>
        </div>

        <div class="actionTabCnt actionTabCnt6nsd5 --actionTabCnt-a2dpd">
            <p class="analyticsCardTitle">Интервал обслуживания</p>
            <div class="actionTabCntRow7vh31">
                <div class="item defaultForm">
                    <input type="number" id="service-reminder-interval" value="{{ $settings->get('service_reminder_interval')?->value ?? 20 }}" min="1" max="120">
                    <span>(минут)</span>
                </div>
            </div>
        </div>

        {{-- Включить напоминания об обслуживании --}}
        <div class="actionTabCnt">
            <p class="analyticsCardTitle">Включить напоминания об обслуживании</p>
            <div class="actionTabCntRow7vh31">
                <label class="switchToggle">
                    <input type="checkbox" id="service-reminder-enabled" {{ $settings->get('service_reminder_enabled')?->value == '1' ? 'checked' : '' }}>
                    <span class="switchToggleTrack"><span class="switchToggleThumb"></span></span>
                </label>
            </div>
        </div>

        <div class="actionTabActions --actionTabActions9f3d1">
            <div class="actionTabActionsButtons">
                <button type="button" onclick="submitNotifications()" class="actionTabActionsButton --buttonPrimary">Сохранить</button>
                <button type="button" onclick="closeSheet('settingsTab2')" class="actionTabActionsButton --buttonSecondary">Отменить</button>
            </div>
        </div>
    </div>
</div>

{{-- Пороги --}}
<div class="actionTab viewProductSameTab settingsTab3" id="settingsTab3">
    <div class="actionTabWrapper">
        <div class="actionTabHeader">
            <h3>Пороги для гостей</h3>
            <div class="actionTabHeaderBtns">
                <button type="button" onclick="closeSheet('settingsTab3')" class="actionTabHeaderBtn --tabBtnClose">
                    <img src="/assets/img/close-icon.svg" alt="">
                </button>
            </div>
        </div>

        <div class="actionTabCnt actionTabCnt6nsd5 actionTabCnt6nsd5-2">
            <p class="analyticsCardTitle">Пороги для статусов</p>
            <div class="actionTabCntRow7vh31">
                <div class="item defaultForm">
                    <span class="title">Регулярный</span><span>от</span>
                    <input type="number" id="threshold-regular" value="{{ $settings->get('guest_regular_threshold')?->value ?? 100 }}">
                    <span>посещений</span>
                </div>
                <div class="item defaultForm">
                    <span class="title">Постоянный</span><span>от</span>
                    <input type="number" id="threshold-permanent" value="{{ $settings->get('guest_permanent_threshold')?->value ?? 50 }}">
                    <span>посещений</span>
                </div>
                <div class="item defaultForm">
                    <span class="title">VIP</span><span>от</span>
                    <input type="number" id="threshold-vip" value="{{ $settings->get('guest_vip_threshold')?->value ?? 400 }}">
                    <span>посещений</span>
                </div>
            </div>
        </div>

        <div class="actionTabCnt actionTabCnt6nsd5 actionTabCnt6nsd5-2 --flex1">
            <p class="analyticsCardTitle">Порог для "Давно не приходил"</p>
            <div class="actionTabCntRow7vh31">
                <div class="item defaultForm">
                    <span>от</span>
                    <input type="number" id="inactive-days" value="{{ $settings->get('guest_inactive_days')?->value ?? 30 }}">
                    <span>дней</span>
                </div>
            </div>
        </div>

        <div class="actionTabActions --actionTabActions9f3d1">
            <div class="actionTabActionsButtons">
                <button type="button" onclick="submitThresholds()" class="actionTabActionsButton --buttonPrimary">Сохранить</button>
                <button type="button" onclick="closeSheet('settingsTab3')" class="actionTabActionsButton --buttonSecondary">Отменить</button>
            </div>
        </div>
    </div>
</div>

{{-- Мастера --}}
<div class="actionTab viewProductSameTab settingsTab4" id="settingsTab4">
    <div class="actionTabWrapper">
        <div class="actionTabHeader">
            <h3>Мастера</h3>
            <div class="actionTabHeaderBtns">
                <button type="button" onclick="closeSheet('settingsTab4')" class="actionTabHeaderBtn --tabBtnClose">
                    <img src="/assets/img/close-icon.svg" alt="">
                </button>
            </div>
        </div>

        <div class="actionTabCnt actionTabCnt6nsd5 actionTabCnt6nsd5-3">
            <p class="analyticsCardTitle">Создать аккаунт для мастера</p>
            <div class="actionTabSection"><div class="defaultForm"><input id="new-master-name" type="text" placeholder="Имя"></div></div>
            <div class="actionTabSection"><div class="defaultForm"><input id="new-master-login" type="text" placeholder="Логин"></div></div>
            <div class="actionTabSection"><div class="defaultForm"><input id="new-master-password" type="password" placeholder="Пароль"></div></div>
            <div class="actionTabActions --actionTabActions9f3d1 --actionTabActions9f3d1-2">
                <div class="actionTabActionsButtons">
                    <button type="button" onclick="submitCreateMaster()" class="actionTabActionsButton --buttonPrimary">Создать</button>
                </div>
            </div>
        </div>

        <div class="actionTabCnt actionTabCnt4d21c --actionTabCnt-w4d20" id="masters-list">
            <p class="analyticsCardTitle">Все мастера</p>
            @foreach($masters as $master)
                @include('settings.partials._master-row', ['master' => $master])
            @endforeach
        </div>
    </div>
</div>

<div class="actionTab viewProductSameTab editMaster" id="editMaster">
    <div class="actionTabWrapper">
        <div class="actionTabHeader">
            <h3>Редактировать мастера</h3>
            <div class="actionTabHeaderBtns">
                <button type="button" onclick="closeSheet('editMaster')" class="actionTabHeaderBtn --tabBtnClose">
                    <img src="/assets/img/close-icon.svg" alt="">
                </button>
            </div>
        </div>
        <div class="actionTabCnt actionTabCnt6nsd5 actionTabCnt6nsd5-3 --flex1">
            <p class="analyticsCardTitle">Данные</p>
            <div class="actionTabSection"><div class="defaultForm"><input id="edit-master-name" type="text" placeholder="Имя"></div></div>
            <div class="actionTabSection"><div class="defaultForm"><input id="edit-master-login" type="text" placeholder="Логин"></div></div>
            <div class="actionTabSection"><div class="defaultForm"><input id="edit-master-password" type="password" placeholder="Для сброса введите новый пароль"></div></div>
        </div>
        <div class="actionTabActions --actionTabActions9f3d1">
            <div class="actionTabActionsButtons">
                <button type="button" onclick="submitEditMaster()" class="actionTabActionsButton --buttonPrimary">Сохранить</button>
                <button type="button" onclick="closeSheet('editMaster')" class="actionTabActionsButton --buttonSecondary">Отменить</button>
            </div>
        </div>
    </div>
</div>

<div class="actionTab viewProductSameTab masterToggleConfirm" id="masterToggleConfirm">
    <div class="actionTabWrapper">
        <div class="actionTabHeader">
            <h3 id="masterToggleTitle"></h3>
            <div class="actionTabHeaderBtns">
                <button type="button" onclick="closeSheet('masterToggleConfirm')" class="actionTabHeaderBtn --tabBtnClose">
                    <img src="/assets/img/close-icon.svg" alt="">
                </button>
            </div>
        </div>
        <div class="actionTabCnt actionTabCnt6nsd5 actionTabCnt6nsd5-3 --flex1">
            <p class="analyticsCardTitle">Данные</p>
            <div class="actionTabSection"><div class="defaultForm"><input id="masterToggleName" type="text" readonly></div></div>
            <div class="actionTabSection"><div class="defaultForm"><input id="masterToggleLogin" type="text" readonly></div></div>
        </div>
        <div class="actionTabActions --actionTabActions9f3d1">
            <div class="actionTabActionsButtons">
                <button type="button" onclick="submitMasterToggle()" class="actionTabActionsButton --buttonPrimary">Подтвердить</button>
                <button type="button" onclick="closeSheet('masterToggleConfirm')" class="actionTabActionsButton --buttonSecondary">Отменить</button>
            </div>
        </div>
    </div>
</div>

{{-- Типы столов --}}
<div class="actionTab viewProductSameTab settingsTab5" id="settingsTab5">
    <div class="actionTabWrapper">
        <div class="actionTabHeader">
            <h3>Типы столов</h3>
            <div class="actionTabHeaderBtns">
                <button type="button" onclick="closeSheet('settingsTab5')" class="actionTabHeaderBtn --tabBtnClose">
                    <img src="/assets/img/close-icon.svg" alt="">
                </button>
            </div>
        </div>

        <div class="actionTabCnt actionTabCnt6nsd5 actionTabCnt6nsd5-3">
            <p class="analyticsCardTitle">Создать новый тип стола</p>
            <div class="actionTabSection"><div class="defaultForm"><input id="new-type-name" type="text" placeholder="Название"></div></div>
            <div class="actionTabSection"><div class="defaultForm"><input id="new-type-description" type="text" placeholder="Описание"></div></div>
            <div class="actionTabActions --actionTabActions9f3d1 --actionTabActions9f3d1-2">
                <div class="actionTabActionsButtons">
                    <button type="button" onclick="submitCreateTableType()" class="actionTabActionsButton --buttonPrimary">Создать</button>
                </div>
            </div>
        </div>

        <div class="actionTabCnt actionTabCnt4d21c" id="table-types-list">
            <p class="analyticsCardTitle">Типы столов</p>
            @foreach($tableTypes as $type)
                @include('settings.partials._table-type-row', ['type' => $type])
            @endforeach
        </div>
    </div>
</div>

<div class="actionTab viewProductSameTab editTableType" id="editTableType">
    <div class="actionTabWrapper">
        <div class="actionTabHeader">
            <h3>Редактировать тип стола</h3>
            <div class="actionTabHeaderBtns">
                <button type="button" onclick="closeSheet('editTableType')" class="actionTabHeaderBtn --tabBtnClose">
                    <img src="/assets/img/close-icon.svg" alt="">
                </button>
            </div>
        </div>
        <div class="actionTabCnt actionTabCnt6nsd5 actionTabCnt6nsd5-3 --flex1">
            <p class="analyticsCardTitle">Данные</p>
            <div class="actionTabSection"><div class="defaultForm"><input id="edit-type-name" type="text"></div></div>
            <div class="actionTabSection"><div class="defaultForm"><input id="edit-type-description" type="text"></div></div>
        </div>
        <div class="actionTabActions --actionTabActions9f3d1">
            <div class="actionTabActionsButtons">
                <button type="button" onclick="submitEditTableType()" class="actionTabActionsButton --buttonPrimary">Сохранить</button>
                <button type="button" onclick="closeSheet('editTableType')" class="actionTabActionsButton --buttonSecondary">Отменить</button>
            </div>
        </div>
    </div>
</div>

<div class="actionTab viewProductSameTab tableTypeToggleConfirm" id="tableTypeToggleConfirm">
    <div class="actionTabWrapper">
        <div class="actionTabHeader">
            <h3 id="tableTypeToggleTitle"></h3>
            <div class="actionTabHeaderBtns">
                <button type="button" onclick="closeSheet('tableTypeToggleConfirm')" class="actionTabHeaderBtn --tabBtnClose">
                    <img src="/assets/img/close-icon.svg" alt="">
                </button>
            </div>
        </div>
        <div class="actionTabCnt actionTabCnt6nsd5 actionTabCnt6nsd5-3 --flex1">
            <p class="analyticsCardTitle">Данные</p>
            <div class="actionTabSection"><div class="defaultForm"><input id="tableTypeToggleName" type="text" readonly></div></div>
        </div>
        <div class="actionTabActions --actionTabActions9f3d1">
            <div class="actionTabActionsButtons">
                <button type="button" onclick="submitTableTypeToggle()" class="actionTabActionsButton --buttonPrimary">Подтвердить</button>
                <button type="button" onclick="closeSheet('tableTypeToggleConfirm')" class="actionTabActionsButton --buttonSecondary">Отменить</button>
            </div>
        </div>
    </div>
</div>

{{-- Столы --}}
<div class="actionTab viewProductSameTab settingsTab6" id="settingsTab6">
    <div class="actionTabWrapper">
        <div class="actionTabHeader">
            <h3>Столы</h3>
            <div class="actionTabHeaderBtns">
                <button type="button" onclick="closeSheet('settingsTab6')" class="actionTabHeaderBtn --tabBtnClose">
                    <img src="/assets/img/close-icon.svg" alt="">
                </button>
            </div>
        </div>

        <div class="actionTabCnt actionTabCnt6nsd5 actionTabCnt6nsd5-3">
            <p class="analyticsCardTitle">Создать новый стол</p>
            <div class="actionTabSection"><div class="defaultForm"><input id="new-table-name" type="text" placeholder="Название"></div></div>
            <div class="actionTabSection">
                <div class="defaultForm customSelect" id="new-table-type-select">
                    <div class="customSelectValue" data-placeholder="— Без типа —">— Без типа —</div>
                    <svg class="customSelectArrow" viewBox="0 0 10 6" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M1 1l4 4 4-4"></path>
                    </svg>
                    <input type="hidden" id="new-table-type">
                    <div class="customSelectDropdown">
                        @include('settings.partials._type-options')
                    </div>
                </div>
            </div>
            <div class="actionTabActions --actionTabActions9f3d1 --actionTabActions9f3d1-2">
                <div class="actionTabActionsButtons">
                    <button type="button" onclick="submitCreateTable()" class="actionTabActionsButton --buttonPrimary">Создать</button>
                </div>
            </div>
        </div>

        <div class="actionTabCnt actionTabCnt4d21c" id="tables-list">
            <p class="analyticsCardTitle">Все столы</p>
            <div class="labelGuestAccordion">
                @include('settings.partials._tables-accordion')
            </div>
        </div>
    </div>
</div>

<div class="actionTab viewProductSameTab editTable" id="editTable">
    <div class="actionTabWrapper">
        <div class="actionTabHeader">
            <h3>Редактировать стол</h3>
            <div class="actionTabHeaderBtns">
                <button type="button" onclick="closeSheet('editTable')" class="actionTabHeaderBtn --tabBtnClose">
                    <img src="/assets/img/close-icon.svg" alt="">
                </button>
            </div>
        </div>
        <div class="actionTabCnt actionTabCnt6nsd5 actionTabCnt6nsd5-3 --flex1">
            <p class="analyticsCardTitle">Данные</p>
            <div class="actionTabSection"><div class="defaultForm"><input id="edit-table-name" type="text"></div></div>
            <div class="actionTabSection">
                <div class="defaultForm customSelect" id="edit-table-type-select">
                    <div class="customSelectValue" data-placeholder="— Без типа —">— Без типа —</div>
                    <svg class="customSelectArrow" viewBox="0 0 10 6" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M1 1l4 4 4-4"></path>
                    </svg>
                    <input type="hidden" id="edit-table-type">
                    <div class="customSelectDropdown">
                        @include('settings.partials._type-options')
                    </div>
                </div>
            </div>
        </div>
        <div class="actionTabActions --actionTabActions9f3d1">
            <div class="actionTabActionsButtons">
                <button type="button" onclick="submitEditTable()" class="actionTabActionsButton --buttonPrimary">Сохранить</button>
                <button type="button" onclick="closeSheet('editTable')" class="actionTabActionsButton --buttonSecondary">Отменить</button>
            </div>
        </div>
    </div>
</div>

<div class="actionTab viewProductSameTab tableToggleConfirm" id="tableToggleConfirm">
    <div class="actionTabWrapper">
        <div class="actionTabHeader">
            <h3 id="tableToggleTitle"></h3>
            <div class="actionTabHeaderBtns">
                <button type="button" onclick="closeSheet('tableToggleConfirm')" class="actionTabHeaderBtn --tabBtnClose">
                    <img src="/assets/img/close-icon.svg" alt="">
                </button>
            </div>
        </div>
        <div class="actionTabCnt actionTabCnt6nsd5 actionTabCnt6nsd5-3 --flex1">
            <p class="analyticsCardTitle">Данные</p>
            <div class="actionTabSection"><div class="defaultForm"><input id="tableToggleName" type="text" readonly></div></div>
        </div>
        <div class="actionTabActions --actionTabActions9f3d1">
            <div class="actionTabActionsButtons">
                <button type="button" onclick="submitTableToggle()" class="actionTabActionsButton --buttonPrimary">Подтвердить</button>
                <button type="button" onclick="closeSheet('tableToggleConfirm')" class="actionTabActionsButton --buttonSecondary">Отменить</button>
            </div>
        </div>
    </div>
</div>

{{-- Журнал --}}
<div class="actionTab viewProductSameTab settingsTab7" id="settingsTab7">
    <div class="actionTabWrapper">
        <div class="actionTabHeader">
            <h3>Журнал смены мастеров действий</h3>
            <div class="actionTabHeaderBtns">
                <button type="button" onclick="closeSheet('settingsTab7')" class="actionTabHeaderBtn --tabBtnClose">
                    <img src="/assets/img/close-icon.svg" alt="">
                </button>
            </div>
        </div>

        <div class="actionTabCnt actionTabCnt1dvp1" id="journal-list">
            @include('settings.partials._journal-rows', ['logs' => $journalLogs])
        </div>

        @if($journalLogs->count() >= 20)
        <div class="actionTabViewOrderCard --actionTabViewOrderCard9k2sn">
            <div class="actionTabViewOrderCardTop">
                <button type="button" id="journal-load-more" onclick="loadMoreJournal()">
                    <img src="/assets/img/arrow-down.svg" alt="">
                    <span>Показать ещё</span>
                </button>
            </div>
        </div>
        @endif
    </div>
</div>

{{-- Памятка --}}
<div class="actionTab viewProductSameTab settingsTab8" id="settingsTab8">
    <div class="actionTabWrapper">
        <div class="actionTabHeader">
            <h3>Памятка</h3>
            <div class="actionTabHeaderBtns">
                <button type="button" onclick="openWikiCreateSheet()" class="actionTabHeaderBtn --tabBtnClose">
                    <img src="/assets/img/edit-icon-gold-lg.svg" alt="">
                </button>
                <button type="button" onclick="closeSheet('settingsTab8')" class="actionTabHeaderBtn --tabBtnClose">
                    <img src="/assets/img/close-icon.svg" alt="">
                </button>
            </div>
        </div>

        <div class="actionTabCnt actionTabCnt1dvp1 settingsSection actionTabCnt0s2s1" id="wiki-list">
            @foreach($wikiPages as $page)
                @include('settings.partials._wiki-row', ['page' => $page])
            @endforeach
        </div>
    </div>
</div>

{{-- Просмотр статьи --}}
<div class="actionTab articleTab viewArticle" id="viewArticle">
    <div class="actionTabWrapper">
        <div class="actionTabHeader">
            <h3>Статья <br><span class="--gold" id="wikiViewTitle"></span></h3>
            <div class="actionTabHeaderBtns">
                <button type="button" onclick="openWikiEditSheetFromView()" class="actionTabHeaderBtn --tabBtnClose">
                    <img src="/assets/img/edit-icon-gold-lg.svg" alt="">
                </button>
                <button type="button" onclick="closeSheet('viewArticle')" class="actionTabHeaderBtn --tabBtnClose">
                    <img src="/assets/img/close-icon.svg" alt="">
                </button>
            </div>
        </div>
        <div id="wikiViewBody"></div>
    </div>
</div>

{{-- Создать/редактировать статью --}}
<div class="actionTab viewProductSameTab editArticle" id="editArticle">
    <div class="actionTabWrapper">
        <div class="actionTabHeader">
            <h3 id="wikiFormTitle">Новая статья</h3>
            <div class="actionTabHeaderBtns">
                <button type="button" onclick="closeSheet('editArticle')" class="actionTabHeaderBtn --tabBtnClose">
                    <img src="/assets/img/close-icon.svg" alt="">
                </button>
            </div>
        </div>
        <div class="actionTabCnt actionTabCnt6nsd5 --fx1" id="wikiFormBody"></div>
        <div class="actionTabActions --actionTabActions9f3d1">
            <div class="actionTabActionsButtons">
                <button type="button" onclick="submitWikiForm()" class="actionTabActionsButton --buttonPrimary">Сохранить</button>
                <button type="button" onclick="closeSheet('editArticle')" class="actionTabActionsButton --buttonSecondary">Отменить</button>
            </div>
        </div>
    </div>
</div>

{{-- Подтверждение публикации/скрытия --}}
<div class="actionTab viewProductSameTab wikiToggleConfirm" id="wikiToggleConfirm">
    <div class="actionTabWrapper">
        <div class="actionTabHeader">
            <h3 id="wikiToggleTitle"></h3>
            <div class="actionTabHeaderBtns">
                <button type="button" onclick="closeSheet('wikiToggleConfirm')" class="actionTabHeaderBtn --tabBtnClose">
                    <img src="/assets/img/close-icon.svg" alt="">
                </button>
            </div>
        </div>
        <div class="actionTabCnt actionTabCnt6nsd5 actionTabCnt6nsd5-3 --flex1">
            <p class="analyticsCardTitle">Данные</p>
            <div class="actionTabSection"><div class="defaultForm"><input id="wikiToggleName" type="text" readonly></div></div>
        </div>
        <div class="actionTabActions --actionTabActions9f3d1">
            <div class="actionTabActionsButtons">
                <button type="button" onclick="submitWikiToggle()" class="actionTabActionsButton --buttonPrimary">Подтвердить</button>
                <button type="button" onclick="closeSheet('wikiToggleConfirm')" class="actionTabActionsButton --buttonSecondary">Отменить</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
const csrf = '{{ csrf_token() }}';

function postJson(url, data) {
    return fetch(url, {
        method: 'POST',
        redirect: 'manual',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
        body: JSON.stringify(data),
    }).then(async r => {
        if (r.type === 'opaqueredirect' || r.status === 0) {
            throw new Error('Такой логин уже используется — выберите другой.');
        }
        const isJson = r.headers.get('content-type')?.includes('application/json');
        const payload = isJson ? await r.json() : await r.text();
        if (!r.ok) {
            console.error('Request failed', r.status, payload);
            const msg = payload.errors ? Object.values(payload.errors).flat().join('\n') : (payload.message || `Ошибка ${r.status}`);
            throw new Error(msg);
        }
        return payload;
    });
}

function putJson(url, data) {
    return fetch(url, {
        method: 'PUT',
        redirect: 'manual',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
        body: JSON.stringify(data),
    }).then(async r => {
        if (r.type === 'opaqueredirect' || r.status === 0) {
            throw new Error('Такой логин уже используется — выберите другой.');
        }
        const isJson = r.headers.get('content-type')?.includes('application/json');
        const payload = isJson ? await r.json() : await r.text();
        if (!r.ok) {
            console.error('Request failed', r.status, payload);
            const msg = payload.errors ? Object.values(payload.errors).flat().join('\n') : (payload.message || 'Ошибка');
            throw new Error(msg);
        }
        return payload;
    });
}

function submitGeneral() {
    const slots = {};
    document.querySelectorAll('#time-slots-list .item[data-slot-key]').forEach(item => {
        const key = item.dataset.slotKey;
        slots[`slots_${key}_label`] = allTimeSlotLabels[key];
        slots[`slots_${key}_from`] = document.getElementById(`slot-${key}-from`).value;
        slots[`slots_${key}_to`] = document.getElementById(`slot-${key}-to`).value;
    });
    postJson('{{ route("settings.general") }}', {
        ...slots,
        analytics_access: document.getElementById('analytics-access').value,
    }).then(() => closeSheet('settingsTab1'));
}

function submitNotifications() {
    postJson('{{ route("settings.notifications") }}', {
        service_reminder_enabled: document.getElementById('service-reminder-enabled').checked,
        service_reminder_interval: document.getElementById('service-reminder-interval').value,
    }).then(() => closeSheet('settingsTab2'));
}

function submitThresholds() {
    postJson('{{ route("settings.thresholds") }}', {
        guest_regular_threshold: document.getElementById('threshold-regular').value,
        guest_permanent_threshold: document.getElementById('threshold-permanent').value,
        guest_vip_threshold: document.getElementById('threshold-vip').value,
    }).then(() => {
        postJson('{{ route("settings.inactive") }}', {
            guest_inactive_days: document.getElementById('inactive-days').value,
        }).then(() => closeSheet('settingsTab3'));
    });
}

function submitCreateMaster() {
    clearFormError('settingsTab4');
    postJson('{{ route("settings.masters.store") }}', {
        name: document.getElementById('new-master-name').value,
        login: document.getElementById('new-master-login').value,
        password: document.getElementById('new-master-password').value,
    }).then(data => {
        if (data.row) {
            const title = document.querySelector('#masters-list .analyticsCardTitle');
            title.insertAdjacentHTML('afterend', data.row);
            resortMastersList();
            document.getElementById('new-master-name').value = '';
            document.getElementById('new-master-login').value = '';
            document.getElementById('new-master-password').value = '';
        }
    }).catch(err => showFormError('settingsTab4', err.message));
}

let editingMasterId = null;
function openEditMaster(id, name, login) {
    editingMasterId = id;
    document.getElementById('edit-master-name').value = name;
    document.getElementById('edit-master-login').value = login;
    document.getElementById('edit-master-password').value = '';
    openSheet('editMaster');
}

function submitEditMaster() {
    clearFormError('editMaster');
    putJson(`/settings/masters/${editingMasterId}`, {
        name: document.getElementById('edit-master-name').value,
        login: document.getElementById('edit-master-login').value,
        password: document.getElementById('edit-master-password').value,
    }).then(data => {
        replaceRow('masters-list', editingMasterId, 'data-master-id', data.row);
        closeSheet('editMaster');
    }).catch(err => showFormError('editMaster', err.message));
}

let toggleMasterId = null;
function openMasterToggleConfirm(id, name, login, isActive) {
    toggleMasterId = id;
    document.getElementById('masterToggleTitle').textContent = (isActive ? 'Деактивировать' : 'Активировать') + ' мастера';
    document.getElementById('masterToggleName').value = name;
    document.getElementById('masterToggleLogin').value = login;
    openSheet('masterToggleConfirm');
}

function submitMasterToggle() {
    clearFormError('masterToggleConfirm');
    postJson(`/settings/masters/${toggleMasterId}/toggle`, {}).then(data => {
        replaceRow('masters-list', toggleMasterId, 'data-master-id', data.row);
        resortMastersList();
        closeSheet('masterToggleConfirm');
    }).catch(err => showFormError('masterToggleConfirm', err.message));
}

function resortMastersList() {
    const container = document.getElementById('masters-list');
    const rows = Array.from(container.querySelectorAll('.ordersCard[data-master-id]'));
    rows.sort((a, b) => {
        const aActive = a.dataset.active === '1' ? 0 : 1;
        const bActive = b.dataset.active === '1' ? 0 : 1;
        return aActive - bActive;
    });
    rows.forEach(row => container.appendChild(row));
}

function submitCreateTableType() {
    clearFormError('settingsTab5');
    postJson('{{ route("settings.table-types.store") }}', {
        name: document.getElementById('new-type-name').value,
        description: document.getElementById('new-type-description').value,
    }).then(data => {
        if (data.row) {
            document.getElementById('table-types-list').insertAdjacentHTML('beforeend', data.row);
            document.getElementById('new-type-name').value = '';
            document.getElementById('new-type-description').value = '';
            refreshTablesPanel();
        }
    }).catch(err => showFormError('settingsTab5', err.message));
}

let editingTypeId = null;
function openEditTableType(id, name, description) {
    editingTypeId = id;
    document.getElementById('edit-type-name').value = name;
    document.getElementById('edit-type-description').value = description;
    openSheet('editTableType');
}

function submitEditTableType() {
    clearFormError('editTableType');
    putJson(`/settings/table-types/${editingTypeId}`, {
        name: document.getElementById('edit-type-name').value,
        description: document.getElementById('edit-type-description').value,
    }).then(data => {
        replaceRow('table-types-list', editingTypeId, 'data-type-id', data.row);
        refreshTablesPanel();
        closeSheet('editTableType');
    }).catch(err => showFormError('editTableType', err.message));
}

let toggleTypeId = null;
function openTableTypeToggleConfirm(id, name, isTrashed) {
    toggleTypeId = id;
    document.getElementById('tableTypeToggleTitle').textContent = (isTrashed ? 'Активировать' : 'Деактивировать') + ' тип стола';
    document.getElementById('tableTypeToggleName').value = name;
    openSheet('tableTypeToggleConfirm');
}

function submitTableTypeToggle() {
    clearFormError('tableTypeToggleConfirm');
    postJson(`/settings/table-types/${toggleTypeId}/toggle`, {}).then(data => {
        replaceRow('table-types-list', toggleTypeId, 'data-type-id', data.row);
        refreshTablesPanel();
        closeSheet('tableTypeToggleConfirm');
    }).catch(err => showFormError('tableTypeToggleConfirm', err.message));
}

function refreshTablesPanel() {
    fetch('{{ route("settings.tables-panel") }}', { headers: { 'Accept': 'application/json' } })
        .then(r => r.json())
        .then(data => {
            const box = document.querySelector('#tables-list .labelGuestAccordion');
            const open = [...box.querySelectorAll('.labelGuestAccordionCard[data-open="1"]')].map(c => c.dataset.typeKey);
            box.innerHTML = data.accordion;
            open.forEach(k => {
                const head = box.querySelector(`.labelGuestAccordionCard[data-type-key="${k}"] .labelGuestAccordionHead`);
                if (head) toggleGuestAccordion(head);
            });
            ['new-table-type-select', 'edit-table-type-select'].forEach(id => {
                const select = document.getElementById(id);
                select.querySelector('.customSelectDropdown').innerHTML = data.options;
                const v = select.querySelector('input[type="hidden"]').value;
                if (!v) return;
                const opt = select.querySelector(`.customSelectOption[data-value="${v}"]`);
                if (opt) {
                    select.querySelector('.customSelectValue').textContent = opt.textContent;
                    opt.classList.add('--active');
                } else {
                    select.resetCustom();
                }
            });
        });
}

function submitCreateTable() {
    clearFormError('settingsTab6');
    postJson('{{ route("settings.tables.store") }}', {
        name: document.getElementById('new-table-name').value,
        type_id: document.getElementById('new-table-type').value || null,
    }).then(data => {
        if (data.row) {
            insertTableRow(data.row, document.getElementById('new-table-type').value || 'none');
            document.getElementById('new-table-name').value = '';
            document.getElementById('new-table-type-select').resetCustom();
        }
    }).catch(err => showFormError('settingsTab6', err.message));
}

function insertTableRow(rowHtml, typeKey) {
    const group = document.querySelector(`#tables-list [data-tables-group="${typeKey}"]`);
    if (!group) return;
    const placeholder = group.querySelector('p');
    if (placeholder) placeholder.remove();
    group.insertAdjacentHTML('beforeend', rowHtml);
    updateGroupCount(group);
}

let editingTableId = null;
function openEditTable(id, name, typeId) {
    editingTableId = id;
    document.getElementById('edit-table-name').value = name;
    const select = document.getElementById('edit-table-type-select');
    if (typeId) {
        const opt = select.querySelector(`.customSelectOption[data-value="${typeId}"]`);
        if (opt) {
            document.getElementById('edit-table-type').value = typeId;
            select.querySelector('.customSelectValue').textContent = opt.textContent;
            select.classList.add('--filled');
            select.querySelectorAll('.customSelectOption').forEach(o => o.classList.remove('--active'));
            opt.classList.add('--active');
        }
    } else {
        select.resetCustom();
    }
    openSheet('editTable');
}

function submitEditTable() {
    clearFormError('editTable');
    putJson(`/settings/tables/${editingTableId}`, {
        name: document.getElementById('edit-table-name').value,
        type_id: document.getElementById('edit-table-type').value || null,
    }).then(data => {
        const old = document.querySelector(`#tables-list [data-table-id="${editingTableId}"]`);
        const oldGroup = old ? old.closest('[data-tables-group]') : null;
        if (old) old.remove();
        if (oldGroup) updateGroupCount(oldGroup);

        insertTableRow(data.row, document.getElementById('edit-table-type').value || 'none');
        closeSheet('editTable');
    }).catch(err => showFormError('editTable', err.message));
}

function updateGroupCount(group) {
    const card = group.closest('.labelGuestAccordionCard');
    const countSpan = card.querySelector('.labelGuestAccordionHead span');
    const count = group.querySelectorAll('[data-table-id]').length;
    countSpan.textContent = countSpan.textContent.replace(/\(\d+\)/, `(${count})`);
    if (count === 0 && !group.querySelector('p')) {
        group.insertAdjacentHTML('beforeend', '<p style="color:var(--info); font-size:13px; padding:12px 0;">Нет столов</p>');
    }
}

let toggleTableId = null;
function openTableToggleConfirm(id, name, isActive) {
    toggleTableId = id;
    document.getElementById('tableToggleTitle').textContent = (isActive ? 'Деактивировать' : 'Активировать') + ' стол';
    document.getElementById('tableToggleName').value = name;
    openSheet('tableToggleConfirm');
}

function submitTableToggle() {
    clearFormError('tableToggleConfirm');
    postJson(`/settings/tables/${toggleTableId}/toggle`, {}).then(data => {
        replaceRow('tables-list', toggleTableId, 'data-table-id', data.row);
        closeSheet('tableToggleConfirm');
    }).catch(err => showFormError('tableToggleConfirm', err.message));
}

function replaceRow(containerId, id, attr, html) {
    const container = document.getElementById(containerId);
    const old = container.querySelector(`[${attr}="${id}"]`);
    if (old) old.outerHTML = html;
}

function loadMoreJournal() {
    const rows = document.querySelectorAll('#journal-list .item[data-created]');
    const last = rows[rows.length - 1];
    if (!last) return;
    fetch(`{{ route('settings.master-changes.sheet') }}?before=${encodeURIComponent(last.dataset.created)}`)
        .then(r => r.text())
        .then(html => {
            document.getElementById('journal-list').insertAdjacentHTML('beforeend', html);
            if (!html.trim()) document.getElementById('journal-load-more').style.display = 'none';
        });
}

const allTimeSlotLabels = { morning: 'Утро', day: 'Обед', evening: 'Вечер', night: 'Ночь' };

function removeTimeSlot(key) {
    const item = document.querySelector(`#time-slots-list .item[data-slot-key="${key}"]`);
    if (item) item.remove();

    const select = document.getElementById('add-time-slot-select');
    const dropdown = select.querySelector('.customSelectDropdown');
    const opt = document.createElement('div');
    opt.className = 'customSelectOption';
    opt.dataset.value = key;
    opt.dataset.label = allTimeSlotLabels[key];
    opt.textContent = allTimeSlotLabels[key];
    dropdown.appendChild(opt);

    document.getElementById('add-time-slot-wrap').style.display = 'block';
}

function addTimeSlot() {
    const select = document.getElementById('add-time-slot-select');
    const key = document.getElementById('add-time-slot-value').value;
    if (!key) return;
    const opt = select.querySelector(`.customSelectOption[data-value="${key}"]`);
    const label = opt.dataset.label;

    const wrap = document.getElementById('time-slots-list');
    const div = document.createElement('div');
    div.className = 'item';
    div.dataset.slotKey = key;
    div.innerHTML = `
        <span class="title">${label}</span>
        <span>с</span>
        <div class="defaultForm customTime --customTime-l2e30" data-target="slot-${key}-from">
            <div class="customTimeValue"><span class="customTimeHH">--</span><span>:</span><span class="customTimeMM">--</span></div>
            <svg class="customTimeIcon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"></circle><path d="M12 7v5l3 2"></path></svg>
            <input type="hidden" id="slot-${key}-from" value="">
            <div class="customTimeDropdown"><div class="customTimeCols">
                <div class="customTimeCol" data-type="hh">${hourOptions()}</div>
                <div class="customTimeCol" data-type="mm">${minuteOptions()}</div>
            </div><button type="button" class="customTimeConfirm">Готово</button></div>
        </div>
        <span>до</span>
        <div class="defaultForm customTime --customTime-l2e30" data-target="slot-${key}-to">
            <div class="customTimeValue"><span class="customTimeHH">--</span><span>:</span><span class="customTimeMM">--</span></div>
            <svg class="customTimeIcon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"></circle><path d="M12 7v5l3 2"></path></svg>
            <input type="hidden" id="slot-${key}-to" value="">
            <div class="customTimeDropdown"><div class="customTimeCols">
                <div class="customTimeCol" data-type="hh">${hourOptions()}</div>
                <div class="customTimeCol" data-type="mm">${minuteOptions()}</div>
            </div><button type="button" class="customTimeConfirm">Готово</button></div>
        </div>
        <button type="button" onclick="removeTimeSlot('${key}')" class="removeTimeSlotBtn --removeTimeSlotBtn-o3c02">
            <img src="/assets/img/close-icon.svg" alt="">
        </button>
    `;
    wrap.appendChild(div);
    initAllCustomTimes(div);

    opt.remove();
    select.resetCustom();
    if (select.querySelectorAll('.customSelectOption').length === 0) {
        document.getElementById('add-time-slot-wrap').style.display = 'none';
    }
}

function hourOptions() {
    let html = '';
    for (let h = 0; h < 24; h++) { const v = String(h).padStart(2, '0'); html += `<div class="customTimeOption" data-value="${v}">${v}</div>`; }
    return html;
}
function minuteOptions() {
    let html = '';
    for (let m = 0; m < 60; m++) { const v = String(m).padStart(2, '0'); html += `<div class="customTimeOption" data-value="${v}">${v}</div>`; }
    return html;
}

function showFormError(sheetId, message) {
    const sheet = document.getElementById(sheetId);
    let box = sheet.querySelector('.formErrorBox');
    if (!box) {
        box = document.createElement('div');
        box.className = 'formErrorBox';
        const actions = sheet.querySelector('.actionTabActions');
        if (actions) actions.parentNode.insertBefore(box, actions);
        else sheet.querySelector('.actionTabWrapper').appendChild(box);
    }
    box.textContent = message;
}

function clearFormError(sheetId) {
    const sheet = document.getElementById(sheetId);
    const box = sheet.querySelector('.formErrorBox');
    if (box) box.remove();
}

let currentWikiPageId = null;
let wikiEditorInstance = null;

function openWikiViewSheet(id) {
    currentWikiPageId = id;
    fetch(`/settings/wiki/${id}/view-sheet`)
        .then(r => r.text())
        .then(html => {
            document.getElementById('wikiViewBody').innerHTML = html;
            const row = document.querySelector(`#wiki-list [data-wiki-id="${id}"] .itemInfo p`);
            document.getElementById('wikiViewTitle').textContent = row ? row.textContent : '';
            openSheet('viewArticle');
        });
}

function openWikiEditSheetFromView() {
    closeSheet('viewArticle');
    openWikiEditSheet(currentWikiPageId);
}

function openWikiCreateSheet() {
    currentWikiPageId = null;
    document.getElementById('wikiFormTitle').textContent = 'Новая статья';
    fetch(`/settings/wiki/create-sheet`)
        .then(r => r.text())
        .then(html => {
            document.getElementById('wikiFormBody').innerHTML = html;
            openSheet('editArticle');
            initWikiEditor('wiki-editor-content');
        });
}

function openWikiEditSheet(id) {
    currentWikiPageId = id;
    document.getElementById('wikiFormTitle').textContent = 'Редактировать статью';
    fetch(`/settings/wiki/${id}/edit-sheet`)
        .then(r => r.text())
        .then(html => {
            document.getElementById('wikiFormBody').innerHTML = html;
            openSheet('editArticle');
            initWikiEditor('wiki-editor-content');
        });
}

function submitWikiForm() {
    clearFormError('editArticle');
    const content = wikiEditorInstance ? wikiEditorInstance.getData() : document.getElementById('wiki-editor-content').value;

    const payload = {
        title: document.getElementById('wiki-title').value,
        description: document.getElementById('wiki-description').value,
        sort_order: document.getElementById('wiki-sort-order').value,
        is_published: document.getElementById('wiki-is-published').checked,
        content: content,
    };

    const request = currentWikiPageId
        ? putJson(`/settings/wiki/${currentWikiPageId}`, payload)
        : postJson(`/settings/wiki`, payload);

    request.then(data => {
        const existing = document.querySelector(`#wiki-list [data-wiki-id]`);
        if (currentWikiPageId) {
            replaceRow('wiki-list', currentWikiPageId, 'data-wiki-id', data.row);
        } else {
            document.getElementById('wiki-list').insertAdjacentHTML('afterbegin', data.row);
        }
        closeSheet('editArticle');
    }).catch(err => showFormError('editArticle', err.message));
}

let toggleWikiId = null;
function openWikiToggleConfirm(id, title, isPublished) {
    toggleWikiId = id;
    document.getElementById('wikiToggleTitle').textContent = (isPublished ? 'Деактивировать' : 'Активировать') + ' статью';
    document.getElementById('wikiToggleName').value = title;
    openSheet('wikiToggleConfirm');
}
function submitWikiToggle() {
    clearFormError('wikiToggleConfirm');
    postJson(`/settings/wiki/${toggleWikiId}/toggle`, {}).then(data => {
        replaceRow('wiki-list', toggleWikiId, 'data-wiki-id', data.row);
        closeSheet('wikiToggleConfirm');
    }).catch(err => showFormError('wikiToggleConfirm', err.message));
}

async function initWikiEditor(textareaId) {
    if (wikiEditorInstance) {
        await wikiEditorInstance.destroy();
        wikiEditorInstance = null;
    }
    const {
        ClassicEditor, Essentials, Bold, Italic, Heading, Link, List,
        Table, TableToolbar, Image, ImageUpload, ImageResize,
        Paragraph, BlockQuote, Indent, SimpleUploadAdapter,
    } = await import('https://cdn.ckeditor.com/ckeditor5/43.3.1/ckeditor5.js');

    wikiEditorInstance = await ClassicEditor.create(document.getElementById(textareaId), {
        plugins: [
            Essentials, Bold, Italic, Heading, Link, List,
            Table, TableToolbar, Image, ImageUpload, ImageResize,
            Paragraph, BlockQuote, Indent, SimpleUploadAdapter,
        ],
        toolbar: [
            'heading', '|', 'bold', 'italic', '|', 'link', 'blockQuote', '|',
            'bulletedList', 'numberedList', 'indent', 'outdent', '|',
            'insertTable', '|', 'uploadImage', '|', 'undo', 'redo',
        ],
        heading: {
            options: [
                { model: 'paragraph', title: 'Параграф', class: 'ck-heading_paragraph' },
                { model: 'heading1', view: 'h1', title: 'Заголовок 1', class: 'ck-heading_heading1' },
                { model: 'heading2', view: 'h2', title: 'Заголовок 2', class: 'ck-heading_heading2' },
                { model: 'heading3', view: 'h3', title: 'Заголовок 3', class: 'ck-heading_heading3' },
            ],
        },
        table: { contentToolbar: ['tableColumn', 'tableRow', 'mergeTableCells'] },
        simpleUpload: {
            uploadUrl: '{{ route("wiki.upload-image") }}',
            headers: { 'X-CSRF-TOKEN': csrf },
        },
        language: 'ru',
    });
}
</script>
@endpush