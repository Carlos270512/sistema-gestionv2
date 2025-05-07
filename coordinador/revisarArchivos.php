<?php
session_start();
require __DIR__ . '/../vendor/autoload.php'; // Autoload de Composer
include "../configuracion/conexion.php";

use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;

// Verificar si el usuario está logueado
if (!isset($_SESSION['id_usuario'])) {
    header("Location: index.php");
    exit();
}

// Obtener el nombre y el cargo del usuario logueado
$id_usuario = $_SESSION['id_usuario'];
$sql_usuario = "SELECT nombre_usuario, cargo FROM usuario WHERE id_usuario = ?";
$stmt_usuario = $conn->prepare($sql_usuario);
$stmt_usuario->bind_param("i", $id_usuario);
$stmt_usuario->execute();
$resultado_usuario = $stmt_usuario->get_result();
$usuario = $resultado_usuario->fetch_assoc();
$nombre_usuario = $usuario['nombre_usuario'];
$cargo_usuario = $usuario['cargo'];
$stmt_usuario->close();

// Configurar Flysystem para almacenar archivos localmente
$adapter = new LocalFilesystemAdapter(__DIR__ . '/../storage/pdfs');
$filesystem = new Filesystem($adapter);

$mensaje = ""; // Variable para almacenar el mensaje de éxito o error

// Procesar la acción de eliminar
if (isset($_GET['eliminar'])) {
    $nombre_archivo = $_GET['eliminar'];
    $sql = "DELETE FROM archivo WHERE nombre_archivo = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $nombre_archivo);

    if ($stmt->execute()) {
        // Eliminar el archivo del sistema de archivos
        $filesystem->delete($nombre_archivo);
        $mensaje = "Archivo eliminado correctamente.";
    } else {
        $mensaje = "Error al eliminar el archivo.";
    }
    $stmt->close();
}

// Procesar la acción de modificar
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['id_archivo'])) {
    $id_archivo = $_POST['id_archivo'];
    $observacion = $_POST['observacion'];
    $revisado = $_POST['revisado'];
    $usuario_revisa = $_POST['usuario_revisa'];

    // Actualizar el archivo en la base de datos
    $sql = "UPDATE archivo SET observacion1 = ?, revisado = ?, usuario_revisa = ? WHERE id_archivo = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssi", $observacion, $revisado, $usuario_revisa, $id_archivo);

    if ($stmt->execute()) {
        $mensaje = "Archivo modificado correctamente.";
    } else {
        $mensaje = "Error al modificar el archivo: " . $stmt->error;
    }
    $stmt->close();
}

// Obtener todos los archivos subidos por todos los usuarios, excluyendo los que tienen "corregir" en revisado
$sql = "SELECT u.nombre_usuario, u.cargo, a.* 
        FROM archivo a 
        JOIN usuario u ON a.id_usuario = u.id_usuario 
        WHERE a.revisado NOT IN ('corregir','revisado')  and a.autorizado NOT IN ('si', 'no')
        ORDER BY a.fecha_registro DESC";
$resultado = $conn->query($sql);



