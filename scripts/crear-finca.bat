@echo off
rem ============================================================
rem  Alta de una ganaderia (finca) con su usuario administrador
rem
rem  Envuelve `php artisan ganaderia:ganaderia` para que no haya
rem  que pelearse con la version de PHP: el proyecto necesita PHP
rem  8.4 y el `php` del PATH es el 8.2 de XAMPP, que falla con
rem  "Composer detected issues... requires PHP >= 8.4.1".
rem
rem  Uso:
rem    crear-finca.bat                      pide los datos por pantalla
rem    crear-finca.bat "Finca" correo@x.com  sin preguntar (para scripts)
rem
rem  Deja la finca creada y muestra las credenciales una sola vez.
rem  El correo del administrador queda verificado: tu entregas la
rem  contrasena en mano. Para que la confirme el propio dueno, usa
rem  --sin-verificar en el comando de artisan.
rem ============================================================
setlocal EnableExtensions EnableDelayedExpansion

cd /d "%~dp0.."

set "PHP=C:\php84\php.exe"
if not exist "%PHP%" (
    rem Alternativa: el primer php del PATH, si es 8.4 o superior.
    for %%p in (php.exe) do if not defined PHP_ALT set "PHP_ALT=%%~$PATH:p"
    if defined PHP_ALT set "PHP=%PHP_ALT%"
)

echo.
echo  ============================================
echo   Nueva ganaderia - Control Ganadero
echo  ============================================
echo.

rem --- Comprobaciones previas -------------------------------------------
if not exist "%PHP%" (
    echo  ERROR: no encuentro PHP. Instala PHP 8.4 o ajusta la ruta C:\php84\php.exe
    goto :fin_error
)

"%PHP%" -r "exit(PHP_VERSION_ID >= 80400 ? 0 : 1);" >nul 2>&1
if errorlevel 1 (
    echo  ERROR: el PHP encontrado es demasiado viejo:
    "%PHP%" -r "echo '     ', PHP_VERSION, ' en ', PHP_BINARY, PHP_EOL;"
    echo     El proyecto necesita PHP 8.4 o superior. Usa C:\php84\php.exe
    goto :fin_error
)

powershell -NoProfile -Command "if (-not (Get-NetTCPConnection -LocalPort 3306 -State Listen -ErrorAction SilentlyContinue)) { exit 1 }" >nul 2>&1
if errorlevel 1 (
    echo  ERROR: MariaDB no esta escuchando en el puerto 3306.
    echo     Arrancalo primero con scripts\arrancar-servicios.bat
    goto :fin_error
)

rem --- Datos ------------------------------------------------------------
rem  /q: sin pausa al final (util para llamarlo desde otro script o una prueba).
if /i "%~1"=="/q" (set "SINPAUSA=1" & shift) else (set "SINPAUSA=")

set "FINCA=%~1"
set "EMAIL=%~2"
set "PERSONA="
set "CLAVE="

if not defined FINCA set /p "FINCA=Nombre de la ganaderia: "
if not defined EMAIL set /p "EMAIL=Correo del administrador: "

if not defined FINCA (
    echo  ERROR: hace falta el nombre de la ganaderia.
    goto :fin_error
)
if not defined EMAIL (
    echo  ERROR: hace falta el correo del administrador.
    goto :fin_error
)

if "%~1"=="" (
    set /p "PERSONA=Nombre de la persona (Enter para 'Administrador de la finca'): "
    echo    Contrasena: dejala vacia y se genera una aleatoria.
    set /p "CLAVE=Contrasena (Enter para generar): "
)

rem --- Alta --------------------------------------------------------------
echo.
echo  Creando la ganaderia...

set "ARGS="
if defined PERSONA set ARGS=%ARGS% --persona="!PERSONA!"
if defined CLAVE set ARGS=%ARGS% --password="!CLAVE!"

"%PHP%" artisan ganaderia:ganaderia "!FINCA!" "!EMAIL!"!ARGS!
if errorlevel 1 (
    echo.
    echo  ERROR: no se pudo crear la ganaderia. Revisa el mensaje de arriba.
    goto :fin_error
)

echo.
echo  ============================================
echo   Listo. Guarda estas credenciales:
echo    Usuario:     !EMAIL!
echo    Contrasena:  si no la escribiste, esta arriba en la salida
echo    Entra en:    http://ganaderia_SaaS.test/login
echo  ============================================
echo.
echo  Comparte la contrasena por un canal privado y pide que la cambie.
goto :fin

:fin_error
echo.
if not defined SINPAUSA pause
exit /b 1

:fin
if not defined SINPAUSA pause
exit /b 0
