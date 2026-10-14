(function () {
    const KEY = document.querySelector('meta[name="vapid-key"]')?.content;
    if (!KEY || !('serviceWorker' in navigator)) return;

    const csrf = () => document.querySelector('meta[name="csrf-token"]').content;
    const store = {
        get: k => { try { return localStorage.getItem(k); } catch (e) { return null; } },
        set: (k, v) => { try { localStorage.setItem(k, v); } catch (e) {} },
    };

    function toBytes(s) {
        const p = '='.repeat((4 - s.length % 4) % 4);
        const raw = atob((s + p).replace(/-/g, '+').replace(/_/g, '/'));
        return Uint8Array.from(raw, c => c.charCodeAt(0));
    }

    async function sync() {
        const reg = await navigator.serviceWorker.ready;
        let sub = await reg.pushManager.getSubscription();
        if (!sub) sub = await reg.pushManager.subscribe({ userVisibleOnly: true, applicationServerKey: toBytes(KEY) });
        await fetch('/push/subscribe', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf(), 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify(sub.toJSON()),
        });
    }

    function banner(text, btnText, dismissKey, onClick) {
        const el = document.createElement('div');
        el.style.cssText = 'position:fixed;left:12px;right:12px;bottom:12px;z-index:9999;display:flex;gap:12px;align-items:center;justify-content:space-between;padding:12px 14px;border-radius:14px;background:var(--box,#181612);border:1px solid var(--border,#231F18);color:var(--white,#fff);font-size:14px';

        const span = document.createElement('span');
        span.textContent = text;
        el.appendChild(span);

        const actions = document.createElement('div');
        actions.style.cssText = 'display:flex;gap:8px;align-items:center;flex-shrink:0';
        const close = () => { store.set(dismissKey, '1'); el.remove(); };

        if (btnText) {
            const b = document.createElement('button');
            b.type = 'button';
            b.textContent = btnText;
            b.style.cssText = 'background:var(--gold,#CDAA7D);color:var(--dark,#0F0E0C);border:0;border-radius:10px;padding:8px 14px;font-weight:600';
            b.onclick = async () => { try { await onClick(); } catch (e) {} close(); };
            actions.appendChild(b);
        }

        const x = document.createElement('button');
        x.type = 'button';
        x.textContent = '×';
        x.style.cssText = 'background:none;border:0;color:var(--info,#5A5348);font-size:22px;line-height:1';
        x.onclick = close;
        actions.appendChild(x);

        el.appendChild(actions);
        document.body.appendChild(el);
    }

    const isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent);
    const standalone = window.matchMedia('(display-mode: standalone)').matches || navigator.standalone;

    if (!('PushManager' in window) || !('Notification' in window)) {
        if (isIOS && !standalone && !store.get('pushHintDismissed')) {
            banner('Чтобы получать уведомления, добавьте сайт на экран «Домой» и откройте его оттуда', null, 'pushHintDismissed');
        }
        return;
    }

    if (Notification.permission === 'granted') {
        sync().catch(() => {});
    } else if (Notification.permission === 'default' && !store.get('pushAskDismissed')) {
        banner('Включить уведомления о заказах?', 'Включить', 'pushAskDismissed', async () => {
            if (await Notification.requestPermission() === 'granted') await sync();
        });
    }
})();