const x7y9z3 = "1313";
const passwordDialog = document.getElementById('passwordDialog');
const container = document.querySelector('.container');
const passwordInput = document.getElementById('passwordInput');
const submitPassword = document.getElementById('submitPassword');
const errorMessage = document.getElementById('errorMessage');
const logoElements = document.querySelectorAll('.logo');

let attemptCount = 0;
const maxAttempts = 10;
let isCtrlPressed = false;
let lastClickTime = 0;
const doubleClickDelay = 300; // milliseconds

function checkPassword() {
    if (passwordInput.value === x7y9z3) {
        setAuthenticated();
        showContent();
    } else {
        attemptCount++;
        if (attemptCount >= maxAttempts) {
            errorMessage.textContent = 'Too many failed attempts. Please try again later.';
            passwordInput.disabled = true;
            submitPassword.disabled = true;
        } else {
            errorMessage.textContent = `Incorrect password. ${maxAttempts - attemptCount} attempts remaining.`;
            passwordInput.value = '';
        }
    }
}

function setAuthenticated() {
    const now = new Date();
    const endOfDay = new Date(now.getFullYear(), now.getMonth(), now.getDate(), 23, 59, 59, 999);
    localStorage.setItem('authExpiration', endOfDay.getTime());
}

function isAuthenticated() {
    const authExpiration = localStorage.getItem('authExpiration');
    if (authExpiration) {
        if (Date.now() < parseInt(authExpiration)) {
            return true;
        } else {
            localStorage.removeItem('authExpiration');
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
                    // This is a control+double-click - redirect to the IP address
                    window.location.href = 'http://192.168.8.127:8888';
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
        event.preventDefault();
        const buttonName = this.textContent.toLowerCase().replace(' ', '');
        const newUrl = '/' + buttonName;
        window.location.href = newUrl;
    });
});

// Initialize the application
init();
