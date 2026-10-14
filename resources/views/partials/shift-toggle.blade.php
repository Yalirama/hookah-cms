<button type="button" class="shiftToggle {{ auth()->user()->on_shift ? '--on' : '' }}" data-on="{{ auth()->user()->on_shift ? 1 : 0 }}" onclick="toggleShift(this)">
    <span class="shiftToggleDot"></span>
    <span class="shiftToggleText">{{ auth()->user()->on_shift ? 'На смене' : 'Не на смене' }}</span>
</button>