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
    $fecha_registro = date("Y-m-d H:i:s");
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css"> <!-- Incluir Font Awesome -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css"> <!-- Incluir DataTables CSS -->
    <style>
        /* Estilo para los encabezados de la tabla */
        .table thead th {
            background-color:#86541a;
            color: white;
            text-align: center;
        }
        /* Estilo para la columna de acciones */
        .btn-actions {
            display: flex;
            justify-content: center;
            gap: 10px;
        }

        /* Agregar scroller vertical */
        .table-responsive {
            max-height: 500px;
            overflow-y: auto;
        }

        /* Colores personalizados para los botones de modificar */
        .btn-coordinador {
            background-color: #ffc107;
            /* Amarillo */
            border-color: #ffc107;
        }

        .btn-vicerrector {
            background-color: #28a745;
            /* Verde */
            border-color: #28a745;
        }

        .btn-rector {
            background-color: #dc3545;
            /* Rojo */
            border-color: #dc3545;
        }
    /* Estilo para el botón Subir Archivo */
    .btn-primary {
        background-color: #86541a; /* Color café */
        color: white; /* Letras blancas */
        border-color: #86541a;
    }

    .btn-primary:hover {
        background-color: #a67c52; /* Café claro */
        border-color: #a67c52;
        color: white; /* Letras blancas */
    }
    </style>
    <!-- PDF.js para visualizar PDF en un modal -->
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

        <!-- Modal para mostrar mensajes -->
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
                // Mostrar el modal automáticamente
                document.addEventListener("DOMContentLoaded", function() {
                    var myModal = new bootstrap.Modal(document.getElementById('mensajeModal'));
                    myModal.show();
                });
            </script>
        <?php endif; ?>

        <!-- Tabla para mostrar los archivos subidos por el usuario actual -->
        <h2 class="mt-5">Mis Archivos Subidos</h2>
        <div class="table-responsive"> <!-- Agregar contenedor para el scroller -->
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
                                    <button class="btn btn-sm btn-warning btn-coordinador ms-2" onclick="modificarArchivoRevisado('<?php echo htmlspecialchars($fila['id_archivo'] ?? ''); ?>', '<?php echo htmlspecialchars($fila['descripcion'] ?? ''); ?>')">
                                        <i class="fas fa-edit fa-lg"></i> Coordinador
                                    </button>
                                <?php endif; ?>
                                <?php if ($fila['aprobado'] == 'no'): ?>
                                    <button class="btn btn-sm btn-success btn-vicerrector ms-2" onclick="modificarArchivoAprobado('<?php echo htmlspecialchars($fila['id_archivo'] ?? ''); ?>', '<?php echo htmlspecialchars($fila['descripcion'] ?? ''); ?>')">
                                        <i class="fas fa-edit fa-lg"></i> Vicerrector
                                    </button>
                                <?php endif; ?>
                                <?php if ($fila['autorizado'] == 'no'): ?>
                                    <button class="btn btn-sm btn-danger btn-rector ms-2" onclick="modificarArchivoAutorizado('<?php echo htmlspecialchars($fila['id_archivo'] ?? ''); ?>', '<?php echo htmlspecialchars($fila['descripcion'] ?? ''); ?>')">
                                        <i class="fas fa-edit fa-lg"></i> Rector
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
            <div class="modal-dialog modal-xl"> <!-- Cambiado a modal-xl para hacerlo más grande -->
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="pdfModalLabel">Visualizar Archivo</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <iframe id="pdfViewer" style="width: 100%; height: 700px;" frameborder="0"></iframe> <!-- Altura aumentada -->
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

        <!-- Modal para modificar archivo (Coordinador) -->
        <div class="modal fade" id="modificarArchivoCoordinadorModal" tabindex="-1" aria-labelledby="modificarArchivoCoordinadorModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modificarArchivoCoordinadorModalLabel">Modificar Archivo (Coordinador)</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form id="modificarArchivoCoordinadorForm" action="inicio.php" method="post" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label for="archivo_pdf_coordinador" class="form-label">Seleccionar Archivo PDF</label>
                                <input type="file" class="form-control" id="archivo_pdf_coordinador" name="archivo_pdf" accept="application/pdf" required>
                            </div>
                            <div class="mb-3">
                                <label for="tipo_archivo_coordinador" class="form-label">Tipo de Archivo</label>
                                <select class="form-select" id="tipo_archivo_coordinador" name="tipo_archivo" required>
                                    <option value="Informe">Informe</option>
                                    <option value="Reporte">Reporte</option>
                                    <option value="Otro">Otro</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="descripcion_coordinador" class="form-label">Descripción</label>
                                <textarea class="form-control" id="descripcion_coordinador" name="descripcion" rows="3" required></textarea>
                            </div>
                            <input type="hidden" id="id_archivo_coordinador" name="id_archivo">
                            <input type="hidden" id="estado_coordinador" name="estado" value="corregir por Coordinador">
                            <input type="hidden" name="modificar" value="1">
                            <button type="submit" class="btn btn-warning">Modificar Archivo</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal para modificar archivo (Vicerrector) -->
        <div class="modal fade" id="modificarArchivoVicerrectorModal" tabindex="-1" aria-labelledby="modificarArchivoVicerrectorModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modificarArchivoVicerrectorModalLabel">Modificar Archivo (Vicerrector)</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form id="modificarArchivoVicerrectorForm" action="inicio.php" method="post" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label for="archivo_pdf_vicerrector" class="form-label">Seleccionar Archivo PDF</label>
                                <input type="file" class="form-control" id="archivo_pdf_vicerrector" name="archivo_pdf" accept="application/pdf" required>
                            </div>
                            <div class="mb-3">
                                <label for="tipo_archivo_vicerrector" class="form-label">Tipo de Archivo</label>
                                <select class="form-select" id="tipo_archivo_vicerrector" name="tipo_archivo" required>
                                    <option value="Informe">Informe</option>
                                    <option value="Reporte">Reporte</option>
                                    <option value="Otro">Otro</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="descripcion_vicerrector" class="form-label">Descripción</label>
                                <textarea class="form-control" id="descripcion_vicerrector" name="descripcion" rows="3" required></textarea>
                            </div>
                            <input type="hidden" id="id_archivo_vicerrector" name="id_archivo">
                            <input type="hidden" id="estado_vicerrector" name="estado" value="corregido por Vicerrector">
                            <input type="hidden" name="modificar" value="1">
                            <button type="submit" class="btn btn-success">Modificar Archivo</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal para modificar archivo (Rector) -->
        <div class="modal fade" id="modificarArchivoRectorModal" tabindex="-1" aria-labelledby="modificarArchivoRectorModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modificarArchivoRectorModalLabel">Modificar Archivo (Rector)</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form id="modificarArchivoRectorForm" action="inicio.php" method="post" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label for="archivo_pdf_rector" class="form-label">Seleccionar Archivo PDF</label>
                                <input type="file" class="form-control" id="archivo_pdf_rector" name="archivo_pdf" accept="application/pdf" required>
                            </div>
                            <div class="mb-3">
                                <label for="tipo_archivo_rector" class="form-label">Tipo de Archivo</label>
                                <select class="form-select" id="tipo_archivo_rector" name="tipo_archivo" required>
                                    <option value="Informe">Informe</option>
                                    <option value="Reporte">Reporte</option>
                                    <option value="Otro">Otro</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="descripcion_rector" class="form-label">Descripción</label>
                                <textarea class="form-control" id="descripcion_rector" name="descripcion" rows="3" required></textarea>
                            </div>
                            <input type="hidden" id="id_archivo_rector" name="id_archivo">
                            <input type="hidden" id="estado_rector" name="estado" value="corregido por rector">
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
                    window.location.href = 'inicio.php?eliminar=' + nombreArchivo;
                };
                myModal.show();
            }

            // Función para modificar un archivo en estado "Revisado" (Coordinador)
            window.modificarArchivoRevisado = function(idArchivo, descripcion) {
                document.getElementById('id_archivo_coordinador').value = idArchivo;
                document.getElementById('descripcion_coordinador').value = descripcion;
                var myModal = new bootstrap.Modal(document.getElementById('modificarArchivoCoordinadorModal'));
                myModal.show();
            }

            // Función para modificar un archivo en estado "Aprobado" (Vicerrector)
            window.modificarArchivoAprobado = function(idArchivo, descripcion) {
                document.getElementById('id_archivo_vicerrector').value = idArchivo;
                document.getElementById('descripcion_vicerrector').value = descripcion;
                var myModal = new bootstrap.Modal(document.getElementById('modificarArchivoVicerrectorModal'));
                myModal.show();
            }

            // Función para modificar un archivo en estado "Autorizado" (Rector)
            window.modificarArchivoAutorizado = function(idArchivo, descripcion) {
                document.getElementById('id_archivo_rector').value = idArchivo;
                document.getElementById('descripcion_rector').value = descripcion;
                var myModal = new bootstrap.Modal(document.getElementById('modificarArchivoRectorModal'));
                myModal.show();
            }
        });
    </script>
</body>

</html>