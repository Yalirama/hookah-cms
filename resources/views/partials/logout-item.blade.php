<div class="item">
    <div class="itemInfo">
        <p>Выйти из аккаунта</p>
        <span>{{ auth()->user()->name }}</span>
    </div>
    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="button" onclick="logoutApp(this)" class="btnOpen btnCancel">Выйти</button>
    </form>
</div>