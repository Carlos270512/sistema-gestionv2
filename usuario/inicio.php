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

// Configurar Flysystem para almacenar archivos localmente
$adapter = new LocalFilesystemAdapter(__DIR__ . '/../storage/pdfs');
$filesystem = new Filesystem($adapter);

$mensaje = ""; // Variable para almacenar el mensaje de éxito o error

// Subir archivo PDF
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["archivo_pdf"]) && !isset($_POST['modificar'])) {
    $id_usuario = $_SESSION['id_usuario'];
    $nombre_archivo = basename($_FILES["archivo_pdf"]["name"]);
    $tipo_archivo = $_POST['tipo_archivo'];
    $descripcion = $_POST['descripcion'];
    $fecha_registro = date("Y-m-d H:i:s"); // esto hace 
    $revisado = 'en espera'; // Valor predeterminado para la columna 'revisado'
    $usuario_revisa = null; // Valor predeterminado para la columna 'usuario_revisa'
    $estado = 'en espera'; // Valor predeterminado para la columna 'estado'

    // Guardar el archivo en el sistema de archivos
    try {
        $stream = fopen($_FILES["archivo_pdf"]["tmp_name"], 'r+');
        $filesystem->writeStream($nombre_archivo, $stream);
        fclose($stream);

        // Insertar en la tabla archivo
        $sql = "INSERT INTO archivo (nombre_archivo, id_usuario, fecha_registro, descripcion, tipo_archivo, revisado, usuario_revisa, estado) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sissssss", $nombre_archivo, $id_usuario, $fecha_registro, $descripcion, $tipo_archivo, $revisado, $usuario_revisa, $estado);

        if ($stmt->execute()) {
            $mensaje = "Archivo subido correctamente.";
        } else {
            $mensaje = "Error al subir el archivo.";
        }
        $stmt->close();
    } catch (Exception $e) {
        $mensaje = "Error: " . $e->getMessage();
    }
}

// Procesar la eliminación de un archivo
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

// Procesar la modificación de un archivo
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['id_archivo']) && isset($_POST['modificar'])) {
    $id_archivo = $_POST['id_archivo'];
    $descripcion = $_POST['descripcion'];
    $estado = $_POST['estado'];
    $nombre_archivo = basename($_FILES["archivo_pdf"]["name"]);

    // Obtener los valores actuales del archivo
    $sql = "SELECT revisado, aprobado, autorizado FROM archivo WHERE id_archivo = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_archivo);
    $stmt->execute();
    $stmt->bind_result($revisado_actual, $aprobado_actual, $autorizado_actual);
    $stmt->fetch();
    $stmt->close();

    // Inicializar valores predeterminados
    $revisado = $revisado_actual;
    $aprobado = $aprobado_actual;
    $autorizado = $autorizado_actual;

    // Ajustar valores según el estado
    if ($estado === 'corregir por Coordinador') {
        $revisado = 'corregido';
    } else if ($estado === 'corregido por Vicerrector') {
        $aprobado = 'en espera';
    } else if ($estado === 'corregido por rector') {
        $autorizado = 'en espera';
    }

    // Actualizar el archivo en la base de datos
    $sql = "UPDATE archivo SET descripcion = ?, estado = ?, revisado = ?, aprobado = ?, autorizado = ?, nombre_archivo = ? WHERE id_archivo = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssssi", $descripcion, $estado, $revisado, $aprobado, $autorizado, $nombre_archivo, $id_archivo);

    if ($stmt->execute()) {
        // Guardar el nuevo archivo en el sistema de archivos
        try {
            $stream = fopen($_FILES["archivo_pdf"]["tmp_name"], 'r+');
            $filesystem->writeStream($nombre_archivo, $stream);
            fclose($stream);
            $mensaje = "Archivo modificado correctamente.";
        } catch (Exception $e) {
            $mensaje = "Error: " . $e->getMessage();
        }
    } else {
        $mensaje = "Error al modificar el archivo.";
    }
    $stmt->close();
}

// Obtener los archivos subidos por el usuario actual
$id_usuario = $_SESSION['id_usuario'];
$sql = "SELECT u.nombre_usuario, a.* 
        FROM archivo a 
        JOIN usuario u ON a.id_usuario = u.id_usuario 
        WHERE a.id_usuario = ? 
        ORDER BY a.fecha_registro DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$resultado = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Inicio</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    <style>
        table thead th {
            background-color: #2c3e50;
            color: white;
            text-align: center;
        }

        .btn-actions {
            display: flex;
            justify-content: center;
            gap: 10px;
        }

        .table-responsive {
            max-height: 500px;
            overflow-y: auto;
        }

        .btn-revisado {
            background-color: #ffc107;
            /* Amarillo */
            border-color: #ffc107;
        }

        .btn-aprobado {
            background-color: #28a745;
            /* Verde */
            border-color: #28a745;
        }

        .btn-autorizado {
            background-color: #dc3545;
            /* Rojo */
            border-color: #dc3545;
        }
    </style>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.11.338/pdf.min.js"></script>
