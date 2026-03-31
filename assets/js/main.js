/**
 * CD SHIPPING HUB - Main JavaScript
 */

document.addEventListener('DOMContentLoaded', function () {

    // Auto-dismiss alerts after 5 seconds
    document.querySelectorAll('.alert-dismissible').forEach(function (alert) {
        setTimeout(function () {
            var bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
            if (bsAlert) bsAlert.close();
        }, 5000);
    });

    // Smooth scroll for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(function (anchor) {
        anchor.addEventListener('click', function (e) {
            var target = document.querySelector(this.getAttribute('href'));
            if (target) {
                e.preventDefault();
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
    });

    // Back to top button
    var btnTop = document.createElement('button');
    btnTop.innerHTML = '<i class="bi bi-chevron-up"></i>';
    btnTop.className = 'btn btn-primary btn-back-to-top';
    btnTop.style.cssText = 'position:fixed;bottom:30px;right:30px;z-index:999;display:none;width:45px;height:45px;border-radius:50%;font-size:1.2rem;padding:0;box-shadow:0 4px 15px rgba(13,110,253,0.3)';
    document.body.appendChild(btnTop);

    window.addEventListener('scroll', function () {
        btnTop.style.display = window.scrollY > 300 ? 'block' : 'none';
    });

    btnTop.addEventListener('click', function () {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });

    // Add loading state to forms on submit
    document.querySelectorAll('form').forEach(function (form) {
        form.addEventListener('submit', function () {
            var btn = form.querySelector('button[type="submit"]');
            if (btn && !btn.disabled) {
                btn.disabled = true;
                var originalText = btn.innerHTML;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Processing...';
                setTimeout(function () {
                    btn.disabled = false;
                    btn.innerHTML = originalText;
                }, 10000);
            }
        });
    });

    // Image preview for file inputs
    document.querySelectorAll('input[type="file"][accept*="image"]').forEach(function (input) {
        input.addEventListener('change', function () {
            if (this.files && this.files[0]) {
                var reader = new FileReader();
                var preview = this.parentElement.querySelector('.img-thumbnail');
                if (!preview) {
                    preview = document.createElement('img');
                    preview.className = 'img-thumbnail mt-2';
                    preview.style.maxHeight = '120px';
                    this.parentElement.insertBefore(preview, this);
                }
                reader.onload = function (e) {
                    preview.src = e.target.result;
                };
                reader.readAsDataURL(this.files[0]);
            }
        });
    });

    // Tooltip initialization
    var tooltips = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    tooltips.forEach(function (el) {
        new bootstrap.Tooltip(el);
    });
});
