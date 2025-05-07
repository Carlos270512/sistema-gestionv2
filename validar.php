<?php
session_start();
include "./configuracion/conexion.php";

if ($conn->connect_error) {
    die("Error en la conexión: " . $conn->connect_error);
}

// Recibir datos
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $correo = $_POST['correo'];
    $contrasena = $_POST['contrasena'];
    
    // Ejecutar consulta SQL
    $sql = "SELECT * FROM usuario WHERE correo = ? AND contrasena = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $correo, $contrasena);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows > 0) { // esto hace que si el resultado es mayor a 0, entonces se inicie la sesion

        // Extraer datos del array resultado
        $row = $resultado->fetch_assoc();
        $correo = $row['correo'];
        $rol = $row['rol'];   
        $id = $row['id_usuario'];
        $cargo = $row['cargo'];
        $nombre = $row['nombre_usuario'];
        
        $_SESSION['nombreUsuario'] = $nombre;
        $_SESSION['correo'] = $correo;
        $_SESSION['rol'] = $rol; 
        $_SESSION['id_usuario'] = $id;
        $_SESSION['cargo'] = $cargo;
        
        if ($rol == "admin") {
            header("Location: ./administrador/menuAdministrador.php");     
        } elseif ($rol == "usuario") {
            header("Location: ./usuario/menuUsuario.php");     
        }elseif($rol == "coordinador"){
            header("Location: ./coordinador/menuCoordinador.php");
        }elseif($rol == "vicerrector"){
            header("Location: ./vicerrector/menuVicerrector.php");
        }

    } else {
        header("Location: index.php?error=Credenciales incorrectas"); 
    }
    $stmt->close();
    
}
$conn->close();


?>