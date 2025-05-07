<?php
session_start(); // Asegurar que la sesión esté activa

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

// Inicializar variables para los mensajes
$mensaje = "";
$tipoMensaje = "";

// Procesar el formulario de edición
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre_usuario = $_POST['nombre_usuario'];
    $telefono = $_POST['telefono'];
    $contrasena = $_POST['contrasena'];

    try {
        // Si se proporciona una nueva contraseña, actualizarla
        if (!empty($contrasena)) {
            $sql = "UPDATE usuario SET 
                    nombre_usuario = ?, 
                    telefono = ?, 
                    contrasena = ? 
                    WHERE correo = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$nombre_usuario, $telefono, $contrasena, $correo]);
        } else {
            // Si no se proporciona una nueva contraseña, no actualizarla
            $sql = "UPDATE usuario SET 
                    nombre_usuario = ?, 
                    telefono = ? 
                    WHERE correo = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$nombre_usuario, $telefono, $correo]);
        }

        // Actualizar los datos en la sesión
        $_SESSION['nombreUsuario'] = $nombre_usuario;

        // Establecer mensaje de éxito
        $mensaje = "Datos guardados correctamente.";
        $tipoMensaje = "success";

        // Redirigir al mismo archivo para recargar el iframe
        header("Location: perfilCoordinador.php?mensaje=" . urlencode($mensaje) . "&tipoMensaje=" . urlencode($tipoMensaje));
        exit();
    } catch (PDOException $e) {
        // Establecer mensaje de error
        $mensaje = "Error al guardar los datos: " . htmlspecialchars($e->getMessage());
        $tipoMensaje = "danger";
    }
}

// Obtener el mensaje de la URL si existe
if (isset($_GET['mensaje']) && isset($_GET['tipoMensaje'])) {
    $mensaje = htmlspecialchars($_GET['mensaje']);
    $tipoMensaje = htmlspecialchars($_GET['tipoMensaje']);
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perfil de Usuario</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
    <style>
        .form-container {
            max-width: 600px;
            margin: 50px auto;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 10px;
            background-color: #f9f9f9;
        }

        .form-container h2 {
            margin-bottom: 20px;
        }

        .form-container label {
            font-weight: bold;
        }

        .form-container .btn {
            width: 100%;
        }

        .form-container input[readonly] {
            background-color: #f8f9fa;
            cursor: not-allowed;
        }
    </style>
</head>

<body>
    <div class="form-container">
        <h2>Perfil de Usuario</h2>
        <?php if (!empty($mensaje)): ?>
            <div class="alert alert-<?php echo $tipoMensaje; ?>" role="alert">
                <?php echo $mensaje; ?>
            </div>
        <?php endif; ?>
        <form method="POST">
            <!-- Campos editables -->
            <div class="mb-3">
                <label for="nombre_usuario" class="form-label">Nombre:</label>
                <input type="text" class="form-control" id="nombre_usuario" name="nombre_usuario" value="<?php echo htmlspecialchars($usuario['nombre_usuario']); ?>" required>
            </div>
            <div class="mb-3">
                <label for="telefono" class="form-label">Teléfono:</label>
                <input type="text" class="form-control" id="telefono" name="telefono" value="<?php echo htmlspecialchars($usuario['telefono']); ?>">
            </div>
            <div class="mb-3">
                <label for="contrasena" class="form-label">Nueva Contraseña:</label>
                <input type="password" class="form-control" id="contrasena" name="contrasena" placeholder="Dejar en blanco para no cambiar">
            </div>

            <!-- Campos de solo lectura -->
            <div class="mb-3">
                <label for="correo" class="form-label">Correo:</label>
                <input type="email" class="form-control" id="correo" name="correo" value="<?php echo htmlspecialchars($usuario['correo']); ?>" readonly>
            </div>

            <button type="submit" class="btn btn-primary">Guardar Cambios</button>
        </form>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
</body>

</html>