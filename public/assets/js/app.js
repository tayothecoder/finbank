// dark mode toggle
const darkToggle = document.getElementById('darkToggle');
if (darkToggle) {
    darkToggle.addEventListener('click', function () {
        const isDark = document.documentElement.classList.toggle('dark');
        localStorage.setItem('darkMode', isDark);
    });
}

// mobile nav toggle
const mobileMenuBtn = document.getElementById('mobileMenuBtn');
const mobileMenu = document.getElementById('mobileMenu');
if (mobileMenuBtn && mobileMenu) {
    mobileMenuBtn.addEventListener('click', function () {
        mobileMenu.classList.toggle('hidden');
    });
}

// pin pad functionality
const pinPad = document.getElementById('pinPad');
if (pinPad) {
    let pinValue = '';
    const pinInput = document.getElementById('pinInput');
    const pinDots = document.querySelectorAll('.pin-dot');
    const pinSubmit = document.getElementById('pinSubmit');

    function updatePinDisplay() {
        pinDots.forEach(function (dot, i) {
            dot.textContent = i < pinValue.length ? '*' : '';
        });
        pinInput.value = pinValue;
        if (pinSubmit) {
            pinSubmit.disabled = pinValue.length !== 4;
            pinSubmit.classList.toggle('opacity-40', pinValue.length !== 4);
        }
    }

    pinPad.addEventListener('click', function (e) {
        const btn = e.target.closest('.pin-key');
        if (!btn) return;
        const key = btn.dataset.key;

        if (key === 'clear') {
            pinValue = '';
        } else if (key === 'back') {
            pinValue = pinValue.slice(0, -1);
        } else if (pinValue.length < 4) {
            pinValue += key;
        }
        updatePinDisplay();
    });
}

// basic client-side form validation feedback
document.querySelectorAll('form').forEach(function (form) {
    form.addEventListener('submit', function () {
        var btn = form.querySelector('button[type="submit"]');
        if (btn && !btn.disabled) {
            btn.disabled = true;
            btn.textContent = 'Please wait...';
            // re-enable after 5s in case of error
            setTimeout(function () {
                btn.disabled = false;
                btn.textContent = btn.dataset.originalText || btn.textContent;
            }, 5000);
        }
    });

    // store original button text
    var btn = form.querySelector('button[type="submit"]');
    if (btn) btn.dataset.originalText = btn.textContent;
});
