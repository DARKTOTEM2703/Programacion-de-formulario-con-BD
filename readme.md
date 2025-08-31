# Sistema de gestion trasportistas

Este proyecto es una aplicación web diseñada para gestionar formularios de registro, autenticación y envío de datos, utilizando tecnologías modernas y buenas prácticas de desarrollo. La aplicación incluye funcionalidades como registro de usuarios, inicio de sesión, integración con Google OAuth, y manejo de formularios con validación y persistencia en base de datos.

---

## Tabla de contenidos

1. [Tecnologías Utilizadas](#tecnologías-utilizadas)
2. [Estructura del Proyecto](#estructura-del-proyecto)
3. [Instalación](#instalación)
4. [Configuración](#configuración)
5. [Módulos y Componentes](#módulos-y-componentes)
6. [Estilos y Diseño](#estilos-y-diseño)
7. [Validación y Seguridad](#validación-y-seguridad)
8. [Base de Datos](#base-de-datos)
9. [Destacado Tecnológico](#destacado-tecnológico)
10. [Mejoras en Accesibilidad](#mejoras-en-accesibilidad)
11. [Contacto](#contacto)
12. [Créditos](#créditos)
13. [Derechos](#derechos)

---

## Tecnologías Utilizadas

| Tecnología            | Icono                                                                                            | Impacto                                                                                                        |
| --------------------- | ------------------------------------------------------------------------------------------------ | -------------------------------------------------------------------------------------------------------------- |
| **PHP 8.x**           | ![PHP](https://img.shields.io/badge/-PHP-777BB4?logo=php&logoColor=white)                        | PHP es el lenguaje principal utilizado para la lógica del servidor, permitiendo la autenticación y validación. |
| **MySQL 8.x**         | ![MySQL](https://img.shields.io/badge/-MySQL-4479A1?logo=mysql&logoColor=white)                  | MySQL se utiliza como base de datos relacional para almacenar información de usuarios y formularios.           |
| **Bootstrap 5.x**     | ![Bootstrap](https://img.shields.io/badge/-Bootstrap-7952B3?logo=bootstrap&logoColor=white)      | Bootstrap facilita el diseño responsivo y atractivo de la interfaz de usuario.                                 |
| **JavaScript (ES6+)** | ![JavaScript](https://img.shields.io/badge/-JavaScript-F7DF1E?logo=javascript&logoColor=black)   | JavaScript se utiliza para la validación en el cliente y la interacción dinámica con los formularios.          |
| **Google OAuth 2.0**  | ![Google OAuth](https://img.shields.io/badge/-Google%20OAuth-4285F4?logo=google&logoColor=white) | Google OAuth permite la autenticación segura de usuarios mediante sus cuentas de Google.                       |
| **Composer**          | ![Composer](https://img.shields.io/badge/-Composer-885630?logo=composer&logoColor=white)         | Composer gestiona las dependencias del proyecto, asegurando que las bibliotecas necesarias estén actualizadas. |
| **PHPMailer**         | ![PHPMailer](https://img.shields.io/badge/-PHPMailer-777BB4?logo=php&logoColor=white)            | PHPMailer se utiliza para enviar correos electrónicos de confirmación y notificaciones.                        |
| **Dotenv**            | ![Dotenv](https://img.shields.io/badge/-Dotenv-ECD53F?logo=dotenv&logoColor=black)               | Dotenv permite manejar variables de entorno de manera segura.                                                  |
| **Apache 2.4**        | ![Apache](https://img.shields.io/badge/-Apache-D22128?logo=apache&logoColor=white)               | Apache se utiliza como servidor web para alojar y servir la aplicación localmente durante el desarrollo.       |
| **XAMPP**             | ![XAMPP](https://img.shields.io/badge/-XAMPP-FB7A24?logo=xampp&logoColor=white)                  | XAMPP proporciona un entorno de desarrollo local que incluye Apache, MySQL y PHP.                              |
| **Git**               | ![Git](https://img.shields.io/badge/-Git-F05032?logo=git&logoColor=white)                        | Git se utiliza para el control de versiones, permitiendo la colaboración y el seguimiento de cambios.          |
| **GitHub**            | ![GitHub](https://img.shields.io/badge/-GitHub-181717?logo=github&logoColor=white)               | GitHub se utiliza como plataforma para alojar el repositorio del proyecto.                                     |
| **CSS3**              | ![CSS3](https://img.shields.io/badge/-CSS3-1572B6?logo=css3&logoColor=white)                     | CSS3 se utiliza para estilizar las páginas web, asegurando un diseño atractivo y responsivo.                   |
| **HTML5**             | ![HTML5](https://img.shields.io/badge/-HTML5-E34F26?logo=html5&logoColor=white)                  | HTML5 se utiliza para estructurar las páginas web, proporcionando una base sólida para el contenido.           |

---

## Estructura del Proyecto

```
└── 📁Programacion-de-formulario-con-BD/
    ├── 📁components/            # Componentes del core del sistema
    │   ├── config.php           # Configuración central y carga de variables de entorno
    │   ├── db_connection.php    # Singleton de conexión PDO a la base de datos
    │   ├── email_service.php    # Servicio de envío de correos con PHPMailer
    │   ├── email_confirmacion.php # Template engine para correos HTML
    │   ├── footer.php           # Componente compartido de pie de página
    │   ├── form_handler.php     # Controlador para procesamiento de formularios
    │   ├── google_login_handler.php # Middleware de autenticación OAuth
    │   ├── header.php           # Componente compartido de encabezado con nav
    │   ├── login_handler.php    # Controlador de autenticación local
    │   └── register_handler.php # Controlador de registro de usuarios
    │
    ├── 📁css/                   # Hojas de estilo modularizadas
    │   ├── dark-mode.css        # Sistema de temas con variables CSS
    │   ├── dashboard.css        # Estilos específicos del dashboard
    │   ├── footer.css           # Estilos del componente footer
    │   ├── forms.css            # Estilos para formularios y validaciones
    │   ├── header.css           # Estilos para navegación y encabezado
    │   ├── index.css            # Estilos de la página principal
    │   ├── login.css            # Estilos específicos de autenticación
    │   ├── register.css         # Estilos para formulario de registro
    │   └── WatchData.css        # Estilos para visualización de datos
    │
    ├── 📁db/                    # Esquemas y migraciones de base de datos
    │   └── gestion_envios.sql   # Schema completo con índices y constraints
    │
    ├── 📁js/                    # Scripts client-side
    │   ├── dark-mode.js         # Sistema de detección de preferencias y cambio de tema
    │   ├── form_validation.js   # Validación asíncrona de formularios
    │   ├── formenvioalert.js    # Gestor de notificaciones temporales
    │   ├── googleconection.js   # Cliente OAuth para Google
    │   └── validation.js        # Utilidades de validación reutilizables
    │
    ├── 📁php/                   # Controladores y vistas principales
    │   ├── dashboard.php        # Panel de control principal
    │   ├── forms.php            # Formulario multi-etapa con validación
    │   ├── login.php            # Vista de inicio de sesión
    │   ├── logout.php           # Controlador de cierre de sesión
    │   ├── register.php         # Vista de registro de usuarios
    │   └── WatchData.php        # Visualización y filtrado de datos
    │
    ├── 📁uploads/               # Directorio para almacenamiento de archivos
    ├── 📁vendor/                # Dependencias gestionadas por Composer
    ├── .env.example             # Plantilla de variables de entorno
    ├── .gitignore               # Configuración de exclusiones para Git
    ├── .htaccess                # Reglas de reescritura y seguridad Apache
    ├── composer.json            # Manifiesto de dependencias
    ├── composer.lock            # Versiones bloqueadas de dependencias
    ├── index.php                # Punto de entrada principal
    └── readme.md                # Documentación técnica

```

---

## Instalación

1. Clona este repositorio:

   ```bash
   git clone hhttps://github.com/DARKTOTEM2703/Programacion-de-formulario-con-BD
   ```

2. Instala las dependencias de PHP con Composer y las librerias necesarias para el proyecto:

   ```bash
   composer install
   composer require stripe/stripe-php
   composer require phpmailer/phpmailer
   composer require vlucas/phpdotenv
   composer require --dev phpunit/phpunit
   composer require --dev phpstan/phpstan
   npm install
   ```

3. Configura la base de datos MySQL:

   - Crea una base de datos llamada `gestion_envios`.
   - Importa el archivo SQL ubicado en `db/gestion_envios.sql`.

4. Configura las variables de entorno en el archivo `.env`:

   ```env
   GOOGLE_CLIENT_ID=TU_CLIENT_ID
   GOOGLE_CLIENT_SECRET=TU_CLIENT_SECRET
   DB_SERVER=Tu_Servidor
   DB_USERNAME=Tu_Usuario
   DB_PASSWORD=Tu_Contraseña
   DB_NAME=Tu_Base_De_Datos
   SMTP_HOST=smtp.gmail.com
   SMTP_USERNAME=Tu_correo@gmail.com
   SMTP_PASSWORD=tu_contraseña_de_Aplicacion
   SMTP_PORT=587
   SMTP_FROM_EMAIL=Tu_correo@gmail.com
   SMTP_FROM_NAME=Tu_nombre

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

## Módulos y Componentes
- **Panel de administración**: Gestión de usuarios, envíos, facturas, repartidores y notificaciones.
- **Gestión financiera**: Facturación electrónica, pagos, panel de ingresos y gastos.
- **Optimización de rutas**: Algoritmos para asignación inteligente y visualización en mapa.

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

## Características Adicionales

### Características de Diseño Frontend

| Característica                   | Descripción                                                                                     |
| -------------------------------- | ----------------------------------------------------------------------------------------------- |
| **Diseño Totalmente Responsive** | Adaptación completa a dispositivos móviles, tablets y escritorio con media queries optimizadas. |
| **Modo Oscuro Integrado**        | Sistema automático de detección de preferencias del usuario que ajusta colores y contrastes.    |
| **Secciones Modulares**          | Estructura por componentes que facilita mantenimiento y escalabilidad del código.               |
| **Efectos Visuales**             | Animaciones sutiles en tarjetas e imágenes para mejorar la experiencia del usuario.             |
**Animaciones y microinteracciones** | Animaciones y microinteracciones (GSAP, Animate.css, tw-animate-css)** | Animaciones y microinteracciones (GSAP, Animate.css, tw-animate-css) |
| **Tablas y paneles modernos**    | Tablas y paneles con filtros y exportación de datos.                                                       |
| **Efectos Visuales**             | Animaciones sutiles en tarjetas e imágenes para mejorar la experiencia del usuario.                         |

### Secciones Principales

| Sección             | Características                                                                                                           |
| ------------------- | ------------------------------------------------------------------------------------------------------------------------- |
| **Servicios**       | Presentación visual con iconos y diseño de tarjetas uniformes. Muestra los distintos servicios de transporte y logística. |
| **Unidades**        | Galería de la flota disponible con especificaciones técnicas e iconos descriptivos.                                       |
| **Certificaciones** | Visualización de credenciales y certificados que avalan la calidad del servicio.                                          |
| **Acerca de**       | Información corporativa que incluye misión, visión y valores con integración de video corporativo responsive.             |

### Optimizaciones Técnicas

- **Contenedores de imagen con proporción fija**: Implementación de técnicas avanzadas CSS como object-fit y padding-top para mantener relaciones de aspecto consistentes.
- **Video Embedding Responsive**: Videos de YouTube integrados que se adaptan perfectamente a cualquier tamaño de pantalla.
- **Uniformidad Visual**: Sistema de espaciado consistente con variables CSS.
- **Optimización de Rendimiento**: Imágenes optimizadas y recursos cargados eficientemente.
- **Microinteracciones**: Efectos hover sutiles para mejorar la experiencia del usuario.

## Validación y Seguridad

- Validación en cliente y servidor.
- Protección CSRF y XSS.
- Hashing seguro de contraseñas.
- Roles y permisos granulares.
- Auditoría y logs de actividad.

## Destacado Tecnológico

- **Dashboard de Bodega con Next.js y React**  
  Panel moderno y responsivo, desarrollado con Next.js y React, que consume datos en tiempo real desde el backend PHP y la base de datos MySQL.  
  Incluye escaneo QR con cámara, gestión de envíos, estadísticas y asignación de repartidores.

- **Integración Fullstack**  
  Comunicación eficiente entre frontend Next.js y backend PHP mediante APIs RESTful, permitiendo escalabilidad y mantenibilidad.

- **Aplicación Progresiva (PWA)**  
  Módulo para repartidores con escaneo QR, geolocalización, sincronización offline y notificaciones push.

- **Seguridad y Roles**  
  Sistema robusto de autenticación, gestión de permisos, protección CSRF/XSS y auditoría.

- **Notificaciones Push y Chatbot IA**  
  Alertas en tiempo real y asistente virtual para soporte automatizado.

---
### Mejoras en Accesibilidad

- **Alto contraste**: Cumplimiento de estándares WCAG para legibilidad.
- **Navegación intuitiva**: Estructura jerárquica clara.
- **Compatibilidad con lectores de pantalla**: Implementación de atributos ARIA para mejorar la accesibilidad.

---

## Contacto

Para más información, puedes contactarme a través de:

Correo electrónico: Jafethgamboa27@gmail.com

¡Gracias por usar este proyecto! Si tienes alguna pregunta o sugerencia, no dudes en abrir un issue.

## Créditos

Este proyecto fue desarrollado por Jafet Gamboa, comprometido con proporcionar soluciones financieras de alta calidad a nuestros clientes.

## Derechos

Todos los derechos reservados. Este proyecto y su contenido están protegidos por derechos de autor y no pueden ser reproducidos, distribuidos ni utilizados sin el permiso expreso del autor.


