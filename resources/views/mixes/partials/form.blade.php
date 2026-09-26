@php $isEdit = isset($mix); @endphp

<div class="form-group">
    <label>Название <span style="color:red">*</span></label>
    <input type="text" name="name" value="{{ old('name', $mix->name ?? '') }}" required placeholder="Название микса">
</div>

<div class="form-group">
    <label>Короткое описание</label>
    <input type="text" name="description_short" value="{{ old('description_short', $mix->description_short ?? '') }}" placeholder="Краткое описание для списка">
</div>

<div class="form-group">
    <label>Полное описание</label>
    <textarea name="description_long" rows="4" placeholder="Подробное описание, особенности...">{{ old('description_long', $mix->description_long ?? '') }}</textarea>
</div>

<div class="form-group">
    <label>Крепость</label>
    <select name="strength">
        <option value="">— Не указано —</option>
        @foreach(['light'=>'Лёгкий','medium'=>'Средний','strong'=>'Крепкий'] as $val => $label)
            <option value="{{ $val }}" {{ old('strength', $mix->strength ?? '') === $val ? 'selected' : '' }}>{{ $label }}</option>
        @endforeach
    </select>
</div>

<div style="margin-bottom:16px;">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
        <label style="font-size:13px; font-weight:600;">Состав <span style="color:red">*</span></label>
        <button type="button" onclick="addIngredient()" class="btn btn-secondary" style="font-size:13px;">+ Добавить табак</button>
    </div>
    <div id="ingredients-list">
        @if($isEdit && $mix->ingredients->count())
            @foreach($mix->ingredients as $i => $ing)
                <div class="ingredient-row" style="border:1px solid #eee; border-radius:8px; padding:16px; margin-bottom:10px;">
                    <div style="display:flex; gap:10px; align-items:end; margin-bottom:10px;">
                        <div style="flex:2;">
                            <label style="font-size:12px; color:#666;">Табак</label>
                            <select name="ingredients[{{ $i }}][tobacco_id]" required style="width:100%; padding:8px; border:1px solid #ddd; border-radius:6px; font-size:14px; margin-top:4px;">
                                <option value="">— Выбрать —</option>
                                @foreach($tobaccos->groupBy(fn($t) => $t->brand?->name ?? 'Без бренда') as $brand => $group)
                                    <optgroup label="{{ $brand }}">
                                        @foreach($group as $tobacco)
                                            <option value="{{ $tobacco->id }}" {{ $ing->tobacco_id == $tobacco->id ? 'selected' : '' }}>{{ $tobacco->name }}</option>
                                        @endforeach
                                    </optgroup>
                                @endforeach
                            </select>
                        </div>
                        <div style="flex:1;">
                            <label style="font-size:12px; color:#666;">Количество</label>
                            <div style="display:flex; gap:4px; margin-top:4px;">
                                <input type="number" name="ingredients[{{ $i }}][grams]" value="{{ $ing->grams }}" min="0" step="0.1" placeholder="—" style="width:100%; padding:8px; border:1px solid #ddd; border-radius:6px; font-size:14px;">
                                <select name="ingredients[{{ $i }}][unit]" style="padding:8px; border:1px solid #ddd; border-radius:6px; font-size:13px; width:70px;">
                                    <option value="g" {{ ($ing->unit ?? 'g') === 'g' ? 'selected' : '' }}>г</option>
                                    <option value="pct" {{ ($ing->unit ?? 'g') === 'pct' ? 'selected' : '' }}>%</option>
                                </select>
                            </div>
                        </div>
                        <button type="button" onclick="this.closest('.ingredient-row').remove()" style="background:none; border:1px solid #f0a0a0; color:#c95a2a; border-radius:6px; padding:8px 12px; cursor:pointer;">✕</button>
                    </div>
                    <div class="alts-container">
                        <div style="font-size:12px; color:#999; margin-bottom:6px;">Альтернативы:</div>
                        @foreach($ing->alternatives as $j => $alt)
                            <div class="alt-row" style="display:flex; gap:8px; align-items:end; margin-bottom:6px;">
                                <div style="flex:2;">
                                    <select name="ingredients[{{ $i }}][alternatives][{{ $j }}][tobacco_id]" style="width:100%; padding:6px; border:1px solid #ddd; border-radius:6px; font-size:13px;">
                                        <option value="">— Альтернатива —</option>
                                        @foreach($tobaccos->groupBy(fn($t) => $t->brand?->name ?? 'Без бренда') as $brand => $group)
                                            <optgroup label="{{ $brand }}">
                                                @foreach($group as $tobacco)
                                                    <option value="{{ $tobacco->id }}" {{ $alt->tobacco_id == $tobacco->id ? 'selected' : '' }}>{{ $tobacco->name }}</option>
                                                @endforeach
                                            </optgroup>
                                        @endforeach
                                    </select>
                                </div>
                                <div style="flex:1;">
                                    <div style="display:flex; gap:4px;">
                                        <input type="number" name="ingredients[{{ $i }}][alternatives][{{ $j }}][grams]" value="{{ $alt->grams }}" min="0" step="0.1" placeholder="—" style="width:100%; padding:6px; border:1px solid #ddd; border-radius:6px; font-size:13px;">
                                        <select name="ingredients[{{ $i }}][alternatives][{{ $j }}][unit]" style="padding:6px; border:1px solid #ddd; border-radius:6px; font-size:13px; width:70px;">
                                            <option value="g" {{ ($alt->unit ?? 'g') === 'g' ? 'selected' : '' }}>г</option>
                                            <option value="pct" {{ ($alt->unit ?? 'g') === 'pct' ? 'selected' : '' }}>%</option>
                                        </select>
                                    </div>
                                </div>
                                <button type="button" onclick="this.closest('.alt-row').remove()" style="background:none; border:none; color:#999; cursor:pointer; font-size:16px;">×</button>
                            </div>
                        @endforeach
                    </div>
                    <button type="button" onclick="addAlt(this)" style="background:none; border:1px dashed #ddd; border-radius:6px; padding:4px 12px; cursor:pointer; font-size:12px; color:#666; margin-top:4px;">+ Альтернатива</button>
                </div>
            @endforeach
        @endif
    </div>
