<?php
session_start();
include "../configuracion/conexion.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_archivo = $_POST['id_archivo'];
    $observacion = $_POST['observacion'];
    $aprobado = $_POST['aprobado'];
    $usuario_revisa = $_SESSION['id_usuario'];

    // Actualizar el archivo en la base de datos
    $sql = "UPDATE archivo SET observacion1 = ?, aprobado = ?, usuario_revisa = ? WHERE id_archivo = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssi", $observacion, $aprobado, $usuario_revisa, $id_archivo);

    if ($stmt->execute()) {
        echo "Archivo mandado a modificar correctamente.";
    } else {
        echo "Error al mandar a modificar el archivo.";
    }
    $stmt->close();
    $conn->close();
}//todo esto hace que se pueda mandar a modificar un archivo  y se actualice en la base de datos
?> 