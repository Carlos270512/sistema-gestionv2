<?php
require '../vendor/autoload.php'; // Asegúrate de que la ruta sea correcta
include "../configuracion/conexion.php";

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Obtener los valores de los filtros
$filtroNombreUsuario = $_POST['filtroNombreUsuario'] ?? '';
$filtroEstado = $_POST['filtroEstado'] ?? '';
$filtroRevisado = $_POST['filtroRevisado'] ?? '';
$filtroFechaInicio = $_POST['filtroFechaInicio'] ?? '';
$filtroFechaFin = $_POST['filtroFechaFin'] ?? '';

// Construir la consulta base
$query = "SELECT a.*, u.nombre_usuario
          FROM archivo a
          JOIN usuario u ON a.id_usuario = u.id_usuario
          WHERE 1=1";

$bindings = [];
$types = '';

// Aplicar filtros
if (!empty($filtroNombreUsuario)) {
    $query .= " AND u.nombre_usuario LIKE ?";
    $bindings[] = "%$filtroNombreUsuario%";
    $types .= 's';
}

if (!empty($filtroEstado)) {
    $query .= " AND a.estado = ?";
    $bindings[] = $filtroEstado;
    $types .= 's';
}

if (!empty($filtroRevisado)) {
    $query .= " AND a.revisado = ?";
    $bindings[] = $filtroRevisado;
    $types .= 's';
}

// Aplicar filtro de rango de fechas
if (!empty($filtroFechaInicio) && !empty($filtroFechaFin)) {
    $query .= " AND DATE(a.fecha_registro) BETWEEN ? AND ?";
    $bindings[] = $filtroFechaInicio;
    $bindings[] = $filtroFechaFin;
    $types .= 'ss';
} elseif (!empty($filtroFechaInicio)) {
    $query .= " AND DATE(a.fecha_registro) >= ?";
    $bindings[] = $filtroFechaInicio;
    $types .= 's';
} elseif (!empty($filtroFechaFin)) {
    $query .= " AND DATE(a.fecha_registro) <= ?";
    $bindings[] = $filtroFechaFin;
    $types .= 's';
}

// Preparar y ejecutar la consulta
$stmt = $conn->prepare($query);

if ($stmt === false) {
    die('Error en la preparación de la consulta: ' . $conn->error);
}

if (!empty($bindings)) {
    $stmt->bind_param($types, ...$bindings);
}

$stmt->execute();
$result = $stmt->get_result();

// Crear una nueva hoja de cálculo
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Agregar encabezados
$sheet->setCellValue('A1', 'Nombre Usuario');
$sheet->setCellValue('B1', 'Nombre Archivo');
$sheet->setCellValue('C1', 'Fecha Registro');
$sheet->setCellValue('D1', 'Descripción');
$sheet->setCellValue('E1', 'Revisado');
$sheet->setCellValue('F1', 'Usuario Revisa');
$sheet->setCellValue('G1', 'Observación 1');
$sheet->setCellValue('H1', 'Aprobado');
$sheet->setCellValue('I1', 'Usuario Aprueba');
$sheet->setCellValue('J1', 'Observación 2');
$sheet->setCellValue('K1', 'Autorizado');
$sheet->setCellValue('L1', 'Usuario Autoriza');
$sheet->setCellValue('M1', 'Observación 3');
$sheet->setCellValue('N1', 'Estado');
$sheet->setCellValue('O1', 'Fecha Entrega');
$sheet->setCellValue('P1', 'Tipo Archivo');

// Agregar datos
$rowNumber = 2;
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $sheet->setCellValue('A' . $rowNumber, htmlspecialchars($row['nombre_usuario'] ?? ''));
        $sheet->setCellValue('B' . $rowNumber, htmlspecialchars($row['nombre_archivo'] ?? ''));
        $sheet->setCellValue('C' . $rowNumber, htmlspecialchars($row['fecha_registro'] ?? ''));
        $sheet->setCellValue('D' . $rowNumber, htmlspecialchars($row['descripcion'] ?? ''));
        $sheet->setCellValue('E' . $rowNumber, htmlspecialchars($row['revisado'] ?? ''));
        $sheet->setCellValue('F' . $rowNumber, htmlspecialchars($row['usuario_revisa'] ?? ''));
        $sheet->setCellValue('G' . $rowNumber, htmlspecialchars($row['observacion1'] ?? ''));
        $sheet->setCellValue('H' . $rowNumber, htmlspecialchars($row['aprobado'] ?? ''));
        $sheet->setCellValue('I' . $rowNumber, htmlspecialchars($row['usuario_aprueba'] ?? ''));
        $sheet->setCellValue('J' . $rowNumber, htmlspecialchars($row['observacion2'] ?? ''));
        $sheet->setCellValue('K' . $rowNumber, htmlspecialchars($row['autorizado'] ?? ''));
        $sheet->setCellValue('L' . $rowNumber, htmlspecialchars($row['usuario_autoriza'] ?? ''));
        $sheet->setCellValue('M' . $rowNumber, htmlspecialchars($row['observacion3'] ?? ''));
        $sheet->setCellValue('N' . $rowNumber, htmlspecialchars($row['estado'] ?? ''));
        $sheet->setCellValue('O' . $rowNumber, htmlspecialchars($row['fecha_entrega'] ?? ''));
        $sheet->setCellValue('P' . $rowNumber, htmlspecialchars($row['tipo_archivo'] ?? ''));
        $rowNumber++;
    }
} else {
    echo "<tr><td colspan='17'>No se encontraron archivos con los filtros seleccionados</td></tr>";
}

// Cerrar la conexión
$stmt->close();
$conn->close();

// Guardar el archivo Excel
$writer = new Xlsx($spreadsheet);
$filename = 'reporte_archivos.xlsx';
$writer->save($filename);

// Descargar el archivo Excel
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');
readfile($filename);

// Eliminar el archivo temporal
unlink($filename);
