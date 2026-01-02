const x7y9z3 = "1313";
const passwordDialog = document.getElementById('passwordDialog');
const container = document.querySelector('.container');
const passwordInput = document.getElementById('passwordInput');
const submitPassword = document.getElementById('submitPassword');
const errorMessage = document.getElementById('errorMessage');
const attemptCounter = document.getElementById('attemptCounter');
const togglePassword = document.getElementById('togglePassword');
const logoElements = document.querySelectorAll('.logo');

// Storage adapter - uses LiveCode compatibility layer if available, falls back to localStorage
const storage = (window.LiveCodeCompat && window.LiveCodeCompat.storage) || {
    getItem: function(key) { try { return localStorage.getItem(key); } catch(e) { return null; } },
    setItem: function(key, value) { try { localStorage.setItem(key, value); } catch(e) {} },
    removeItem: function(key) { try { localStorage.removeItem(key); } catch(e) {} }
};

let attemptCount = 0;
const maxAttempts = 10;
let isCtrlPressed = false;
let lastClickTime = 0;
const doubleClickDelay = 300; // milliseconds

// Password visibility toggle
if (togglePassword) {
    togglePassword.addEventListener('click', function() {
        const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
        passwordInput.setAttribute('type', type);

        // Update icon and aria-label
        const eyeIcon = document.getElementById('eyeIcon');
        if (type === 'text') {
            this.setAttribute('aria-label', 'Hide password');
            eyeIcon.innerHTML = '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line>';
        } else {
            this.setAttribute('aria-label', 'Show password');
            eyeIcon.innerHTML = '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle>';
        }
    });
}

function updateAttemptCounter() {
    if (attemptCounter && attemptCount > 0 && attemptCount < maxAttempts) {
        attemptCounter.textContent = `${maxAttempts - attemptCount} attempts remaining`;
    } else if (attemptCounter) {
        attemptCounter.textContent = '';
    }
}

function checkPassword() {
    if (passwordInput.value === x7y9z3) {
        // Show success feedback before transitioning
        errorMessage.style.color = '#00C853';
        errorMessage.textContent = 'Access granted!';
        submitPassword.disabled = true;

        setTimeout(() => {
            setAuthenticated();
            showContent();
            errorMessage.textContent = '';
            errorMessage.style.color = '';
        }, 500);
    } else {
        attemptCount++;
        if (attemptCount >= maxAttempts) {
            errorMessage.textContent = 'Too many failed attempts. Please refresh the page to try again.';
            passwordInput.disabled = true;
            submitPassword.disabled = true;
            if (attemptCounter) attemptCounter.textContent = '';
        } else {
            errorMessage.textContent = 'Incorrect password. Please try again.';
            passwordInput.value = '';
            passwordInput.focus();
            updateAttemptCounter();

            // Add shake animation
            passwordInput.classList.add('shake');
            setTimeout(() => passwordInput.classList.remove('shake'), 500);
        }
    }
}

function setAuthenticated() {
    const now = new Date();
    const endOfDay = new Date(now.getFullYear(), now.getMonth(), now.getDate(), 23, 59, 59, 999);
    storage.setItem('authExpiration', endOfDay.getTime().toString());
}

function isAuthenticated() {
    const authExpiration = storage.getItem('authExpiration');
    if (authExpiration) {
        if (Date.now() < parseInt(authExpiration)) {
            return true;
        } else {
            storage.removeItem('authExpiration');
        }
    }
    return false;
}

function showContent() {
    passwordDialog.style.display = 'none';
    container.style.display = 'block';
}

function init() {
    if (isAuthenticated()) {
        showContent();
    } else {
        passwordDialog.style.display = 'flex';
        container.style.display = 'none';
    }
    
    // Set up event listeners for the logo control+double-click functionality
    setupLogoControlDoubleClick();
}

// Track Control key state
document.addEventListener('keydown', function(event) {
    if (event.key === 'Control') {
        isCtrlPressed = true;
    }
});

document.addEventListener('keyup', function(event) {
    if (event.key === 'Control') {
        isCtrlPressed = false;
    }
});

// Setup logo control+double-click functionality
function setupLogoControlDoubleClick() {
    logoElements.forEach(logo => {
        logo.addEventListener('click', function(event) {
            const currentTime = new Date().getTime();
            
            if (isCtrlPressed) {
                // Check if this is a double click (two clicks within doubleClickDelay ms)
                if (currentTime - lastClickTime < doubleClickDelay) {
                    // This is a control+double-click - redirect to home page
                    window.location.href = 'index.html';
                }
                
                // Update the last click time
                lastClickTime = currentTime;
            }
        });
    });
}

submitPassword.addEventListener('click', checkPassword);

passwordInput.addEventListener('keyup', function(event) {
    if (event.key === 'Enter') {
        checkPassword();
    }
});

document.querySelectorAll('.button').forEach(button => {
    button.addEventListener('click', function(event) {
        // Only intercept if this button doesn't have an href (dynamically generated ones do)
        if (!this.href || this.href === window.location.href) {
            event.preventDefault();
            const buttonName = this.textContent.toLowerCase().replace(' ', '');
            const newUrl = buttonName + '/';
            window.location.href = newUrl;
        }
        // Let buttons with proper hrefs navigate normally
    });
});

// Initialize the application
init();
