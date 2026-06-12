<?php
// admin/includes/footer.php
if (!defined('DATA_DIR')) {
    exit;
}
?>
    <div class="wp-admin-footer">
        <p>Raptio へのご協力に感謝いたします。</p>
        <p>バージョン 1.8.5</p>
    </div>

</div></div></div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const submenus = document.querySelectorAll('.has-submenu > a');

    submenus.forEach(function(submenu) {
        submenu.addEventListener('click', function(e) {
            e.preventDefault();
            
            const parent = this.parentElement;
            const isOpen = parent.classList.contains('open');

            // 他のメニューをすべて閉じる
            document.querySelectorAll('.has-submenu').forEach(function(el) {
                el.classList.remove('open');
            });

            // クリックしたメニューが閉じていた場合のみ開く
            if (!isOpen) {
                parent.classList.add('open');
            }
        });
    });
});
</script>

</body>
</html>