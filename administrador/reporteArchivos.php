<?php
include "../configuracion/conexion.php";
session_start();

if (!isset($_SESSION['id_usuario'])) {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['id_usuario'];

// Si se recibe una solicitud AJAX para filtrar los datos
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $filtroNombreUsuario = $_POST['filtroNombreUsuario'] ?? '';
    $filtroEstado = $_POST['filtroEstado'] ?? '';
    $filtroRevisado = $_POST['filtroRevisado'] ?? '';
    $filtroFechaInicio = $_POST['filtroFechaInicio'] ?? '';
    $filtroFechaFin = $_POST['filtroFechaFin'] ?? '';

    // Construir la consulta SQL con los filtros
    $sql = "
        SELECT 
            u.nombre_usuario,
            a.nombre_archivo,
            a.fecha_registro,
            a.descripcion,
            a.revisado,
            a.usuario_revisa,
            a.observacion1,
            a.aprobado,
            a.usuario_aprueba,
            a.observacion2,
            a.autorizado,
            a.usuario_autoriza,
            a.observacion3,
            a.estado,
            a.fecha_entrega,
            a.tipo_archivo
        FROM 
            archivo a
        JOIN 
            usuario u ON a.id_usuario = u.id_usuario
        WHERE 
            (u.nombre_usuario LIKE '%$filtroNombreUsuario%' OR '$filtroNombreUsuario' = '')
            AND (a.estado LIKE '%$filtroEstado%' OR '$filtroEstado' = '')
            AND (a.revisado LIKE '%$filtroRevisado%' OR '$filtroRevisado' = '')
    ";

    // Manejar filtros de fecha
    if (!empty($filtroFechaInicio)) {
        $sql .= " AND a.fecha_registro >= '$filtroFechaInicio'";
    }
    if (!empty($filtroFechaFin)) {
        $sql .= " AND a.fecha_entrega <= '$filtroFechaFin'";
    }

    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            echo "<tr>
                <td>" . htmlspecialchars($row['nombre_usuario'] ?? '') . "</td>
                <td>" . htmlspecialchars($row['nombre_archivo'] ?? '') . "</td>
                <td>" . htmlspecialchars($row['fecha_registro'] ?? '') . "</td>
                <td>" . htmlspecialchars($row['descripcion'] ?? '') . "</td>
                <td>" . htmlspecialchars($row['revisado'] ?? '') . "</td>
                <td>" . htmlspecialchars($row['usuario_revisa'] ?? '') . "</td>
                <td>" . htmlspecialchars($row['observacion1'] ?? '') . "</td>
                <td>" . htmlspecialchars($row['aprobado'] ?? '') . "</td>
                <td>" . htmlspecialchars($row['usuario_aprueba'] ?? '') . "</td>
                <td>" . htmlspecialchars($row['observacion2'] ?? '') . "</td>
                <td>" . htmlspecialchars($row['autorizado'] ?? '') . "</td>
                <td>" . htmlspecialchars($row['usuario_autoriza'] ?? '') . "</td>
                <td>" . htmlspecialchars($row['observacion3'] ?? '') . "</td>
                <td>" . htmlspecialchars($row['estado'] ?? '') . "</td>
                <td>" . htmlspecialchars($row['fecha_entrega'] ?? '') . "</td>
                <td>" . htmlspecialchars($row['tipo_archivo'] ?? '') . "</td>
            </tr>";
        }
    } else {
        echo "<tr><td colspan='16'>No hay datos disponibles</td></tr>";
    }
    exit(); // Terminar la ejecución después de enviar la respuesta AJAX
}
?>

<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Sistema de Archivos</title>
    <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
    <link rel="stylesheet" href="../css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/font-awesome.css">
    <style>
        /* Estilos para la tabla y el contenedor con scroll */
        .table-container {
            width: 100%;
            overflow-x: auto;
            margin-bottom: 20px;
            border: 1px solid #ddd;
            background-color: #fff;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 0;
        }

        .table th,
        .table td {
            padding: 12px;
            border: 1px solid #ddd;
            text-align: left;
            white-space: nowrap;
        }

        .table th {
            background-color: #f8f9fa;
            font-weight: bold;
        }

        .table-striped tbody tr:nth-of-type(odd) {
            background-color: #f9f9f9;
        }

        .table-hover tbody tr:hover {
            background-color: #f1f1f1;
        }

        .btn {
            margin-right: 5px;
        }

        .btn-actions {
            display: flex;
            justify-content: center;
            gap: 10px;
        }
    </style>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</head>

<body>
    <div class="container">
        <h2>Archivos</h2>
        <div class="panel panel-default">
            <div class="panel-heading">Filtros</div>
            <div class="panel-body">
                <form id="filtrosForm" method="POST" action="generarExcel.php">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="filtroNombreUsuario">Filtrar por Nombre de Usuario:</label>
                                <select id="filtroNombreUsuario" name="filtroNombreUsuario" class="form-control">
                                    <option value="">Todos</option>
                                    <?php
                                    $sqlUsuarios = "SELECT DISTINCT nombre_usuario FROM usuario";
                                    $resultUsuarios = $conn->query($sqlUsuarios);
                                    while ($row = $resultUsuarios->fetch_assoc()) {
                                        echo "<option value='" . htmlspecialchars($row['nombre_usuario'] ?? '') . "'>" . htmlspecialchars($row['nombre_usuario'] ?? '') . "</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="filtroEstado">Filtrar por Estado:</label>
                                <select id="filtroEstado" name="filtroEstado" class="form-control">
                                    <option value="">Todos</option>
                                    <option value="aprobado">Aprobado</option>
                                    <option value="no aprobado">No Aprobado</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="filtroRevisado">Filtrar por Estado de Revisión:</label>
                                <select id="filtroRevisado" name="filtroRevisado" class="form-control">
                                    <option value="">Todos</option>
                                    <option value="Revisado">Revisado</option>
                                    <option value="en espera">En espera</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="filtroFechaInicio">Fecha de Inicio:</label>
                                <input type="date" id="filtroFechaInicio" name="filtroFechaInicio" class="form-control">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="filtroFechaFin">Fecha de Fin:</label>
                                <input type="date" id="filtroFechaFin" name="filtroFechaFin" class="form-control">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12 text-right">
                            <button type="button" class="btn btn-primary" id="aplicarFiltros">Aplicar Filtros</button>
                            <button type="submit" class="btn btn-danger" id="generarPDF">Generar Excel</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="panel panel-default">
            <div class="panel-body">
                <div class="table-container">
                    <table class="table table-bordered table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Usuario</th>
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
                                <th>Fecha Entrega</th>
                                <th>Tipo Archivo</th>
                            </tr>
                        </thead>
                        <tbody id="tablaArchivosBody">
                            <!-- Los datos se cargarán aquí dinámicamente -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            // Cargar datos iniciales al abrir la página
            cargarDatos();

            // Aplicar filtros cuando se hace clic en el botón
            $('#aplicarFiltros').click(function() {
                cargarDatos();
            });

            // Función para cargar datos dinámicamente
            function cargarDatos() {
                $.ajax({
                    url: 'reporteArchivos.php', // El mismo archivo PHP
                    type: 'POST',
                    data: $('#filtrosForm').serialize(), // Enviar datos del formulario
                    success: function(response) {
                        $('#tablaArchivosBody').html(response); // Actualizar la tabla con los datos filtrados
                    },
                    error: function() {
                        alert('Error al cargar los datos.');
                    }
                });
            }
        });
    </script>
</body>

</html>