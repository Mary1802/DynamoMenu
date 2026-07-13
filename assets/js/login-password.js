(function () {
    var toggle = document.getElementById('passwordToggle');
    var input = document.getElementById('password');

    if (!toggle || !input) {
        return;
    }

    var icon = toggle.querySelector('i');

    toggle.addEventListener('click', function () {
        var visible = input.type === 'text';
        input.type = visible ? 'password' : 'text';
        toggle.setAttribute('aria-pressed', visible ? 'false' : 'true');
        toggle.setAttribute('aria-label', visible ? 'Afficher le mot de passe' : 'Masquer le mot de passe');

        if (icon) {
            icon.classList.toggle('bi-eye', visible);
            icon.classList.toggle('bi-eye-slash', !visible);
        }
    });
})();
