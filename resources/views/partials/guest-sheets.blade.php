{{-- sheet: карточка гостя --}}
<div class="actionTab viewProductSameTab viewGuest" id="viewGuest">
    <div class="actionTabWrapper">
        <div class="actionTabHeader">
            <h3>Карточка гостя</h3>
            <div class="actionTabHeaderBtns">
                <button type="button" onclick="openGuestEditSheetFromCard()" class="actionTabHeaderBtn --tabBtnClose">
                    <img src="/assets/img/edit-icon-gold-lg.svg" alt="">
                </button>
                <button onclick="closeSheet('viewGuest')" class="actionTabHeaderBtn --tabBtnClose">
                    <img src="/assets/img/close-icon.svg" alt="">
                </button>
            </div>
        </div>
        <div id="viewGuestInner">
            <p style="padding:20px; color:var(--info); text-align:center;">Загрузка...</p>
        </div>
    </div>
</div>

{{-- sheet: создать/редактировать гостя --}}
<div class="actionTab viewProductSameTab createGuest" id="createGuest">
    <div class="actionTabWrapper">
        <div class="actionTabHeader">
            <h3 id="guestFormTitle">Создать гостя</h3>
            <div class="actionTabHeaderBtns">
                <button onclick="closeSheet('createGuest')" class="actionTabHeaderBtn --tabBtnClose">
                    <img src="/assets/img/close-icon.svg" alt="">
                </button>
            </div>
        </div>
        <div id="guestFormInner"></div>
    </div>
</div>

{{-- sheet: ярлыки --}}
<div class="actionTab addOrEditBagesToGuest" id="addOrEditBagesToGuest">
    <div class="actionTabWrapper">
        <div class="actionTabHeader">
            <h3>Добавить/убрать ярлыки <br> <span id="guestLabelsTitle"></span></h3>
            <div class="actionTabHeaderBtns">
                <button onclick="closeSheet('addOrEditBagesToGuest')" class="actionTabHeaderBtn --tabBtnClose">
                    <img src="/assets/img/close-icon.svg" alt="">
                </button>
            </div>
        </div>
        <div id="guestLabelsInner">
            <p style="padding:20px; color:var(--info); text-align:center;">Загрузка...</p>
        </div>
    </div>
</div>

{{-- sheet: полная история визитов --}}
<div class="actionTab viewProductSameTab viewGuestsVisitHistory" id="viewGuestsVisitHistory">
    <div class="actionTabWrapper">
        <div class="actionTabHeader">
            <h3>История визитов</h3>
            <div class="actionTabHeaderBtns">
                <button onclick="closeSheet('viewGuestsVisitHistory')" class="actionTabHeaderBtn --tabBtnClose">
                    <img src="/assets/img/close-icon.svg" alt="">
                </button>
            </div>
        </div>
        <div id="guestVisitHistoryInner">
            <p style="padding:20px; color:var(--info); text-align:center;">Загрузка...</p>
        </div>
    </div>
</div>

<div class="actionTab viewMixStoryTab" id="viewGuestHistoryTab">
    <div class="actionTabWrapper">
        <div class="actionTabHeader">
            <h3>История изменений <br> <span id="guestHistoryTitle"></span></h3>
            <div class="actionTabHeaderBtns">
                <button onclick="closeSheet('viewGuestHistoryTab')" class="actionTabHeaderBtn --tabBtnClose">
                    <img src="/assets/img/close-icon.svg" alt="">
                </button>
            </div>
        </div>
        <div class="viewTabaccoDeactiveListWrapper" id="guestHistoryInner">
            <p style="padding:20px; color:var(--info); text-align:center;">Загрузка...</p>
        </div>
    </div>
</div>