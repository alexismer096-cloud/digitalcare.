<?php
declare(strict_types=1);

if (!function_exists('digital_care_header')) {
    function digital_care_cart_count(?mysqli $conexion = null): int
    {
        if (!$conexion || empty($_SESSION['id_usuario'])) {
            return 0;
        }

        $stmt = mysqli_prepare($conexion, 'SELECT COALESCE(SUM(cantidad), 0) total FROM carrito WHERE id_usuario = ?');
        if (!$stmt) {
            return 0;
        }

        $idUsuario = (int) $_SESSION['id_usuario'];
        mysqli_stmt_bind_param($stmt, 'i', $idUsuario);
        mysqli_stmt_execute($stmt);
        $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        mysqli_stmt_close($stmt);

        return (int) ($row['total'] ?? 0);
    }

    function digital_care_header(?mysqli $conexion = null): void
    {
        $cartCount = digital_care_cart_count($conexion);
        ?>
        <header class="topbar">
            <a class="brand small brand-link" href="index.php?view=dashboard" aria-label="Digital Care">
                <span class="brand-blue">Digital</span>
                <span class="brand-green">Care</span>
            </a>
            <nav class="topbar-icons" aria-label="Acciones principales">
                <a href="index.php?view=dashboard" class="top-icon" title="Dashboard" aria-label="Dashboard">🏠</a>
                <a href="carrito.php" class="top-icon cart-icon" title="Carrito" aria-label="Carrito">
                    🛒
                    <span id="cartCount" class="cart-count"<?= $cartCount > 0 ? '' : ' hidden' ?>><?= $cartCount ?></span>
                </a>
                <a href="logout.php" class="top-icon" title="Cerrar sesión" aria-label="Cerrar sesión">🚪</a>
            </nav>
        </header>
        <?php
    }
}
