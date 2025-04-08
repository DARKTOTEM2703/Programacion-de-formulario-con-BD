# Sistema de Gestión de Formularios con Base de Datos

Este proyecto es una aplicación web diseñada para gestionar formularios de registro, autenticación y envío de datos, utilizando tecnologías modernas y buenas prácticas de desarrollo. La aplicación incluye funcionalidades como registro de usuarios, inicio de sesión, integración con Google OAuth, y manejo de formularios con validación y persistencia en base de datos.

## Tabla de Contenidos

1. [Tecnologías Utilizadas](#tecnologías-utilizadas)
2. [Estructura del Proyecto](#estructura-del-proyecto)
3. [Instalación](#instalación)
4. [Configuración](#configuración)
5. [Componentes Principales](#componentes-principales)
6. [Estilos y Diseño](#estilos-y-diseño)
7. [Validación y Seguridad](#validación-y-seguridad)
8. [Base de Datos](#base-de-datos)
9. [Contribuciones](#contribuciones)

---

## Tecnologías Utilizadas

| Tecnología       | Icono                                                                                            | Impacto                                                                                                        |
| ---------------- | ------------------------------------------------------------------------------------------------ | -------------------------------------------------------------------------------------------------------------- |
| **PHP**          | ![PHP](https://img.shields.io/badge/-PHP-777BB4?logo=php&logoColor=white)                        | PHP es el lenguaje principal utilizado para la lógica del servidor, permitiendo la autenticación y validación. |
| **MySQL**        | ![MySQL](https://img.shields.io/badge/-MySQL-4479A1?logo=mysql&logoColor=white)                  | MySQL se utiliza como base de datos relacional para almacenar información de usuarios y formularios.           |
| **Bootstrap**    | ![Bootstrap](https://img.shields.io/badge/-Bootstrap-7952B3?logo=bootstrap&logoColor=white)      | Bootstrap facilita el diseño responsivo y atractivo de la interfaz de usuario.                                 |
| **JavaScript**   | ![JavaScript](https://img.shields.io/badge/-JavaScript-F7DF1E?logo=javascript&logoColor=black)   | JavaScript se utiliza para la validación en el cliente y la interacción dinámica con los formularios.          |
| **Google OAuth** | ![Google OAuth](https://img.shields.io/badge/-Google%20OAuth-4285F4?logo=google&logoColor=white) | Google OAuth permite la autenticación segura de usuarios mediante sus cuentas de Google.                       |
| **Composer**     | ![Composer](https://img.shields.io/badge/-Composer-885630?logo=composer&logoColor=white)         | Composer gestiona las dependencias del proyecto, asegurando que las bibliotecas necesarias estén actualizadas. |
| **Dotenv**       | ![Dotenv](https://img.shields.io/badge/-Dotenv-ECD53F?logo=dotenv&logoColor=black)               | Dotenv permite manejar variables de entorno de manera segura.                                                  |
| **Apache**       | ![Apache](https://img.shields.io/badge/-Apache-D22128?logo=apache&logoColor=white)               | Apache se utiliza como servidor web para alojar y servir la aplicación localmente durante el desarrollo.       |
| **XAMPP**        | ![XAMPP](https://img.shields.io/badge/-XAMPP-FB7A24?logo=xampp&logoColor=white)                  | XAMPP proporciona un entorno de desarrollo local que incluye Apache, MySQL y PHP.                              |
| **Git**          | ![Git](https://img.shields.io/badge/-Git-F05032?logo=git&logoColor=white)                        | Git se utiliza para el control de versiones, permitiendo la colaboración y el seguimiento de cambios.          |
| **GitHub**       | ![GitHub](https://img.shields.io/badge/-GitHub-181717?logo=github&logoColor=white)               | GitHub se utiliza como plataforma para alojar el repositorio del proyecto.                                     |
| **CSS3**         | ![CSS3](https://img.shields.io/badge/-CSS3-1572B6?logo=css3&logoColor=white)                     | CSS3 se utiliza para estilizar las páginas web, asegurando un diseño atractivo y responsivo.                   |
| **HTML5**        | ![HTML5](https://img.shields.io/badge/-HTML5-E34F26?logo=html5&logoColor=white)                  | HTML5 se utiliza para estructurar las páginas web, proporcionando una base sólida para el contenido.           |

---

## Estructura del Proyecto

```
.
├── components/         # Componentes PHP reutilizables
├── css/                # Archivos de estilos CSS
├── db/                 # Archivos relacionados con la base de datos
├── img/                # Imágenes utilizadas en la aplicación
├── js/                 # Scripts JavaScript
├── vendor/             # Dependencias instaladas por Composer
├── .env                # Variables de entorno
├── .gitignore          # Archivos y carpetas ignorados por Git
├── composer.json       # Configuración de dependencias de Composer
├── index.php           # Página principal
├── login.php           # Página de inicio de sesión
├── register.php        # Página de registro de usuarios
├── forms.php           # Página para gestionar formularios
├── WatchData.php       # Página para visualizar datos enviados
└── logout.php          # Lógica de cierre de sesión
```

---

## Instalación

1. Clona este repositorio:

   ```bash
   git clone https://github.com/tu-repositorio.git
   cd Programacion-de-formulario-con-BD
   ```

2. Instala las dependencias de PHP con Composer:

   ```bash
   composer install
   ```

3. Configura la base de datos MySQL:

   - Crea una base de datos llamada `gestion_envios`.
   - Importa el archivo SQL ubicado en `db/gestion_envios.sql`.

4. Configura las variables de entorno en el archivo `.env`:

   ```env
   GOOGLE_CLIENT_ID="TU_CLIENT_ID"
   GOOGLE_CLIENT_SECRET="TU_CLIENT_SECRET"

   ```

5. Inicia un servidor local (por ejemplo, XAMPP o WAMP) y accede al proyecto desde tu navegador.

---

## Configuración

### Variables de Entorno

El archivo `.env.example` contiene las siguientes variables:

#### Recuerda renombrarlo a ".env"

- `GOOGLE_CLIENT_ID`: ID del cliente de Google OAuth.
- `GOOGLE_CLIENT_SECRET`: Secreto del cliente de Google OAuth.

### Dependencias

Las dependencias principales están definidas en `composer.json`:

- `google/apiclient`: Cliente oficial de Google para PHP.
- `vlucas/phpdotenv`: Manejo de variables de entorno.

---

## Componentes Principales

### Autenticación

- **Registro de Usuarios**: Implementado en [`components/register_handler.php`](components/register_handler.php).
- **Inicio de Sesión**: Implementado en [`components/login_handler.php`](components/login_handler.php).
- **Google OAuth**: Integración en [`components/google_login_handler.php`](components/google_login_handler.php).

### Formularios

- **Formulario de Registro**: Página [`register.php`](register.php) con validación en el cliente y servidor.
- **Formulario de Envíos**: Página [`forms.php`](forms.php) para registrar datos de envíos.

### Visualización de Datos

- **Ver Datos**: Página [`WatchData.php`](WatchData.php) para listar los datos enviados por el usuario autenticado.

---

## Estilos y Diseño

- **Modo Oscuro**: Implementado mediante el archivo [`css/dark-mode.css`](css/dark-mode.css) y el script [`js/dark-mode.js`](js/dark-mode.js).
- **Estilos Personalizados**: Cada página tiene su propio archivo CSS en la carpeta `css/`.

---

## Validación y Seguridad

- **Validación del Cliente**: Scripts como [`js/validation.js`](js/validation.js) y [`js/form_validation.js`](js/form_validation.js) aseguran que los datos sean válidos antes de enviarlos al servidor.
- **Validación del Servidor**: Los datos son sanitizados y validados en los controladores PHP.
- **Protección CSRF**: Implementada en el formulario de envíos mediante tokens únicos.
- **Hashing de Contraseñas**: Contraseñas almacenadas con `password_hash()`.

---

## Base de Datos

La base de datos `gestion_envios` contiene las siguientes tablas principales:

- **usuarios**: Almacena información de los usuarios registrados.
- **envios**: Almacena los datos de los formularios enviados.

---

¡Gracias por usar este proyecto! Si tienes alguna pregunta o sugerencia, no dudes en abrir un issue.