</head>

<body>
    <div class="container mt-5">
        <h1>Subir Reporte</h1>
        <form action="inicio.php" method="post" enctype="multipart/form-data">
            <div class="mb-3">
                <label for="archivo_pdf" class="form-label">Seleccionar Archivo PDF</label>
                <input type="file" class="form-control" id="archivo_pdf" name="archivo_pdf" accept="application/pdf" required>
            </div>
            <div class="mb-3">
                <label for="tipo_archivo" class="form-label">Tipo de Archivo</label>
                <select class="form-select" id="tipo_archivo" name="tipo_archivo" required>
                    <option value="Informe">Informe</option>
                    <option value="Reporte">Reporte</option>
                    <option value="Otro">Otro</option>
                </select>
            </div>
            <div class="mb-3">
                <label for="descripcion" class="form-label">Descripción</label>
                <textarea class="form-control" id="descripcion" name="descripcion" rows="3" required></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Subir Archivo</button>
        </form>

        <?php if (!empty($mensaje)): ?>
            <div class="modal fade" id="mensajeModal" tabindex="-1" aria-labelledby="mensajeModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="mensajeModalLabel">Mensaje</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <?php echo htmlspecialchars($mensaje); ?>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                        </div>
                    </div>
                </div>
            </div>
            <script>
                document.addEventListener("DOMContentLoaded", function() {
                    var myModal = new bootstrap.Modal(document.getElementById('mensajeModal'));
                    myModal.show();
                });
            </script>
        <?php endif; ?>

        <h2 class="mt-5">Mis Archivos Subidos</h2>
        <div class="table-responsive">
            <table id="tablaArchivos" class="table table-bordered">
                <thead>
                    <tr>
                        <th>Nombre Usuario</th>
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
                                <button class="btn btn-sm btn-primary" onclick="verArchivo('<?php echo htmlspecialchars($fila['nombre_archivo'] ?? ''); ?>')">
                                    <i class="fas fa-eye fa-lg"></i>
                                </button>
                                <?php if ($fila['autorizado'] != 'si'): ?>
                                    <button class="btn btn-sm btn-danger ms-2" onclick="confirmarEliminar('<?php echo htmlspecialchars($fila['nombre_archivo'] ?? ''); ?>')">
                                        <i class="fas fa-trash-alt fa-lg"></i>
                                    </button>
                                <?php endif; ?>
                                <?php if (strtolower($fila['revisado']) == 'corregir'): ?>
                                    <button class="btn btn-sm btn-warning btn-revisado ms-2" onclick="abrirModalRevisado('<?php echo htmlspecialchars($fila['id_archivo'] ?? ''); ?>', '<?php echo htmlspecialchars($fila['descripcion'] ?? ''); ?>')">
                                        <i class="fas fa-edit fa-lg"></i> Cooregir C.
                                    </button>
                                <?php endif; ?>
                                <?php if ($fila['aprobado'] == 'no'): ?>
                                    <button class="btn btn-sm btn-success btn-aprobado ms-2" onclick="abrirModalAprobado('<?php echo htmlspecialchars($fila['id_archivo'] ?? ''); ?>', '<?php echo htmlspecialchars($fila['descripcion'] ?? ''); ?>')">
                                        <i class="fas fa-edit fa-lg"></i> Cooregir V.
                                    </button>
                                <?php endif; ?>
                                <?php if ($fila['autorizado'] == 'no'): ?>
                                    <button class="btn btn-sm btn-danger btn-autorizado ms-2" onclick="abrirModalAutorizado('<?php echo htmlspecialchars($fila['id_archivo'] ?? ''); ?>', '<?php echo htmlspecialchars($fila['descripcion'] ?? ''); ?>')">
                                        <i class="fas fa-edit fa-lg"></i> Cooregir R.
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

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

        <!-- Modal Revisado -->
        <div class="modal fade" id="modalRevisado" tabindex="-1" aria-labelledby="modalRevisadoLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalRevisadoLabel">Modificar Archivo (Revisado)</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form id="formRevisado" action="inicio.php" method="post" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label for="archivo_pdf_revisado" class="form-label">Seleccionar Archivo PDF</label>
                                <input type="file" class="form-control" id="archivo_pdf_revisado" name="archivo_pdf" accept="application/pdf" required>
                            </div>
                            <div class="mb-3">
                                <label for="tipo_archivo_revisado" class="form-label">Tipo de Archivo</label>
                                <select class="form-select" id="tipo_archivo_revisado" name="tipo_archivo" required>
                                    <option value="Informe">Informe</option>
                                    <option value="Reporte">Reporte</option>
                                    <option value="Otro">Otro</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="descripcion_revisado" class="form-label">Descripción</label>
                                <textarea class="form-control" id="descripcion_revisado" name="descripcion" rows="3" required></textarea>
                            </div>
                            <input type="hidden" id="id_archivo_revisado" name="id_archivo">
                            <input type="hidden" id="estado_revisado" name="estado" value="corregir por Coordinador">
                            <input type="hidden" name="modificar" value="1">
                            <button type="submit" class="btn btn-warning">Modificar Archivo</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Aprobado -->
        <div class="modal fade" id="modalAprobado" tabindex="-1" aria-labelledby="modalAprobadoLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalAprobadoLabel">Modificar Archivo (Aprobado)</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form id="formAprobado" action="inicio.php" method="post" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label for="archivo_pdf_aprobado" class="form-label">Seleccionar Archivo PDF</label>
                                <input type="file" class="form-control" id="archivo_pdf_aprobado" name="archivo_pdf" accept="application/pdf" required>
                            </div>
                            <div class="mb-3">
                                <label for="tipo_archivo_aprobado" class="form-label">Tipo de Archivo</label>
                                <select class="form-select" id="tipo_archivo_aprobado" name="tipo_archivo" required>
                                    <option value="Informe">Informe</option>
                                    <option value="Reporte">Reporte</option>
                                    <option value="Otro">Otro</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="descripcion_aprobado" class="form-label">Descripción</label>
                                <textarea class="form-control" id="descripcion_aprobado" name="descripcion" rows="3" required></textarea>
                            </div>
                            <input type="hidden" id="id_archivo_aprobado" name="id_archivo">
                            <input type="hidden" id="estado_aprobado" name="estado" value="corregido por Vicerrector">
                            <input type="hidden" name="modificar" value="1">
                            <button type="submit" class="btn btn-success">Modificar Archivo</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Autorizado -->
        <div class="modal fade" id="modalAutorizado" tabindex="-1" aria-labelledby="modalAutorizadoLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalAutorizadoLabel">Modificar Archivo (Autorizado)</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form id="formAutorizado" action="inicio.php" method="post" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label for="archivo_pdf_autorizado" class="form-label">Seleccionar Archivo PDF</label>
                                <input type="file" class="form-control" id="archivo_pdf_autorizado" name="archivo_pdf" accept="application/pdf" required>
                            </div>
                            <div class="mb-3">
                                <label for="tipo_archivo_autorizado" class="form-label">Tipo de Archivo</label>
                                <select class="form-select" id="tipo_archivo_autorizado" name="tipo_archivo" required>
                                    <option value="Informe">Informe</option>
                                    <option value="Reporte">Reporte</option>
                                    <option value="Otro">Otro</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="descripcion_autorizado" class="form-label">Descripción</label>
                                <textarea class="form-control" id="descripcion_autorizado" name="descripcion" rows="3" required></textarea>
                            </div>
                            <input type="hidden" id="id_archivo_autorizado" name="id_archivo">
                            <input type="hidden" id="estado_autorizado" name="estado" value="corregido por rector">
                            <input type="hidden" name="modificar" value="1">
                            <button type="submit" class="btn btn-danger">Modificar Archivo</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts de Bootstrap y PDF.js -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script>
        $(document).ready(function() {
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
                    window.location.href = 'inicio.php?eliminar=' + nombreArchivo;
                };
                myModal.show();
            }

            // Función para abrir el modal de Revisado
            window.abrirModalRevisado = function(idArchivo, descripcion) {
                document.getElementById('id_archivo_revisado').value = idArchivo;
                document.getElementById('descripcion_revisado').value = descripcion;
                var myModal = new bootstrap.Modal(document.getElementById('modalRevisado'));
                myModal.show();
            }

            // Función para abrir el modal de Aprobado
            window.abrirModalAprobado = function(idArchivo, descripcion) {
                document.getElementById('id_archivo_aprobado').value = idArchivo;
                document.getElementById('descripcion_aprobado').value = descripcion;
                var myModal = new bootstrap.Modal(document.getElementById('modalAprobado'));
                myModal.show();
            }

            // Función para abrir el modal de Autorizado
            window.abrirModalAutorizado = function(idArchivo, descripcion) {
                document.getElementById('id_archivo_autorizado').value = idArchivo;
                document.getElementById('descripcion_autorizado').value = descripcion;
                var myModal = new bootstrap.Modal(document.getElementById('modalAutorizado'));
                myModal.show();
            }
        });
    </script>
</body>

</html>