<?php
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['archivos'])) {
    $archivos = $_POST['archivos'];

    // Crear un archivo ZIP
    $zip = new ZipArchive();
    $zipFileName = 'reportes.zip';

    if ($zip->open($zipFileName, ZipArchive::CREATE) !== TRUE) {
        exit("No se puede abrir el archivo ZIP.");
    }

    // Agregar archivos al ZIP
    foreach ($archivos as $archivo) {
        $filePath = __DIR__ . '/../storage/pdfs/' . $archivo;
        if (file_exists($filePath)) {
            $zip->addFile($filePath, $archivo);
        }
    }

    $zip->close();

    // Enviar el archivo ZIP al navegador para su descarga
    header('Content-Type: application/zip');
    header('Content-disposition: attachment; filename=' . $zipFileName);
    header('Content-Length: ' . filesize($zipFileName));
    readfile($zipFileName);

    // Eliminar el archivo ZIP temporal
    unlink($zipFileName);
    exit();
}
?>