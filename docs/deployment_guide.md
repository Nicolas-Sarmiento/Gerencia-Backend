# Guía de Despliegue del Backend (Laravel) en Plataformas Gratuitas

Para que tu equipo pueda realizar pruebas del backend de Laravel de manera gratuita, existen excelentes opciones en la nube. Debido a que Laravel requiere PHP y un servidor web (o soporte para contenedores Docker), y usualmente una base de datos, la forma más robusta y estándar de desplegarlo es empaquetándolo en un contenedor Docker. 

Ya hemos creado un `Dockerfile` y un `docker-entrypoint.sh` en la raíz de tu proyecto para automatizar este proceso.

---

## 📊 Comparativa de Opciones Gratuitas

| Plataforma | Tipo de Base de Datos | ¿Requiere Tarjeta? | Ventajas | Limitaciones / Detalles |
| :--- | :--- | :--- | :--- | :--- |
| **Render + Neon.tech** <br>*(Recomendado)* | PostgreSQL (Neon.tech) o SQLite (Efímero) | **No** | 100% gratuito. Muy fácil de conectar con GitHub. | La app "se duerme" tras 15 minutos de inactividad (tarda ~50 segundos en despertar en la primera petición). |
| **Koyeb + Neon.tech** | PostgreSQL (Neon.tech) | **No** | Alto rendimiento, no se duerme tan agresivamente. | Límite mensual de recursos en la capa gratuita ($5 de crédito mensual). |
| **Fly.io** | SQLite (Persistente) o PostgreSQL | **Sí** (Solo verificación) | Permite adjuntar un disco duro virtual gratuito de 3GB, por lo que puedes usar SQLite de forma persistente y rápida. | Requiere tarjeta de crédito para validar la cuenta (aunque no te cobra si te mantienes en el plan gratuito). |

---

## 🛠️ Opción Recomendada: Render (App) + Neon.tech (Base de Datos)

Esta combinación es **100% gratuita y no requiere ingresar tarjeta de crédito**.

