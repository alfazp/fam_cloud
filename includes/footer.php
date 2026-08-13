<?php
/**
 * includes/footer.php
 * 
 * Template Footer HTML global.
 * Menutup pembungkus layout, memuat Bootstrap 5 JS Bundle, dan script interaktif kustom.
 */
?>
    </div> <!-- /#wrapper -->

    <!-- Bootstrap 5.3.x Bundle dengan Popper JS (CDN) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>
    
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const wrapper = document.getElementById('wrapper');
            const sidebarCollapse = document.getElementById('sidebarCollapse');

            document.querySelectorAll('.table-responsive .modal').forEach(function (modal) {
                document.body.appendChild(modal);
            });

            if (wrapper && window.innerWidth < 992) {
                wrapper.classList.add('sidebar-collapsed');
            }

            if (sidebarCollapse && wrapper) {
                sidebarCollapse.addEventListener('click', function () {
                    wrapper.classList.toggle('sidebar-collapsed');
                });

                window.addEventListener('resize', function() {
                    if (window.innerWidth < 992) {
                        wrapper.classList.add('sidebar-collapsed');
                    }
                });
            }
        });
    </script>
</body>
</html>
