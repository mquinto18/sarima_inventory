{{-- Post-login splash: the logo pops in, holds, then the whole overlay fades
     away to reveal the page underneath.

     Driven entirely by CSS with `forwards`, so it still clears itself if the JS
     never runs. `pointer-events: none` from the start means it can never trap
     the user behind an invisible overlay — the worst failure mode for a
     full-screen element like this. --}}
<div class="login-splash" id="loginSplash" role="presentation" aria-hidden="true">
    <div class="login-splash__inner">
        {{-- The 512px asset: at this size the 128px one is visibly soft on a
             retina screen. The login page preloads it (auth/login.blade.php) so
             it is already cached by the time the splash renders, and the
             dashboard reuses it for its watermark, so the fetch is not wasted. --}}
        <img class="login-splash__logo" src="{{ asset('images/logo-icon.png') }}" alt="">
        <div class="login-splash__word">LARIOS PHARMACY</div>
    </div>
</div>

<style>
    .login-splash {
        position: fixed;
        inset: 0;
        z-index: 10070;
        /* Above the toast (10060) so a flashed message cannot show through. */
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(150deg, var(--color-primary, #6366f1) 0%, var(--color-primary-dark, #4338ca) 100%);
        pointer-events: none;
        animation: loginSplashOut 520ms var(--ease-out, cubic-bezier(.4, 0, .2, 1)) 900ms forwards;
    }

    .login-splash__inner {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 26px;
        padding: 24px;
    }

    /* Scales with the viewport so it stays large on a desktop without
       overflowing a phone. Percentage radius keeps the rounding proportional. */
    .login-splash__logo {
        width: min(240px, 46vw);
        height: min(240px, 46vw);
        border-radius: 22%;
        background: #fff;
        box-shadow: 0 26px 70px rgba(15, 23, 42, 0.32);
        animation: loginSplashPop 620ms cubic-bezier(.2, .9, .25, 1.15) both;
    }

    .login-splash__word {
        color: #fff;
        font-weight: 800;
        font-size: clamp(1.05rem, 2.4vw, 1.45rem);
        letter-spacing: 0.16em;
        text-align: center;
        opacity: 0;
        animation: loginSplashWord 420ms var(--ease-out, ease) 260ms forwards;
    }

    @keyframes loginSplashPop {
        0% { opacity: 0; transform: scale(0.62); }
        62% { opacity: 1; transform: scale(1.06); }
        100% { opacity: 1; transform: scale(1); }
    }

    @keyframes loginSplashWord {
        from { opacity: 0; transform: translateY(6px); }
        to { opacity: 1; transform: none; }
    }

    @keyframes loginSplashOut {
        to { opacity: 0; visibility: hidden; }
    }

    /* The global reduced-motion rule collapses these durations, which lands the
       splash straight on its end state — hidden. That is the right outcome:
       no animation, no delay, straight to the page. */
</style>

<script>
    (function () {
        var splash = document.getElementById('loginSplash');
        if (!splash) return;

        function remove() {
            if (splash && splash.parentNode) {
                splash.parentNode.removeChild(splash);
                splash = null;
            }
        }

        splash.addEventListener('animationend', function (e) {
            // Only the overlay's own fade-out ends the splash; the logo pop and
            // the wordmark fade bubble up here too.
            if (e.animationName === 'loginSplashOut') remove();
        });

        // Failsafe: if the animation never fires (reduced motion collapsing it,
        // a backgrounded tab, an interrupted paint), take the node out anyway.
        setTimeout(remove, 3000);
    })();
</script>
