@php $isEdit = isset($guest) && $guest; @endphp

<form id="{{ $isEdit ? 'editGuestForm' : 'createGuestForm' }}" method="POST" action="{{ $isEdit ? route('guests.update', $guest) : route('guests.store') }}" data-guest-id="{{ $isEdit ? $guest->id : '' }}">
    @csrf
    @if($isEdit) @method('PUT') @endif

    <div class="actionTabCnt actionTabCnt6nsd5">
       <div class="actionTabSection">
            <label class="actionTabTitle">Имя*</label>
            <div class="defaultForm">
                <input type="text" name="name" id="{{ $isEdit ? 'editGuestName' : 'createGuestName' }}" placeholder="Антон С., Игорь Сергеев..." value="{{ $isEdit ? $guest->name : '' }}" required oninput="toggleGuestFormSubmit(this)">
            </div>
        </div>

        @if(!$isEdit)
        <div class="actionTabSection">
            <label class="actionTabTitle">Username</label>
            <div class="defaultForm">
                <input type="text" name="username" placeholder="Необязательно — сгенерируется автоматически">
            </div>
        </div>
        @endif

        <div class="actionTabSection">
            <label class="actionTabTitle">Номер телефона</label>
            <div class="defaultForm">
                <input type="text" name="phone" placeholder="Необязательно" value="{{ $isEdit ? $guest->phone : '' }}">
            </div>
        </div>

        <div class="actionTabSection">
            <label class="actionTabTitle">Email</label>
            <div class="defaultForm">
                <input type="text" name="email" placeholder="Необязательно" value="{{ $isEdit ? $guest->email : '' }}">
            </div>
        </div>

        @if(!$isEdit)
        <div class="actionTabSection">
            <label class="actionTabTitle">Заметка</label>
            <div class="defaultForm">
                <textarea name="note" rows="3"></textarea>
            </div>
        </div>
        @endif
    </div>

    <div class="actionTabActions --actionTabActions9f3d1">
        <div class="actionTabActionsButtons">
            <button type="submit" id="{{ $isEdit ? 'editGuestSubmitBtn' : 'createGuestSubmitBtn' }}" class="actionTabActionsButton --buttonPrimary" {{ !$isEdit ? 'disabled' : '' }}>Сохранить</button>
            <button type="button" onclick="closeSheet('createGuest')" class="actionTabActionsButton --buttonSecondary">Отменить</button>
        </div>
    </div>
</form>