</div>

<div class="form-group">
    <label>Заметка</label>
    <textarea name="note" rows="2" placeholder="Заметка к миксу..."></textarea>
</div>

<script>
let ingIndex = {{ $isEdit ? $mix->ingredients->count() : 0 }};
let altIndex = 0;

const tobaccoOptions = `@foreach($tobaccos->groupBy(fn($t) => $t->brand?->name ?? 'Без бренда') as $brand => $group)<optgroup label="{{ addslashes($brand) }}">@foreach($group as $tobacco)<option value="{{ $tobacco->id }}">{{ addslashes($tobacco->name) }}</option>@endforeach</optgroup>@endforeach`;

function addIngredient() {
    const i = ingIndex++;
    const html = `
        <div class="ingredient-row" style="border:1px solid #eee; border-radius:8px; padding:16px; margin-bottom:10px;">
            <div style="display:flex; gap:10px; align-items:end; margin-bottom:10px;">
                <div style="flex:2;">
                    <label style="font-size:12px; color:#666;">Табак</label>
                    <select name="ingredients[${i}][tobacco_id]" required style="width:100%; padding:8px; border:1px solid #ddd; border-radius:6px; font-size:14px; margin-top:4px;">
                        <option value="">— Выбрать —</option>
                        ${tobaccoOptions}
                    </select>
                </div>
                <div style="flex:1;">
                    <label style="font-size:12px; color:#666;">Количество</label>
                    <div style="display:flex; gap:4px; margin-top:4px;">
                        <input type="number" name="ingredients[${i}][grams]" min="0" step="0.1" placeholder="—" style="width:100%; padding:8px; border:1px solid #ddd; border-radius:6px; font-size:14px; margin-top:0;">
                        <select name="ingredients[${i}][unit]" style="padding:8px; border:1px solid #ddd; border-radius:6px; font-size:13px; width:70px;">
                            <option value="g">г</option>
                            <option value="pct">%</option>
                        </select>
                    </div>
                </div>
                <button type="button" onclick="this.closest('.ingredient-row').remove()" style="background:none; border:1px solid #f0a0a0; color:#c95a2a; border-radius:6px; padding:8px 12px; cursor:pointer;">✕</button>
            </div>
            <div class="alts-container"></div>
            <button type="button" onclick="addAlt(this)" style="background:none; border:1px dashed #ddd; border-radius:6px; padding:4px 12px; cursor:pointer; font-size:12px; color:#666; margin-top:4px;">+ Альтернатива</button>
        </div>`;
    document.getElementById('ingredients-list').insertAdjacentHTML('beforeend', html);
}

function addAlt(btn) {
    const container = btn.previousElementSibling;
    const ingRow    = btn.closest('.ingredient-row');
    const ingSelect = ingRow.querySelector('select[name*="tobacco_id"]');
    const ingName   = ingSelect?.name.match(/ingredients\[(\d+)\]/)?.[1] ?? ingIndex;
    const j         = altIndex++;
    const html = `
        <div class="alt-row" style="display:flex; gap:8px; align-items:end; margin-bottom:6px;">
            <div style="flex:2;">
                <select name="ingredients[${ingName}][alternatives][${j}][tobacco_id]" style="width:100%; padding:6px; border:1px solid #ddd; border-radius:6px; font-size:13px;">
                    <option value="">— Альтернатива —</option>
                    ${tobaccoOptions}
                </select>
            </div>
            <div style="flex:1;">
                <div style="display:flex; gap:4px;">
                    <input type="number" name="ingredients[${ingName}][alternatives][${j}][grams]" min="0" step="0.1" placeholder="—" style="width:100%; padding:6px; border:1px solid #ddd; border-radius:6px; font-size:13px;">
                    <select name="ingredients[${ingName}][alternatives][${j}][unit]" style="padding:6px; border:1px solid #ddd; border-radius:6px; font-size:13px; width:70px;">
                        <option value="g">г</option>
                        <option value="pct">%</option>
                    </select>
                </div>
            </div>
            <button type="button" onclick="this.closest('.alt-row').remove()" style="background:none; border:none; color:#999; cursor:pointer; font-size:16px;">×</button>
        </div>`;
    container.insertAdjacentHTML('beforeend', html);
}
</script>