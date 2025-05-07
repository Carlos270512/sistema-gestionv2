<?php
include 'conexion.php';

$mensaje = ""; // Variable para almacenar el mensaje de éxito o error

// Procesar el formulario de creación o edición
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['guardar_usuario'])) {
        $nombre_usuario = $_POST['nombre_usuario'];
        $contrasena = $_POST['contrasena'];
        $correo = $_POST['correo'];
        $rol = $_POST['rol'];
        $cargo = $_POST['cargo'];
        $especialidad = $_POST['especialidad'];
        $departamento = $_POST['departamento'];
        $telefono = $_POST['telefono'];

        if (isset($_POST['id_usuario']) && !empty($_POST['id_usuario'])) {
            // Actualizar usuario existente
            $id_usuario = $_POST['id_usuario'];
            $sql = "UPDATE usuario SET 
                    nombre_usuario = ?, 
                    contrasena = ?, 
                    correo = ?, 
                    rol = ?, 
                    cargo = ?, 
                    especialidad = ?, 
                    departamento = ?, 
                    telefono = ? 
                    WHERE id_usuario = ?";
            $stmt = $pdo->prepare($sql);
            if ($stmt->execute([$nombre_usuario, $contrasena, $correo, $rol, $cargo, $especialidad, $departamento, $telefono, $id_usuario])) {
                $mensaje = "Usuario modificado correctamente.";
            } else {
                $mensaje = "Error al modificar el usuario.";
            }
        } else {
            // Crear nuevo usuario
            $sql = "INSERT INTO usuario (nombre_usuario, contrasena, correo, rol, cargo, especialidad, departamento, telefono) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            if ($stmt->execute([$nombre_usuario, $contrasena, $correo, $rol, $cargo, $especialidad, $departamento, $telefono])) {
                $mensaje = "Usuario guardado correctamente.";
            } else {
                $mensaje = "Error al guardar el usuario.";
            }
        }
    }
}

// Procesar la eliminación de un usuario
if (isset($_GET['eliminar'])) {
    $id_usuario = $_GET['eliminar'];
    $sql = "DELETE FROM usuario WHERE id_usuario = ?";
    $stmt = $pdo->prepare($sql);
    if ($stmt->execute([$id_usuario])) {
        $mensaje = "Usuario eliminado correctamente.";
    } else {
        $mensaje = "Error al eliminar el usuario.";
    }
}

// Obtener la lista de usuarios
$sql = "SELECT * FROM usuario";
$stmt = $pdo->query($sql);
$usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CRUD de Usuarios</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .table thead th {
            background-color:#86541a;
            color: white;
        }

        .btn-actions {
            display: flex;
            justify-content: space-between;
        }

        .btn-actions .btn {
            margin-right: 5px;
        }

        .btn-actions .btn:last-child {
            margin-right: 0;
        }

        .btn-primary-custom {
        background-color: #5a3829; /* Café oscuro */
        color: white; /* Letras blancas */
        border-color: #5a3829;
    }

    .btn-primary-custom:hover {
        background-color:rgb(197, 169, 79); /* Café más claro */
        border-color: rgb(197, 169, 79);
        color: white;
    }
    </style>
</head>

