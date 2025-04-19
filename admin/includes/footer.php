<!-- Script comunes para todas las páginas -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.1/dist/chart.min.js"></script>

<!-- Scripts específicos con nonce para CSP -->
<script nonce="<?php echo $_SESSION['admin_csrf_token']; ?>">
    // Ejecutar al cargar el DOM
    document.addEventListener('DOMContentLoaded', function() {
        // Tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function(tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });

        // Auto-cierre de alertas
        setTimeout(function() {
            var alerts = document.querySelectorAll('.alert.alert-dismissible');
            alerts.forEach(function(alert) {
                var bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000); // 5 segundos
    });
</script>

<?php
// Cargar scripts específicos de la página si existen
if (isset($page_scripts)):
?>
    <script nonce="<?php echo $_SESSION['admin_csrf_token']; ?>">
        <?php echo $page_scripts; ?>
    </script>
<?php endif; ?>

</body>

</html>