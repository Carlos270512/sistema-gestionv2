<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/styles.css" rel="stylesheet">
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
    <style>
        /* Estilos personalizados */
        body {
            /* Mantienes el fondo gradiente anterior o lo cambias a transparente si solo deseas la imagen de fondo */
            /* background: linear-gradient(135deg, #667eea, #764ba2); */
            background: url('imagenes/fondologin.jpeg') center center no-repeat;
            /* Ajusta el tamaño de la imagen de fondo según sea necesario */
            background-size: cover;
            /* Para cubrir todo el fondo */
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }

        .login-container {
            background: #fff;
            padding: 2rem;
            border-radius: 1rem;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
            text-align: center;
        }

        .login-container h2 {
            margin-bottom: 2rem;
        }

        .form-control {
            background-color: #f8f9fa;
            border: none;
            border-bottom: 2px solid #ced4da;
            border-radius: 0;
        }

        .form-control:focus {
            box-shadow: none;
            border-color: #80bdff;
        }

        .btn-login {
            background: linear-gradient(to right, #667eea, #764ba2);
            border: none;
            color: #fff;
        }

        .btn-login:hover {
            background: linear-gradient(to right, #5a67d8, #6b46c1);
        }

        .social-buttons .btn {
            width: 48%;
            margin: 0.5rem 1%;
        }

        .signup {
            margin-top: 2rem;
        }
    </style>
    <script>
        // Generar una identificación única para cada pestaña
        function generateUUID() {
            return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
                var r = Math.random() * 16 | 0,
                    v = c == 'x' ? r : (r & 0x3 | 0x8);
                return v.toString(16);
            });
        }

        // Almacenar la identificación en localStorage
        if (!localStorage.getItem('sessionTabId')) {
            localStorage.setItem('sessionTabId', generateUUID());
        }
    </script>
</head>

<body>
    <div class="login-container">
        <h2>Login</h2>
        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger" role="alert">
                <?php echo htmlspecialchars($_GET['error']); ?>
            </div>
        <?php endif; ?>
        <form action="validar.php" method="POST">
            <div class="mb-3">
                <input class="form-control" id="correo" type="email" placeholder="Usuario" name="correo" required>
            </div>
            <div class="mb-3">
                <input class="form-control" id="contrasena" type="password" placeholder="Contraseña" name="contrasena" required>
            </div>
            <input type="hidden" name="sessionTabId" id="sessionTabId">
            <button type="submit" class="btn btn-login btn-block">Ingresar</button>
            <div class="mt-3">
                <a href="#">¿Olvidaste la Contraseña?</a>
            </div>
            <div class="social-buttons mt-3">

                <button type="button" class="btn btn-outline-primary" onclick="window.location.href='#'"><i link class="fab fa-facebook-f"></i></button>

            </div>
        </form>
    </div>
    <script>
        // Establecer el valor del campo oculto con la identificación de la pestaña
        document.getElementById('sessionTabId').value = localStorage.getItem('sessionTabId');
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
    <script src="js/scripts.js"></script>
</body>

</html>