// Verificar si hay errores en la consulta
if (!$resultado) {
    die("Error en la consulta: " . $conn->error);
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Revisar Archivos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css"> <!-- Incluir DataTables CSS -->
    <style>
        /* Estilos para la tabla */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th,
        td {
            padding: 10px;
            text-align: left;
            border: 1px solid #ddd;
        }

        th {
            background-color: #2c3e50;
            color: white;
        }

        .btn-actions {
            display: flex;
            gap: 10px;
        }

        .btn-actions .btn {
            padding: 5px 10px;
            font-size: 14px;
        }

        /* Estilos para el encabezado */
        h2 {
            margin-top: 20px;
            font-size: 1.5rem;
            color: #2c3e50;
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>Revisar Archivos</h1>
        <h2><?php echo htmlspecialchars($nombre_usuario); ?> - <?php echo htmlspecialchars($cargo_usuario); ?></h2>
        <div class="table-responsive">
            <table id="tablaArchivos" class="table table-bordered">
                <thead>
                    <tr>
                        <th>Nombre Usuario</th>
                        <th>Cargo</th>
                        <th>Nombre Archivo</th>
                        <th>Fecha Registro</th>
                        <th>Descripción</th>
                        <th>Revisado</th>
                        <th>Usuario Revisa</th>
                        <th>Observación 1</th>
                        <th>Aprobado</th>
                        <th>Usuario Aprueba</th>
                        <th>Observación 2</th>
                        <th>Autorizado</th>
                        <th>Usuario Autoriza</th>
                        <th>Observación 3</th>
                        <th>Estado</th>
                        <th>Observaciones</th>
                        <th>Fecha Entrega</th>
                        <th>Tipo Archivo</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($fila = $resultado->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($fila['nombre_usuario'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($fila['cargo'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($fila['nombre_archivo'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($fila['fecha_registro'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($fila['descripcion'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($fila['revisado'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($fila['usuario_revisa'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($fila['observacion1'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($fila['aprobado'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($fila['usuario_aprueba'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($fila['observacion2'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($fila['autorizado'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($fila['usuario_autoriza'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($fila['observacion3'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($fila['estado'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($fila['observaciones'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($fila['fecha_entrega'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($fila['tipo_archivo'] ?? ''); ?></td>
                            <td class="btn-actions">
                                <!-- Botón para ver el archivo -->
                                <button class="btn btn-sm btn-primary" onclick="verArchivo('<?php echo htmlspecialchars($fila['nombre_archivo'] ?? ''); ?>')">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <!-- Botón para eliminar el archivo -->
                                <button class="btn btn-sm btn-danger" onclick="confirmarEliminar('<?php echo htmlspecialchars($fila['nombre_archivo'] ?? ''); ?>')">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                                <!-- Botón para modificar el archivo -->
                                <button class="btn btn-sm btn-warning" onclick="modificarArchivo('<?php echo htmlspecialchars($fila['id_archivo'] ?? ''); ?>', '<?php echo htmlspecialchars($fila['observacion1'] ?? ''); ?>', '<?php echo htmlspecialchars($fila['aprobado'] ?? ''); ?>', '<?php echo htmlspecialchars($fila['revisado'] ?? ''); ?>', '<?php echo htmlspecialchars($fila['usuario_revisa'] ?? ''); ?>', '<?php echo htmlspecialchars($fila['usuario_aprueba'] ?? ''); ?>')">
                                    <i class="fas fa-edit"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <button class="btn btn-secondary mt-3" onclick="window.location.reload();">Refrescar</button>

        <!-- Modal para visualizar el archivo PDF -->
        <div class="modal fade" id="pdfModal" tabindex="-1" aria-labelledby="pdfModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="pdfModalLabel">Visualizar Archivo</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <iframe id="pdfViewer" style="width: 100%; height: 700px;" frameborder="0"></iframe>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal para confirmar la eliminación -->
        <div class="modal fade" id="confirmarEliminarModal" tabindex="-1" aria-labelledby="confirmarEliminarModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="confirmarEliminarModalLabel">Confirmar Eliminación</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        ¿Estás seguro de que deseas eliminar este archivo?
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="button" class="btn btn-danger" id="btnEliminarConfirmado">Eliminar</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal para modificar -->
        <div class="modal fade" id="modificarArchivoModal" tabindex="-1" aria-labelledby="modificarArchivoModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modificarArchivoModalLabel">Modificar</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form id="modificarArchivoForm">
                            <div class="mb-3">
                                <label for="observacion" class="form-label">Observación</label>
                                <textarea class="form-control" id="observacion" name="observacion" rows="3" required></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="revisado" class="form-label">Revisado</label>
                                <select class="form-select" id="revisado" name="revisado" required>
                                    <option value="revisado">Revisado</option>
                                    <option value="corregir">Corregir</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="usuario_revisa" class="form-label">Usuario Revisa</label>
                                <input type="text" class="form-control" id="usuario_revisa" name="usuario_revisa" value="<?php echo htmlspecialchars($nombre_usuario); ?>" readonly>
                            </div>
                            <input type="hidden" id="id_archivo" name="id_archivo">
                            <button type="button" class="btn btn-primary" onclick="confirmarModificar()">Modificar</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal para confirmar la modificación -->
        <div class="modal fade" id="confirmarModificarModal" tabindex="-1" aria-labelledby="confirmarModificarModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="confirmarModificarModalLabel">Confirmar Modificación</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        ¿Estás seguro de que deseas modificar este archivo?
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="button" class="btn btn-primary" id="btnModificarConfirmado">Modificar</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts de Bootstrap y PDF.js -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script> <!-- Incluir jQuery -->
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script> <!-- Incluir DataTables JS -->
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script> <!-- Incluir DataTables Bootstrap JS -->
    <script>
        $(document).ready(function() {
            // Inicializar DataTables
            $('#tablaArchivos').DataTable();

            // Función para abrir el modal y mostrar el PDF
            window.verArchivo = function(nombreArchivo) {
                var pdfViewer = document.getElementById('pdfViewer');
                pdfViewer.src = '../storage/pdfs/' + nombreArchivo;
                var myModal = new bootstrap.Modal(document.getElementById('pdfModal'));
                myModal.show();
            }

            // Función para confirmar la eliminación de un archivo
            window.confirmarEliminar = function(nombreArchivo) {
                var myModal = new bootstrap.Modal(document.getElementById('confirmarEliminarModal'));
                document.getElementById('btnEliminarConfirmado').onclick = function() {
                    window.location.href = 'revisarArchivos.php?eliminar=' + nombreArchivo;
                };
                myModal.show();
            }

            // Función para modificar un archivo
            window.modificarArchivo = function(idArchivo, observacion, revisado) {
                document.getElementById('id_archivo').value = idArchivo;
                document.getElementById('observacion').value = observacion;
                document.getElementById('revisado').value = revisado;
                document.getElementById('usuario_revisa').value = '<?php echo htmlspecialchars($nombre_usuario); ?>';
                var myModal = new bootstrap.Modal(document.getElementById('modificarArchivoModal'));
                myModal.show();
            }

            // Función para confirmar la modificación de un archivo
            window.confirmarModificar = function() {
                var myModal = new bootstrap.Modal(document.getElementById('confirmarModificarModal'));
                myModal.show();
            }

            // Manejar el envío del formulario de modificar
            document.getElementById('btnModificarConfirmado').addEventListener('click', function() {
                var formData = new FormData(document.getElementById('modificarArchivoForm'));
                console.log([...formData]); // Verifica los datos que se están enviando
                fetch('revisarArchivos.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.text())
                    .then(data => {
                        // Recargar la página después de modificar el archivo
                        location.reload();
                    })
                    .catch(error => console.error('Error:', error));
            });
        });
    </script>
</body>

</html>