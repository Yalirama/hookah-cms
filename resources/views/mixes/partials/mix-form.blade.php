@php
    $isEdit = isset($mix) && $mix;
    $formId = $isEdit ? 'editMixForm' : 'createMixForm';
    $ingredients = $isEdit ? $mix->ingredients->sortBy('sort_order')->values() : collect();
@endphp

<form id="{{ $formId }}" method="POST" action="{{ $isEdit ? route('mixes.update', $mix) : route('mixes.store') }}" data-mix-id="{{ $isEdit ? $mix->id : '' }}">
    @csrf
    @if($isEdit) @method('PUT') @endif

    <div class="actionTabCnt actionTabCnt6nsd5">
        <div class="actionTabSection">
            <label class="actionTabTitle">Название</label>
            <div class="defaultForm">
                <input type="text" name="name" placeholder="Название" value="{{ $isEdit ? $mix->name : '' }}">
            </div>
        </div>

        <div class="actionTabSection">
            <label class="actionTabTitle">Короткое описание</label>
            <div class="defaultForm">
                <input type="text" name="description_short" placeholder="Короткое описание" value="{{ $isEdit ? $mix->description_short : '' }}">
            </div>
        </div>

        <div class="actionTabSection">
            <label class="actionTabTitle">Полное описание</label>
            <div class="defaultForm">
                <textarea name="description_long" rows="3">{{ $isEdit ? $mix->description_long : '' }}</textarea>
            </div>
        </div>
    </div>

    <div class="actionTabCnt --fx1">
        <p class="analyticsCardTitle">СОСТАВ</p>

        <div class="mixIngredientsWrap" id="{{ $formId }}Ingredients">
            @forelse($ingredients as $i => $ing)
                @include('mixes.partials.ingredient-row', ['index' => $i, 'ing' => $ing, 'formId' => $formId])
            @empty
                @include('mixes.partials.ingredient-row', ['index' => 0, 'ing' => null, 'formId' => $formId])
            @endforelse
        </div>

        <div class="actionTabViewOrderCard --actionTabViewOrderCard9k2sn">
            <div class="actionTabViewOrderCardTop">
                <button type="button" onclick="addMixIngredient('{{ $formId }}')">
                    <img src="/assets/img/edit-icon-gold.svg" alt="">
                    <span>Создать ещё</span>
                </button>
            </div>
        </div>
    </div>

    @if(!$isEdit)
    <div class="actionTabCnt">
        <label class="actionTabTitle">Заметка</label>
        <div class="defaultForm --defaultForm-v42d">
            <textarea name="note" rows="2" placeholder="Заметка к миксу..."></textarea>
        </div>
    </div>
    @else
    <div class="actionTabCnt">
        <label class="actionTabTitle">Комментарий к правке</label>
        <div class="defaultForm --defaultForm-3d91">
            <input type="text" name="comment" placeholder="Что изменили и почему...">
        </div>
    </div>
    @endif

    <div class="actionTabActions --actionTabActions9f3d1 --actionTabActions5c2ds">
        <div class="actionTabActionsButtons">
            <button type="submit" class="actionTabActionsButton --buttonPrimary">Сохранить</button>
            <button type="button" onclick="closeSheet('{{ $isEdit ? 'viewMixEditTab' : 'createMix' }}')" class="actionTabActionsButton --buttonSecondary">Отменить</button>
        </div>
    </div>
</form>