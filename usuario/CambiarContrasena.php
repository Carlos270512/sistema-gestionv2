<?php
session_start(); // Asegurar que la sesión esté activa

// Configurar zona horaria de Guayaquil (Ecuador)
date_default_timezone_set('America/Guayaquil');

// Configuración manual de la conexión a la base de datos
$host = 'localhost'; // Host de la base de datos
$dbname = 'bd_gestion'; // Nombre de la base de datos
$user = 'root'; // Usuario de la base de datos
$pass = ''; // Contraseña de la base de datos

try {
    // Crear la conexión PDO
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}

// Verificar si el usuario está logueado
if (!isset($_SESSION['correo'])) {
    header("Location: login.php"); // Redirigir al login si no hay sesión
    exit();
}

// Obtener el correo del usuario actual desde la sesión
$correo = $_SESSION['correo'];

// Buscar los datos del usuario por correo
$sql = "SELECT * FROM usuario WHERE correo = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$correo]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$usuario) {
    die("Usuario no encontrado.");
}

// Variables para controlar el flujo
$mostrarModal = false;
$mostrarBienvenida = false;
$mostrarExito = false;
$mensaje = "";
$tipoMensaje = "";

// Verificar el campo ultimoAcceso
if ($usuario['ultimoAcceso'] === null) {
    $mostrarModal = true;
} else {
    $mostrarBienvenida = true;
}

// Procesar el cambio de contraseña si se envió el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cambiar_contrasena'])) {
    $contrasena_actual = $_POST['contrasena_actual'];
    $nueva_contrasena = $_POST['nueva_contrasena'];
    $confirmar_contrasena = $_POST['confirmar_contrasena'];
    
    // Verificar que la contraseña actual coincida
    if ($contrasena_actual !== $usuario['contrasena']) {
        $mensaje = "La contraseña actual no es correcta.";
        $tipoMensaje = "danger";
        $mostrarModal = true;
    } 
    // Verificar que las nuevas contraseñas coincidan
    elseif ($nueva_contrasena !== $confirmar_contrasena) {
        $mensaje = "Las nuevas contraseñas no coinciden.";
        $tipoMensaje = "danger";
        $mostrarModal = true;
    } 
    // Verificar que la nueva contraseña no esté vacía
    elseif (empty($nueva_contrasena)) {
        $mensaje = "La nueva contraseña no puede estar vacía.";
        $tipoMensaje = "danger";
        $mostrarModal = true;
    } 
    // Todo correcto, actualizar contraseña
    else {
        try {
            // Obtener fecha y hora actual en Guayaquil
            $fecha_actual = date('Y-m-d H:i:s');
            
            // Actualizar contraseña y establecer fecha de último acceso
            $sql = "UPDATE usuario SET 
                    contrasena = ?, 
                    ultimoAcceso = ? 
                    WHERE correo = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$nueva_contrasena, $fecha_actual, $correo]);
            
            // Mostrar modal de éxito
            $mostrarExito = true;
            $mostrarModal = false;
            $mostrarBienvenida = false;
            
        } catch (PDOException $e) {
            $mensaje = "Error al actualizar la contraseña: " . $e->getMessage();
            $tipoMensaje = "danger";
            $mostrarModal = true;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cambiar Contraseña</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
    <style>
        .welcome-container {
            max-width: 600px;
            margin: 100px auto;
            padding: 30px;
            text-align: center;
            border-radius: 10px;
            background-color: #f8f9fa;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
        }
        
        .welcome-title {
            font-size: 2.5rem;
            margin-bottom: 20px;
            color: #0d6efd;
        }
        
        .welcome-message {
            font-size: 1.2rem;
            margin-bottom: 30px;
        }
        
        .start-button {
            padding: 10px 30px;
            font-size: 1.2rem;
        }
        
        /* Estilo para el modal de éxito */
        .modal-success .modal-header {
            background-color: #28a745;
            color: white;
        }
    </style>
</head>

<body>
    <?php if ($mostrarBienvenida): ?>
        <div class="welcome-container">
            <h1 class="welcome-title">Bienvenido, <?php echo htmlspecialchars($usuario['nombre_usuario']); ?></h1>
            <p class="welcome-message">Sistema de Gestión</p>
            <a href="inicio.php" class="btn btn-primary start-button">Empezar a Trabajar</a>
        </div>
    <?php endif; ?>

    <!-- Modal para cambiar contraseña -->
    <?php if ($mostrarModal): ?>
        <div class="modal fade show" id="passwordModal" tabindex="-1" aria-labelledby="passwordModalLabel" aria-hidden="false" style="display: block; background-color: rgba(0,0,0,0.5);">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="passwordModalLabel">Cambio de Contraseña Obligatorio</h5>
                    </div>
                    <div class="modal-body">
                        <?php if (!empty($mensaje)): ?>
                            <div class="alert alert-<?php echo $tipoMensaje; ?>" role="alert">
                                <?php echo $mensaje; ?>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" id="changePasswordForm">
                            <div class="mb-3">
                                <label for="nombre_usuario" class="form-label">Nombre:</label>
                                <input type="text" class="form-control" id="nombre_usuario" name="nombre_usuario" value="<?php echo htmlspecialchars($usuario['nombre_usuario']); ?>" readonly>
                            </div>
                            <div class="mb-3">
                                <label for="correo" class="form-label">Correo:</label>
                                <input type="email" class="form-control" id="correo" name="correo" value="<?php echo htmlspecialchars($usuario['correo']); ?>" readonly>
                            </div>
                            <div class="mb-3">
                                <label for="contrasena_actual" class="form-label">Contraseña Actual:</label>
                                <input type="password" class="form-control" id="contrasena_actual" name="contrasena_actual" required>
                            </div>
                            <div class="mb-3">
                                <label for="nueva_contrasena" class="form-label">Nueva Contraseña:</label>
                                <input type="password" class="form-control" id="nueva_contrasena" name="nueva_contrasena" required>
                            </div>
                            <div class="mb-3">
                                <label for="confirmar_contrasena" class="form-label">Confirmar Nueva Contraseña:</label>
                                <input type="password" class="form-control" id="confirmar_contrasena" name="confirmar_contrasena" required>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" form="changePasswordForm" name="cambiar_contrasena" class="btn btn-primary">Cambiar Contraseña</button>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Modal de éxito -->
    <?php if ($mostrarExito): ?>
        <div class="modal fade show modal-success" id="successModal" tabindex="-1" aria-labelledby="successModalLabel" aria-hidden="false" style="display: block; background-color: rgba(0,0,0,0.5);">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="successModalLabel">Contraseña Actualizada</h5>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-success" role="alert">
                            Credenciales correctamente modificadas.
                        </div>
                        <p>Serás redirigido automáticamente a la página de inicio.</p>
                    </div>
                </div>
            </div>
        </div>
        
        <script>
            // Redirigir después de 3 segundos
            setTimeout(function() {
                window.location.href = "inicio.php";
            }, 3000);
        </script>
    <?php endif; ?>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
</body>

</html>