### Paso 1: Configurar la Base de Datos en Neon.tech
1. Regístrate en [Neon.tech](https://neon.tech/) (es instantáneo con GitHub).
2. Crea un proyecto nuevo. Elige la base de datos **PostgreSQL** y la región más cercana a tu equipo (por ejemplo, `us-east-1` o `us-east-2`).
3. Neon te dará una URL de conexión (Connection String). Cópiala. Tendrá un formato similar a:
   `postgresql://neondb_owner:PASSWORD@ep-xxx-xxx.us-east-2.aws.neon.tech/neondb?sslmode=require`
4. De esta URL, extrae los siguientes datos para Laravel:
   - **DB_CONNECTION**: `pgsql`
   - **DB_HOST**: `ep-xxx-xxx.us-east-2.aws.neon.tech`
   - **DB_PORT**: `5432`
   - **DB_DATABASE**: `neondb`
   - **DB_USERNAME**: `neondb_owner`
   - **DB_PASSWORD**: `PASSWORD`

---

### Paso 2: Generar la Clave de Encriptación de Laravel (`APP_KEY`)
Laravel necesita una clave única para encriptar cookies y sesiones. Debes generarla localmente ejecutando en tu terminal:
```bash
php artisan key:generate --show
```
Copia el resultado (comienza con `base64:...`). Lo necesitarás para las variables de entorno de Render.

---

### Paso 3: Desplegar en Render
1. Regístrate en [Render.com](https://render.com/) con tu cuenta de GitHub.
2. Haz clic en **New** (Nuevo) y selecciona **Web Service**.
3. Conecta el repositorio de GitHub de este proyecto (`backend-gerencia`).
4. Configura el servicio con los siguientes datos:
   - **Name**: `backend-gerencia` (o el nombre que prefieras).
   - **Environment**: `Docker` *(Render detectará automáticamente tu Dockerfile)*.
   - **Region**: Selecciona la misma región que elegiste en Neon (ej. Oregon o Virginia) para menor latencia.
   - **Branch**: `main` (o la rama que use tu equipo para pruebas).
   - **Instance Type**: **Free** (Gratuito).

5. Despliega hacia abajo y haz clic en **Advanced** para agregar las **Environment Variables** (Variables de Entorno):

   | Clave | Valor | Descripción |
   | :--- | :--- | :--- |
   | `APP_ENV` | `production` | Modo producción para optimizar velocidad. |
   | `APP_DEBUG` | `true` | *(Opcional)* Manténlo en `true` si deseas ver detalles de errores en pruebas. |
   | `APP_KEY` | `base64:xxxx...` | La clave que generaste en el Paso 2. |
   | `APP_URL` | *(Dejar vacío al inicio)* | Una vez Render te dé la URL de tu app, la puedes actualizar aquí. |
   | `DB_CONNECTION` | `pgsql` | Conexión PostgreSQL para Neon. |
   | `DB_HOST` | `ep-xxx-xxx.us-east-2.aws.neon.tech` | Servidor de Neon. |
   | `DB_PORT` | `5432` | Puerto estándar. |
   | `DB_DATABASE` | `neondb` | Nombre de base de datos. |
   | `DB_USERNAME` | `neondb_owner` | Usuario de Neon. |
   | `DB_PASSWORD` | `PASSWORD` | Contraseña de Neon. |

6. Haz clic en **Create Web Service**.

> [!NOTE]
> El primer despliegue puede tardar de 3 a 5 minutos mientras se descarga la imagen de PHP y se compilan las dependencias. El script `docker-entrypoint.sh` ejecutará automáticamente `php artisan migrate --force` al iniciar el contenedor para que no tengas que preocuparte por crear las tablas.

---

## 💾 Alternativa: Desplegar con SQLite (Efímero) en Render
Si **no** deseas crear una base de datos externa y a tu equipo no le importa que **los datos de prueba se borren periódicamente** (cada vez que la aplicación se duerma o se reinicie), puedes usar SQLite directamente en Render:

1. Sigue los mismos pasos anteriores para crear el **Web Service** en Render.
2. En las Variables de Entorno, define únicamente:
   - `APP_ENV` = `production`
   - `APP_KEY` = `base64:xxxx...`
   - `DB_CONNECTION` = `sqlite`
3. Al iniciar, el contenedor creará automáticamente un archivo SQLite en `/var/www/html/database/database.sqlite` y correrá las migraciones en limpio.

---

## ⚡ Alternativa: Fly.io (Con SQLite Persistente)
Si tu equipo quiere una base de datos SQLite persistente (que no se borre al reiniciar) de forma gratuita, Fly.io es la mejor opción. Requiere tarjeta de crédito para registrarse, pero el consumo para esta escala de pruebas será de $0.00.

1. Instala el CLI de Fly.io en tu máquina:
   ```bash
   curl -L https://fly.io/install.sh | sh
   ```
2. Inicia sesión:
   ```bash
   fly auth login
   ```
3. Crea una nueva aplicación en Fly.io (reemplaza `tu-nombre-de-app` por un nombre único):
   ```bash
   fly apps create tu-nombre-de-app
   ```
4. Abre el archivo `fly.toml` que hemos creado en la raíz del proyecto y actualiza la propiedad `app` con el nombre de tu aplicación:
   ```toml
   app = "tu-nombre-de-app"
   ```
5. Crea un volumen de disco virtual en la misma región configurada en tu `fly.toml` (por defecto `iad`) para almacenar la base de datos SQLite de forma persistente:
   ```bash
   fly volumes create sqlite_data --size 1 --region iad --app tu-nombre-de-app
   ```
6. Configura la clave de encriptación de Laravel en los secretos de Fly.io:
   ```bash
   fly secrets set APP_KEY="base64:xxxx..." --app tu-nombre-de-app
   ```
7. Despliega la aplicación directamente:
   ```bash
   fly deploy
   ```