<body>
    <div class="container mt-5">
        <h1 class="mb-4">Registro de Usuarios</h1>
        <button class="btn btn-primary-custom mb-3" data-bs-toggle="modal" data-bs-target="#modalUsuario">
            <i class="fas fa-plus"></i> Agregar Usuario
        </button>

        <!-- Mostrar mensaje de éxito o error -->
        <?php if (!empty($mensaje)): ?>
            <div class="alert alert-info alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($mensaje); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Tabla de usuarios -->
        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Correo</th>
                    <th>Rol</th>
                    <th>Cargo</th>
                    <th>Especialidad</th>
                    <th>Departamento</th>
                    <th>Teléfono</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($usuarios as $usuario): ?>
                    <tr>
                        <td><?php echo $usuario['id_usuario']; ?></td>
                        <td><?php echo $usuario['nombre_usuario']; ?></td>
                        <td><?php echo $usuario['correo']; ?></td>
                        <td><?php echo $usuario['rol']; ?></td>
                        <td><?php echo $usuario['cargo']; ?></td>
                        <td><?php echo $usuario['especialidad']; ?></td>
                        <td><?php echo $usuario['departamento']; ?></td>
                        <td><?php echo $usuario['telefono']; ?></td>
                        <td class="btn-actions">
                            <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#modalUsuario"
                                onclick="editarUsuario(<?php echo $usuario['id_usuario']; ?>, '<?php echo $usuario['nombre_usuario']; ?>', '<?php echo $usuario['correo']; ?>', '<?php echo $usuario['rol']; ?>', '<?php echo $usuario['cargo']; ?>', '<?php echo $usuario['especialidad']; ?>', '<?php echo $usuario['departamento']; ?>', '<?php echo $usuario['telefono']; ?>')">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#modalEliminar" onclick="confirmarEliminar(<?php echo $usuario['id_usuario']; ?>)">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Modal para agregar/editar usuario -->
    <div class="modal fade" id="modalUsuario" tabindex="-1" aria-labelledby="modalUsuarioLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalUsuarioLabel">Agregar/Editar Usuario</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="POST">
                        <input type="hidden" id="id_usuario" name="id_usuario">
                        <div class="mb-3">
                            <label for="nombre_usuario" class="form-label">Nombre:</label>
                            <input type="text" class="form-control" id="nombre_usuario" name="nombre_usuario" required oninput="this.value = this.value.toUpperCase()">
                        </div>
                        <div class="mb-3">
                            <label for="contrasena" class="form-label">Contraseña:</label>
                            <input type="password" class="form-control" id="contrasena" name="contrasena" required>
                        </div>
                        <div class="mb-3">
                            <label for="correo" class="form-label">Correo:</label>
                            <input type="email" class="form-control" id="correo" name="correo" required>
                        </div>
                        <div class="mb-3">
                            <label for="rol" class="form-label">Rol:</label>
                            <select class="form-select" id="rol" name="rol" required>
                                <option value="usuario">usuario</option>
                                <option value="coordinador">coordinador</option>
                                <option value="vicerrector">vicerrector</option>
                                <option value="admin">admin</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="cargo" class="form-label">Cargo:</label>
                            <input type="text" class="form-control" id="cargo" name="cargo" required oninput="this.value = this.value.toUpperCase()">
                        </div>
                        <div class="mb-3">
                            <label for="especialidad" class="form-label">Especialidad:</label>
                            <input type="text" class="form-control" id="especialidad" name="especialidad">
                        </div>
                        <div class="mb-3">
                            <label for="departamento" class="form-label">Departamento:</label>
                            <input type="text" class="form-control" id="departamento" name="departamento">
                        </div>
                        <div class="mb-3">
                            <label for="telefono" class="form-label">Teléfono:</label>
                            <input type="text" class="form-control" id="telefono" name="telefono" maxlength="10" pattern="\d{10}" required oninput="this.value = this.value.replace(/\D/g, '').slice(0, 10)">

                        </div>
                        <button type="submit" name="guardar_usuario" class="btn btn-primary">Guardar</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de confirmación de eliminación -->
    <div class="modal fade" id="modalEliminar" tabindex="-1" aria-labelledby="modalEliminarLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalEliminarLabel">Confirmar Eliminación</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>¿Estás seguro de que deseas eliminar este usuario?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <a href="#" id="btnEliminar" class="btn btn-danger">Eliminar</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script>
        // Función para cargar datos en el modal de edición
        function editarUsuario(id, nombre, correo, rol, cargo, especialidad, departamento, telefono) {
            document.getElementById('id_usuario').value = id;
            document.getElementById('nombre_usuario').value = nombre;
            document.getElementById('contrasena').value = ''; // No mostrar la contraseña por seguridad
            document.getElementById('correo').value = correo;
            document.getElementById('rol').value = rol;
            document.getElementById('cargo').value = cargo;
            document.getElementById('especialidad').value = especialidad;
            document.getElementById('departamento').value = departamento;
            document.getElementById('telefono').value = telefono;
        }

        // Función para confirmar la eliminación de un usuario
        function confirmarEliminar(id) {
            document.getElementById('btnEliminar').href = 'registroUsuario.php?eliminar=' + id;
        }
    </script>
</body>

</html>