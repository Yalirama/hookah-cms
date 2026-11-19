@php $catalogTab = request()->routeIs('mixes.*') ? 'mixes' : (request()->routeIs('labels.*') ? 'labels' : 'tobaccos'); @endphp
<nav class="catalogTabs">
    <a href="{{ route('tobaccos.index') }}" class="{{ $catalogTab === 'tobaccos' ? 'active' : '' }}">Табаки</a>
    <a href="{{ route('mixes.index') }}" class="{{ $catalogTab === 'mixes' ? 'active' : '' }}">Миксы</a>
    <a href="{{ route('labels.index') }}" class="{{ $catalogTab === 'labels' ? 'active' : '' }}">Ярлыки</a>
</nav>
<script>try{localStorage.setItem('catalogTab','{{ $catalogTab }}')}catch(e){}</script>
