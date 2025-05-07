<?php
session_start();
if (!isset($_SESSION['id_usuario'])) {
    header("Location: ../index.php");
    exit();
} // Asegurar que la sesión esté activa
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Archivos</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="menuAdministrador.css">
    <!-- Font Awesome v6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
</head>

<body>
    <nav class="navbar navbar-dark bg-dark w-100">
        <div class="container-fluid">
            <span class="navbar-brand">Sistema de Archivos</span>
            <div class="dropdown">
                <a class="d-flex align-items-center text-white text-decoration-none dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fas fa-user-circle me-2"></i>
                    <!-- Cambiamos "Bienvenido" por el cargo del usuario -->
                    <span class="text-warning" style="font-weight: bold;"><?php echo isset($_SESSION['cargo']) ? $_SESSION['cargo'] : 'Usuario'; ?></span>, <?php echo isset($_SESSION['nombreUsuario']) ? $_SESSION['nombreUsuario'] : 'Usuario'; ?>
                </a>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li class="px-3 py-2">
                        <strong><?php echo isset($_SESSION['nombreUsuario']) ? $_SESSION['nombreUsuario'] : 'Usuario'; ?></strong><br>
                        <small class="text-muted"><?php echo isset($_SESSION['correo']) ? $_SESSION['correo'] : 'Correo no disponible'; ?></small>
                    </li>
                    <li>
                        <hr class="dropdown-divider">
                    </li>
                    <li>
                        <a class="dropdown-item text-danger" href="../configuracion/cerrarSesion.php">
                            <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    <div class="d-flex">
        <div id="sidebar">
            <!-- Opción RECTOR -->
            <a href="#" data-bs-toggle="collapse" data-bs-target="#menuPerfil">
                <i class="fas fa-user-graduate me-2"></i> RECTOR
            </a>
            <div class="collapse" id="menuPerfil">
                <a href="#" onclick="loadPage('perfilAdministrador.php')" class="ps-3">
                    <i class="fas fa-id-badge me-2"></i> Perfil
                </a>
            </div>

            <!-- Opción Registro -->
            <a href="#" data-bs-toggle="collapse" data-bs-target="#menuRegistro">
                <i class="fas fa-edit me-2"></i> Registro
            </a>
            <div class="collapse" id="menuRegistro">
                <a href="#" onclick="loadPage('registroUsuario.php')" class="ps-3">
                    <i class="fas fa-clipboard-list me-2"></i> Registro Usuario
                </a>
            </div>

            <!-- Opción Archivos -->
            <a href="#" data-bs-toggle="collapse" data-bs-target="#menuArchivos">
                <i class="fas fa-folder me-2"></i> Archivos
            </a>
            <div class="collapse" id="menuArchivos">
                <a href="#" onclick="loadPage('revisarArchivos.php')" class="ps-3">
                    <i class="fas fa-file-alt me-2"></i> Revisar Archivos
                </a>
            </div>

            <!-- Opción Reportes -->
            <a href="#" data-bs-toggle="collapse" data-bs-target="#menuReportes">
                <i class="fas fa-chart-bar me-2"></i> Reportes
            </a>
            <div class="collapse" id="menuReportes">
                <a href="#" onclick="loadPage('reporteArchivos.php')" class="ps-3">
                    <i class="fas fa-chart-line me-2"></i> Reporte Archivos
                </a>
                <a href="#" onclick="loadPage('inicio.php')" class="ps-3">
                    <i class="fas fa-chart-line me-2"></i> Subir Archivos
                </a>
            </div>

            <!-- Opción Ayuda -->
            <a href="#" onclick="loadPage('imprimirReportes.php')">
                <i class="fas fa-file-alt me-2"></i> Descargar Reportes
            </a>

            <!-- Opción Acerca De -->
            <a href="#" onclick="loadPage('../usuario/acerca.html')">
                <i class="fas fa-info-circle me-2"></i> Acerca De
            </a>
        </div>
        <div id="content">
            <iframe id="iframe" src="inicio.php"></iframe>
        </div>
    </div>
    <script>
        function loadPage(url) {
            document.getElementById('iframe').src = url;
        }
    </script>
</body>

</html>