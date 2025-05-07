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

// Obtener todos los archivos subidos por todos los usuarios, excluyendo los que tienen "corregir" en revisado
$sql = "SELECT u.nombre_usuario, u.cargo, a.* 
        FROM archivo a 
        JOIN usuario u ON a.id_usuario = u.id_usuario 
        WHERE a.revisado NOT IN ('corregir','corregido') and a.aprobado NOT IN ('no','en espera') and a.autorizado NOT IN ('no','en espera')
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
        .table thead th {
            background-color:#86541a;
            color: white;
            text-align: center;
        }
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
    /* Estilo para el botón Descargar Reportes */
    .btn-descargar {
        background-color: #4CAF50; /* Verde */
        color: white; /* Letras blancas */
        border-color: #4CAF50;
    }

    .btn-descargar:hover {
        background-color: #45a049; /* Verde más oscuro */
        border-color: #45a049;
        color: white; /* Letras blancas */
    }

    /* Estilo para el botón Refrescar */
    .btn-refrescar {
        background-color: #2196F3; /* Azul */
        color: white; /* Letras blancas */
        border-color: #2196F3;
    }

    .btn-refrescar:hover {
        background-color: #0b7dda; /* Azul más oscuro */
        border-color: #0b7dda;
        color: white; /* Letras blancas */
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
                        <th>Seleccionar</th>
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
                                <button class="btn btn-sm btn-primary" onclick="verArchivo('<?php echo htmlspecialchars($fila['nombre_archivo'] ?? ''); ?>')">
                                    <i class="fas fa-eye fa-lg"></i>
                                </button>
                            </td>
                            <td>
                                <input type="checkbox" class="form-check-input" name="seleccionar_archivo" value="<?php echo htmlspecialchars($fila['nombre_archivo'] ?? ''); ?>">
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <!-- Botón para descargar reportes -->
        <button class="btn btn-descargar mt-3" onclick="descargarReportes()">
    <i class="fas fa-download"></i> Descargar Reportes
</button>
<button class="btn btn-refrescar mt-3" onclick="window.location.reload();">
    <i class="fas fa-sync-alt"></i> Refrescar
</button>
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

            // Función para descargar los reportes seleccionados
            window.descargarReportes = function() {
                var archivosSeleccionados = [];
                $('input[name="seleccionar_archivo"]:checked').each(function() {
                    archivosSeleccionados.push($(this).val());
                });

                if (archivosSeleccionados.length > 0) {
                    var form = $('<form method="POST" action="descargarReportes.php"></form>');
                    archivosSeleccionados.forEach(function(archivo) {
                        form.append('<input type="hidden" name="archivos[]" value="' + archivo + '">');
                    });
                    $('body').append(form);
                    form.submit();
                } else {
                    alert('Por favor, seleccione al menos un archivo para descargar.');
                }
            }
        });
    </script>
</body>

</html>